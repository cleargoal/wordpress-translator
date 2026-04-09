<?php

namespace WPSTE\Tests\Unit\Core;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WPSTE\Core\Provider_Factory;

class ProviderFactoryTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( 'do_action' )->justReturn( null );
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_default_providers_are_registered(): void {
		$factory = new Provider_Factory();

		$this->assertTrue( $factory->is_registered( 'deepl' ) );
		$this->assertTrue( $factory->is_registered( 'azure' ) );
		$this->assertTrue( $factory->is_registered( 'aws' ) );
	}

	public function test_get_registered_providers_returns_all_names(): void {
		$factory   = new Provider_Factory();
		$providers = $factory->get_registered_providers();

		$this->assertContains( 'deepl', $providers );
		$this->assertContains( 'azure', $providers );
		$this->assertContains( 'aws', $providers );
	}

	public function test_register_custom_provider(): void {
		$factory = new Provider_Factory();
		$factory->register_provider( 'custom', 'Some\\Custom\\Class' );

		$this->assertTrue( $factory->is_registered( 'custom' ) );
		$this->assertContains( 'custom', $factory->get_registered_providers() );
	}

	public function test_is_registered_returns_false_for_unknown(): void {
		$factory = new Provider_Factory();

		$this->assertFalse( $factory->is_registered( 'nonexistent' ) );
	}

	public function test_register_provider_overwrites_existing_class_without_adding_duplicates(): void {
		$factory = new Provider_Factory();
		$factory->register_provider( 'deepl', 'My\\Custom\\DeepL' );

		$this->assertTrue( $factory->is_registered( 'deepl' ) );
		// Overwriting the same key must not increase the count.
		$this->assertCount( 3, $factory->get_registered_providers() );
	}
}
