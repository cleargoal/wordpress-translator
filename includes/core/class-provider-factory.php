<?php
/**
 * Provider Factory
 *
 * Creates and manages translation provider instances.
 *
 * @package WP_Smart_Translation_Engine
 */

namespace WPSTE\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provider Factory class
 */
class Provider_Factory {

	/**
	 * Registered providers
	 *
	 * @var array
	 */
	protected $providers = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->register_default_providers();
	}

	/**
	 * Register default providers
	 */
	protected function register_default_providers(): void {
		$this->register_provider( 'deepl', 'WPSTE\Providers\DeepL_Provider' );
		$this->register_provider( 'azure', 'WPSTE\Providers\Azure_Provider' );
		$this->register_provider( 'aws', 'WPSTE\Providers\AWS_Provider' );

		do_action( 'wpste_register_providers', $this );
	}

	/**
	 * Register a provider
	 *
	 * @param string $name Provider name
	 * @param string $class Provider class name
	 */
	public function register_provider( string $name, string $class ): void {
		$this->providers[ $name ] = $class;
	}

	/**
	 * Get provider instance
	 *
	 * @param string $name Provider name
	 * @return \WPSTE\Providers\Translation_Provider_Interface|null
	 */
	public function get_provider( string $name ): ?\WPSTE\Providers\Translation_Provider_Interface {
		if ( ! isset( $this->providers[ $name ] ) ) {
			return null;
		}

		$class = $this->providers[ $name ];

		if ( ! class_exists( $class ) ) {
			// Try to load the provider file
			$file_name = 'class-' . str_replace( '_', '-', strtolower( $name ) ) . '-provider.php';
			$file_path = WPSTE_PLUGIN_DIR . 'includes/providers/' . $file_name;

			if ( file_exists( $file_path ) ) {
				require_once $file_path;
			}

			// Also try to load key manager
			$key_manager_file = 'class-' . str_replace( '_', '-', strtolower( $name ) ) . '-key-manager.php';
			$key_manager_path = WPSTE_PLUGIN_DIR . 'includes/key-management/' . $key_manager_file;

			if ( file_exists( $key_manager_path ) ) {
				require_once $key_manager_path;
			}
		}

		if ( ! class_exists( $class ) ) {
			return null;
		}

		// Get key manager class
		$key_manager_class = 'WPSTE\KeyManagement\\' . ucfirst( $name ) . '_Key_Manager';

		if ( ! class_exists( $key_manager_class ) ) {
			return null;
		}

		$key_manager = new $key_manager_class();

		return new $class( $key_manager );
	}

	/**
	 * Get all registered provider names
	 *
	 * @return array
	 */
	public function get_registered_providers(): array {
		return array_keys( $this->providers );
	}

	/**
	 * Check if provider is registered
	 *
	 * @param string $name Provider name
	 * @return bool
	 */
	public function is_registered( string $name ): bool {
		return isset( $this->providers[ $name ] );
	}
}
