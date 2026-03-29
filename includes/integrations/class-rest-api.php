<?php
namespace WPSTE\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class REST_API {

	protected $namespace = 'wpste/v1';

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/translate',
			array(
				'methods' => 'POST',
				'callback' => array( $this, 'translate_endpoint' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_rest_route(
			$this->namespace,
			'/languages',
			array(
				'methods' => 'GET',
				'callback' => array( $this, 'languages_endpoint' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function translate_endpoint( \WP_REST_Request $request ) {
		$text = $request->get_param( 'text' );
		$source_lang = $request->get_param( 'source_lang' );
		$target_lang = $request->get_param( 'target_lang' );

		if ( ! $text || ! $target_lang ) {
			return new \WP_Error( 'missing_params', 'Text and target_lang are required', array( 'status' => 400 ) );
		}

		$manager = new \WPSTE\Core\Translation_Manager();
		$result = $manager->translate( $text, $source_lang ?: 'en', $target_lang );

		if ( isset( $result['error'] ) ) {
			return new \WP_Error( 'translation_failed', $result['error'], array( 'status' => 500 ) );
		}

		return rest_ensure_response( $result );
	}

	public function languages_endpoint( \WP_REST_Request $request ) {
		$settings = get_option( 'wpste_settings', array() );
		return rest_ensure_response(
			array(
				'enabled_languages' => $settings['enabled_languages'] ?? array( 'en' ),
				'default_language' => $settings['default_language'] ?? 'en',
			)
		);
	}
}
