<?php

namespace WpBatcher\Tests;

use PHPUnit\Framework\TestCase;

use WpBatcher\CallbackBatcher;

class BaseTest extends TestCase {
	public function testCallbackBatcher() {
		$iterator = (new CallbackBatcher())
			->set_fetcher( 'WpBatcher\\Tests\\paged_range' )
			->set_items_per_page( 3 )
			->set_limit( 10 );

		$actual = [];

		foreach ( $iterator as $item ) {
			$actual[] = $item;
		}

		$this->assertSame( range( 1, 10 ), $actual );
		$this->assertSame( 11, $iterator->key() );
		$this->assertSame( 11, $iterator->current() ); // TODO null ???
		$this->assertFalse( $iterator->valid() );
	}

	/** @dataProvider providePagedRangeHelperData */
	public function testPagedRangeHelper( $start, $item_per_page, $expected ) {
		$this->assertSame( $expected, paged_range( $start, $item_per_page ) );
	}

	public function providePagedRangeHelperData(  ) {
		return [
			[1, 1, [1]],
			[1, 3, [1, 2, 3]],
			[2, 3, [4, 5, 6]],
			[3, 3, [7, 8, 9]],
			[1, 5, [1, 2, 3, 4, 5]],
			[2, 5, [6, 7, 8, 9, 10]],
		];
	}

	/** @dataProvider provideGetFilledArrayData */
	public function testGetFilledArrayHelper( $start, $item_per_page, $expected ) {
		$this->assertSame( $expected, get_filled_array( $start, $item_per_page ) );
	}

	public function provideGetFilledArrayData(  ) {
		return [
			[1, 1, [1]],
			[1, 3, [1, 1, 1]],
			[2, 3, [1, 1, 1]],
			[3, 3, [1, 1, 1]],
			[1, 5, [1, 1, 1, 1, 1]],
			[2, 5, [1, 1, 1, 1, 1]],
		];
	}

	public function testDump() {
		$iterator = (new CallbackBatcher())
			->set_fetcher( 'WpBatcher\\Tests\\paged_range' )
			->set_items_per_page( 3 )
			->set_limit( 10 );

		$expected = [
			'chunk' => [],
			'chunk_position' => 0,
			'total_position' => 0,
			'first_iteration_executed' => false,
			'paged' => 0,
			'items_per_page' => 3,
			'limit' => 10,
			'changes_locked' => false,
			'temporary_wp_actions' => [],
			'use_suspend_cache_addition' => false,
		];

		$this->assertSame( $expected, $iterator->dump() );
	}
}
