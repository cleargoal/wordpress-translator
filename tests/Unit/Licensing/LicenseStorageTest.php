<?php

namespace WPSTE\Tests\Unit\Licensing;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;
use WPSTE\Licensing\License_Storage;

class LicenseStorageTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Mockery::close();
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_get_tier_returns_free_by_default(): void {
		// get_option returns false → no stored license.
		Functions\when( 'get_option' )->justReturn( false );

		$storage = new License_Storage();
		$this->assertSame( 'free', $storage->get_tier() );
	}

	public function test_get_tier_returns_stored_tier(): void {
		Functions\when( 'get_option' )->justReturn( [ 'tier' => 'pro', 'status' => 'active' ] );

		$storage = new License_Storage();
		$this->assertSame( 'pro', $storage->get_tier() );
	}

	public function test_get_license_returns_defaults_when_nothing_stored(): void {
		// Real WordPress get_option returns the $default argument when the option
		// is absent. Simulate that here.
		Functions\when( 'get_option' )->justReturn( [ 'tier' => 'free', 'status' => 'inactive' ] );

		$storage = new License_Storage();
		$license = $storage->get_license();

		$this->assertSame( 'free', $license['tier'] );
		$this->assertSame( 'inactive', $license['status'] );
	}

	public function test_save_license_calls_update_option_with_correct_args(): void {
		Functions\expect( 'update_option' )
			->once()
			->with( 'wpste_license', Mockery::type( 'array' ) )
			->andReturn( true );

		$storage = new License_Storage();
		$result  = $storage->save_license( [ 'tier' => 'pro', 'status' => 'active' ] );

		$this->assertTrue( $result );
	}
}
