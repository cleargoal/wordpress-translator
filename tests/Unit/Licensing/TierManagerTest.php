<?php

namespace WPSTE\Tests\Unit\Licensing;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WPSTE\Licensing\License_Storage;
use WPSTE\Licensing\Tier_Manager;

class TierManagerTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		// Default — free tier unless overridden per test.
		Functions\when( 'get_option' )->justReturn( [ 'tier' => 'free', 'status' => 'inactive' ] );
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	// -----------------------------------------------------------------------
	// Language limit
	// -----------------------------------------------------------------------

	public function test_free_tier_language_limit_is_three(): void {
		$manager = $this->makeManagerWithTier( 'free' );
		$this->assertSame( 3, $manager->get_language_limit() );
	}

	public function test_paid_tier_language_limit_is_unlimited(): void {
		foreach ( [ 'starter', 'pro', 'business', 'agency' ] as $tier ) {
			$manager = $this->makeManagerWithTier( $tier );
			$this->assertSame( -1, $manager->get_language_limit(), "Tier '{$tier}' should have unlimited languages" );
		}
	}

	// -----------------------------------------------------------------------
	// API key limit
	// -----------------------------------------------------------------------

	public function test_free_tier_max_api_keys_is_one(): void {
		$manager = $this->makeManagerWithTier( 'free' );
		$this->assertSame( 1, $manager->get_max_api_keys() );
	}

	public function test_paid_tier_max_api_keys_is_unlimited(): void {
		foreach ( [ 'starter', 'pro', 'business', 'agency' ] as $tier ) {
			$manager = $this->makeManagerWithTier( $tier );
			$this->assertSame( -1, $manager->get_max_api_keys(), "Tier '{$tier}' should allow unlimited API keys" );
		}
	}

	// -----------------------------------------------------------------------
	// Helper
	// -----------------------------------------------------------------------

	private function makeManagerWithTier( string $tier ): Tier_Manager {
		$mock_storage = $this->createMock( License_Storage::class );
		$mock_storage->method( 'get_tier' )->willReturn( $tier );

		$manager = new Tier_Manager();

		$ref = new \ReflectionProperty( Tier_Manager::class, 'storage' );
		$ref->setAccessible( true );
		$ref->setValue( $manager, $mock_storage );

		return $manager;
	}
}
