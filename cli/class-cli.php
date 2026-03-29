<?php
namespace WPSTE\CLI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CLI_Commands {

	/**
	 * Translate a post
	 *
	 * ## OPTIONS
	 *
	 * <post_id>
	 * : The post ID to translate
	 *
	 * <target_lang>
	 * : Target language code
	 *
	 * ## EXAMPLES
	 *
	 *     wp wpste translate-post 123 uk
	 *
	 * @param array $args Positional arguments
	 * @param array $assoc_args Associative arguments
	 */
	public function translate_post( $args, $assoc_args ) {
		list($post_id, $target_lang) = $args;

		\WP_CLI::log( 'Starting translation...' );

		$translator = new \WPSTE\Core\Post_Translator();
		$result = $translator->translate_post( (int) $post_id, $target_lang );

		if ( isset( $result['error'] ) ) {
			\WP_CLI::error( $result['error'] );
		}

		\WP_CLI::success( "Translation created! New post ID: {$result['post_id']}" );
	}

	/**
	 * List enabled languages
	 *
	 * ## EXAMPLES
	 *
	 *     wp wpste languages
	 */
	public function languages() {
		$settings = get_option( 'wpste_settings', array() );
		$languages = $settings['enabled_languages'] ?? array( 'en' );

		\WP_CLI::log( 'Enabled languages:' );
		foreach ( $languages as $lang ) {
			\WP_CLI::log( "  - {$lang}" );
		}
	}
}
