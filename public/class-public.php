<?php
/**
 * Public Frontend Class
 *
 * Handles frontend post translation display with session-based language switching.
 *
 * @package WP_Smart_Translation_Engine
 */

namespace WPSTE\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Public Frontend class
 */
class PublicFrontend {

	/**
	 * Current language
	 *
	 * @var string
	 */
	protected $current_lang;

	/**
	 * Translation cache
	 *
	 * @var array
	 */
	protected $translation_cache = array();

	/**
	 * Initialize frontend hooks
	 */
	public function init(): void {
		// Start session if not already started
		if ( session_status() === PHP_SESSION_NONE ) {
			session_start();
		}

		// Get current language
		$this->current_lang = $this->get_current_language();

		// Only filter if not default language
		if ( $this->current_lang === 'en' ) {
			// Add language meta even for English
			add_action( 'wp_head', array( $this, 'add_language_meta' ) );
			return;
		}

		// Filter post content and title
		add_filter( 'the_content', array( $this, 'filter_content' ), 10 );
		add_filter( 'the_title', array( $this, 'filter_title' ), 10, 2 );
		add_filter( 'the_excerpt', array( $this, 'filter_excerpt' ), 10 );
		add_action( 'wp_head', array( $this, 'add_language_meta' ) );
	}

	/**
	 * Filter post content
	 *
	 * @param string $content Post content.
	 * @return string Filtered content.
	 */
	public function filter_content( $content ) {
		if ( ! is_singular() ) {
			return $content;
		}

		global $post;

		if ( ! $post || ! $post->ID ) {
			return $content;
		}

		$translation = $this->get_translation( $post->ID, $this->current_lang );

		if ( $translation && ! empty( $translation['translated_content'] ) ) {
			return $translation['translated_content'];
		}

		return $content;
	}

	/**
	 * Filter post title
	 *
	 * @param string $title   Post title.
	 * @param int    $post_id Post ID.
	 * @return string Filtered title.
	 */
	public function filter_title( $title, $post_id = 0 ) {
		if ( ! $post_id ) {
			return $title;
		}

		// Skip if in admin or not a real post
		if ( is_admin() || ! $title ) {
			return $title;
		}

		$translation = $this->get_translation( $post_id, $this->current_lang );

		if ( $translation && ! empty( $translation['translated_title'] ) ) {
			return $translation['translated_title'];
		}

		return $title;
	}

	/**
	 * Filter post excerpt
	 *
	 * @param string $excerpt Post excerpt.
	 * @return string Filtered excerpt.
	 */
	public function filter_excerpt( $excerpt ) {
		if ( ! is_singular() ) {
			return $excerpt;
		}

		global $post;

		if ( ! $post || ! $post->ID ) {
			return $excerpt;
		}

		$translation = $this->get_translation( $post->ID, $this->current_lang );

		if ( $translation && ! empty( $translation['translated_excerpt'] ) ) {
			return $translation['translated_excerpt'];
		}

		return $excerpt;
	}

	/**
	 * Get translation for post
	 *
	 * @param int    $post_id Post ID.
	 * @param string $lang    Language code.
	 * @return array|null Translation data or null.
	 */
	protected function get_translation( int $post_id, string $lang ): ?array {
		// Check cache first
		$cache_key = $post_id . '_' . $lang;
		if ( isset( $this->translation_cache[ $cache_key ] ) ) {
			return $this->translation_cache[ $cache_key ];
		}

		global $wpdb;

		$translation = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wpste_post_translations
				WHERE post_id = %d
				AND lang_code = %s
				AND status = 'published'
				LIMIT 1",
				$post_id,
				$lang
			),
			ARRAY_A
		);

		// Cache the result (even if null)
		$this->translation_cache[ $cache_key ] = $translation ?: null;

		return $this->translation_cache[ $cache_key ];
	}

	/**
	 * Get current language from various sources
	 *
	 * @return string Language code.
	 */
	protected function get_current_language(): string {
		// Check URL parameter FIRST (allows explicit language switching)
		if ( isset( $_GET['lang'] ) ) {
			$lang = sanitize_text_field( wp_unslash( $_GET['lang'] ) );
			// Save to session for persistence
			$_SESSION['wpste_lang'] = $lang;
			return $lang;
		}

		// Check session (persists across pages when no URL param)
		if ( ! empty( $_SESSION['wpste_lang'] ) ) {
			return sanitize_text_field( $_SESSION['wpste_lang'] );
		}

		// Check cookie
		if ( isset( $_COOKIE['wpste_lang'] ) ) {
			$lang = sanitize_text_field( wp_unslash( $_COOKIE['wpste_lang'] ) );
			// Save to session for consistency
			$_SESSION['wpste_lang'] = $lang;
			return $lang;
		}

		// Check subdirectory in URL
		$uri = wp_unslash( $_SERVER['REQUEST_URI'] ) ?? '';
		if ( preg_match( '#^/([a-z]{2})/#', $uri, $matches ) ) {
			$lang = $matches[1];
			$_SESSION['wpste_lang'] = $lang;
			return $lang;
		}

		// Default to English
		return 'en';
	}

	/**
	 * Add language meta tags
	 */
	public function add_language_meta(): void {
		if ( is_singular() ) {
			$lang = $this->current_lang ?? 'en';
			echo '<meta property="og:locale" content="' . esc_attr( $lang ) . '_' . esc_attr( strtoupper( $lang ) ) . '" />' . "\n";
			echo '<meta name="language" content="' . esc_attr( $lang ) . '" />' . "\n";
		}
	}
}
