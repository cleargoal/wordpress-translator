<?php
/**
 * PHPUnit bootstrap — defines constants and stubs so plugin classes
 * can be loaded without a real WordPress installation.
 */

// WordPress core constants.
define( 'ABSPATH', '/tmp/' );
define( 'WPINC', 'wp-includes' );

// Plugin constants.
define( 'WPSTE_VERSION', '1.0.0' );
define( 'WPSTE_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'WPSTE_PLUGIN_URL', 'https://example.com/wp-content/plugins/smart-translation-engine/' );
define( 'WPSTE_PLUGIN_BASENAME', 'smart-translation-engine/smart-translation-engine.php' );

// WordPress auth constants (used by encryption helpers).
define( 'AUTH_KEY', 'test-auth-key' );
define( 'AUTH_SALT', 'test-auth-salt' );
define( 'SECURE_AUTH_KEY', 'test-secure-auth-key' );
define( 'SECURE_AUTH_SALT', 'test-secure-auth-salt' );

// WordPress DB output-type constants.
if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
}
define( 'ARRAY_A', 'ARRAY_A' );
define( 'ARRAY_N', 'ARRAY_N' );

// Composer autoloader (loads plugin classes + Brain\Monkey).
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

/**
 * Minimal $wpdb stub.
 *
 * The Database class grabs the global $wpdb in its constructor.
 * This stub satisfies that dependency so constructors don't crash.
 * Individual tests that need specific DB return values should set
 * expectations on their own mock objects injected via reflection.
 */
global $wpdb;
$wpdb = new class() {
	/** @var string */
	public $prefix = 'wp_';
	/** @var int */
	public $insert_id = 0;

	public function prepare( string $sql, ...$args ): string {
		return $sql;
	}
	public function insert( string $table, array $data, array $format = [] ): int {
		return 1;
	}
	public function update( string $table, array $data, array $where ): int {
		return 1;
	}
	public function delete( string $table, array $where ): int {
		return 1;
	}
	/** @return array|object|null */
	public function get_row( string $sql, string $output = 'OBJECT' ) {
		return null;
	}
	public function get_results( string $sql, string $output = 'OBJECT' ): array {
		return [];
	}
	/** @return string|null */
	public function get_var( string $sql, int $col = 0, int $row = 0 ) {
		return null;
	}
	public function query( string $sql ): bool {
		return false;
	}
};
