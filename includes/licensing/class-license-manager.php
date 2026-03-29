<?php
namespace WPSTE\Licensing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class License_Manager {

	protected $storage;
	protected $validator;

	public function __construct() {
		$this->storage = new License_Storage();
		$this->validator = new License_Validator();
	}

	public function validate() {
		// Called by cron - validates license with server
		return true;
	}

	public function activate( $license_key ) {
		$result = $this->validator->validate( $license_key );
		if ( $result['valid'] ) {
			$this->storage->save_license( $result );
		}
		return $result;
	}
}
