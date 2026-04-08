<?php
/**
 * Plugin Name:       Smart Translation Engine
 * Plugin URI:        https://github.com/cleargoal/wordpress-translator
 * Description:       Multi-provider AI translation for WordPress posts and pages. Supports DeepL, Azure Translator, and AWS Translate with smart key rotation and quota management.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Volodymyr Yefremov
 * Author URI:        https://github.com/cleargoal
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       smart-translation-engine
 * Domain Path:       /languages
 *
 * @package           Smart_Translation_Engine
 */

declare(strict_types=1);

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version.
 */
define( 'WPSTE_VERSION', '1.0.0' );

/**
 * Plugin directory path.
 */
define( 'WPSTE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 */
define( 'WPSTE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin basename.
 */
define( 'WPSTE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Database table prefix for plugin tables.
 */
function wpste_get_table_prefix(): string {
	global $wpdb;

	return $wpdb->prefix . 'wpste_';
}

/**
 * Encrypt API key using WordPress auth salt.
 *
 * @param string $api_key The API key to encrypt.
 * @return string Encrypted API key.
 */
function wpste_encrypt_api_key( string $api_key ): string {
	if ( empty( $api_key ) ) {
		return '';
	}

	$method = 'AES-256-CBC';
	$key = hash( 'sha256', AUTH_KEY . AUTH_SALT );
	$iv = substr( hash( 'sha256', SECURE_AUTH_KEY . SECURE_AUTH_SALT ), 0, 16 );

	$encrypted = openssl_encrypt( $api_key, $method, $key, 0, $iv );

	return base64_encode( $encrypted );
}

/**
 * Decrypt API key using WordPress auth salt.
 *
 * @param string $encrypted_key The encrypted API key.
 * @return string Decrypted API key.
 */
function wpste_decrypt_api_key( string $encrypted_key ): string {
	if ( empty( $encrypted_key ) ) {
		return '';
	}

	$method = 'AES-256-CBC';
	$key = hash( 'sha256', AUTH_KEY . AUTH_SALT );
	$iv = substr( hash( 'sha256', SECURE_AUTH_KEY . SECURE_AUTH_SALT ), 0, 16 );

	$decoded = base64_decode( $encrypted_key );
	$decrypted = openssl_decrypt( $decoded, $method, $key, 0, $iv );

	return $decrypted !== false ? $decrypted : '';
}

/**
 * Activation hook.
 * Runs when plugin is activated.
 */
function wpste_activate(): void {
	require_once WPSTE_PLUGIN_DIR . 'includes/database/class-installer.php';

	$installer = new WPSTE\Database\Installer();
	$installer->create_tables();

	// Add default options
	add_option(
		'wpste_settings',
		array(
			'default_language' => 'en',
			'enabled_languages' => array( 'en' ), // Start with English only, user can add more
			'primary_provider' => 'deepl',
			'fallback_providers' => array(),
			'post_types' => array( 'post', 'page' ),
			'url_structure' => 'subdirectory',
			'cache_ttl' => 300, // 5 minutes
		)
	);

	// Add custom capability to administrator role
	$role = get_role( 'administrator' );
	if ( $role ) {
		$role->add_cap( 'manage_translations' );
	}

	// Initialize license option (Free tier by default)
	add_option(
		'wpste_license',
		array(
			'tier' => 'free',
			'status' => 'inactive',
			'features' => array(),
			'limits' => array(),
		)
	);

	// Schedule license validation cron
	if ( ! wp_next_scheduled( 'wpste_validate_license' ) ) {
		wp_schedule_event( time(), 'daily', 'wpste_validate_license' );
	}

	// Schedule quota alert cleanup
	if ( ! wp_next_scheduled( 'wpste_cleanup_quota_alerts' ) ) {
		wp_schedule_event( time(), 'weekly', 'wpste_cleanup_quota_alerts' );
	}

	// Flush rewrite rules
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'wpste_activate' );

/**
 * Deactivation hook.
 * Runs when plugin is deactivated.
 */
function wpste_deactivate(): void {
	// Unschedule cron jobs
	$timestamp = wp_next_scheduled( 'wpste_validate_license' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'wpste_validate_license' );
	}

	$timestamp = wp_next_scheduled( 'wpste_cleanup_quota_alerts' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'wpste_cleanup_quota_alerts' );
	}

	// Flush rewrite rules
	flush_rewrite_rules();

	// Clear transients
	delete_transient( 'wpste_api_keys_deepl' );
	delete_transient( 'wpste_api_keys_azure' );
	delete_transient( 'wpste_api_keys_aws' );
}

register_deactivation_hook( __FILE__, 'wpste_deactivate' );

/**
 * AJAX handler for setting language session
 *
 * @return void
 */
function wpste_set_language_session_handler(): void {
	// Verify nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpste_set_language' ) ) {
		wp_send_json_error( array( 'message' => 'Security check failed' ) );
	}

	if ( ! isset( $_POST['lang'] ) ) {
		wp_send_json_error( array( 'message' => 'No language provided' ) );
	}

	$lang = sanitize_text_field( wp_unslash( $_POST['lang'] ) );

	// Validate language code (2 letter code)
	if ( ! preg_match( '/^[a-z]{2}$/', $lang ) ) {
		wp_send_json_error( array( 'message' => 'Invalid language code' ) );
	}

	// Start session if not started
	if ( session_status() === PHP_SESSION_NONE ) {
		session_start();
	}

	// Set session variable
	$_SESSION['wpste_lang'] = $lang;

	// Also set cookie as backup
	setcookie( 'wpste_lang', $lang, time() + ( 365 * 24 * 60 * 60 ), '/' );

	wp_send_json_success(
		array(
			'language' => $lang,
			'message'  => 'Language preference saved',
		)
	);
}

