<?php
/**
 * Taxonomy Frontend Class
 *
 * Handles displaying translated taxonomy terms on the frontend.
 *
 * @package WP_Smart_Translation_Engine
 */

namespace WPSTE\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Taxonomy Frontend class
 */
class Taxonomy_Frontend {

	/**
	 * Current language
	 *
	 * @var string
	 */
	protected $current_lang;

	/**
	 * Taxonomy Translator instance
	 *
	 * @var \WPSTE\Core\Taxonomy_Translator
	 */
	protected $translator;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->translator = new \WPSTE\Core\Taxonomy_Translator();
		$this->current_lang = $this->get_current_language();
	}

	/**
	 * Initialize frontend hooks
	 */
	public function init(): void {
		// Only filter if not default language
		if ( $this->current_lang === 'en' ) {
			return;
		}

		// Filter term names
		add_filter( 'get_term', array( $this, 'filter_term' ), 10, 2 );
		add_filter( 'get_terms', array( $this, 'filter_terms' ), 10, 4 );

		// Filter term links
		add_filter( 'term_link', array( $this, 'filter_term_link' ), 10, 3 );
	}

	/**
	 * Get current language from various sources
	 *
	 * @return string Language code.
	 */
	protected function get_current_language(): string {
		// Check URL parameter
		if ( isset( $_GET['lang'] ) ) {
			return sanitize_text_field( $_GET['lang'] );
		}

		// Check cookie
		if ( isset( $_COOKIE['wpste_lang'] ) ) {
			return sanitize_text_field( $_COOKIE['wpste_lang'] );
		}

		// Check subdirectory in URL
		$uri = $_SERVER['REQUEST_URI'] ?? '';
		if ( preg_match( '#^/([a-z]{2})/#', $uri, $matches ) ) {
			return $matches[1];
		}

		// Default to English
		return 'en';
	}

	/**
	 * Filter a single term
	 *
	 * @param object $term     Term object.
	 * @param string $taxonomy Taxonomy name.
	 * @return object Modified term object.
	 */
	public function filter_term( $term, string $taxonomy ) {
		if ( ! $term || is_wp_error( $term ) ) {
			return $term;
		}

		$translation = $this->translator->get_term_translation( $term->term_id, $this->current_lang );

		if ( $translation ) {
			$term->name        = $translation['translated_name'];
			$term->slug        = $translation['translated_slug'];
			$term->description = $translation['translated_description'] ?? '';
		}

		return $term;
	}

	/**
	 * Filter multiple terms
	 *
	 * @param array  $terms      Array of term objects.
	 * @param array  $taxonomies Array of taxonomy names.
	 * @param array  $args       Query arguments.
	 * @param object $term_query WP_Term_Query object.
	 * @return array Modified terms array.
	 */
	public function filter_terms( array $terms, array $taxonomies, array $args, $term_query ): array {
		if ( empty( $terms ) ) {
			return $terms;
		}

		foreach ( $terms as &$term ) {
			if ( ! is_object( $term ) ) {
				continue;
			}

			$translation = $this->translator->get_term_translation( $term->term_id, $this->current_lang );

			if ( $translation ) {
				$term->name        = $translation['translated_name'];
				$term->slug        = $translation['translated_slug'];
				$term->description = $translation['translated_description'] ?? '';
			}
		}

		return $terms;
	}

	/**
	 * Filter term link to include language
	 *
	 * @param string $termlink Term link URL.
	 * @param object $term     Term object.
	 * @param string $taxonomy Taxonomy name.
	 * @return string Modified term link.
	 */
	public function filter_term_link( string $termlink, $term, string $taxonomy ): string {
		if ( $this->current_lang === 'en' ) {
			return $termlink;
		}

		$settings = get_option( 'wpste_settings', array() );
		$url_structure = $settings['url_structure'] ?? 'subdirectory';

		switch ( $url_structure ) {
			case 'subdirectory':
				// Add language prefix to URL
				$termlink = preg_replace( '#^(https?://[^/]+)/#', '$1/' . $this->current_lang . '/', $termlink );
				break;

			case 'parameter':
				// Add language parameter
				$termlink = add_query_arg( 'lang', $this->current_lang, $termlink );
				break;
		}

		return $termlink;
	}
}
