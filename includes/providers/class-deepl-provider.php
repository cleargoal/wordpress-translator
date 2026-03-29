<?php
/**
 * DeepL Provider
 *
 * DeepL translation provider implementation.
 *
 * @package WP_Smart_Translation_Engine
 */

namespace WPSTE\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * DeepL Provider class
 */
class DeepL_Provider extends Abstract_Translation_Provider {

	/**
	 * API endpoint
	 *
	 * @var string
	 */
	protected $api_url = 'https://api-free.deepl.com/v2/translate';

	/**
	 * Provider name
	 *
	 * @var string
	 */
	protected $name = 'deepl';

	/**
	 * DeepL language codes mapping
	 *
	 * @var array
	 */
	protected $deepl_codes = array(
		'uk' => 'UK',
		'de' => 'DE',
		'fr' => 'FR',
		'es' => 'ES',
		'it' => 'IT',
		'pt' => 'PT',
		'pl' => 'PL',
		'ru' => 'RU',
		'ja' => 'JA',
		'zh' => 'ZH',
		'nl' => 'NL',
		'sv' => 'SV',
		'da' => 'DA',
		'fi' => 'FI',
		'no' => 'NB',
		'cs' => 'CS',
		'el' => 'EL',
		'ar' => 'AR',
		'tr' => 'TR',
		'ko' => 'KO',
		'he' => 'HE',
		'hi' => 'HI',
	);

	/**
	 * Translate text
	 *
	 * @param string $text Text to translate
	 * @param string $source_lang Source language code
	 * @param string $target_lang Target language code
	 * @param array  $options Additional options
	 * @return array
	 */
	public function translate( string $text, string $source_lang, string $target_lang, array $options = array() ): array {
		if ( empty( $text ) ) {
			return array( 'error' => 'Empty text provided' );
		}

		// Convert language codes to DeepL format
		$source_deepl = $this->to_deepl_code( $source_lang );
		$target_deepl = $this->to_deepl_code( $target_lang );

		if ( ! $target_deepl ) {
			return array( 'error' => "Unsupported target language: {$target_lang}" );
		}

		// Get API keys sorted by quota
		$keys = $this->key_manager->get_all_keys( true );

		if ( empty( $keys ) ) {
			return array( 'error' => 'No DeepL API keys configured' );
		}

		// Try each key until one succeeds
		foreach ( $keys as $key_data ) {
			$api_key = $key_data['api_key'];
			$key_id = $key_data['id'];

			// Check quota
			if ( ! $this->key_manager->has_quota( $key_id ) ) {
				continue;
			}

			$result = $this->make_deepl_request( $api_key, $text, $source_deepl, $target_deepl );

			if ( ! isset( $result['error'] ) ) {
				// Success - update usage
				$char_count = $this->count_characters( $text );
				$this->key_manager->update_usage( $key_id, $char_count );

				return array(
					'text' => $result['text'],
					'api_key_id' => $key_id,
					'characters' => $char_count,
				);
			}

			// Check if quota error
			if ( isset( $result['quota_exceeded'] ) && $result['quota_exceeded'] ) {
				// Mark key as exhausted and try next
				do_action(
					'wpste_quota_exhausted',
					array(
						'provider' => $this->name,
						'api_key_id' => $key_id,
					)
				);
				continue;
			}
		}

		return array( 'error' => 'All DeepL API keys failed or quota exhausted' );
	}

	/**
	 * Make DeepL API request
	 *
	 * @param string      $api_key API key
	 * @param string      $text Text to translate
	 * @param string|null $source_lang Source language
	 * @param string      $target_lang Target language
	 * @return array
	 */
	protected function make_deepl_request( string $api_key, string $text, ?string $source_lang, string $target_lang ): array {
		$args = array(
			'headers' => array(
				'Authorization' => 'DeepL-Auth-Key ' . $api_key,
				'Content-Type' => 'application/json',
			),
			'body' => json_encode(
				array(
					'text' => array( $text ),
					'source_lang' => $source_lang,
					'target_lang' => $target_lang,
					'preserve_formatting' => true,
					'tag_handling' => 'html',
				)
			),
			'timeout' => 30,
			'method' => 'POST',
		);

		$response = wp_remote_request( $this->api_url, $args );

		if ( is_wp_error( $response ) ) {
			return array( 'error' => $response->get_error_message() );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( $code === 456 ) {
			return array(
				'error' => 'Quota exceeded',
				'quota_exceeded' => true,
			);
		}

		if ( $code !== 200 ) {
			$error_message = $data['message'] ?? 'Unknown error';
			return array( 'error' => "DeepL API error: {$error_message}" );
		}

		if ( empty( $data['translations'][0]['text'] ) ) {
			return array( 'error' => 'No translation returned' );
		}

		return array(
			'text' => $data['translations'][0]['text'],
			'detected_source_language' => $data['translations'][0]['detected_source_language'] ?? null,
		);
	}

	/**
	 * Translate batch of texts
	 *
	 * @param array  $texts Array of texts
	 * @param string $source_lang Source language
	 * @param string $target_lang Target language
	 * @param array  $options Options
	 * @return array
	 */
	public function translate_batch( array $texts, string $source_lang, string $target_lang, array $options = array() ): array {
		$results = array();

		foreach ( $texts as $text ) {
			$result = $this->translate( $text, $source_lang, $target_lang, $options );
			$results[] = $result;

			// Small delay to avoid rate limiting
			usleep( 100000 ); // 100ms
		}

		return array( 'translations' => $results );
	}

	/**
	 * Detect language of text
	 *
	 * @param string $text Text to analyze
	 * @return array
	 */
	public function detect_language( string $text ): array {
		$key_data = $this->key_manager->get_next_key();

		if ( ! $key_data ) {
			return array( 'error' => 'No API key available' );
		}

		// Use translate with EN target to get detected source language
		$result = $this->make_deepl_request(
			$key_data['api_key'],
			substr( $text, 0, 500 ),
			null,
			'EN-US'
		);

		if ( isset( $result['error'] ) ) {
			return $result;
		}

		$detected = $result['detected_source_language'] ?? null;

		if ( ! $detected ) {
			return array( 'error' => 'Could not detect language' );
		}

		// Convert from DeepL code to our code
		$language = $this->from_deepl_code( $detected );

		return array( 'language' => $language );
	}

	/**
	 * Get supported languages
	 *
	 * @return array
	 */
	public function get_supported_languages(): array {
		return array_keys( $this->deepl_codes ) + array( 'en' );
	}

	/**
	 * Check if provider is available
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		$keys = $this->key_manager->get_all_keys( true );
		return ! empty( $keys );
	}

	/**
	 * Convert to DeepL language code
	 *
	 * @param string $lang Our language code
	 * @return string|null DeepL code
	 */
	protected function to_deepl_code( string $lang ): ?string {
		$lang = $this->normalize_language_code( $lang );

		if ( $lang === 'en' ) {
			return 'EN-US';
		}

		return $this->deepl_codes[ $lang ] ?? null;
	}

	/**
	 * Convert from DeepL language code
	 *
	 * @param string $deepl_code DeepL code
	 * @return string Our code
	 */
	protected function from_deepl_code( string $deepl_code ): string {
		$deepl_code = strtoupper( $deepl_code );

		if ( $deepl_code === 'EN' || $deepl_code === 'EN-US' || $deepl_code === 'EN-GB' ) {
			return 'en';
		}

		$map = array_flip( $this->deepl_codes );
		return $map[ $deepl_code ] ?? strtolower( $deepl_code );
	}
}
