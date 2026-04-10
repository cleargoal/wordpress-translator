<?php
/**
 * License Validator
 *
 * Validates a license key against the external license server.
 *
 * @package WP_Smart_Translation_Engine
 */

namespace WPSTE\Licensing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * License Validator class
 */
class License_Validator {

	/**
	 * License server base URL
	 *
	 * @var string
	 */
	private $server_url;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->server_url = defined( 'WPSTE_LICENSE_SERVER_URL' )
			? rtrim( WPSTE_LICENSE_SERVER_URL, '/' )
			: 'https://license.yoursite.com';
	}

	/**
	 * Validate a license key with the license server.
	 *
	 * @param string $license_key License key to validate.
	 * @return array{valid: bool, tier: string, expires_at?: string, features?: array, message?: string}
	 */
	public function validate( string $license_key ): array {
		$uuid = get_option( 'wpste_site_uuid' );

		if ( ! $uuid ) {
			return array(
				'valid'   => false,
				'tier'    => 'free',
				'message' => 'Site not registered.',
			);
		}

		$response = wp_remote_post(
			$this->server_url . '/api/v1/license/validate',
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
					'Accept'       => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'license_key' => $license_key,
						'uuid'        => $uuid,
						'site_url'    => get_site_url(),
					)
				),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'valid'   => false,
				'tier'    => 'free',
				'message' => 'Could not reach license server: ' . $response->get_error_message(),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code || empty( $data['valid'] ) ) {
			return array(
				'valid'   => false,
				'tier'    => 'free',
				'message' => $data['message'] ?? sprintf( 'Server returned HTTP %d.', $code ),
			);
		}

		return array(
			'valid'      => true,
			'tier'       => sanitize_text_field( $data['tier'] ?? 'free' ),
			'expires_at' => sanitize_text_field( $data['expires_at'] ?? '' ),
			'features'   => is_array( $data['features'] ?? null ) ? $data['features'] : array(),
		);
	}
}
