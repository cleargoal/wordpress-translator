<?php
/**
 * AWS Key Manager
 *
 * Manages AWS Translate credentials (Access Key ID, Secret Access Key, Region).
 *
 * @package WP_Smart_Translation_Engine
 */

namespace WPSTE\KeyManagement;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AWS Key Manager class
 */
class AWS_Key_Manager implements Key_Manager_Interface {

	/**
	 * Provider name
	 *
	 * @var string
	 */
	protected $provider = 'aws';

	/**
	 * Database instance
	 *
	 * @var \WPSTE\Database\Database
	 */
	protected $database;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->database = new \WPSTE\Database\Database();
	}

	/**
	 * Get next available key
	 *
	 * @return array|null Key data with 'id', 'access_key_id', 'secret_access_key', 'region'
	 */
	public function get_next_key(): ?array {
		$keys = $this->get_all_keys( true );
		if ( empty( $keys ) ) {
			return null;
		}

		$key = $keys[0];

		// Parse API key JSON (stored as JSON: {"access_key_id": "...", "secret_access_key": "...", "region": "..."})
		$credentials = json_decode( wpste_decrypt_api_key( $key['api_key'] ), true );

		if ( ! $credentials || ! isset( $credentials['access_key_id'], $credentials['secret_access_key'] ) ) {
			return null;
		}

		return array(
			'id'                 => $key['id'],
			'access_key_id'      => $credentials['access_key_id'],
			'secret_access_key'  => $credentials['secret_access_key'],
			'region'             => $credentials['region'] ?? 'us-east-1',
		);
	}

	/**
	 * Add AWS credentials
	 *
	 * @param string $api_key JSON string with AWS credentials
	 * @param string $label Key label
	 * @param int    $quota_limit Quota limit (unused for AWS)
	 * @return int|false Key ID or false on failure
	 */
	public function add_key( string $api_key, string $label = '', ?int $quota_limit = null ) {
		// Validate JSON format
		$credentials = json_decode( $api_key, true );
		if ( ! $credentials || ! isset( $credentials['access_key_id'], $credentials['secret_access_key'] ) ) {
			return false;
		}

		// Encrypt credentials
		$encrypted = wpste_encrypt_api_key( $api_key );

		return $this->database->insert(
			'api_keys',
			array(
				'provider'  => $this->provider,
				'api_key'   => $encrypted,
				'label'     => $label ?: 'AWS Translate',
				'region'    => $credentials['region'] ?? 'us-east-1',
				'is_active' => 1,
			)
		);
	}

	public function remove_key( int $key_id ): bool {
		return (bool) $this->database->delete(
			'api_keys',
			array(
				'id' => $key_id,
				'provider' => $this->provider,
			)
		);
	}

	public function get_all_keys( bool $active_only = true ): array {
		$where = array( 'provider' => $this->provider );
		if ( $active_only ) {
			$where['is_active'] = 1;
		}
		return $this->database->get_results( 'api_keys', $where, ARRAY_A );
	}

	public function update_usage( int $key_id, int $characters ): bool {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Updating AWS key usage in custom table
		return (bool) $this->database->query(
			$this->database->wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safely constructed via helper method
				"UPDATE {$this->database->get_table_name('api_keys')}
            SET usage_count = usage_count + 1, characters_used = characters_used + %d
            WHERE id = %d",
				$characters,
				$key_id
			)
		);
	}

	public function has_quota( int $key_id ): bool {
		return true; // AWS has different quota model
	}

	public function set_active( int $key_id, bool $active ): bool {
		return (bool) $this->database->update( 'api_keys', array( 'is_active' => $active ? 1 : 0 ), array( 'id' => $key_id ) );
	}

	public function get_provider_name(): string {
		return $this->provider;
	}
}
