<?php
/**
 * Plugin Name:       WP Smart Translation Engine
 * Plugin URI:        https://github.com/cleargoal/wordpress-translator
 * Description:       Multi-provider AI translation for WordPress posts and pages. Supports DeepL, Azure Translator, and AWS Translate with smart key rotation and quota management.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Volodymyr Yefremov
 * Author URI:        https://github.com/cleargoal
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-smart-translation-engine
 * Domain Path:       /languages
 *
 * @package           WP_Smart_Translation_Engine
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
 * Begin execution of the plugin.
 *
 * Load dependencies and initialize the plugin.
 */
function wpste_run(): void {
	// Load composer autoloader if available (for AWS SDK)
	if ( file_exists( WPSTE_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
		require_once WPSTE_PLUGIN_DIR . 'vendor/autoload.php';
	}

	// Load plugin text domain for translations
	load_plugin_textdomain(
		'wp-smart-translation-engine',
		false,
		dirname( WPSTE_PLUGIN_BASENAME ) . '/languages/'
	);

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

	// Register language switcher widget
	require_once WPSTE_PLUGIN_DIR . 'public/class-language-switcher-widget.php';
	add_action(
		'widgets_init',
		function () {
			register_widget( 'WPSTE\Frontend\Language_Switcher_Widget' );
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
