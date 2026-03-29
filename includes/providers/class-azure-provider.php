<?php
/**
 * Azure Translator Provider
 *
 * Implements translation using Microsoft Azure Translator API v3.0.
 *
 * @package WP_Smart_Translation_Engine
 */

namespace WPSTE\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Azure Provider class
 */
class Azure_Provider extends Abstract_Translation_Provider {

	/**
	 * Provider name
	 *
	 * @var string
	 */
	protected $name = 'azure';

	/**
	 * API version
	 *
	 * @var string
	 */
	protected $api_version = '3.0';

	/**
	 * Base API URL format
	 *
	 * @var string
	 */
	protected $api_base_url = 'https://%s.api.cognitive.microsofttranslator.com';

	/**
	 * Translate text
	 *
	 * @param string $text Text to translate
	 * @param string $source_lang Source language code
	 * @param string $target_lang Target language code
	 * @param array  $options Additional options
	 * @return array Translation result with 'text' key or 'error'
	 */
	public function translate( string $text, string $source_lang, string $target_lang, array $options = array() ): array {
		$key_data = $this->key_manager->get_next_key();

		if ( ! $key_data ) {
			return array( 'error' => 'No Azure API keys available' );
		}

		$api_key = $key_data['api_key'];
		$region = $key_data['region'] ?? 'eastus';
		$key_id = $key_data['id'];

		// Normalize language codes
		$source_lang = $this->normalize_language_code( $source_lang );
		$target_lang = $this->normalize_language_code( $target_lang );

		// Build API URL
		$url = sprintf( $this->api_base_url, $region ) . '/translate';
		$url = add_query_arg(
			array(
				'api-version' => $this->api_version,
				'from' => $source_lang,
				'to' => $target_lang,
			),
			$url
		);

		// Prepare request body
		$body = wp_json_encode(
			array(
				array( 'Text' => $text ),
			)
		);

		// Make request
		$response = $this->make_request(
			$url,
			array(
				'method' => 'POST',
				'headers' => array(
					'Ocp-Apim-Subscription-Key' => $api_key,
					'Ocp-Apim-Subscription-Region' => $region,
					'Content-Type' => 'application/json',
				),
				'body' => $body,
			)
		);

		if ( isset( $response['error'] ) ) {
			return $response;
		}

		// Parse response
		$data = json_decode( $response['body'], true );

		if ( ! $data || ! isset( $data[0]['translations'][0]['text'] ) ) {
			return array(
				'error' => 'Invalid response from Azure API',
				'response' => $response['body'],
			);
		}

		$translated_text = $data[0]['translations'][0]['text'];

		// Log usage
		$characters = $this->count_characters( $text );
		$this->log_usage( $key_id, $characters );
		$this->key_manager->update_usage( $key_id, $characters );

		return array(
			'text' => $translated_text,
			'characters' => $characters,
			'provider' => $this->name,
			'source_lang' => $source_lang,
			'target_lang' => $target_lang,
		);
	}

	/**
	 * Translate batch of texts
	 *
	 * @param array  $texts Array of texts to translate
	 * @param string $source_lang Source language code
	 * @param string $target_lang Target language code
	 * @param array  $options Additional options
	 * @return array Array of translation results
	 */
	public function translate_batch( array $texts, string $source_lang, string $target_lang, array $options = array() ): array {
		if ( empty( $texts ) ) {
			return array( 'error' => 'No texts provided' );
		}

		$key_data = $this->key_manager->get_next_key();

		if ( ! $key_data ) {
			return array( 'error' => 'No Azure API keys available' );
		}

		$api_key = $key_data['api_key'];
		$region = $key_data['region'] ?? 'eastus';
		$key_id = $key_data['id'];

		// Normalize language codes
		$source_lang = $this->normalize_language_code( $source_lang );
		$target_lang = $this->normalize_language_code( $target_lang );

		// Build API URL
		$url = sprintf( $this->api_base_url, $region ) . '/translate';
		$url = add_query_arg(
			array(
				'api-version' => $this->api_version,
				'from' => $source_lang,
				'to' => $target_lang,
			),
			$url
		);

		// Prepare request body - Azure supports up to 100 texts per request
		$batch_items = array();
		foreach ( $texts as $text ) {
			$batch_items[] = array( 'Text' => $text );
		}

		$body = wp_json_encode( $batch_items );

		// Make request
		$response = $this->make_request(
			$url,
			array(
				'method' => 'POST',
				'headers' => array(
					'Ocp-Apim-Subscription-Key' => $api_key,
					'Ocp-Apim-Subscription-Region' => $region,
					'Content-Type' => 'application/json',
				),
				'body' => $body,
			)
		);

		if ( isset( $response['error'] ) ) {
			return $response;
		}

		// Parse response
		$data = json_decode( $response['body'], true );

		if ( ! $data || ! is_array( $data ) ) {
			return array(
				'error' => 'Invalid response from Azure API',
				'response' => $response['body'],
			);
		}

		$results = array();
		$total_characters = 0;

		foreach ( $data as $index => $item ) {
			if ( isset( $item['translations'][0]['text'] ) ) {
				$results[] = $item['translations'][0]['text'];
				$total_characters += $this->count_characters( $texts[ $index ] );
			} else {
				$results[] = '';
			}
		}

		// Log usage
		$this->log_usage( $key_id, $total_characters );
		$this->key_manager->update_usage( $key_id, $total_characters );

		return array(
			'texts' => $results,
			'characters' => $total_characters,
			'provider' => $this->name,
			'source_lang' => $source_lang,
			'target_lang' => $target_lang,
		);
	}

