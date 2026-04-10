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
		$limits = array(
			'free'       => 3,
			'starter'    => 4,
			'basic'      => 5,
			'plus'       => 8,
			'pro'        => 12,
			'agency'     => -1,
			'enterprise' => -1,
		);

		$tier = $this->get_tier();
		return $limits[ $tier ] ?? -1;
	}

	/**
	 * Get maximum number of API keys allowed for current tier
	 *
	 * @return int Maximum keys allowed (-1 for unlimited)
	 */
	public function get_max_api_keys() {
		$limits = array(
			'free'       => 1,
			'starter'    => 2,
			'basic'      => 3,
			'plus'       => 5,
			'pro'        => -1,
			'agency'     => -1,
			'enterprise' => -1,
		);

		$tier = $this->get_tier();
		return $limits[ $tier ] ?? -1;
	}
}
