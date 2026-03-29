<?php
namespace WPSTE\Licensing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class License_Storage {

	public function get_license() {
		return get_option(
			'wpste_license',
			array(
				'tier' => 'free',
				'status' => 'inactive',
			)
		);
	}

	public function save_license( $license ) {
		return update_option( 'wpste_license', $license );
	}

	public function get_tier() {
		$license = $this->get_license();
		return $license['tier'] ?? 'free';
	}
}
