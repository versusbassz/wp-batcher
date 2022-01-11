<?php

namespace Versusbassz\WpBatcher\Tests;

use PHPUnit\Framework\TestCase;

use Versusbassz\WpBatcher\WpBatcher;
use Versusbassz\WpBatcher\CallbackIterator;
use Versusbassz\WpBatcher\Feature\CacheCleaner;
use Versusbassz\WpBatcher\Feature\WpActionsRestorer;
use Versusbassz\WpBatcher\Feature\WpdbQueriesLogCleaner;
use Versusbassz\WpBatcher\Feature\WpQueryGcProblemsFixer;

class BaseTest extends TestCase {
	public function testCallbackBatcher() {
		$iterator = (new CallbackIterator())
			->set_fetcher( 'Versusbassz\\WpBatcher\\Tests\\paged_range' )
			->set_items_per_page( 3 )
			->set_limit( 10 );

		$actual = [];

		foreach ( $iterator as $key => $item ) {
			$actual[] = $item;
		}

		$this->assertSame( range( 1, 10 ), $actual );
		$this->assertSame( 10, $iterator->key() );
		$this->assertNull( $iterator->current() );
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

	public function testDumpSimple() {
		$iterator = (new CallbackIterator())
			->set_fetcher( 'Versusbassz\\WpBatcher\\Tests\\paged_range' )
			->set_items_per_page( 3 )
			->set_limit( 10 );

		$expected = [
			'chunk' => [],
			'chunk_position' => 0,
			'total_position' => 0,
			'loop_started' => false,
			'loop_finished' => false,
			'paged' => 0,
			'items_per_page' => 3,
			'limit' => 10,
			'changes_locked' => false,
			'features' => [],
			'handlers' => [
				'onStart' => [],
				'afterEachChunk' => [],
				'onFinish' => []
			],
		];

		$this->assertSame( $expected, $iterator->dump() );
	}

	public function testDumpComplex() {
		$iterator = WpBatcher::callback( 'Versusbassz\\WpBatcher\\Tests\\paged_range' )
		                     ->set_items_per_page( 3 )
		                     ->set_limit( 10 );

		$expected = [
			'chunk' => [],
			'chunk_position' => 0,
			'total_position' => 0,
			'loop_started' => false,
			'loop_finished' => false,
			'paged' => 0,
			'items_per_page' => 3,
			'limit' => 10,
			'changes_locked' => false,
			'features' => [
				CacheCleaner::class,
				WpQueryGcProblemsFixer::class,
				WpdbQueriesLogCleaner::class,
				WpActionsRestorer::class,
			],
			'handlers' => [
				'onStart' => [
					WpActionsRestorer::class,
				],
				'afterEachChunk' => [
					CacheCleaner::class,
					WpQueryGcProblemsFixer::class,
					WpdbQueriesLogCleaner::class,
					WpActionsRestorer::class,
				],
				'onFinish' => [
					WpActionsRestorer::class,
				],
			],
		];

		$this->assertSame( $expected, $iterator->dump() );
	}

	public function testActionsOnFeatures() {
		$iterator = (new CallbackIterator())
			->set_fetcher( 'Versusbassz\\WpBatcher\\Tests\\paged_range' )
			->set_limit( 10 );

		// Empty
		$state = $iterator->dump();

		$this->assertCount( 0, $state['features'] );
		$this->assertCount( 0, $state['handlers']['onStart'] );
		$this->assertCount( 0, $state['handlers']['afterEachChunk'] );
		$this->assertCount( 0, $state['handlers']['onFinish'] );

		// Add feature
		$feature = new CacheCleaner();
		$iterator->add_feature( $feature );

		$state = $iterator->dump();

		$this->assertCount( 1, $state['features'] );
		$this->assertCount( 0, $state['handlers']['onStart'] );
		$this->assertCount( 1, $state['handlers']['afterEachChunk'] );
		$this->assertCount( 0, $state['handlers']['onFinish'] );

		// Remove feature
		$iterator->remove_feature( $feature->get_name() );

		$state = $iterator->dump();

		$this->assertCount( 0, $state['features'] );
		$this->assertCount( 0, $state['handlers']['onStart'] );
		$this->assertCount( 0, $state['handlers']['afterEachChunk'] );
		$this->assertCount( 0, $state['handlers']['onFinish'] );

		// use_cache_suspending
		$iterator->use_cache_suspending();

		$state = $iterator->dump();

		$this->assertCount( 1, $state['features'] );
		$this->assertCount( 1, $state['handlers']['onStart'] );
		$this->assertCount( 0, $state['handlers']['afterEachChunk'] );
		$this->assertCount( 1, $state['handlers']['onFinish'] );

		// use_cache_clearing
		$iterator->use_cache_clearing();

		$state = $iterator->dump();

		$this->assertCount( 1, $state['features'] );
		$this->assertCount( 0, $state['handlers']['onStart'] );
		$this->assertCount( 1, $state['handlers']['afterEachChunk'] );
		$this->assertCount( 0, $state['handlers']['onFinish'] );

		// and again "use_cache_suspending" (to check the correct removing of "use_cache_clearing")
		$iterator->use_cache_suspending();

		$state = $iterator->dump();

		$this->assertCount( 1, $state['features'] );
		$this->assertCount( 1, $state['handlers']['onStart'] );
		$this->assertCount( 0, $state['handlers']['afterEachChunk'] );
		$this->assertCount( 1, $state['handlers']['onFinish'] );
	}
}
