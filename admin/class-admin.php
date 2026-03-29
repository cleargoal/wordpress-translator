<?php
/**
 * Admin Class
 *
 * Handles all admin functionality.
 *
 * @package WP_Smart_Translation_Engine
 */

namespace WPSTE\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin class
 */
class Admin {

	/**
	 * Initialize admin functionality
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_translation_metabox' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_ajax_wpste_translate_post', array( $this, 'ajax_translate_post' ) );
	}

	/**
	 * Add admin menu pages
	 */
	public function add_admin_menu(): void {
		add_menu_page(
			__( 'WP Translation Engine', 'wp-smart-translation-engine' ),
			__( 'Translation', 'wp-smart-translation-engine' ),
			'manage_options',
			'wpste-settings',
			array( $this, 'render_settings_page' ),
			'dashicons-translation',
			80
		);

		add_submenu_page(
			'wpste-settings',
			__( 'Settings', 'wp-smart-translation-engine' ),
			__( 'Settings', 'wp-smart-translation-engine' ),
			'manage_options',
			'wpste-settings',
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			'wpste-settings',
			__( 'API Keys', 'wp-smart-translation-engine' ),
			__( 'API Keys', 'wp-smart-translation-engine' ),
			'manage_options',
			'wpste-keys',
			array( $this, 'render_keys_page' )
		);
	}

	/**
	 * Render settings page
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		include WPSTE_PLUGIN_DIR . 'admin/partials/settings-page.php';
	}

	/**
	 * Render API keys page
	 */
	public function render_keys_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		include WPSTE_PLUGIN_DIR . 'admin/partials/api-keys-page.php';
	}

	/**
	 * Add translation metabox to posts
	 */
	public function add_translation_metabox(): void {
		$settings = get_option( 'wpste_settings', array() );
		$post_types = $settings['post_types'] ?? array( 'post', 'page' );

		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'wpste_translation',
				__( 'Translation', 'wp-smart-translation-engine' ),
				array( $this, 'render_translation_metabox' ),
				$post_type,
				'side',
				'default'
			);
		}
	}

	/**
	 * Render translation metabox
	 *
	 * @param \WP_Post $post Post object
	 */
	public function render_translation_metabox( $post ): void {
		$settings = get_option( 'wpste_settings', array() );
		$enabled_langs = $settings['enabled_languages'] ?? array( 'en' );
		$current_lang = get_post_meta( $post->ID, '_wpste_lang_code', true ) ?: 'en';

		wp_nonce_field( 'wpste_translate_post', 'wpste_translate_nonce' );

		echo '<div class="wpste-metabox">';
		echo '<p><strong>' . esc_html__( 'Current Language:', 'wp-smart-translation-engine' ) . '</strong> ' . esc_html( strtoupper( $current_lang ) ) . '</p>';

		echo '<p><label for="wpste_target_lang">' . esc_html__( 'Translate to:', 'wp-smart-translation-engine' ) . '</label>';
		echo '<select id="wpste_target_lang" name="wpste_target_lang">';
		foreach ( $enabled_langs as $lang ) {
			if ( $lang !== $current_lang ) {
				echo '<option value="' . esc_attr( $lang ) . '">' . esc_html( strtoupper( $lang ) ) . '</option>';
			}
		}
		echo '</select></p>';

		echo '<p><button type="button" class="button button-primary wpste-translate-btn" data-post-id="' . esc_attr( $post->ID ) . '">';
		echo esc_html__( 'Translate', 'wp-smart-translation-engine' );
		echo '</button></p>';

		echo '<div class="wpste-translation-status" style="display:none;"></div>';
		echo '</div>';
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Hook suffix
	 */
	public function enqueue_admin_assets( string $hook ): void {
		if ( $hook === 'post.php' || $hook === 'post-new.php' ) {
			wp_enqueue_script(
				'wpste-admin',
				WPSTE_PLUGIN_URL . 'admin/js/admin.js',
				array( 'jquery' ),
				WPSTE_VERSION,
				true
			);

			wp_localize_script(
				'wpste-admin',
				'wpste',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce' => wp_create_nonce( 'wpste_translate_post' ),
				)
			);
		}
	}

	/**
	 * AJAX handler for post translation
	 */
	public function ajax_translate_post(): void {
		check_ajax_referer( 'wpste_translate_post', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$post_id = absint( $_POST['post_id'] ?? 0 );
		$target_lang = sanitize_text_field( $_POST['target_lang'] ?? '' );

		if ( ! $post_id || ! $target_lang ) {
			wp_send_json_error( array( 'message' => 'Invalid parameters' ) );
		}

		$translator = new \WPSTE\Core\Post_Translator();
		$result = $translator->translate_post( $post_id, $target_lang );

		if ( isset( $result['error'] ) ) {
			wp_send_json_error( array( 'message' => $result['error'] ) );
		}

		wp_send_json_success(
			array(
				'message' => 'Translation created successfully',
				'new_post_id' => $result['post_id'],
				'edit_link' => get_edit_post_link( $result['post_id'] ),
			)
		);
	}
}
