<?php
/**
 * AWS Translate Provider
 *
 * Implements translation using Amazon Web Services Translate API.
 *
 * @package WP_Smart_Translation_Engine
 */

namespace WPSTE\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AWS Provider class
 */
class AWS_Provider extends Abstract_Translation_Provider {

	/**
	 * Provider name
	 *
	 * @var string
	 */
	protected $name = 'aws';

	/**
	 * AWS service name
	 *
	 * @var string
	 */
	protected $service = 'translate';

	/**
	 * AWS Translate language code mapping
	 *
	 * @var array
	 */
	protected $aws_codes = array(
		'uk' => 'uk',
		'de' => 'de',
		'fr' => 'fr',
		'es' => 'es',
		'it' => 'it',
		'pt' => 'pt',
		'pl' => 'pl',
		'ru' => 'ru',
		'ja' => 'ja',
		'zh' => 'zh',
		'nl' => 'nl',
		'sv' => 'sv',
		'da' => 'da',
		'fi' => 'fi',
		'no' => 'no',
		'cs' => 'cs',
		'el' => 'el',
		'ar' => 'ar',
		'tr' => 'tr',
		'ko' => 'ko',
		'he' => 'he',
		'hi' => 'hi',
		'th' => 'th',
		'id' => 'id',
		'ro' => 'ro',
		'vi' => 'vi',
	);

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
		$credentials = $this->key_manager->get_next_key();

		if ( ! $credentials ) {
			return array( 'error' => 'No AWS credentials available' );
		}

		// Normalize language codes
		$source_lang = $this->normalize_aws_language( $source_lang );
		$target_lang = $this->normalize_aws_language( $target_lang );

		// Prepare request payload
		$payload = array(
			'Text'             => $text,
			'SourceLanguageCode' => $source_lang,
			'TargetLanguageCode' => $target_lang,
		);

		// Make signed request
		$response = $this->make_aws_request( $credentials, 'TranslateText', $payload );

		if ( isset( $response['error'] ) ) {
			return $response;
		}

		// Parse response
		$data = json_decode( $response['body'], true );

		if ( ! $data || ! isset( $data['TranslatedText'] ) ) {
			return array(
				'error'    => 'Invalid response from AWS Translate. HTTP ' . $response['code'] . ': ' . substr( $response['body'], 0, 200 ),
				'response' => $response['body'],
			);
		}

		$translated_text = $data['TranslatedText'];

		// Log usage
		$characters = $this->count_characters( $text );
		$this->log_usage( $credentials['id'], $characters );
		$this->key_manager->update_usage( $credentials['id'], $characters );

		return array(
			'text'        => $translated_text,
			'characters'  => $characters,
			'provider'    => $this->name,
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

		// AWS Translate doesn't have native batch API, so translate one by one
		$results = array();
		$total_characters = 0;

		foreach ( $texts as $text ) {
			$result = $this->translate( $text, $source_lang, $target_lang, $options );

			if ( isset( $result['error'] ) ) {
				$results[] = '';
			} else {
				$results[] = $result['text'];
				$total_characters += $result['characters'];
			}
		}

		return array(
			'texts'       => $results,
			'characters'  => $total_characters,
			'provider'    => $this->name,
			'source_lang' => $source_lang,
			'target_lang' => $target_lang,
		);
	}

	/**
	 * Detect language of text
	 *
	 * AWS Translate doesn't support language detection.
	 *
	 * @param string $text Text to analyze
	 * @return array Result with 'error'
	 */
	public function detect_language( string $text ): array {
		return array( 'error' => 'AWS Translate does not support language detection' );
	}

	/**
	 * Get supported languages
	 *
	 * @return array Array of supported language codes
	 */
	public function get_supported_languages(): array {
		return array_keys( $this->aws_codes );
	}

	/**
	 * Check if provider is available
	 *
	 * @return bool True if provider has valid credentials
	 */
	public function is_available(): bool {
		$keys = $this->key_manager->get_all_keys( true );
		return ! empty( $keys );
	}

