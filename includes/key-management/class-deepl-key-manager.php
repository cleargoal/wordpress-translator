<?php
/**
 * DeepL Key Manager
 *
 * Manages DeepL API keys with smart rotation based on quota availability.
 *
 * @package WP_Smart_Translation_Engine
 */

namespace WPSTE\KeyManagement;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * DeepL Key Manager class
 */
class DeepL_Key_Manager implements Key_Manager_Interface {

	/**
	 * Provider name
	 *
	 * @var string
	 */
	protected $provider = 'deepl';

	/**
	 * Database instance
	 *
	 * @var \WPSTE\Database\Database
	 */
	protected $database;

	/**
	 * Cache TTL (5 minutes)
	 *
	 * @var int
	 */
	protected $cache_ttl = 300;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->database = new \WPSTE\Database\Database();
	}

	/**
	 * Get next available API key using rotation strategy
	 *
	 * @return array|null
	 */
	public function get_next_key(): ?array {
		$keys = $this->get_all_keys( true );

		if ( empty( $keys ) ) {
			return null;
		}

		// Sort by remaining quota (most available first)
		usort(
			$keys,
			function ( $a, $b ) {
				$remaining_a = ( $a['quota_limit'] ?? 500000 ) - ( $a['characters_used'] ?? 0 );
				$remaining_b = ( $b['quota_limit'] ?? 500000 ) - ( $b['characters_used'] ?? 0 );
				return $remaining_b - $remaining_a; // Descending
			}
		);

		return array(
			'id' => $keys[0]['id'],
			'api_key' => $keys[0]['api_key'],
		);
	}

	/**
	 * Add new API key
	 *
	 * @param string   $api_key API key
	 * @param string   $label Optional label
	 * @param int|null $quota_limit Optional quota limit
	 * @return int|false
	 */
	public function add_key( string $api_key, string $label = '', ?int $quota_limit = null ) {
		$data = array(
			'provider' => $this->provider,
			'api_key' => $api_key,
			'label' => $label ?: 'DeepL Key',
			'quota_limit' => $quota_limit ?: 500000, // Default 500K for free tier
			'is_active' => 1,
			'created_at' => current_time( 'mysql' ),
		);

		$result = $this->database->insert( 'api_keys', $data );

		if ( $result ) {
			// Clear cache
			delete_transient( 'wpste_api_keys_deepl' );
		}

		return $result;
	}

	/**
	 * Remove API key
	 *
	 * @param int $key_id Key ID
	 * @return bool
	 */
	public function remove_key( int $key_id ): bool {
		$result = $this->database->delete(
			'api_keys',
			array(
				'id' => $key_id,
				'provider' => $this->provider,
			)
		);

		if ( $result ) {
			delete_transient( 'wpste_api_keys_deepl' );
		}

		return (bool) $result;
	}

	/**
	 * Get all keys for this provider
	 *
	 * @param bool $active_only Only get active keys
	 * @return array
	 */
	public function get_all_keys( bool $active_only = true ): array {
		$cache_key = 'wpste_api_keys_deepl';
		$keys = get_transient( $cache_key );

		if ( $keys === false ) {
			$where = array( 'provider' => $this->provider );
			if ( $active_only ) {
				$where['is_active'] = 1;
			}

			$keys = $this->database->get_results( 'api_keys', $where, ARRAY_A );

			set_transient( $cache_key, $keys, $this->cache_ttl );
		}

		return $keys;
	}

	/**
	 * Update key usage statistics
	 *
	 * @param int $key_id Key ID
	 * @param int $characters Number of characters used
	 * @return bool
	 */
	public function update_usage( int $key_id, int $characters ): bool {
		global $wpdb;

		$table = $this->database->get_table_name( 'api_keys' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Updating DeepL key usage in custom table
		$result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table}
            SET usage_count = usage_count + 1,
                characters_used = characters_used + %d,
                updated_at = NOW()
            WHERE id = %d AND provider = %s",
				$characters,
				$key_id,
				$this->provider
			)
		);

		if ( $result ) {
			delete_transient( 'wpste_api_keys_deepl' );
		}

		return (bool) $result;
	}

	/**
	 * Check if key has available quota
	 *
	 * @param int $key_id Key ID
	 * @return bool
	 */
	public function has_quota( int $key_id ): bool {
		$key = $this->database->get_row( 'api_keys', array( 'id' => $key_id ), ARRAY_A );

		if ( ! $key || ! $key['quota_limit'] ) {
			return true; // Assume unlimited if no limit set
		}

		return $key['characters_used'] < $key['quota_limit'];
	}

	/**
	 * Activate/deactivate key
	 *
	 * @param int  $key_id Key ID
	 * @param bool $active Active status
	 * @return bool
	 */
	public function set_active( int $key_id, bool $active ): bool {
		$result = $this->database->update(
			'api_keys',
			array(
				'is_active' => $active ? 1 : 0,
			),
			array(
				'id' => $key_id,
				'provider' => $this->provider,
			)
		);

		if ( $result ) {
			delete_transient( 'wpste_api_keys_deepl' );
		}

		return (bool) $result;
	}

	/**
	 * Get provider name
	 *
	 * @return string
	 */
	public function get_provider_name(): string {
		return $this->provider;
	}
}
