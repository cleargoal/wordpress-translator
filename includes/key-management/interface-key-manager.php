<?php
/**
 * Key Manager Interface
 *
 * Defines the contract for API key management.
 *
 * @package WP_Smart_Translation_Engine
 */

namespace WPSTE\KeyManagement;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Key Manager Interface
 */
interface Key_Manager_Interface {

	/**
	 * Get next available API key using rotation strategy
	 *
	 * @return array|null Array with 'id', 'api_key' keys or null if no keys available
	 */
	public function get_next_key(): ?array;

	/**
	 * Add new API key
	 *
	 * @param string   $api_key API key
	 * @param string   $label Optional label
	 * @param int|null $quota_limit Optional quota limit
	 * @return int|false Key ID or false on failure
	 */
	public function add_key( string $api_key, string $label = '', ?int $quota_limit = null );

	/**
	 * Remove API key
	 *
	 * @param int $key_id Key ID
	 * @return bool Success status
	 */
	public function remove_key( int $key_id ): bool;

	/**
	 * Get all keys for this provider
	 *
	 * @param bool $active_only Only get active keys
	 * @return array Array of key records
	 */
	public function get_all_keys( bool $active_only = true ): array;

	/**
	 * Update key usage statistics
	 *
	 * @param int $key_id Key ID
	 * @param int $characters Number of characters used
	 * @return bool Success status
	 */
	public function update_usage( int $key_id, int $characters ): bool;

	/**
	 * Check if key has available quota
	 *
	 * @param int $key_id Key ID
	 * @return bool True if quota available
	 */
	public function has_quota( int $key_id ): bool;

	/**
	 * Activate/deactivate key
	 *
	 * @param int  $key_id Key ID
	 * @param bool $active Active status
	 * @return bool Success status
	 */
	public function set_active( int $key_id, bool $active ): bool;

	/**
	 * Get provider name
	 *
	 * @return string Provider name
	 */
	public function get_provider_name(): string;
}
