<?php
/**
 * Feature Downloader
 *
 * Downloads and installs premium feature packages from the license server.
 *
 * @package WP_Smart_Translation_Engine
 */

namespace WPSTE\Licensing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Feature Downloader class
 */
class Feature_Downloader {

	/**
	 * Database instance
	 *
	 * @var \WPSTE\Database\Database
	 */
	private $database;

	/**
	 * License server base URL
	 *
	 * @var string
	 */
	private $server_url;

	/**
	 * Directory where feature packages are extracted
	 *
	 * @var string
	 */
	private $features_dir;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->database     = new \WPSTE\Database\Database();
		$this->server_url   = defined( 'WPSTE_LICENSE_SERVER_URL' )
			? rtrim( WPSTE_LICENSE_SERVER_URL, '/' )
			: 'https://license.yoursite.com';
		$this->features_dir = WP_CONTENT_DIR . '/uploads/wpste-premium/features';
	}

	// -----------------------------------------------------------------------
	// Public API
	// -----------------------------------------------------------------------

	/**
	 * Download and install a single feature package.
	 *
	 * @param string $feature_slug Feature slug, e.g. 'translation-memory'.
	 * @return array{success: bool, message?: string}
	 */
	public function download_feature( string $feature_slug ): array {
		$license_key = $this->get_license_key();
		if ( ! $license_key ) {
			return array( 'success' => false, 'message' => 'No active license found.' );
		}

		// Ensure the features root directory exists.
		if ( ! wp_mkdir_p( $this->features_dir ) ) {
			return array( 'success' => false, 'message' => 'Could not create features directory.' );
		}

		// Download the ZIP to a temp file.
		$download = $this->download_zip( $feature_slug, $license_key );
		if ( isset( $download['error'] ) ) {
			return array( 'success' => false, 'message' => $download['error'] );
		}

		$zip_path = $download['zip_path'];
		$checksum = $download['checksum'];
		$version  = $download['version'];

		// Verify integrity before extracting.
		if ( $checksum && ! $this->verify_checksum( $zip_path, $checksum ) ) {
			wp_delete_file( $zip_path );
			return array( 'success' => false, 'message' => 'Checksum verification failed. The package may be corrupt.' );
		}

		// Extract to the feature-specific subdirectory.
		$extract_path = $this->features_dir . '/' . sanitize_file_name( $feature_slug );
		$extracted    = $this->extract_zip( $zip_path, $extract_path );

		wp_delete_file( $zip_path );

		if ( ! $extracted ) {
			return array( 'success' => false, 'message' => 'Failed to extract feature package.' );
		}

		$this->record_download( $feature_slug, $version, $extract_path, $checksum );

		return array( 'success' => true );
	}

	/**
	 * Download all features available for the current license tier.
	 *
	 * @return array Results keyed by feature slug.
	 */
	public function download_all_features(): array {
		$features = $this->get_available_features();
		$results  = array();

		foreach ( $features as $feature ) {
			$slug             = $feature['slug'] ?? '';
			$results[ $slug ] = $slug ? $this->download_feature( $slug ) : array( 'success' => false );
		}

		return $results;
	}

	/**
	 * Fetch the list of features available for the current license from the server.
	 *
	 * @return array Array of feature descriptors, each with 'slug', 'name', 'version'.
	 */
	public function get_available_features(): array {
		$license = get_option( 'wpste_license', array() );
		$tier    = $license['tier'] ?? 'free';

		if ( 'free' === $tier ) {
			return array();
		}

		$response = wp_remote_get(
			add_query_arg( 'tier', rawurlencode( $tier ), $this->server_url . '/api/v1/features' ),
			array(
				'headers' => array( 'Accept' => 'application/json' ),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return array();
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		return isset( $data['features'] ) && is_array( $data['features'] ) ? $data['features'] : array();
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Stream a feature ZIP from the license server into a temp file.
	 *
	 * @param string $slug        Feature slug.
	 * @param string $license_key Active license key (used as Bearer token).
	 * @return array{zip_path: string, checksum: string, version: string}|array{error: string}
	 */
	private function download_zip( string $slug, string $license_key ): array {
		$temp_file = get_temp_dir() . 'wpste-' . sanitize_file_name( $slug ) . '-' . uniqid() . '.zip';

		$response = wp_remote_get(
			$this->server_url . '/api/v1/features/' . rawurlencode( $slug ) . '/download',
			array(
				'headers'  => array(
					'Authorization' => 'Bearer ' . $license_key,
					'Accept'        => 'application/zip',
				),
				'timeout'  => 120,
				'stream'   => true,
				'filename' => $temp_file,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array( 'error' => 'Could not reach license server: ' . $response->get_error_message() );
		}

		$code = wp_remote_retrieve_response_code( $response );

		if ( 401 === $code ) {
			return array( 'error' => 'Invalid or expired license key.' );
		}

		if ( 403 === $code ) {
			return array( 'error' => 'Your license tier does not include this feature.' );
		}

		if ( 200 !== $code ) {
			return array( 'error' => sprintf( 'Download failed (HTTP %d).', $code ) );
		}

		return array(
			'zip_path' => $temp_file,
			'checksum' => wp_remote_retrieve_header( $response, 'x-checksum' ) ?: '',
			'version'  => wp_remote_retrieve_header( $response, 'x-feature-version' ) ?: '1.0.0',
		);
	}

	/**
	 * Verify a file's SHA-256 checksum.
	 *
	 * @param string $file_path       Path to the file.
	 * @param string $expected Header value, e.g. "sha256:abc123".
	 * @return bool
	 */
	private function verify_checksum( string $file_path, string $expected ): bool {
		$expected_hash = preg_replace( '/^sha256:/i', '', $expected );
		$actual_hash   = hash_file( 'sha256', $file_path );

		return hash_equals( $expected_hash, $actual_hash );
	}

	/**
	 * Extract a ZIP archive to a destination directory.
	 *
	 * @param string $zip_path    Path to the ZIP file.
	 * @param string $destination Destination directory (will be created if needed).
	 * @return bool
	 */
	private function extract_zip( string $zip_path, string $destination ): bool {
		$zip = new \ZipArchive();

		if ( true !== $zip->open( $zip_path ) ) {
			return false;
		}

		wp_mkdir_p( $destination );
		$zip->extractTo( $destination );
		$zip->close();

		return true;
	}

	/**
	 * Record or update a feature download in the database.
	 *
	 * @param string $slug      Feature slug.
	 * @param string $version   Feature version.
	 * @param string $file_path Extracted directory path.
	 * @param string $checksum  Checksum string from server.
	 */
	private function record_download( string $slug, string $version, string $file_path, string $checksum ): void {
		$data = array(
			'version'       => $version,
			'file_path'     => $file_path,
			'checksum'      => $checksum,
			'status'        => 'downloaded',
			'downloaded_at' => current_time( 'mysql' ),
			'updated_at'    => current_time( 'mysql' ),
		);

		$existing = $this->database->get_row( 'features', array( 'feature_slug' => $slug ) );

		if ( $existing ) {
			$this->database->update( 'features', $data, array( 'feature_slug' => $slug ) );
		} else {
			$this->database->insert(
				'features',
				array_merge(
					$data,
					array(
						'feature_slug' => $slug,
						'created_at'   => current_time( 'mysql' ),
					)
				)
			);
		}
	}

	/**
	 * Get the active license key from storage.
	 *
	 * @return string|null
	 */
	private function get_license_key(): ?string {
		$license = get_option( 'wpste_license', array() );
		$key     = $license['key'] ?? '';

		return ! empty( $key ) && 'active' === ( $license['status'] ?? '' ) ? $key : null;
	}
}
