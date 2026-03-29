<?php
/**
 * Post Translator
 *
 * Handles translation of WordPress posts and pages.
 *
 * @package WP_Smart_Translation_Engine
 */

namespace WPSTE\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post Translator class
 */
class Post_Translator {

	/**
	 * Translation manager
	 *
	 * @var Translation_Manager
	 */
	protected $translation_manager;

	/**
	 * Database
	 *
	 * @var \WPSTE\Database\Database
	 */
	protected $database;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->translation_manager = new Translation_Manager();
		$this->database = new \WPSTE\Database\Database();
	}

	/**
	 * Translate a post to target language
	 *
	 * @param int    $post_id Source post ID
	 * @param string $target_lang Target language code
	 * @param array  $options Translation options
	 * @return array Result with 'post_id' or 'error'
	 */
	public function translate_post( int $post_id, string $target_lang, array $options = array() ): array {
		$source_post = get_post( $post_id );

		if ( ! $source_post ) {
			return array( 'error' => 'Post not found' );
		}

		// Get source language
		$source_lang = get_post_meta( $post_id, '_wpste_lang_code', true ) ?: 'en';

		// Generate translation group ID
		$translation_group = get_post_meta( $post_id, '_wpste_translation_group', true );
		if ( ! $translation_group ) {
			$translation_group = wp_generate_uuid4();
			update_post_meta( $post_id, '_wpste_translation_group', $translation_group );
		}

		// Check if translation already exists
		$existing = $this->find_translation( $post_id, $target_lang );
		if ( $existing ) {
			return array(
				'post_id' => $existing,
				'message' => 'Translation already exists',
			);
		}

		// Translate title
		$title_result = $this->translation_manager->translate(
			$source_post->post_title,
			$source_lang,
			$target_lang
		);

		if ( isset( $title_result['error'] ) ) {
			return $title_result;
		}

		// Translate content
		$content_result = $this->translation_manager->translate(
			$source_post->post_content,
			$source_lang,
			$target_lang
		);

		if ( isset( $content_result['error'] ) ) {
			return $content_result;
		}

		// Translate excerpt if exists
		$translated_excerpt = '';
		if ( ! empty( $source_post->post_excerpt ) ) {
			$excerpt_result = $this->translation_manager->translate(
				$source_post->post_excerpt,
				$source_lang,
				$target_lang
			);

			if ( ! isset( $excerpt_result['error'] ) ) {
				$translated_excerpt = $excerpt_result['text'];
			}
		}

		// Create new post
		$new_post_data = array(
			'post_title' => $title_result['text'],
			'post_content' => $content_result['text'],
			'post_excerpt' => $translated_excerpt,
			'post_status' => 'draft',
			'post_type' => $source_post->post_type,
			'post_author' => $source_post->post_author,
		);

		$new_post_id = wp_insert_post( $new_post_data );

		if ( is_wp_error( $new_post_id ) ) {
			return array( 'error' => $new_post_id->get_error_message() );
		}

		// Add post meta
		update_post_meta( $new_post_id, '_wpste_lang_code', $target_lang );
		update_post_meta( $new_post_id, '_wpste_translation_group', $translation_group );
		update_post_meta( $new_post_id, '_wpste_source_post_id', $post_id );

		// Store translation record
		$characters = strlen( $source_post->post_title ) + strlen( $source_post->post_content ) + strlen( $source_post->post_excerpt );

		$this->database->insert(
			'translations',
			array(
				'post_id' => $new_post_id,
				'source_post_id' => $post_id,
				'lang_code' => $target_lang,
				'translation_group' => $translation_group,
				'provider_used' => $title_result['provider'] ?? 'unknown',
				'status' => 'draft',
				'translated_at' => current_time( 'mysql' ),
				'characters_translated' => $characters,
			)
		);

		do_action(
			'wpste_post_translated',
			array(
				'source_post_id' => $post_id,
				'new_post_id' => $new_post_id,
				'target_lang' => $target_lang,
				'provider' => $title_result['provider'] ?? 'unknown',
			)
		);

		return array(
			'post_id' => $new_post_id,
			'source_post_id' => $post_id,
			'target_lang' => $target_lang,
			'characters' => $characters,
		);
	}

	/**
	 * Find existing translation
	 *
	 * @param int    $post_id Source post ID
	 * @param string $target_lang Target language
	 * @return int|null Translation post ID or null
	 */
	protected function find_translation( int $post_id, string $target_lang ): ?int {
		$translation_group = get_post_meta( $post_id, '_wpste_translation_group', true );

		if ( ! $translation_group ) {
			return null;
		}

		global $wpdb;
		$post_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} pm1
            INNER JOIN {$wpdb->postmeta} pm2 ON pm1.post_id = pm2.post_id
            WHERE pm1.meta_key = '_wpste_translation_group'
            AND pm1.meta_value = %s
            AND pm2.meta_key = '_wpste_lang_code'
            AND pm2.meta_value = %s
            AND pm1.post_id != %d
            LIMIT 1",
				$translation_group,
				$target_lang,
				$post_id
			)
		);

		return $post_id ? (int) $post_id : null;
	}

	/**
	 * Get all translations for a post
	 *
	 * @param int $post_id Post ID
	 * @return array Array of translations with lang_code => post_id
	 */
	public function get_translations( int $post_id ): array {
		$translation_group = get_post_meta( $post_id, '_wpste_translation_group', true );

		if ( ! $translation_group ) {
			return array();
		}

		global $wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT pm1.post_id, pm2.meta_value as lang_code
            FROM {$wpdb->postmeta} pm1
            INNER JOIN {$wpdb->postmeta} pm2 ON pm1.post_id = pm2.post_id
            WHERE pm1.meta_key = '_wpste_translation_group'
            AND pm1.meta_value = %s
            AND pm2.meta_key = '_wpste_lang_code'",
				$translation_group
			),
			ARRAY_A
		);

		$translations = array();
		foreach ( $results as $row ) {
			$translations[ $row['lang_code'] ] = (int) $row['post_id'];
		}

		return $translations;
	}
}