/**
 * AJAX handler for starting checkout process.
 * Generates/retrieves UUID and builds checkout URL.
 */
function wpste_start_checkout_handler(): void {
	// Verify nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpste_start_checkout' ) ) {
		wp_send_json_error( array( 'message' => 'Security check failed' ) );
	}

	// Validate required parameters
	if ( ! isset( $_POST['tier'] ) || ! isset( $_POST['period'] ) || ! isset( $_POST['price'] ) ) {
		wp_send_json_error( array( 'message' => 'Missing required parameters' ) );
	}

	$tier = sanitize_text_field( wp_unslash( $_POST['tier'] ) );
	$period = sanitize_text_field( wp_unslash( $_POST['period'] ) );
	$price = absint( $_POST['price'] );

	// Validate tier
	$valid_tiers = array( 'starter', 'basic', 'plus', 'pro', 'agency', 'enterprise' );
	if ( ! in_array( $tier, $valid_tiers, true ) ) {
		wp_send_json_error( array( 'message' => 'Invalid tier' ) );
	}

	// Validate period
	if ( ! in_array( $period, array( 'monthly', 'yearly' ), true ) ) {
		wp_send_json_error( array( 'message' => 'Invalid billing period' ) );
	}

	// Generate or retrieve UUID for this site
	$uuid = get_option( 'wpste_site_uuid' );
	if ( ! $uuid ) {
		$uuid = wp_generate_uuid4();
		update_option( 'wpste_site_uuid', $uuid );
	}

	// Get site information
	$site_url = get_site_url();
	$admin_email = get_option( 'admin_email' );
	$site_name = get_bloginfo( 'name' );

	// Register site with license server first (more secure than GET params)
	$license_server_url = defined( 'WPSTE_LICENSE_SERVER_URL' )
		? WPSTE_LICENSE_SERVER_URL
		: 'https://license.yoursite.com'; // Production default

	// Step 1: POST site data to get a checkout token (secure)
	$registration_response = wp_remote_post(
		$license_server_url . '/api/checkout/register',
		array(
			'body'    => array(
				'uuid'        => $uuid,
				'site_url'    => $site_url,
				'admin_email' => $admin_email,
				'site_name'   => $site_name,
				'tier'        => $tier,
				'period'      => $period,
				'price'       => $price,
			),
			'timeout' => 15,
		)
	);

	if ( is_wp_error( $registration_response ) ) {
		$error_msg = 'Failed to connect to license server: ' . $registration_response->get_error_message();
		error_log( 'WPSTE Checkout Error: ' . $error_msg );
		wp_send_json_error( array( 'message' => $error_msg ) );
	}

	$reg_body = wp_remote_retrieve_body( $registration_response );
	$reg_data = json_decode( $reg_body, true );

	if ( ! $reg_data || ! isset( $reg_data['token'] ) ) {
		$error_detail = 'License server response: ' . substr( $reg_body, 0, 500 );
		error_log( 'WPSTE Checkout Error: Invalid response from license server. ' . $error_detail );
		wp_send_json_error( array(
			'message' => 'License server error. Check if API is running on ' . $license_server_url,
			'debug' => WP_DEBUG ? $error_detail : null
		) );
	}

	// Step 2: Build checkout URL with just the token (secure)
	$checkout_url = add_query_arg(
		array(
			'token' => $reg_data['token'], // Short-lived token (not PII)
		),
		$license_server_url . '/checkout'
	);

	// Return UUID and checkout URL
	wp_send_json_success(
		array(
			'uuid'         => $uuid,
			'checkout_url' => $checkout_url,
			'message'      => 'Checkout initialized',
		)
	);
}

