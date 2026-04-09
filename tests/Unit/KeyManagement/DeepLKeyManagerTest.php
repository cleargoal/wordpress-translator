<?php

namespace WPSTE\Tests\Unit\KeyManagement;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WPSTE\KeyManagement\DeepL_Key_Manager;

/**
 * Testable subclass that bypasses the database for key retrieval so we can
 * unit-test the rotation / sorting logic in isolation.
 */
class Testable_DeepL_Key_Manager extends DeepL_Key_Manager {

	/** @var array */
	private $test_keys = [];

	public function set_test_keys( array $keys ): void {
		$this->test_keys = $keys;
	}

	public function get_all_keys( bool $active_only = true ): array {
		return $this->test_keys;
	}
}

class DeepLKeyManagerTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		// Stubs needed by the constructor chain (Database → $wpdb is handled by bootstrap).
		Functions\when( 'get_transient' )->justReturn( false );
		Functions\when( 'set_transient' )->justReturn( true );
		Functions\when( 'delete_transient' )->justReturn( true );
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_get_next_key_returns_null_when_no_keys(): void {
		$manager = new Testable_DeepL_Key_Manager();
		$manager->set_test_keys( [] );

		$this->assertNull( $manager->get_next_key() );
	}

	public function test_get_next_key_returns_key_with_most_remaining_quota(): void {
		$manager = new Testable_DeepL_Key_Manager();
		$manager->set_test_keys(
			[
				[ 'id' => 1, 'api_key' => 'key-a', 'quota_limit' => 500000, 'characters_used' => 400000 ], // 100k left
				[ 'id' => 2, 'api_key' => 'key-b', 'quota_limit' => 500000, 'characters_used' => 100000 ], // 400k left
				[ 'id' => 3, 'api_key' => 'key-c', 'quota_limit' => 500000, 'characters_used' => 250000 ], // 250k left
			]
		);

		$result = $manager->get_next_key();

		$this->assertNotNull( $result );
		$this->assertSame( 2, $result['id'] );
		$this->assertSame( 'key-b', $result['api_key'] );
	}

	public function test_get_next_key_works_with_single_key(): void {
		$manager = new Testable_DeepL_Key_Manager();
		$manager->set_test_keys(
			[
				[ 'id' => 5, 'api_key' => 'only-key', 'quota_limit' => 500000, 'characters_used' => 0 ],
			]
		);

		$result = $manager->get_next_key();

		$this->assertNotNull( $result );
		$this->assertSame( 5, $result['id'] );
	}

	public function test_get_next_key_uses_default_quota_when_field_missing(): void {
		// Keys without quota_limit / characters_used should still sort without errors.
		$manager = new Testable_DeepL_Key_Manager();
		$manager->set_test_keys(
			[
				[ 'id' => 1, 'api_key' => 'no-quota-field' ], // No quota_limit / characters_used
			]
		);

		$result = $manager->get_next_key();

		$this->assertNotNull( $result );
		$this->assertSame( 1, $result['id'] );
	}

	public function test_keys_with_equal_quota_remaining_both_eligible(): void {
		$manager = new Testable_DeepL_Key_Manager();
		$manager->set_test_keys(
			[
				[ 'id' => 1, 'api_key' => 'key-a', 'quota_limit' => 500000, 'characters_used' => 250000 ],
				[ 'id' => 2, 'api_key' => 'key-b', 'quota_limit' => 500000, 'characters_used' => 250000 ],
			]
		);

		$result = $manager->get_next_key();

		// One of the two must be returned; either is acceptable.
		$this->assertNotNull( $result );
		$this->assertContains( $result['id'], [ 1, 2 ] );
	}

	public function test_get_provider_name_returns_deepl(): void {
		$manager = new Testable_DeepL_Key_Manager();
		$this->assertSame( 'deepl', $manager->get_provider_name() );
	}
}
