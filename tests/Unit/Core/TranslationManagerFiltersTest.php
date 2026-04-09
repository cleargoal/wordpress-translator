<?php

namespace WPSTE\Tests\Unit\Core;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WPSTE\Core\Provider_Factory;
use WPSTE\Core\Translation_Manager;
use WPSTE\Providers\Translation_Provider_Interface;

/**
 * Tests for the premium-feature filter hooks wired into Translation_Manager:
 *
 *   - wpste_before_translate  (Translation Memory short-circuit)
 *   - wpste_translate_source  (Glossary preprocessing)
 *   - wpste_translate_result  (Glossary postprocessing)
 */
class TranslationManagerFiltersTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( 'do_action' )->justReturn( null );
		Functions\when( 'get_option' )->justReturn( array() );
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	// -----------------------------------------------------------------------
	// wpste_before_translate
	// -----------------------------------------------------------------------

	public function test_before_translate_override_short_circuits_provider_call(): void {
		Functions\when( 'apply_filters' )->alias(
			function ( string $hook, $value ) {
				if ( $hook === 'wpste_before_translate' ) {
					return array( 'text' => 'Cached translation', 'source' => 'translation_memory' );
				}
				return $value;
			}
		);

		$mock_factory = $this->createMock( Provider_Factory::class );
		// Provider must NEVER be called when TM returns a hit.
		$mock_factory->expects( $this->never() )->method( 'get_provider' );

		$manager = $this->makeManagerWithFactory( $mock_factory );
		$result  = $manager->translate( 'Hello', 'en', 'uk' );

		$this->assertSame( 'Cached translation', $result['text'] );
		$this->assertSame( 'translation_memory', $result['source'] );
	}

	public function test_before_translate_null_does_not_short_circuit(): void {
		Functions\when( 'apply_filters' )->alias(
			function ( string $hook, $value ) {
				// wpste_before_translate returns null → no short-circuit.
				return $value;
			}
		);

		$mock_provider = $this->createMock( Translation_Provider_Interface::class );
		$mock_provider->method( 'is_available' )->willReturn( true );
		$mock_provider->expects( $this->once() )
			->method( 'translate' )
			->willReturn( array( 'text' => 'Привіт' ) );

		$mock_factory = $this->createMock( Provider_Factory::class );
		$mock_factory->method( 'get_provider' )->willReturn( $mock_provider );

		$manager = $this->makeManagerWithFactory( $mock_factory );
		$result  = $manager->translate( 'Hello', 'en', 'uk' );

		$this->assertSame( 'Привіт', $result['text'] );
	}

	// -----------------------------------------------------------------------
	// wpste_translate_source (Glossary preprocessing)
	// -----------------------------------------------------------------------

	public function test_translate_source_filter_modifies_text_sent_to_provider(): void {
		Functions\when( 'apply_filters' )->alias(
			function ( string $hook, $value ) {
				if ( $hook === 'wpste_translate_source' ) {
					// Simulate Glossary replacing 'Plugin' with a token.
					return str_replace( 'Plugin', '[[GLOSS_0]]', $value );
				}
				return $value;
			}
		);

		$mock_provider = $this->createMock( Translation_Provider_Interface::class );
		$mock_provider->method( 'is_available' )->willReturn( true );
		// The provider must receive the token-substituted text, NOT the original.
		$mock_provider->expects( $this->once() )
			->method( 'translate' )
			->with( 'Install the [[GLOSS_0]] today.', 'en', 'uk', array() )
			->willReturn( array( 'text' => 'Встановіть [[GLOSS_0]] сьогодні.' ) );

		$mock_factory = $this->createMock( Provider_Factory::class );
		$mock_factory->method( 'get_provider' )->willReturn( $mock_provider );

		$manager = $this->makeManagerWithFactory( $mock_factory );
		$manager->translate( 'Install the Plugin today.', 'en', 'uk' );
	}

	public function test_translate_source_filter_original_text_preserved_as_hash_key(): void {
		$source_received_by_filter = null;
		$original_received_by_filter = null;

		Functions\when( 'apply_filters' )->alias(
			function ( string $hook, $value, ...$args ) use ( &$source_received_by_filter, &$original_received_by_filter ) {
				if ( $hook === 'wpste_translate_source' ) {
					$source_received_by_filter = $value;
				}
				if ( $hook === 'wpste_translate_result' ) {
					// Third arg is the ORIGINAL (pre-filter) text.
					$original_received_by_filter = $args[0] ?? null;
				}
				return $value;
			}
		);

		$mock_provider = $this->createMock( Translation_Provider_Interface::class );
		$mock_provider->method( 'is_available' )->willReturn( true );
		$mock_provider->method( 'translate' )->willReturn( array( 'text' => 'Привіт' ) );

		$mock_factory = $this->createMock( Provider_Factory::class );
		$mock_factory->method( 'get_provider' )->willReturn( $mock_provider );

		$manager = $this->makeManagerWithFactory( $mock_factory );
		$manager->translate( 'Hello', 'en', 'uk' );

		// wpste_translate_source receives the original text.
		$this->assertSame( 'Hello', $source_received_by_filter );
		// wpste_translate_result receives the original text as 3rd arg (for hash key lookup).
		$this->assertSame( 'Hello', $original_received_by_filter );
	}

	// -----------------------------------------------------------------------
	// wpste_translate_result (Glossary postprocessing)
	// -----------------------------------------------------------------------

	public function test_translate_result_filter_post_processes_translation(): void {
		Functions\when( 'apply_filters' )->alias(
			function ( string $hook, $value ) {
				if ( $hook === 'wpste_translate_result' ) {
					// Simulate Glossary restoring token with target term.
					return str_replace( '[[GLOSS_0]]', 'Розширення', $value );
				}
				return $value;
			}
		);

		$mock_provider = $this->createMock( Translation_Provider_Interface::class );
		$mock_provider->method( 'is_available' )->willReturn( true );
		$mock_provider->method( 'translate' )
			->willReturn( array( 'text' => 'Встановіть [[GLOSS_0]] сьогодні.' ) );

		$mock_factory = $this->createMock( Provider_Factory::class );
		$mock_factory->method( 'get_provider' )->willReturn( $mock_provider );

		$manager = $this->makeManagerWithFactory( $mock_factory );
		$result  = $manager->translate( 'Install the Plugin today.', 'en', 'uk' );

		$this->assertSame( 'Встановіть Розширення сьогодні.', $result['text'] );
	}

	public function test_translate_result_filter_not_called_when_provider_fails(): void {
		$result_filter_called = false;

		Functions\when( 'apply_filters' )->alias(
			function ( string $hook, $value ) use ( &$result_filter_called ) {
				if ( $hook === 'wpste_translate_result' ) {
					$result_filter_called = true;
				}
				return $value;
			}
		);

		$mock_provider = $this->createMock( Translation_Provider_Interface::class );
		$mock_provider->method( 'is_available' )->willReturn( true );
		$mock_provider->method( 'translate' )->willReturn( array( 'error' => 'API down' ) );

		$mock_factory = $this->createMock( Provider_Factory::class );
		$mock_factory->method( 'get_provider' )->willReturn( $mock_provider );

		$manager = $this->makeManagerWithFactory( $mock_factory );
		$manager->translate( 'Hello', 'en', 'uk' );

		$this->assertFalse( $result_filter_called, 'wpste_translate_result should not fire when the provider fails' );
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