	/**
	 * Detect language of text
	 *
	 * @param string $text Text to analyze
	 * @return array Result with 'language' code or 'error'
	 */
	public function detect_language( string $text ): array {
		$key_data = $this->key_manager->get_next_key();

		if ( ! $key_data ) {
			return array( 'error' => 'No Azure API keys available' );
		}

		$api_key = $key_data['api_key'];
		$region = $key_data['region'] ?? 'eastus';

		// Build API URL
		$url = sprintf( $this->api_base_url, $region ) . '/detect';
		$url = add_query_arg( 'api-version', $this->api_version, $url );

		// Prepare request body
		$body = wp_json_encode(
			array(
				array( 'Text' => $text ),
			)
		);

		// Make request
		$response = $this->make_request(
			$url,
			array(
				'method' => 'POST',
				'headers' => array(
					'Ocp-Apim-Subscription-Key' => $api_key,
					'Ocp-Apim-Subscription-Region' => $region,
					'Content-Type' => 'application/json',
				),
				'body' => $body,
			)
		);

		if ( isset( $response['error'] ) ) {
			return $response;
		}

		// Parse response
		$data = json_decode( $response['body'], true );

		if ( ! $data || ! isset( $data[0]['language'] ) ) {
			return array(
				'error' => 'Invalid response from Azure API',
				'response' => $response['body'],
			);
		}

		return array(
			'language' => $data[0]['language'],
			'confidence' => $data[0]['score'] ?? 1.0,
		);
	}

	/**
	 * Get supported languages
	 *
	 * @return array Array of supported language codes
	 */
	public function get_supported_languages(): array {
		// Azure Translator supports 100+ languages
		// Returning most common ones
		return array(
			'af', 'ar', 'bg', 'bn', 'bs', 'ca', 'cs', 'cy', 'da', 'de',
			'el', 'en', 'es', 'et', 'fa', 'fi', 'fr', 'ga', 'gu', 'he',
			'hi', 'hr', 'hu', 'id', 'is', 'it', 'ja', 'ka', 'kk', 'km',
			'kn', 'ko', 'ku', 'lo', 'lt', 'lv', 'mg', 'mi', 'mk', 'ml',
			'mn', 'mr', 'ms', 'mt', 'my', 'nb', 'ne', 'nl', 'or', 'pa',
			'pl', 'ps', 'pt', 'ro', 'ru', 'sk', 'sl', 'sm', 'sq', 'sr',
			'sv', 'sw', 'ta', 'te', 'th', 'tl', 'to', 'tr', 'ty', 'uk',
			'ur', 'uz', 'vi', 'zh', 'zh-Hans', 'zh-Hant',
		);
	}

	/**
	 * Check if provider is available
	 *
	 * @return bool True if provider has valid API keys
	 */
	public function is_available(): bool {
		$keys = $this->key_manager->get_all_keys( true );
		return ! empty( $keys );
	}

	/**
	 * Normalize language code for Azure
	 *
	 * @param string $lang Language code
	 * @return string Normalized code
	 */
	protected function normalize_language_code( string $lang ): string {
		$lang = parent::normalize_language_code( $lang );

		// Azure-specific mappings
		$map = array(
			'nb' => 'no',  // Norwegian Bokmål
			'nn' => 'no',  // Norwegian Nynorsk
		);

		return $map[ $lang ] ?? $lang;
	}
}
