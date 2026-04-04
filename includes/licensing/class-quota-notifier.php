<?php
namespace WPSTE\Licensing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Quota_Notifier {

	protected $storage;

	public function __construct( $storage ) {
		$this->storage = $storage;
	}

	public function display_admin_notices() {
		// Display quota warnings in admin
	}

	public function cleanup_old_alerts() {
		// Clean up old quota alerts from database
		global $wpdb;
		$table = $wpdb->prefix . 'wpste_quota_alerts';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Cleaning up old resolved quota alerts from custom table
		$wpdb->query( "DELETE FROM {$table} WHERE resolved = 1 AND resolved_at < DATE_SUB(NOW(), INTERVAL 30 DAY)" );
	}
}