/**
 * AJAX handler for activating license after purchase.
 * Called after successful payment with license key from server.
 */
function wpste_activate_license_handler(): void {
	// Verify nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpste_activate_license' ) ) {
		wp_send_json_error( array( 'message' => 'Security check failed' ) );
	}

	// Validate required parameters
	if ( ! isset( $_POST['license_key'] ) ) {
		wp_send_json_error( array( 'message' => 'License key required' ) );
	}

	$license_key = sanitize_text_field( wp_unslash( $_POST['license_key'] ) );

	// Get UUID
	$uuid = get_option( 'wpste_site_uuid' );
	if ( ! $uuid ) {
		wp_send_json_error( array( 'message' => 'Site not registered' ) );
	}

	// Validate license with server
	$license_api_url = defined( 'WPSTE_LICENSE_SERVER_URL' )
		? WPSTE_LICENSE_SERVER_URL . '/api/license/validate'
		: 'https://license.yoursite.com/api/license/validate'; // Production default

	$response = wp_remote_post(
		$license_api_url,
		array(
			'body'    => array(
				'license_key' => $license_key,
				'uuid'        => $uuid,
				'site_url'    => get_site_url(),
			),
			'timeout' => 15,
		)
	);

	if ( is_wp_error( $response ) ) {
		wp_send_json_error( array( 'message' => 'Failed to validate license: ' . $response->get_error_message() ) );
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	if ( ! $data || ! isset( $data['valid'] ) || ! $data['valid'] ) {
		wp_send_json_error( array( 'message' => 'Invalid license key' ) );
	}

	// Save license data
	$license_data = array(
		'key'        => $license_key,
		'tier'       => $data['tier'] ?? 'free',
		'status'     => 'active',
		'expires_at' => $data['expires_at'] ?? null,
		'activated_at' => current_time( 'mysql' ),
	);

	update_option( 'wpste_license', $license_data );

	// Download premium features if available
	// TODO: Implement feature download from license server

	wp_send_json_success(
		array(
			'tier'    => $license_data['tier'],
			'message' => 'License activated successfully',
		)
	);
}

/**
 * Begin execution of the plugin.
 *
 * Load dependencies and initialize the plugin.
 */
