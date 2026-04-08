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
 * Validates a license key with the license server and saves it locally on success.
 *
 * @param string $license_key The license key to validate.
 * @return array{success: bool, tier?: string, message?: string}
 */
function wpste_validate_and_save_license( string $license_key ): array {
	$uuid = get_option( 'wpste_site_uuid' );
	if ( ! $uuid ) {
		return array( 'success' => false, 'message' => 'Site not registered. Please try purchasing again.' );
	}

	$license_server_url = defined( 'WPSTE_LICENSE_SERVER_URL' )
		? WPSTE_LICENSE_SERVER_URL
		: 'https://license.yoursite.com';

	$response = wp_remote_post(
		$license_server_url . '/api/v1/license/validate',
		array(
			'headers' => array(
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
			),
			'body'    => wp_json_encode(
				array(
					'license_key' => $license_key,
					'uuid'        => $uuid,
					'site_url'    => get_site_url(),
				)
			),
			'timeout' => 15,
		)
	);

	if ( is_wp_error( $response ) ) {
		return array( 'success' => false, 'message' => 'Failed to reach license server: ' . $response->get_error_message() );
	}

	$http_code   = wp_remote_retrieve_response_code( $response );
	$raw_body    = wp_remote_retrieve_body( $response );
	$data        = json_decode( $raw_body, true );

	if ( ! $data || empty( $data['valid'] ) ) {
		$msg = isset( $data['message'] )
			? $data['message']
			: sprintf( 'Validate endpoint HTTP %s: %s', $http_code, substr( $raw_body, 0, 300 ) );
		error_log( 'WPSTE License Validate Error: HTTP ' . $http_code . ' | ' . $raw_body );
		return array( 'success' => false, 'message' => $msg );
	}

	$tier = isset( $data['tier'] ) ? sanitize_text_field( $data['tier'] ) : 'free';

	update_option(
		'wpste_license',
		array(
			'key'          => $license_key,
			'tier'         => $tier,
			'status'       => 'active',
			'expires_at'   => isset( $data['expires_at'] ) ? sanitize_text_field( $data['expires_at'] ) : null,
			'activated_at' => current_time( 'mysql' ),
		)
	);

	return array( 'success' => true, 'tier' => $tier );
}

/**
 * Registers the REST API endpoint that the license server redirects to after payment.
 * Using REST API avoids server-level restrictions on direct wp-admin access from external domains.
 */
function wpste_register_license_callback_endpoint(): void {
	register_rest_route(
		'wpste/v1',
		'/license/activate-callback',
		array(
			'methods'             => 'GET',
			'callback'            => 'wpste_license_activate_callback',
			'permission_callback' => '__return_true',
			'args'                => array(
				'license_key'  => array(
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				),
				'wpste_status' => array(
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'wpste_register_license_callback_endpoint' );

/**
 * Handles the browser redirect from the license server after a successful payment.
 * Validates and saves the license, then redirects to wp-admin with a notice.
 *
 * @param WP_REST_Request $request The REST request object.
 */
function wpste_license_activate_callback( WP_REST_Request $request ): WP_REST_Response {
	$admin_page = admin_url( 'admin.php?page=wpste-upgrade' );

	if ( 'activated' !== $request->get_param( 'wpste_status' ) ) {
		return new WP_REST_Response(
			array(
				'success'   => false,
				'message'   => 'Invalid callback status.',
				'admin_url' => add_query_arg(
					array(
						'wpste_notice' => 'license_error',
						'wpste_error'  => rawurlencode( 'Invalid callback status.' ),
					),
					$admin_page
				),
			),
			400
		);
	}

	$result = wpste_validate_and_save_license( $request->get_param( 'license_key' ) );

	if ( $result['success'] ) {
		return new WP_REST_Response(
			array(
				'success'   => true,
				'tier'      => $result['tier'],
				'admin_url' => add_query_arg(
					array(
						'wpste_notice' => 'license_activated',
						'wpste_tier'   => $result['tier'],
					),
					$admin_page
				),
			),
			200
		);
	}

	return new WP_REST_Response(
		array(
			'success'   => false,
			'message'   => $result['message'],
			'admin_url' => add_query_arg(
				array(
					'wpste_notice' => 'license_error',
					'wpste_error'  => rawurlencode( $result['message'] ),
				),
				$admin_page
			),
		),
		422
	);
}

/**
 * Displays admin notices for license activation results.
 * Hooked into admin_notices.
 */
function wpste_license_admin_notices(): void {
	if ( ! isset( $_GET['wpste_notice'] ) ) {
		return;
	}

	$notice = sanitize_key( $_GET['wpste_notice'] );

	if ( 'license_activated' === $notice ) {
		$tier = isset( $_GET['wpste_tier'] ) ? ucfirst( sanitize_text_field( wp_unslash( $_GET['wpste_tier'] ) ) ) : '';
		printf(
			'<div class="notice notice-success is-dismissible"><p><strong>%s</strong> %s</p></div>',
			esc_html__( 'License activated!', 'smart-translation-engine' ),
			/* translators: %s: tier name, e.g. "Pro" */
			esc_html( sprintf( __( 'Your plan has been upgraded to %s.', 'smart-translation-engine' ), $tier ) )
		);
	}

	if ( 'license_error' === $notice ) {
		$error = isset( $_GET['wpste_error'] ) ? rawurldecode( sanitize_text_field( wp_unslash( $_GET['wpste_error'] ) ) ) : __( 'Unknown error.', 'smart-translation-engine' );
		printf(
			'<div class="notice notice-error is-dismissible"><p><strong>%s</strong> %s</p></div>',
			esc_html__( 'License activation failed:', 'smart-translation-engine' ),
			esc_html( $error )
		);
	}
}
add_action( 'admin_notices', 'wpste_license_admin_notices' );

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
		$license_server_url . '/api/v1/checkout/register',
		array(
			'headers' => array(
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
			),
			'body'    => wp_json_encode(
				array(
					'uuid'        => $uuid,
					'site_url'    => $site_url,
					'admin_email' => $admin_email,
					'site_name'   => $site_name,
					'tier'        => $tier,
					'period'      => $period,
					'price'       => $price,
					'return_url'  => rest_url( 'wpste/v1/license/activate-callback' ),
				)
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
		$http_code    = wp_remote_retrieve_response_code( $registration_response );
		$error_detail = sprintf(
			'HTTP %s | Body: %s',
			$http_code,
			substr( $reg_body, 0, 500 )
		);
		error_log( 'WPSTE Checkout Error: ' . $error_detail );
		wp_send_json_error( array(
			'message' => 'License server error: ' . $error_detail,
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
 * AJAX handler for manually activating a license key.
 * Used as a fallback if the automatic redirect activation fails.
 */
function wpste_activate_license_handler(): void {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpste_activate_license' ) ) {
		wp_send_json_error( array( 'message' => 'Security check failed.' ) );
	}

	if ( ! isset( $_POST['license_key'] ) ) {
		wp_send_json_error( array( 'message' => 'License key required.' ) );
	}

	$license_key = sanitize_text_field( wp_unslash( $_POST['license_key'] ) );
	$result      = wpste_validate_and_save_license( $license_key );

	if ( ! $result['success'] ) {
		wp_send_json_error( array( 'message' => $result['message'] ) );
	}

	wp_send_json_success(
		array(
			'tier'    => $result['tier'],
			'message' => 'License activated successfully.',
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
