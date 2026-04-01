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

		// Taxonomy translation hooks
		add_action( 'category_edit_form_fields', array( $this, 'add_term_translation_fields' ), 10, 2 );
		add_action( 'post_tag_edit_form_fields', array( $this, 'add_term_translation_fields' ), 10, 2 );
		add_action( 'wp_ajax_wpste_translate_term', array( $this, 'ajax_translate_term' ) );
		add_action( 'wp_ajax_wpste_delete_term_translation', array( $this, 'ajax_delete_term_translation' ) );

		// Add to all public taxonomies
		$taxonomies = get_taxonomies( array( 'public' => true ), 'names' );
		foreach ( $taxonomies as $taxonomy ) {
			if ( ! in_array( $taxonomy, array( 'category', 'post_tag' ), true ) ) {
				add_action( "{$taxonomy}_edit_form_fields", array( $this, 'add_term_translation_fields' ), 10, 2 );
			}
		}
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

		add_submenu_page(
			'wpste-settings',
			__( 'Upgrade', 'wp-smart-translation-engine' ),
			__( 'Upgrade', 'wp-smart-translation-engine' ),
			'manage_options',
			'wpste-upgrade',
			array( $this, 'render_upgrade_page' )
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
	 * Render upgrade page
	 */
	public function render_upgrade_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		include WPSTE_PLUGIN_DIR . 'admin/partials/upgrade-page.php';
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
		$current_lang = 'en'; // Always English for source

		// Get existing translations
		global $wpdb;
		$translations = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT lang_code, translated_at, characters_translated, provider_used
				FROM {$wpdb->prefix}wpste_post_translations
				WHERE post_id = %d
				AND status = 'published'
				ORDER BY created_at DESC",
				$post->ID
			),
			ARRAY_A
		);

		$translated_langs = wp_list_pluck( $translations, 'lang_code' );

		wp_nonce_field( 'wpste_translate_post', 'wpste_translate_nonce' );

		echo '<div class="wpste-metabox">';
		echo '<p><strong>' . esc_html__( 'Source Language:', 'wp-smart-translation-engine' ) . '</strong> ' . esc_html( strtoupper( $current_lang ) ) . '</p>';

		// Show existing translations
		if ( ! empty( $translations ) ) {
			echo '<div style="margin: 15px 0; padding: 10px; background: #f0f0f1; border-left: 3px solid #2271b1;">';
			echo '<strong>' . esc_html__( 'Existing Translations:', 'wp-smart-translation-engine' ) . '</strong>';
			echo '<ul style="margin: 5px 0 0 0; padding-left: 20px;">';
			foreach ( $translations as $translation ) {
				$date = date_i18n( get_option( 'date_format' ), strtotime( $translation['translated_at'] ) );
				echo '<li>' . esc_html( strtoupper( $translation['lang_code'] ) ) . ' (' . esc_html( $date ) . ')</li>';
			}
			echo '</ul></div>';
		}

		// Show available languages to translate to
		$available_langs = array_diff( $enabled_langs, $translated_langs, array( $current_lang ) );

		if ( empty( $available_langs ) ) {
			echo '<p style="color: #666;"><em>' . esc_html__( 'All enabled languages have been translated.', 'wp-smart-translation-engine' ) . '</em></p>';
		} else {
			echo '<p><label for="wpste_target_lang">' . esc_html__( 'Translate to:', 'wp-smart-translation-engine' ) . '</label>';
			echo '<select id="wpste_target_lang" name="wpste_target_lang">';
			echo '<option value="">' . esc_html__( '-- Select Language --', 'wp-smart-translation-engine' ) . '</option>';
			foreach ( $available_langs as $lang ) {
				echo '<option value="' . esc_attr( $lang ) . '">' . esc_html( strtoupper( $lang ) ) . '</option>';
			}
			echo '</select></p>';

			echo '<p><button type="button" class="button button-primary wpste-translate-btn" data-post-id="' . esc_attr( $post->ID ) . '">';
			echo esc_html__( 'Translate', 'wp-smart-translation-engine' );
			echo '</button></p>';
		}

		echo '<div class="wpste-translation-status" style="display:none;"></div>';
		echo '</div>';
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Hook suffix
	 */
	public function enqueue_admin_assets( string $hook ): void {
		// Post translation assets
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

		// Taxonomy translation assets
		if ( $hook === 'term.php' || $hook === 'edit-tags.php' ) {
			wp_enqueue_script(
				'wpste-taxonomy-admin',
				WPSTE_PLUGIN_URL . 'admin/js/taxonomy-admin.js',
				array( 'jquery' ),
				WPSTE_VERSION,
				true
			);

			wp_localize_script(
				'wpste-taxonomy-admin',
				'wpste_taxonomy',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'wpste_taxonomy_translate' ),
					'strings'  => array(
						'select_language' => __( 'Please select a target language.', 'wp-smart-translation-engine' ),
						'success'         => __( 'Success!', 'wp-smart-translation-engine' ),
						'error'           => __( 'Error:', 'wp-smart-translation-engine' ),
						'confirm_delete'  => __( 'Are you sure you want to delete this translation?', 'wp-smart-translation-engine' ),
						'deleting'        => __( 'Deleting...', 'wp-smart-translation-engine' ),
						'delete'          => __( 'Delete', 'wp-smart-translation-engine' ),
						'delete_error'    => __( 'Failed to delete translation.', 'wp-smart-translation-engine' ),
					),
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

		$post = get_post( $post_id );
		$lang_name = strtoupper( $target_lang );

		wp_send_json_success(
			array(
				'message'         => sprintf( 'Post translated to %s successfully! Translation stored in database.', $lang_name ),
				'translation_id'  => $result['translation_id'],
				'post_id'         => $post_id,
				'target_lang'     => $target_lang,
				'characters'      => $result['characters'],
				'view_link'       => get_permalink( $post_id ) . '?lang=' . $target_lang,
			)
		);
	}

	/**
	 * Add translation fields to term edit screen
	 *
	 * @param object $term     Term object.
	 * @param string $taxonomy Taxonomy name.
	 */
	public function add_term_translation_fields( $term, string $taxonomy ): void {
		include WPSTE_PLUGIN_DIR . 'admin/partials/term-metabox.php';
	}

	/**
	 * AJAX handler for term translation
	 */
	public function ajax_translate_term(): void {
		check_ajax_referer( 'wpste_taxonomy_translate', 'nonce' );

		if ( ! current_user_can( 'manage_categories' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$term_id = absint( $_POST['term_id'] ?? 0 );
		$target_lang = sanitize_text_field( $_POST['target_lang'] ?? '' );

		if ( ! $term_id || ! $target_lang ) {
			wp_send_json_error( array( 'message' => 'Invalid parameters' ) );
		}

		$translator = new \WPSTE\Core\Taxonomy_Translator();
		$result = $translator->translate_term( $term_id, $target_lang );

		if ( isset( $result['error'] ) ) {
			wp_send_json_error( array( 'message' => $result['error'] ) );
		}

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: %s: Translated term name */
					__( 'Term translated successfully: %s', 'wp-smart-translation-engine' ),
					$result['translated_name']
				),
			)
		);
	}

	/**
	 * AJAX handler for deleting term translation
	 */
	public function ajax_delete_term_translation(): void {
		check_ajax_referer( 'wpste_taxonomy_translate', 'nonce' );

		if ( ! current_user_can( 'manage_categories' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$term_id = absint( $_POST['term_id'] ?? 0 );
		$lang = sanitize_text_field( $_POST['lang'] ?? '' );

		if ( ! $term_id || ! $lang ) {
			wp_send_json_error( array( 'message' => 'Invalid parameters' ) );
		}

		$translator = new \WPSTE\Core\Taxonomy_Translator();
		$deleted = $translator->delete_term_translation( $term_id, $lang );

		if ( ! $deleted ) {
			wp_send_json_error( array( 'message' => __( 'Failed to delete translation.', 'wp-smart-translation-engine' ) ) );
		}

		wp_send_json_success( array( 'message' => __( 'Translation deleted successfully.', 'wp-smart-translation-engine' ) ) );
	}
}
