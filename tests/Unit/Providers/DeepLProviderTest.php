<?php

namespace WPSTE\Tests\Unit\Providers;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WPSTE\KeyManagement\Key_Manager_Interface;
use WPSTE\Providers\DeepL_Provider;

class DeepLProviderTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	// -----------------------------------------------------------------------
	// Availability
	// -----------------------------------------------------------------------

	public function test_is_available_returns_true_when_keys_exist(): void {
		$km = $this->createMock( Key_Manager_Interface::class );
		$km->method( 'get_all_keys' )->willReturn( [ [ 'id' => 1, 'api_key' => 'k' ] ] );

		$provider = new DeepL_Provider( $km );
		$this->assertTrue( $provider->is_available() );
	}

	public function test_is_available_returns_false_when_no_keys(): void {
		$km = $this->createMock( Key_Manager_Interface::class );
		$km->method( 'get_all_keys' )->willReturn( [] );

		$provider = new DeepL_Provider( $km );
		$this->assertFalse( $provider->is_available() );
	}

	// -----------------------------------------------------------------------
	// translate() — guard clauses
	// -----------------------------------------------------------------------

	public function test_translate_returns_error_for_empty_text(): void {
		$km       = $this->createMock( Key_Manager_Interface::class );
		$provider = new DeepL_Provider( $km );

		$result = $provider->translate( '', 'en', 'de' );

		$this->assertArrayHasKey( 'error', $result );
		$this->assertSame( 'Empty text provided', $result['error'] );
	}

	public function test_translate_returns_error_for_unsupported_target_language(): void {
		$km = $this->createMock( Key_Manager_Interface::class );
		$km->method( 'get_all_keys' )->willReturn(
			[ [ 'id' => 1, 'api_key' => 'key', 'quota_limit' => 500000, 'characters_used' => 0 ] ]
		);
		$km->method( 'has_quota' )->willReturn( true );

		$provider = new DeepL_Provider( $km );
		$result   = $provider->translate( 'Hello', 'en', 'xx' ); // 'xx' not in deepl_codes

		$this->assertArrayHasKey( 'error', $result );
		$this->assertStringContainsString( 'Unsupported target language', $result['error'] );
	}

	public function test_translate_returns_error_when_no_keys_configured(): void {
		$km = $this->createMock( Key_Manager_Interface::class );
		$km->method( 'get_all_keys' )->willReturn( [] );

		$provider = new DeepL_Provider( $km );
		$result   = $provider->translate( 'Hello', 'en', 'de' );

		$this->assertArrayHasKey( 'error', $result );
		$this->assertStringContainsString( 'No DeepL API keys configured', $result['error'] );
	}

	// -----------------------------------------------------------------------
	// translate() — HTTP success path
	// -----------------------------------------------------------------------

	public function test_translate_returns_translated_text_on_success(): void {
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'wp_remote_request' )->justReturn( [] );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 200 );
		Functions\when( 'wp_remote_retrieve_body' )->justReturn(
			(string) json_encode(
				[
					'translations' => [
						[
							'text'                      => 'Hallo',
							'detected_source_language'  => 'EN',
						],
					],
				]
			)
		);
		Functions\when( 'wp_strip_all_tags' )->returnArg( 1 );

		$km = $this->createMock( Key_Manager_Interface::class );
		$km->method( 'get_all_keys' )->willReturn(
			[ [ 'id' => 1, 'api_key' => 'test-key', 'quota_limit' => 500000, 'characters_used' => 0 ] ]
		);
		$km->method( 'has_quota' )->willReturn( true );
		$km->method( 'update_usage' )->willReturn( true );

		$provider = new DeepL_Provider( $km );
		$result   = $provider->translate( 'Hello', 'en', 'de' );

		$this->assertArrayHasKey( 'text', $result );
		$this->assertSame( 'Hallo', $result['text'] );
		$this->assertSame( 1, $result['api_key_id'] );
	}

	// -----------------------------------------------------------------------
	// translate() — quota / error path
	// -----------------------------------------------------------------------

	public function test_translate_returns_error_when_api_returns_456_quota_exceeded(): void {
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'wp_remote_request' )->justReturn( [] );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 456 );
		Functions\when( 'wp_remote_retrieve_body' )->justReturn( '{}' );
		Functions\when( 'do_action' )->justReturn( null );

		$km = $this->createMock( Key_Manager_Interface::class );
		$km->method( 'get_all_keys' )->willReturn(
			[ [ 'id' => 1, 'api_key' => 'exhausted-key', 'quota_limit' => 500000, 'characters_used' => 0 ] ]
		);
		$km->method( 'has_quota' )->willReturn( true );

		$provider = new DeepL_Provider( $km );
		$result   = $provider->translate( 'Hello', 'en', 'de' );

		$this->assertArrayHasKey( 'error', $result );
		$this->assertStringContainsString( 'quota exhausted', $result['error'] );
	}

	public function test_translate_skips_key_without_quota_and_uses_next(): void {
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'wp_remote_request' )->justReturn( [] );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 200 );
		Functions\when( 'wp_remote_retrieve_body' )->justReturn(
			(string) json_encode(
				[
					'translations' => [
						[ 'text' => 'Bonjour', 'detected_source_language' => 'EN' ],
					],
				]
			)
		);
		Functions\when( 'wp_strip_all_tags' )->returnArg( 1 );

		$km = $this->createMock( Key_Manager_Interface::class );
		$km->method( 'get_all_keys' )->willReturn(
			[
				[ 'id' => 1, 'api_key' => 'key-no-quota', 'quota_limit' => 500000, 'characters_used' => 0 ],
				[ 'id' => 2, 'api_key' => 'key-with-quota', 'quota_limit' => 500000, 'characters_used' => 0 ],
			]
		);
		// Key 1 has no quota; key 2 has quota.
		$km->method( 'has_quota' )->willReturnCallback(
			static function ( int $key_id ): bool {
				return $key_id === 2;
			}
		);
		$km->method( 'update_usage' )->willReturn( true );

		$provider = new DeepL_Provider( $km );
		$result   = $provider->translate( 'Hello', 'en', 'fr' );

		$this->assertSame( 'Bonjour', $result['text'] );
		$this->assertSame( 2, $result['api_key_id'] );
	}
}
