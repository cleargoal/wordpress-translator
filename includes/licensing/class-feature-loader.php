<?php
namespace WPSTE\Licensing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Feature_Loader {

	protected $storage;

	public function __construct( $storage ) {
		$this->storage = $storage;
	}

	public function load_features() {
		// TODO: Load downloaded premium features
	}
}
