<?php
/**
 * Translation Manager
 *
 * Manages translation requests with provider fallback.
 *
 * @package WP_Smart_Translation_Engine
 */

namespace WPSTE\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Translation Manager class
 */
class Translation_Manager {

	/**
	 * Provider factory
	 *
	 * @var Provider_Factory
	 */
	protected $factory;

	/**
	 * Settings
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->factory = new Provider_Factory();
		$this->settings = get_option( 'wpste_settings', array() );
	}

	/**
	 * Translate text
	 *
	 * @param string $text Text to translate
	 * @param string $source_lang Source language
	 * @param string $target_lang Target language
	 * @param array  $options Additional options
	 * @return array Translation result
	 */
	public function translate( string $text, string $source_lang, string $target_lang, array $options = array() ): array {
		if ( empty( $text ) ) {
			return array( 'error' => 'Empty text provided' );
		}

		// Allow premium features (e.g. Translation Memory) to short-circuit the API call.
		$override = apply_filters( 'wpste_before_translate', null, $text, $source_lang, $target_lang );
		if ( is_array( $override ) && isset( $override['text'] ) ) {
			return $override;
		}

		// Allow premium features (e.g. Glossary) to pre-process the source text.
		$original_text = $text;
		$text          = apply_filters( 'wpste_translate_source', $text, $source_lang, $target_lang );

		// Get provider list (primary + fallbacks)
		$providers = $this->get_provider_list();

		$last_error = '';

		foreach ( $providers as $provider_name ) {
			$provider = $this->factory->get_provider( $provider_name );

			if ( ! $provider || ! $provider->is_available() ) {
				$last_error = "Provider {$provider_name} not available";
				continue;
			}

			$result = $provider->translate( $text, $source_lang, $target_lang, $options );

			if ( ! isset( $result['error'] ) ) {
				// Allow premium features (e.g. Glossary) to post-process the translated text.
				$result['text'] = apply_filters( 'wpste_translate_result', $result['text'], $original_text, $source_lang, $target_lang );
				$result['provider'] = $provider_name;
				return $result;
			}

			$last_error = $result['error'];

			// Log failure
			do_action(
				'wpste_translation_failed',
				array(
					'provider' => $provider_name,
					'error' => $last_error,
					'source_lang' => $source_lang,
					'target_lang' => $target_lang,
				)
			);
		}

		return array(
			'error' => $last_error ?: 'No providers available',
			'providers_tried' => $providers,
		);
	}

	/**
	 * Translate batch of texts
	 *
	 * @param array  $texts Texts to translate
	 * @param string $source_lang Source language
	 * @param string $target_lang Target language
	 * @param array  $options Additional options
	 * @return array Results array
	 */
	public function translate_batch( array $texts, string $source_lang, string $target_lang, array $options = array() ): array {
		$providers = $this->get_provider_list();

		foreach ( $providers as $provider_name ) {
			$provider = $this->factory->get_provider( $provider_name );

			if ( ! $provider || ! $provider->is_available() ) {
				continue;
			}

			$result = $provider->translate_batch( $texts, $source_lang, $target_lang, $options );

			if ( ! isset( $result['error'] ) ) {
				$result['provider'] = $provider_name;
				return $result;
			}
		}

		return array(
			'error' => 'No providers available for batch translation',
		);
	}

	/**
	 * Detect language
	 *
	 * @param string $text Text to analyze
	 * @return array Result with language code
	 */
	public function detect_language( string $text ): array {
		$provider_name = $this->settings['primary_provider'] ?? 'deepl';
		$provider = $this->factory->get_provider( $provider_name );

		if ( ! $provider || ! $provider->is_available() ) {
			return array( 'error' => 'Provider not available' );
		}

		return $provider->detect_language( $text );
	}

	/**
	 * Get provider list (primary + fallbacks)
	 *
	 * @return array
	 */
	protected function get_provider_list(): array {
		$providers = array();

		// Add primary provider
		if ( ! empty( $this->settings['primary_provider'] ) ) {
			$providers[] = $this->settings['primary_provider'];
		}

		// Add fallback providers
		if ( ! empty( $this->settings['fallback_providers'] ) ) {
			foreach ( $this->settings['fallback_providers'] as $fallback ) {
				if ( ! in_array( $fallback, $providers ) ) {
					$providers[] = $fallback;
				}
			}
		}

		// If no providers configured, use default
		if ( empty( $providers ) ) {
			$providers[] = 'deepl';
		}

		return apply_filters( 'wpste_provider_list', $providers );
	}

	/**
	 * Get available providers
	 *
	 * @return array
	 */
	public function get_available_providers(): array {
		$all_providers = $this->factory->get_registered_providers();
		$available = array();

		foreach ( $all_providers as $name ) {
			$provider = $this->factory->get_provider( $name );
			if ( $provider && $provider->is_available() ) {
				$available[] = $name;
			}
		}

		return $available;
	}
}
