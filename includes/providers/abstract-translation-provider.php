<?php
/**
 * Abstract Translation Provider
 *
 * Base class for all translation providers with shared functionality.
 *
 * @package WP_Smart_Translation_Engine
 */

namespace WPSTE\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract Translation Provider
 */
abstract class Abstract_Translation_Provider implements Translation_Provider_Interface {

	/**
	 * Provider name
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Key manager instance
	 *
	 * @var object
	 */
	protected $key_manager;

	/**
	 * Database instance
	 *
	 * @var \WPSTE\Database\Database
	 */
	protected $database;

	/**
	 * Constructor
	 *
	 * @param object $key_manager Key manager instance
	 */
	public function __construct( $key_manager ) {
		$this->key_manager = $key_manager;
		$this->database = new \WPSTE\Database\Database();
	}

	/**
	 * Get provider name
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Make HTTP request
	 *
	 * @param string $url URL to request
	 * @param array  $args Request arguments
	 * @return array Response with 'body', 'code' keys or 'error'
	 */
	protected function make_request( string $url, array $args = array() ): array {
		$defaults = array(
			'timeout' => 30,
			'headers' => array(
				'Content-Type' => 'application/json',
			),
		);

		$args = wp_parse_args( $args, $defaults );

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return array(
				'error' => $response->get_error_message(),
				'code' => 'request_failed',
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		return array(
			'code' => $code,
			'body' => $body,
			'headers' => wp_remote_retrieve_headers( $response ),
		);
	}

	/**
	 * Log translation usage
	 *
	 * @param int $api_key_id API key ID
	 * @param int $characters Number of characters translated
	 * @return void
	 */
	protected function log_usage( int $api_key_id, int $characters ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Updating API key usage statistics in custom table
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$this->database->get_table_name('api_keys')}
            SET usage_count = usage_count + 1,
                characters_used = characters_used + %d,
                updated_at = NOW()
            WHERE id = %d",
				$characters,
				$api_key_id
			)
		);
	}

	/**
	 * Check if quota exceeded
	 *
	 * @param int $api_key_id API key ID
	 * @return bool True if quota exceeded
	 */
	protected function is_quota_exceeded( int $api_key_id ): bool {
		$key = $this->database->get_row( 'api_keys', array( 'id' => $api_key_id ), ARRAY_A );

		if ( ! $key || ! $key['quota_limit'] ) {
			return false;
		}

		return $key['characters_used'] >= $key['quota_limit'];
	}

	/**
	 * Get usage statistics
	 *
	 * @return array
	 */
	public function get_usage_stats(): array {
		$keys = $this->database->get_results(
			'api_keys',
			array(
				'provider' => $this->name,
				'is_active' => 1,
			),
			ARRAY_A
		);

		$total_characters = 0;
		$total_requests = 0;

		foreach ( $keys as $key ) {
			$total_characters += (int) $key['characters_used'];
			$total_requests += (int) $key['usage_count'];
		}

		return array(
			'provider' => $this->name,
			'total_characters' => $total_characters,
			'total_requests' => $total_requests,
			'active_keys' => count( $keys ),
		);
	}

	/**
	 * Normalize language code
	 *
	 * @param string $lang Language code
	 * @return string Normalized code
	 */
	protected function normalize_language_code( string $lang ): string {
		$lang = strtolower( trim( $lang ) );

		// Handle special cases
		$map = array(
			'ua' => 'uk',  // Ukrainian
			'en-us' => 'en',
			'en-gb' => 'en',
			'pt-br' => 'pt',
			'pt-pt' => 'pt',
			'zh-cn' => 'zh',
			'zh-tw' => 'zh',
		);

		return $map[ $lang ] ?? $lang;
	}

	/**
	 * Count characters in text
	 *
	 * @param string $text Text to count
	 * @return int Character count
	 */
	protected function count_characters( string $text ): int {
		return mb_strlen( wp_strip_all_tags( $text ), 'UTF-8' );
	}
}
