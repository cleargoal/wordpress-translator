<?php
namespace WPSTE\Licensing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tier_Manager {

	protected $storage;

	public function __construct() {
		$this->storage = new License_Storage();
	}

	public function get_tier() {
		return $this->storage->get_tier();
	}

	public function has_feature( $feature ) {
		// Free tier has all core features for now
		return true;
	}

	public function get_language_limit() {
		$tier = $this->get_tier();
		if ( $tier === 'free' ) {
			return 3;
		}
		return -1; // unlimited
	}

	/**
	 * Get maximum number of API keys allowed for current tier
	 *
	 * @return int Maximum keys allowed (-1 for unlimited)
	 */
	public function get_max_api_keys() {
		$tier = $this->get_tier();

		if ( $tier === 'free' ) {
			return 1; // Free tier: only 1 API key total
		}

		// Paid tiers: unlimited keys (for rotation)
		return -1;
	}
}
