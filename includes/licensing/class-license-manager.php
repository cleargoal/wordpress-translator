<?php
/**
 * License Manager
 *
 * Handles license activation and daily cron re-validation, including
 * the 5-day grace period and feature file cleanup on expiry.
 *
 * @package WP_Smart_Translation_Engine
 */

namespace WPSTE\Licensing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * License Manager class
 */
class License_Manager {

	/**
	 * Grace period in days before feature files are deleted after expiry.
	 */
	const GRACE_PERIOD_DAYS = 5;

	/**
	 * @var License_Storage
	 */
	protected $storage;

	/**
	 * @var License_Validator
	 */
	protected $validator;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->storage   = new License_Storage();
		$this->validator = new License_Validator();
	}

	/**
	 * Activate a license key (manual entry or post-checkout).
	 *
	 * @param string $license_key License key.
	 * @return array{valid: bool, tier?: string, message?: string}
	 */
	public function activate( string $license_key ): array {
		$result = $this->validator->validate( $license_key );

		if ( $result['valid'] ) {
			$this->storage->save_license(
				array(
					'key'                      => $license_key,
					'tier'                     => $result['tier'],
					'status'                   => 'active',
					'expires_at'               => $result['expires_at'] ?? null,
					'activated_at'             => current_time( 'mysql' ),
					'grace_period_started_at'  => null,
				)
			);
		}

		return $result;
	}

	/**
	 * Re-validate the stored license against the server.
	 * Called daily by the wpste_validate_license cron event.
	 *
	 * On success : refreshes tier + expiry, clears any grace period.
	 * On failure : downgrades to free immediately, starts 5-day grace period.
	 *              After grace period: deletes feature files from disk.
	 */
	public function validate(): void {
		$license = $this->storage->get_license();
		$key     = $license['key'] ?? '';

		if ( empty( $key ) ) {
			return;
		}

		$result = $this->validator->validate( $key );

		if ( $result['valid'] ) {
			$this->storage->save_license(
				array(
					'key'                     => $key,
					'tier'                    => $result['tier'],
					'status'                  => 'active',
					'expires_at'              => $result['expires_at'] ?? null,
					'activated_at'            => $license['activated_at'] ?? current_time( 'mysql' ),
					'grace_period_started_at' => null,
				)
			);

			// Check for feature updates while we have a valid server response.
			$this->check_feature_updates( $result['features'] ?? array() );

			// Check if expiry reminder should fire.
			$this->maybe_set_expiry_reminder( $result['expires_at'] ?? '' );

			return;
		}

		// --- License is invalid / expired ---

		$grace_started = $license['grace_period_started_at'] ?? null;

		if ( ! $grace_started ) {
			// First failed check: downgrade tier, start grace period countdown.
			$this->storage->save_license(
				array(
					'key'                     => $key,
					'tier'                    => 'free',
					'status'                  => 'expired',
					'expires_at'              => $license['expires_at'] ?? null,
					'activated_at'            => $license['activated_at'] ?? null,
					'grace_period_started_at' => current_time( 'mysql' ),
				)
			);
		} elseif ( $this->grace_period_elapsed( $grace_started ) ) {
			// Grace period over: delete feature files.
			$this->delete_feature_files();

			$this->storage->save_license(
				array(
					'key'                     => $key,
					'tier'                    => 'free',
					'status'                  => 'expired',
					'expires_at'              => $license['expires_at'] ?? null,
					'activated_at'            => $license['activated_at'] ?? null,
					'grace_period_started_at' => $grace_started,
				)
			);
		}
		// else: still within grace period — tier is already 'free', nothing more to do.
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Check if 5-day grace period has elapsed since the given timestamp.
	 *
	 * @param string $started_at MySQL datetime string.
	 * @return bool
	 */
	private function grace_period_elapsed( string $started_at ): bool {
		$elapsed_days = ( time() - (int) strtotime( $started_at ) ) / DAY_IN_SECONDS;
		return $elapsed_days >= self::GRACE_PERIOD_DAYS;
	}

	/**
	 * Recursively delete the premium features directory.
	 * Settings and DB rows are intentionally left intact.
	 */
	private function delete_feature_files(): void {
		$features_dir = WP_CONTENT_DIR . '/uploads/wpste-premium/features';

		if ( ! is_dir( $features_dir ) ) {
			return;
		}

		$this->delete_directory( $features_dir );
	}

	/**
	 * Recursively delete a directory and all its contents.
	 *
	 * @param string $dir Directory path.
	 */
	private function delete_directory( string $dir ): void {
		$items = @scandir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( ! $items ) {
			return;
		}

		foreach ( $items as $item ) {
			if ( '.' === $item || '..' === $item ) {
				continue;
			}

			$path = $dir . DIRECTORY_SEPARATOR . $item;

			if ( is_dir( $path ) ) {
				$this->delete_directory( $path );
			} else {
				wp_delete_file( $path );
			}
		}

		rmdir( $dir );
	}

	/**
	 * Set a transient to show an admin notice if expiry is within 14 days
	 * and the user has enabled the reminder.
	 *
	 * @param string $expires_at ISO date string from the server.
	 */
	private function maybe_set_expiry_reminder( string $expires_at ): void {
		if ( ! $expires_at || ! get_option( 'wpste_remind_before_expiry', false ) ) {
			return;
		}

		$days_left = ( strtotime( $expires_at ) - time() ) / DAY_IN_SECONDS;

		if ( $days_left <= 14 && $days_left > 0 ) {
			set_transient( 'wpste_expiry_reminder', (int) ceil( $days_left ), DAY_IN_SECONDS );
		} else {
			delete_transient( 'wpste_expiry_reminder' );
		}
	}

	/**
	 * Compare server feature versions against locally installed ones.
	 * Stores a transient so the admin UI can show an "update available" notice.
	 *
	 * @param array $server_features Features array from the validation response.
	 */
	private function check_feature_updates( array $server_features ): void {
		if ( empty( $server_features ) ) {
			return;
		}

		$database = new \WPSTE\Database\Database();
		$updates  = array();

		foreach ( $server_features as $feature ) {
			$slug           = $feature['slug'] ?? '';
			$server_version = $feature['version'] ?? '';

			if ( ! $slug || ! $server_version ) {
				continue;
			}

			$installed = $database->get_row(
				'features',
				array( 'feature_slug' => $slug ),
				ARRAY_A
			);

			$local_version = $installed['version'] ?? null;

			if ( $local_version && version_compare( $server_version, $local_version, '>' ) ) {
				$updates[ $slug ] = array(
					'name'           => $feature['name'] ?? $slug,
					'local_version'  => $local_version,
					'server_version' => $server_version,
				);
			}
		}

		// Cache for 24 h so the admin notice can read it without an extra API call.
		set_transient( 'wpste_feature_updates', $updates, DAY_IN_SECONDS );
	}
}
