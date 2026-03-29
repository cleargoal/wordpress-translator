<?php
/**
 * Database Installer
 *
 * Creates and manages database tables for the plugin.
 *
 * @package WP_Smart_Translation_Engine
 */

namespace WPSTE\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Installer class
 */
class Installer {

	/**
	 * Database version
	 */
	const DB_VERSION = '1.0.0';

	/**
	 * Create all plugin tables
	 */
	public function create_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// API Keys table
		$table_name = $wpdb->prefix . 'wpste_api_keys';
		$sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            provider varchar(50) NOT NULL,
            api_key varchar(255) NOT NULL,
            label varchar(100) DEFAULT NULL,
            region varchar(50) DEFAULT NULL,
            usage_count bigint(20) UNSIGNED DEFAULT 0,
            characters_used bigint(20) UNSIGNED DEFAULT 0,
            quota_limit bigint(20) UNSIGNED DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            last_used_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY provider (provider),
            KEY is_active (is_active)
        ) $charset_collate;";
		dbDelta( $sql );

		// Translations table
		$table_name = $wpdb->prefix . 'wpste_translations';
		$sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id bigint(20) UNSIGNED NOT NULL,
            source_post_id bigint(20) UNSIGNED DEFAULT NULL,
            lang_code varchar(10) NOT NULL,
            translation_group varchar(36) NOT NULL,
            provider_used varchar(50) DEFAULT NULL,
            api_key_id bigint(20) UNSIGNED DEFAULT NULL,
            status varchar(20) DEFAULT 'draft',
            translated_at datetime DEFAULT NULL,
            characters_translated int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY source_post_id (source_post_id),
            KEY lang_code (lang_code),
            KEY translation_group (translation_group),
            KEY status (status)
        ) $charset_collate;";
		dbDelta( $sql );

		// Licenses table
		$table_name = $wpdb->prefix . 'wpste_licenses';
		$sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            license_key varchar(255) NOT NULL,
            tier varchar(50) DEFAULT 'free',
            status varchar(20) DEFAULT 'inactive',
            site_url varchar(255) DEFAULT NULL,
            activation_date datetime DEFAULT NULL,
            expiry_date datetime DEFAULT NULL,
            last_check datetime DEFAULT NULL,
            next_check datetime DEFAULT NULL,
            license_data text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY license_key (license_key),
            KEY tier (tier),
            KEY status (status)
        ) $charset_collate;";
		dbDelta( $sql );

		// Features table
		$table_name = $wpdb->prefix . 'wpste_features';
		$sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            feature_slug varchar(100) NOT NULL,
            tier varchar(50) NOT NULL,
            feature_name varchar(255) NOT NULL,
            version varchar(20) DEFAULT NULL,
            download_url varchar(500) DEFAULT NULL,
            checksum varchar(64) DEFAULT NULL,
            file_path varchar(500) DEFAULT NULL,
            status varchar(20) DEFAULT 'inactive',
            downloaded_at datetime DEFAULT NULL,
            activated_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY feature_slug (feature_slug),
            KEY tier (tier),
            KEY status (status)
        ) $charset_collate;";
		dbDelta( $sql );

		// Quota Alerts table
		$table_name = $wpdb->prefix . 'wpste_quota_alerts';
		$sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            provider varchar(50) NOT NULL,
            api_key_id bigint(20) UNSIGNED NOT NULL,
            alert_type varchar(20) NOT NULL,
            quota_percentage int(11) NOT NULL,
            notified tinyint(1) DEFAULT 0,
            notification_sent_at datetime DEFAULT NULL,
            resolved tinyint(1) DEFAULT 0,
            resolved_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY api_key_id (api_key_id),
            KEY alert_type (alert_type),
            KEY notified (notified),
            KEY resolved (resolved)
        ) $charset_collate;";
		dbDelta( $sql );

		// Term Translations table (for categories, tags, custom taxonomies)
		$table_name = $wpdb->prefix . 'wpste_term_translations';
		$sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            term_id bigint(20) UNSIGNED NOT NULL,
            lang_code varchar(10) NOT NULL,
            translated_name varchar(255) NOT NULL,
            translated_slug varchar(255) NOT NULL,
            translated_description text DEFAULT NULL,
            translation_group varchar(36) DEFAULT NULL,
            provider_used varchar(50) DEFAULT NULL,
            api_key_id bigint(20) UNSIGNED DEFAULT NULL,
            characters_translated int(11) DEFAULT 0,
            translated_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY term_lang (term_id, lang_code),
            KEY lang_code (lang_code),
            KEY translation_group (translation_group),
            KEY provider_used (provider_used)
        ) $charset_collate;";
		dbDelta( $sql );

		// Update database version
		update_option( 'wpste_db_version', self::DB_VERSION );
	}

	/**
	 * Check if tables need updating
	 */
	public function maybe_update_tables(): void {
		$current_version = get_option( 'wpste_db_version', '0.0.0' );

		if ( version_compare( $current_version, self::DB_VERSION, '<' ) ) {
			$this->create_tables();
		}
	}
}
