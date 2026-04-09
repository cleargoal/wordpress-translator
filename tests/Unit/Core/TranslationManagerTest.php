<?php

namespace WPSTE\Tests\Unit\Core;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WPSTE\Core\Provider_Factory;
use WPSTE\Core\Translation_Manager;
use WPSTE\Providers\Translation_Provider_Interface;

class TranslationManagerTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( 'do_action' )->justReturn( null );
		Functions\when( 'get_option' )->justReturn( [] );
		// Pass the $providers array through unchanged.
		Functions\when( 'apply_filters' )->returnArg( 2 );
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_translate_empty_text_returns_error(): void {
		$manager = new Translation_Manager();
		$result  = $manager->translate( '', 'en', 'de' );

		$this->assertArrayHasKey( 'error', $result );
		$this->assertSame( 'Empty text provided', $result['error'] );
	}

	public function test_translate_succeeds_with_primary_provider(): void {
		$mock_provider = $this->createMock( Translation_Provider_Interface::class );
		$mock_provider->method( 'is_available' )->willReturn( true );
		$mock_provider->method( 'translate' )->willReturn( [ 'text' => 'Hallo' ] );

		$mock_factory = $this->createMock( Provider_Factory::class );
		$mock_factory->method( 'get_provider' )->with( 'deepl' )->willReturn( $mock_provider );

		$manager = $this->makeManagerWithFactory( $mock_factory );
		$result  = $manager->translate( 'Hello', 'en', 'de' );

		$this->assertSame( 'Hallo', $result['text'] );
		$this->assertSame( 'deepl', $result['provider'] );
	}

	public function test_translate_falls_back_when_primary_fails(): void {
		$failing_provider = $this->createMock( Translation_Provider_Interface::class );
		$failing_provider->method( 'is_available' )->willReturn( true );
		$failing_provider->method( 'translate' )->willReturn( [ 'error' => 'DeepL down' ] );

		$fallback_provider = $this->createMock( Translation_Provider_Interface::class );
		$fallback_provider->method( 'is_available' )->willReturn( true );
		$fallback_provider->method( 'translate' )->willReturn( [ 'text' => 'Hola' ] );

		$mock_factory = $this->createMock( Provider_Factory::class );
		$mock_factory->method( 'get_provider' )->willReturnMap(
			[
				[ 'deepl', $failing_provider ],
				[ 'azure', $fallback_provider ],
			]
		);

		Functions\when( 'get_option' )->justReturn(
			[
				'primary_provider'   => 'deepl',
				'fallback_providers' => [ 'azure' ],
			]
		);

		$manager = $this->makeManagerWithFactory( $mock_factory );
		$result  = $manager->translate( 'Hello', 'en', 'es' );

		$this->assertSame( 'Hola', $result['text'] );
		$this->assertSame( 'azure', $result['provider'] );
	}

	public function test_translate_returns_error_when_all_providers_fail(): void {
		$failing_provider = $this->createMock( Translation_Provider_Interface::class );
		$failing_provider->method( 'is_available' )->willReturn( true );
		$failing_provider->method( 'translate' )->willReturn( [ 'error' => 'API error' ] );

		$mock_factory = $this->createMock( Provider_Factory::class );
		$mock_factory->method( 'get_provider' )->willReturn( $failing_provider );

		$manager = $this->makeManagerWithFactory( $mock_factory );
		$result  = $manager->translate( 'Hello', 'en', 'de' );

		$this->assertArrayHasKey( 'error', $result );
		$this->assertArrayHasKey( 'providers_tried', $result );
	}

	public function test_translate_skips_unavailable_providers(): void {
		$unavailable = $this->createMock( Translation_Provider_Interface::class );
		$unavailable->method( 'is_available' )->willReturn( false );

		$mock_factory = $this->createMock( Provider_Factory::class );
		$mock_factory->method( 'get_provider' )->willReturn( $unavailable );

		$manager = $this->makeManagerWithFactory( $mock_factory );
		$result  = $manager->translate( 'Hello', 'en', 'de' );

		$this->assertArrayHasKey( 'error', $result );
	}

	public function test_default_provider_is_deepl_when_no_settings(): void {
		$mock_provider = $this->createMock( Translation_Provider_Interface::class );
		$mock_provider->method( 'is_available' )->willReturn( true );
		$mock_provider->method( 'translate' )->willReturn( [ 'text' => 'Привіт' ] );

		$mock_factory = $this->createMock( Provider_Factory::class );
		// 'deepl' must be the only provider requested when settings are empty.
		$mock_factory->expects( $this->once() )
			->method( 'get_provider' )
			->with( 'deepl' )
			->willReturn( $mock_provider );

		$manager = $this->makeManagerWithFactory( $mock_factory );
		$manager->translate( 'Hello', 'en', 'uk' );
	}

	// -----------------------------------------------------------------------

	private function makeManagerWithFactory( Provider_Factory $factory ): Translation_Manager {
		$manager = new Translation_Manager();

		$ref = new \ReflectionProperty( Translation_Manager::class, 'factory' );
		$ref->setAccessible( true );
		$ref->setValue( $manager, $factory );

		return $manager;
	}
}