function wpste_run(): void {
	// Load composer autoloader if available (for AWS SDK)
	if ( file_exists( WPSTE_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
		require_once WPSTE_PLUGIN_DIR . 'vendor/autoload.php';
	}

	// Load core classes
	require_once WPSTE_PLUGIN_DIR . 'includes/database/class-database.php';
	require_once WPSTE_PLUGIN_DIR . 'includes/providers/interface-translation-provider.php';
	require_once WPSTE_PLUGIN_DIR . 'includes/providers/abstract-translation-provider.php';
	require_once WPSTE_PLUGIN_DIR . 'includes/key-management/interface-key-manager.php';
	require_once WPSTE_PLUGIN_DIR . 'includes/core/class-provider-factory.php';
	require_once WPSTE_PLUGIN_DIR . 'includes/core/class-translation-manager.php';
	require_once WPSTE_PLUGIN_DIR . 'includes/core/class-post-translator.php';
	require_once WPSTE_PLUGIN_DIR . 'includes/core/class-taxonomy-translator.php';

	// Load licensing classes
	require_once WPSTE_PLUGIN_DIR . 'includes/licensing/class-license-storage.php';
	require_once WPSTE_PLUGIN_DIR . 'includes/licensing/class-license-validator.php';
	require_once WPSTE_PLUGIN_DIR . 'includes/licensing/class-license-manager.php';
	require_once WPSTE_PLUGIN_DIR . 'includes/licensing/class-tier-manager.php';
	require_once WPSTE_PLUGIN_DIR . 'includes/licensing/class-feature-downloader.php';
	require_once WPSTE_PLUGIN_DIR . 'includes/licensing/class-feature-loader.php';
	require_once WPSTE_PLUGIN_DIR . 'includes/licensing/class-quota-notifier.php';

	// Load features
	$license_storage = new WPSTE\Licensing\License_Storage();
	$feature_loader = new WPSTE\Licensing\Feature_Loader( $license_storage );
	$feature_loader->load_features();

	// Schedule cron jobs
	add_action(
		'wpste_validate_license',
		function () {
			$license_manager = new WPSTE\Licensing\License_Manager();
			$license_manager->validate();
		}
	);

	add_action(
		'wpste_cleanup_quota_alerts',
		function () use ( $license_storage ) {
			$quota_notifier = new WPSTE\Licensing\Quota_Notifier( $license_storage );
			$quota_notifier->cleanup_old_alerts();
		}
	);

	// Initialize admin functionality if in admin
	if ( is_admin() ) {
		require_once WPSTE_PLUGIN_DIR . 'admin/class-admin.php';
		$admin = new WPSTE\Admin\Admin();
		$admin->init();

		// Display quota alerts
		$quota_notifier = new WPSTE\Licensing\Quota_Notifier( $license_storage );
		add_action( 'admin_notices', array( $quota_notifier, 'display_admin_notices' ) );
	}

	// Initialize public functionality
	require_once WPSTE_PLUGIN_DIR . 'public/class-public.php';
	$public = new WPSTE\Frontend\PublicFrontend();
	$public->init();

	// Initialize taxonomy frontend
	require_once WPSTE_PLUGIN_DIR . 'public/class-taxonomy-frontend.php';
	$taxonomy_frontend = new WPSTE\Frontend\Taxonomy_Frontend();
	$taxonomy_frontend->init();

	// Initialize language switcher
	require_once WPSTE_PLUGIN_DIR . 'public/class-language-switcher.php';
	$language_switcher = new WPSTE\Frontend\Language_Switcher();

	// Register language switcher shortcode
	add_shortcode( 'wpste_language_switcher', array( $language_switcher, 'shortcode' ) );

	// AJAX handler for setting language session
	add_action( 'wp_ajax_wpste_set_language', 'wpste_set_language_session_handler' );
	add_action( 'wp_ajax_nopriv_wpste_set_language', 'wpste_set_language_session_handler' );

	// AJAX handlers for licensing
	add_action( 'wp_ajax_wpste_start_checkout', 'wpste_start_checkout_handler' );
	add_action( 'wp_ajax_wpste_activate_license', 'wpste_activate_license_handler' );

	// Register language switcher widget
	require_once WPSTE_PLUGIN_DIR . 'public/class-language-switcher-widget.php';
	add_action(
		'widgets_init',
		function () {
			register_widget( 'WPSTE\Frontend\Language_Switcher_Widget' );
		}
	);

	// Register language switcher Gutenberg block
	require_once WPSTE_PLUGIN_DIR . 'admin/blocks/class-language-switcher-block.php';
	add_action(
		'init',
		function () {
			$block = new WPSTE\Admin\Blocks\Language_Switcher_Block();
			$block->register();
		}
	);

	// Initialize REST API
	require_once WPSTE_PLUGIN_DIR . 'includes/integrations/class-rest-api.php';
	add_action(
		'rest_api_init',
		function () {
			$rest_api = new WPSTE\Integrations\REST_API();
			$rest_api->register_routes();
		}
	);

	// Initialize WP-CLI commands if available
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		require_once WPSTE_PLUGIN_DIR . 'cli/class-cli.php';
		WP_CLI::add_command( 'wpste', WPSTE\CLI\CLI_Commands::class );
	}
}

add_action( 'plugins_loaded', 'wpste_run' );