	/**
	 * Normalize language code for AWS
	 *
	 * @param string $lang Language code
	 * @return string AWS language code
	 */
	protected function normalize_aws_language( string $lang ): string {
		$lang = strtolower( trim( $lang ) );
		return $this->aws_codes[ $lang ] ?? $lang;
	}

	/**
	 * Make AWS signed request
	 *
	 * @param array  $credentials AWS credentials
	 * @param string $action AWS action name
	 * @param array  $payload Request payload
	 * @return array Response with 'body', 'code' keys or 'error'
	 */
	protected function make_aws_request( array $credentials, string $action, array $payload ): array {
		$region = $credentials['region'];
		$access_key = $credentials['access_key_id'];
		$secret_key = $credentials['secret_access_key'];

		// AWS endpoint
		$host = "translate.{$region}.amazonaws.com";
		$endpoint = "https://{$host}/";

		// Request timestamp
		$timestamp = gmdate( 'Ymd\THis\Z' );
		$date = gmdate( 'Ymd' );

		// Request body
		$body = wp_json_encode( $payload );

		// Headers
		$headers = array(
			'Content-Type'       => 'application/x-amz-json-1.1',
			'X-Amz-Target'       => "AWSShineFrontendService_20170701.{$action}",
			'X-Amz-Date'         => $timestamp,
			'Host'               => $host,
		);

		// Create canonical request
		$canonical_uri = '/';
		$canonical_querystring = '';
		$canonical_headers = '';
		$signed_headers = '';

		$header_keys = array_keys( $headers );
		sort( $header_keys );

		foreach ( $header_keys as $key ) {
			$lower_key = strtolower( $key );
			$canonical_headers .= $lower_key . ':' . trim( $headers[ $key ] ) . "\n";
			$signed_headers .= $lower_key . ';';
		}
		$signed_headers = rtrim( $signed_headers, ';' );

		$payload_hash = hash( 'sha256', $body );

		$canonical_request = "POST\n{$canonical_uri}\n{$canonical_querystring}\n{$canonical_headers}\n{$signed_headers}\n{$payload_hash}";

		// Create string to sign
		$algorithm = 'AWS4-HMAC-SHA256';
		$credential_scope = "{$date}/{$region}/{$this->service}/aws4_request";
		$string_to_sign = "{$algorithm}\n{$timestamp}\n{$credential_scope}\n" . hash( 'sha256', $canonical_request );

		// Calculate signature
		$signing_key = $this->get_signature_key( $secret_key, $date, $region, $this->service );
		$signature = hash_hmac( 'sha256', $string_to_sign, $signing_key );

		// Add authorization header
		$authorization = "{$algorithm} Credential={$access_key}/{$credential_scope}, SignedHeaders={$signed_headers}, Signature={$signature}";
		$headers['Authorization'] = $authorization;

		// Make request
		$response = wp_remote_post(
			$endpoint,
			array(
				'headers' => $headers,
				'body'    => $body,
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'error' => 'AWS request failed: ' . $response->get_error_message(),
				'code'  => 'request_failed',
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		// Handle HTTP errors
		if ( $code >= 400 ) {
			$error_data = json_decode( $body, true );
			$error_message = $error_data['__type'] ?? 'Unknown error';
			$error_detail = $error_data['message'] ?? $body;

			return array(
				'error' => "AWS Translate error: {$error_message} - {$error_detail}",
				'code'  => $code,
			);
		}

		return array(
			'code'    => $code,
			'body'    => $body,
			'headers' => wp_remote_retrieve_headers( $response ),
		);
	}

	/**
	 * Get AWS Signature V4 signing key
	 *
	 * @param string $secret_key Secret access key
	 * @param string $date Date in Ymd format
	 * @param string $region AWS region
	 * @param string $service AWS service name
	 * @return string Signing key
	 */
	protected function get_signature_key( string $secret_key, string $date, string $region, string $service ): string {
		$k_date = hash_hmac( 'sha256', $date, 'AWS4' . $secret_key, true );
		$k_region = hash_hmac( 'sha256', $region, $k_date, true );
		$k_service = hash_hmac( 'sha256', $service, $k_region, true );
		$k_signing = hash_hmac( 'sha256', 'aws4_request', $k_service, true );

		return $k_signing;
	}
}
