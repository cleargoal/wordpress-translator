<?php
/**
 * Taxonomy Translator Class
 *
 * Handles translation of taxonomy terms (categories, tags, custom taxonomies).
 *
 * @package WP_Smart_Translation_Engine
 */

namespace WPSTE\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Taxonomy Translator class
 */
class Taxonomy_Translator {

	/**
	 * Translation Manager instance
	 *
	 * @var Translation_Manager
	 */
	protected $translation_manager;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->translation_manager = new Translation_Manager();
	}

	/**
	 * Translate a term
	 *
	 * @param int    $term_id     Term ID to translate.
	 * @param string $target_lang Target language code.
	 * @param array  $options     Translation options.
	 * @return array Translation result with 'success' or 'error'.
	 */
	public function translate_term( int $term_id, string $target_lang, array $options = array() ): array {
		global $wpdb;

		// Get term details
		$term = get_term( $term_id );
		if ( is_wp_error( $term ) || ! $term ) {
			return array( 'error' => __( 'Term not found.', 'smart-translation-engine' ) );
		}

		// Get source language
		$source_lang = $options['source_lang'] ?? 'en';

		// Check if translation already exists
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking for existing term translation in custom table
		$existing = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wpste_term_translations WHERE term_id = %d AND lang_code = %s",
				$term_id,
				$target_lang
			)
		);

		if ( $existing ) {
			return array( 'error' => __( 'Translation already exists for this language.', 'smart-translation-engine' ) );
		}

		// Generate or get translation group
		$translation_group = $this->get_translation_group( $term_id );
		if ( ! $translation_group ) {
			$translation_group = wp_generate_uuid4();
			$this->set_translation_group( $term_id, $source_lang, $translation_group );
		}

		// Translate term name
		$name_result = $this->translation_manager->translate(
			$term->name,
			$source_lang,
			$target_lang,
			$options
		);

		if ( isset( $name_result['error'] ) ) {
			return array( 'error' => $name_result['error'] );
		}

		$translated_name = $name_result['text'];
		$provider_used = $name_result['provider'] ?? 'unknown';
		$api_key_id = $name_result['api_key_id'] ?? null;
		$characters_used = strlen( $term->name );

		// Translate description if exists
		$translated_description = '';
		if ( ! empty( $term->description ) ) {
			$desc_result = $this->translation_manager->translate(
				$term->description,
				$source_lang,
				$target_lang,
				$options
			);

			if ( ! isset( $desc_result['error'] ) ) {
				$translated_description = $desc_result['text'];
				$characters_used += strlen( $term->description );
			}
		}

		// Generate slug from translated name
		$translated_slug = sanitize_title( $translated_name );

		// Store translation
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Storing term translation in custom table
		$inserted = $wpdb->insert(
			$wpdb->prefix . 'wpste_term_translations',
			array(
				'term_id'                => $term_id,
				'lang_code'              => $target_lang,
				'translated_name'        => $translated_name,
				'translated_slug'        => $translated_slug,
				'translated_description' => $translated_description,
				'translation_group'      => $translation_group,
				'provider_used'          => $provider_used,
				'api_key_id'             => $api_key_id,
				'characters_translated'  => $characters_used,
				'translated_at'          => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s' )
		);

		if ( ! $inserted ) {
			return array( 'error' => __( 'Failed to save translation.', 'smart-translation-engine' ) );
		}

		// Trigger action hook
		do_action( 'wpste_term_translated', $term_id, $target_lang, $translated_name );

		return array(
			'success'           => true,
			'term_id'           => $term_id,
			'target_lang'       => $target_lang,
			'translated_name'   => $translated_name,
			'translated_slug'   => $translated_slug,
			'translation_group' => $translation_group,
			'provider'          => $provider_used,
		);
	}

	/**
	 * Get translation for a term
	 *
	 * @param int    $term_id Term ID.
	 * @param string $lang    Language code.
	 * @return array|null Translation data or null if not found.
	 */
	public function get_term_translation( int $term_id, string $lang ): ?array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Fetching term translation from custom table
		$translation = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wpste_term_translations WHERE term_id = %d AND lang_code = %s",
				$term_id,
				$lang
			),
			ARRAY_A
		);

		return $translation ?: null;
	}

	/**
	 * Get all translations for a term
	 *
	 * @param int $term_id Term ID.
	 * @return array Array of translations.
	 */
	public function get_all_term_translations( int $term_id ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Fetching all term translations from custom table
		$translations = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wpste_term_translations WHERE term_id = %d",
				$term_id
			),
			ARRAY_A
		);

		return $translations ?: array();
	}

	/**
	 * Delete term translation
	 *
	 * @param int    $term_id Term ID.
	 * @param string $lang    Language code.
	 * @return bool Success status.
	 */
	public function delete_term_translation( int $term_id, string $lang ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Deleting term translation from custom table
		$deleted = $wpdb->delete(
			$wpdb->prefix . 'wpste_term_translations',
			array(
				'term_id'   => $term_id,
				'lang_code' => $lang,
			),
			array( '%d', '%s' )
		);

		if ( $deleted ) {
			do_action( 'wpste_term_translation_deleted', $term_id, $lang );
		}

		return (bool) $deleted;
	}

	/**
	 * Get translation group for a term
	 *
	 * @param int $term_id Term ID.
	 * @return string|null Translation group UUID or null.
	 */
	protected function get_translation_group( int $term_id ): ?string {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Fetching translation group for term from custom table
		$group = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT translation_group FROM {$wpdb->prefix}wpste_term_translations WHERE term_id = %d LIMIT 1",
				$term_id
			)
		);

		return $group ?: null;
	}

	/**
	 * Set translation group for original term
	 *
	 * @param int    $term_id           Term ID.
	 * @param string $lang              Language code.
	 * @param string $translation_group Translation group UUID.
	 * @return bool Success status.
	 */
	protected function set_translation_group( int $term_id, string $lang, string $translation_group ): bool {
		global $wpdb;

		// Store original term in translations table with empty translated fields
		$term = get_term( $term_id );
		if ( ! $term || is_wp_error( $term ) ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Setting translation group for original term in custom table
		$inserted = $wpdb->insert(
			$wpdb->prefix . 'wpste_term_translations',
			array(
				'term_id'                => $term_id,
				'lang_code'              => $lang,
				'translated_name'        => $term->name,
				'translated_slug'        => $term->slug,
				'translated_description' => $term->description ?? '',
				'translation_group'      => $translation_group,
				'translated_at'          => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return (bool) $inserted;
	}

	/**
	 * Bulk translate all terms in a taxonomy
	 *
	 * @param string $taxonomy    Taxonomy name.
	 * @param string $target_lang Target language code.
	 * @param array  $options     Translation options.
	 * @return array Results with counts.
	 */
	public function bulk_translate_taxonomy( string $taxonomy, string $target_lang, array $options = array() ): array {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) ) {
			return array( 'error' => $terms->get_error_message() );
		}

		$success_count = 0;
		$error_count = 0;
		$errors = array();

		foreach ( $terms as $term ) {
			$result = $this->translate_term( $term->term_id, $target_lang, $options );

			if ( isset( $result['error'] ) ) {
				$error_count++;
				$errors[] = array(
					'term' => $term->name,
					'error' => $result['error'],
				);
			} else {
				$success_count++;
			}
		}

		return array(
			'success_count' => $success_count,
			'error_count'   => $error_count,
			'errors'        => $errors,
			'total'         => count( $terms ),
		);
	}
}
