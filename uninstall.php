<?php
/**
 * Uninstall Script
 *
 * Fired when the plugin is uninstalled.
 *
 * @package WP_Smart_Translation_Engine
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Delete plugin tables
$wpste_tables = array(
	$wpdb->prefix . 'wpste_api_keys',
	$wpdb->prefix . 'wpste_translations',
	$wpdb->prefix . 'wpste_licenses',
	$wpdb->prefix . 'wpste_features',
	$wpdb->prefix . 'wpste_quota_alerts',
);

foreach ( $wpste_tables as $wpste_table ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Dropping custom tables on uninstall
	$wpdb->query( "DROP TABLE IF EXISTS {$wpste_table}" );
}

// Delete plugin options
delete_option( 'wpste_settings' );
delete_option( 'wpste_license' );
delete_option( 'wpste_db_version' );

// Delete transients
delete_transient( 'wpste_api_keys_deepl' );
delete_transient( 'wpste_api_keys_azure' );
delete_transient( 'wpste_api_keys_aws' );

// Delete post meta
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Cleaning up plugin post meta on uninstall
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_wpste_%'" );

// Remove custom capability
$role = get_role( 'administrator' );
if ( $role ) {
	$role->remove_cap( 'manage_translations' );
}
