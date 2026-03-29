<?php
namespace WPSTE\Licensing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class License_Validator {

	public function validate( $license_key ) {
		// TODO: Implement actual validation with external server
		return array(
			'valid' => false,
			'tier' => 'free',
			'message' => 'License validation not yet implemented',
		);
	}
}
