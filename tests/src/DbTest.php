<?php

namespace Versusbassz\WpBatcher\Tests;

use Versusbassz\WpBatcher\WpBatcher;

class DbTest extends \WP_UnitTestCase {
	public function testSimpleWpQueryMemoryConsumption() {
		log( '========================================' );

		log_value( 'start', memory_get_usage() );
		wp_suspend_cache_addition( true );

		$posts_qty = 500;
		self::factory()->post->create_many( $posts_qty );

		wp_suspend_cache_addition( false );
		log_value( 'added', memory_get_usage() );

		$this->assertSame( (string) $posts_qty, get_posts_count() );

		$memory_usage_before = memory_get_usage();

		$posts_query = new \WP_Query( [
			'post_type' => [ 'post' ],
			'post_status' => [ 'any' ],
			'nopaging' => true,
		] );

		log_value( 'after wp_query', memory_get_usage() );

		foreach ( $posts_query->posts as $post ) {
			has_post_thumbnail( $post->ID);
		}

		$memory_usage_after = memory_get_usage();

		log_value( 'after loop', $memory_usage_after );

		log_value( 'diff', $memory_usage_after - $memory_usage_before );
		$this->assertGreaterThan( 1 * 1000 * 1000, $memory_usage_after - $memory_usage_before );

		unset( $posts_query, $post );
		gc_collect_cycles();
		wp_cache_flush();

		log_value( 'after manual cleaning', memory_get_usage() );
	}

	public function testLibraryMemoryConsumption() {
		log( '========================================' );
		log_value( 'start', memory_get_usage() );

		wp_suspend_cache_addition( true );

		$posts_qty = 500;
		self::factory()->post->create_many( $posts_qty );

		wp_suspend_cache_addition( false );
		log_value( 'added' , memory_get_usage() );

		$this->assertSame( (string) $posts_qty, get_posts_count() );

		$memory_usage_before = memory_get_usage();

		$iterable = WpBatcher::callback( function ( $paged, $posts_per_page ) {
				$query = new \WP_Query( [
					'post_type' => [ 'post' ],
					'post_status' => [ 'any' ],

					'paged' => $paged,
					'posts_per_page' => $posts_per_page,
				] );

				return $query->posts;
			} )
			->set_items_per_page( 50 );

		log_value( 'before loop', memory_get_usage() );

		$iterations_count = 0;

		foreach ( $iterable as $post ) {
			++$iterations_count;
			has_post_thumbnail( $post->ID);
		}

		$memory_usage_after = memory_get_usage();

		log_value( 'end', $memory_usage_after );
		log_value( 'diff', $memory_usage_after - $memory_usage_before );

		$this->assertSame( $posts_qty, $iterations_count );

		$this->assertLessThan( 500 * 1000, $memory_usage_after - $memory_usage_before );
	}

	public function testDataConsistency() {
		log( '========================================' );

		wp_suspend_cache_addition( true );

		$posts_qty = 30;
		self::factory()->post->create_many( $posts_qty );

		wp_suspend_cache_addition( false );

		$this->assertSame( (string) $posts_qty, get_posts_count() );

		global $wp_actions;
		$wp_actions_count_before = count( $wp_actions );

		$iterable = WpBatcher::callback( function ( $paged, $posts_per_page ) {
				$query = new \WP_Query( [
					'post_type' => [ 'post' ],
					'post_status' => [ 'any' ],

					'paged' => $paged,
					'posts_per_page' => $posts_per_page,
					'orderby' => 'ID',
					'order' => 'ASC',
				] );

				return $query->posts;
			} )
			->set_items_per_page( 5 );

		$memory_usage_before = memory_get_usage();

		log_value( 'before loop', $memory_usage_before );

		$iterations_count = 0;

		foreach ( $iterable as $post ) {
			++$iterations_count;
			$update_result = update_post_meta( $post->ID, 'test_field', 'test_value' );
		}

		$memory_usage_after = memory_get_usage();

		log_value( 'end', $memory_usage_after );
		log_value( 'diff', $memory_usage_after - $memory_usage_before );

		$this->assertSame( $posts_qty, $iterations_count );

		// test \WpBatcher\Cleaners::backup_wp_actions
		$this->assertSame( $wp_actions_count_before, count( $wp_actions ) );

		// test \WpBatcher\Cleaners::clear_wpdb_queries_log
		global $wpdb;
		$this->assertSame( [], $wpdb->queries );

		// there is no posts without the field
		$posts = new \WP_Query( [
			'post_type' => [ 'post' ],
			'post_status' => [ 'any' ],

			'meta_query' => [
				[
					'key' => 'test_field',
					'compare' => 'NOT EXISTS',
				],
			],

			'nopaging' => true,
			'orderby' => 'ID',
			'order' => 'ASC',
			'fields' => 'ids',
		] );

		$this->assertSame( 0, $posts->found_posts );

		// there are N posts with the field
		$posts = new \WP_Query( [
			'post_type' => [ 'post' ],
			'post_status' => [ 'any' ],

			'meta_query' => [
				[
					'key' => 'test_field',
					'value' => 'test_value',
				],
			],

			'nopaging' => true,
			'orderby' => 'ID',
			'order' => 'ASC',
			'fields' => 'ids',
		] );

		$this->assertSame( 30, $posts->found_posts );
	}
}
