<?php
/**
 * Azure Key Manager
 *
 * Manages API keys for Azure Translator service.
 *
 * @package WP_Smart_Translation_Engine
 */

namespace WPSTE\KeyManagement;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Azure Key Manager class
 */
class Azure_Key_Manager implements Key_Manager_Interface {

	/**
	 * Provider name
	 *
	 * @var string
	 */
	protected $provider = 'azure';

	/**
	 * Database instance
	 *
	 * @var \WPSTE\Database\Database
	 */
	protected $database;

	/**
	 * Cache key
	 *
	 * @var string
	 */
	protected $cache_key = 'wpste_api_keys_azure';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->database = new \WPSTE\Database\Database();
	}

	/**
	 * Get next available API key using rotation strategy
	 *
	 * @return array|null Array with 'id', 'api_key', 'region' keys or null if no keys available
	 */
	public function get_next_key(): ?array {
		$keys = $this->get_all_keys( true );

		if ( empty( $keys ) ) {
			return null;
		}

		// Filter keys with available quota
		$available_keys = array_filter(
			$keys,
			function ( $key ) {
				return $this->has_quota( $key['id'] );
			}
		);

		if ( empty( $available_keys ) ) {
			return null;
		}

		// Sort by usage (least used first)
		usort(
			$available_keys,
			function ( $a, $b ) {
				return $a['usage_count'] <=> $b['usage_count'];
			}
		);

		$selected = $available_keys[0];

		// Decrypt API key
		$selected['api_key'] = wpste_decrypt_api_key( $selected['api_key'] );

		return $selected;
	}

	/**
	 * Add new API key
	 *
	 * @param string   $api_key API key
	 * @param string   $label Optional label
	 * @param int|null $quota_limit Optional quota limit
	 * @param string   $region Azure region (e.g., 'eastus', 'westeurope')
	 * @return int|false Key ID or false on failure
	 */
	public function add_key( string $api_key, string $label = '', ?int $quota_limit = null, string $region = 'eastus' ) {
		// Encrypt API key
		$encrypted = wpste_encrypt_api_key( $api_key );

		$data = array(
			'provider' => $this->provider,
			'api_key' => $encrypted,
			'label' => $label,
			'quota_limit' => $quota_limit,
			'region' => $region,
			'is_active' => 1,
		);

		$result = $this->database->insert( 'api_keys', $data );

		if ( $result ) {
			// Clear cache
			delete_transient( $this->cache_key );
			return $this->database->wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Remove API key
	 *
	 * @param int $key_id Key ID
	 * @return bool Success status
	 */
	public function remove_key( int $key_id ): bool {
		$result = $this->database->delete( 'api_keys', array( 'id' => $key_id ) );

		if ( $result ) {
			delete_transient( $this->cache_key );
		}

		return (bool) $result;
	}

	/**
	 * Get all keys for this provider
	 *
	 * @param bool $active_only Only get active keys
	 * @return array Array of key records
	 */
	public function get_all_keys( bool $active_only = true ): array {
		// Try cache first
		$cached = get_transient( $this->cache_key );
		if ( false !== $cached && $active_only ) {
			return $cached;
		}

		$where = array( 'provider' => $this->provider );

		if ( $active_only ) {
			$where['is_active'] = 1;
		}

		$keys = $this->database->get_results( 'api_keys', $where, ARRAY_A );

		// Cache for 5 minutes
		if ( $active_only ) {
			set_transient( $this->cache_key, $keys, 300 );
		}

		return $keys;
	}

	/**
	 * Update key usage statistics
	 *
	 * @param int $key_id Key ID
	 * @param int $characters Number of characters used
	 * @return bool Success status
	 */
	public function update_usage( int $key_id, int $characters ): bool {
		$result = $this->database->query(
			$this->database->wpdb->prepare(
				"UPDATE {$this->database->get_table_name('api_keys')}
                SET usage_count = usage_count + 1,
                    characters_used = characters_used + %d,
                    last_used_at = NOW(),
                    updated_at = NOW()
                WHERE id = %d",
				$characters,
				$key_id
			)
		);

		if ( $result ) {
			delete_transient( $this->cache_key );
		}

		return (bool) $result;
	}

	/**
	 * Check if key has available quota
	 *
	 * @param int $key_id Key ID
	 * @return bool True if quota available
	 */
	public function has_quota( int $key_id ): bool {
		$key = $this->database->get_row( 'api_keys', array( 'id' => $key_id ), ARRAY_A );

		if ( ! $key ) {
			return false;
		}

		// If no quota limit set, always available
		if ( ! $key['quota_limit'] ) {
			return true;
		}

		return $key['characters_used'] < $key['quota_limit'];
	}

	/**
	 * Activate/deactivate key
	 *
	 * @param int  $key_id Key ID
	 * @param bool $active Active status
	 * @return bool Success status
	 */
	public function set_active( int $key_id, bool $active ): bool {
		$result = $this->database->update(
			'api_keys',
			array( 'is_active' => $active ? 1 : 0 ),
			array( 'id' => $key_id )
		);

		if ( $result !== false ) {
			delete_transient( $this->cache_key );
		}

		return $result !== false;
	}

	/**
	 * Get provider name
	 *
	 * @return string Provider name
	 */
	public function get_provider_name(): string {
		return $this->provider;
	}

	/**
	 * Update key region
	 *
	 * @param int    $key_id Key ID
	 * @param string $region Region code
	 * @return bool Success status
	 */
	public function update_region( int $key_id, string $region ): bool {
		$result = $this->database->update(
			'api_keys',
			array( 'region' => $region ),
			array( 'id' => $key_id )
		);

		if ( $result !== false ) {
			delete_transient( $this->cache_key );
		}

		return $result !== false;
	}
}
