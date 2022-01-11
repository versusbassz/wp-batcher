<?php

namespace Versusbassz\WpBatcher\Tests;

use WP_Comment;
use WP_Term;
use WP_UnitTestCase;

use Versusbassz\WpBatcher\WpBatcher;

class WpBatcherTest extends WP_UnitTestCase {
	public function testGetPostsMethod() {
		log( '======================================== ::get_posts()' );

		wp_suspend_cache_addition( true );

		$items_qty = 30;
		self::factory()->post->create_many( $items_qty );

		wp_suspend_cache_addition( false );

		global $wp_actions;
		$wp_actions_count_before = count( $wp_actions );

		$iterable = WpBatcher::get_posts()->set_items_per_page( 5 );

		$memory_usage_before = memory_get_usage();

		log_value( 'before loop', $memory_usage_before );

		$iterations_count = 0;

		foreach ( $iterable as $item ) {
			++$iterations_count;
			update_post_meta( $item->ID, 'test_field', 'test_value' );
		}

		$memory_usage_after = memory_get_usage();

		log_value( 'end', $memory_usage_after );
		log_value( 'diff', $memory_usage_after - $memory_usage_before );

		$this->assertSame( $items_qty, $iterations_count );

		// test \WpBatcher\Cleaners::backup_wp_actions
		$this->assertSame( $wp_actions_count_before, count( $wp_actions ) );

		// test \WpBatcher\Cleaners::clear_wpdb_queries_log
		global $wpdb;
		$this->assertSame( [], $wpdb->queries );

		// there is no items without the field
		$no_items = new \WP_Query( [
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

		$this->assertSame( 0, $no_items->found_posts );

		// there are N items with the field
		$items = new \WP_Query( [
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

		$this->assertSame( $items_qty, $items->found_posts );
	}

	public function testGetUsersMethod() {
		log( '======================================== ::get_users()' );

		wp_suspend_cache_addition( true );

		$items_qty = 30;
		$items_qty_expected = $items_qty + 1; // since one user already in the DB
		self::factory()->user->create_many( $items_qty );

		wp_suspend_cache_addition( false );

		global $wp_actions;
		$wp_actions_count_before = count( $wp_actions );

		$iterable = WpBatcher::get_users()->set_items_per_page( 5 );

		$memory_usage_before = memory_get_usage();

		log_value( 'before loop', $memory_usage_before );

		$iterations_count = 0;

		foreach ( $iterable as $item ) {
			++$iterations_count;
			update_user_meta( $item->ID, 'test_field', 'test_value' );
		}

		$memory_usage_after = memory_get_usage();

		log_value( 'end', $memory_usage_after );
		log_value( 'diff', $memory_usage_after - $memory_usage_before );

		$this->assertSame( $items_qty_expected, $iterations_count );

		// test \WpBatcher\Cleaners::backup_wp_actions
		$this->assertSame( $wp_actions_count_before, count( $wp_actions ) );

		// test \WpBatcher\Cleaners::clear_wpdb_queries_log
		global $wpdb;
		$this->assertSame( [], $wpdb->queries );

		// there is no items without the field
		$no_items = get_users( [
			'meta_query' => [
				[
					'key' => 'test_field',
					'compare' => 'NOT EXISTS',
				],
			],
			'orderby' => 'ID',
			'order' => 'ASC',
			'number' => -1,
			'fields' => 'ids',
		] );

		$this->assertCount( 0, $no_items );

		// there are N items with the field
		$items = get_users( [
			'meta_query' => [
				[
					'key' => 'test_field',
					'value' => 'test_value',
				],
			],

			'nopaging' => true,
			'orderby' => 'ID',
			'order' => 'ASC',
			'fields' => 'ID',
		] );

		$this->assertCount( $items_qty_expected, $items );
	}

	public function testGetTermsMethod() {
		log( '======================================== ::get_terms()' );

		wp_suspend_cache_addition( true );

		$taxonomy = 'post_tag';
		$items_qty = 30;
		self::factory()->tag->create_many( $items_qty );

		wp_suspend_cache_addition( false );

		global $wp_actions;
		$wp_actions_count_before = count( $wp_actions );

		$iterable = WpBatcher::get_terms( [
			'taxonomy' => $taxonomy,
			'hide_empty' => false,
		] )->set_items_per_page( 5 );

		$memory_usage_before = memory_get_usage();

		log_value( 'before loop', $memory_usage_before );

		$iterations_count = 0;

		foreach ( $iterable as $item ) {
			/** @var WP_Term $item */
			++$iterations_count;
			update_term_meta( $item->term_id, 'test_field', 'test_value' );
		}

		$memory_usage_after = memory_get_usage();

		log_value( 'end', $memory_usage_after );
		log_value( 'diff', $memory_usage_after - $memory_usage_before );

		$this->assertSame( $items_qty, $iterations_count );

		// test \WpBatcher\Cleaners::backup_wp_actions
		$this->assertSame( $wp_actions_count_before, count( $wp_actions ) );

		// test \WpBatcher\Cleaners::clear_wpdb_queries_log
		global $wpdb;
		$this->assertSame( [], $wpdb->queries );

		// there is no items without the field
		$no_items = get_terms( [
			'taxonomy' => $taxonomy,

			'meta_query' => [
				[
					'key' => 'test_field',
					'compare' => 'NOT EXISTS',
				],
			],

			'number' => 0,
			'orderby' => 'term_id',
			'order' => 'ASC',
			'hide_empty' => false,
			'fields' => 'ids',
		] );

		$this->assertCount( 0, $no_items );

		// there are N items with the field
		$items = get_terms( [
			'taxonomy' => $taxonomy,

			'meta_query' => [
				[
					'key' => 'test_field',
					'value' => 'test_value',
				],
			],

			'number' => 0,
			'orderby' => 'term_id',
			'order' => 'ASC',
			'hide_empty' => false,
			'fields' => 'ids',
		] );

		$this->assertCount( $items_qty, $items );
	}

	public function testGetCommentsMethod() {
		log( '======================================== ::get_comments()' );

		wp_suspend_cache_addition( true );

		$items_qty = 30;
		self::factory()->comment->create_many( $items_qty );

		wp_suspend_cache_addition( false );

		global $wp_actions;
		$wp_actions_count_before = count( $wp_actions );

		$iterable = WpBatcher::get_comments()->set_items_per_page( 5 );

		$memory_usage_before = memory_get_usage();

		log_value( 'before loop', $memory_usage_before );

		$iterations_count = 0;

		foreach ( $iterable as $item ) {
			/** @var WP_Comment $item */
			++$iterations_count;
			update_comment_meta( $item->comment_ID, 'test_field', 'test_value' );
		}

		$memory_usage_after = memory_get_usage();

		log_value( 'end', $memory_usage_after );
		log_value( 'diff', $memory_usage_after - $memory_usage_before );

		$this->assertSame( $items_qty, $iterations_count );

		// test \WpBatcher\Cleaners::backup_wp_actions
		$this->assertSame( $wp_actions_count_before, count( $wp_actions ) );

		// test \WpBatcher\Cleaners::clear_wpdb_queries_log
		global $wpdb;
		$this->assertSame( [], $wpdb->queries );

		// there is no items without the field
		$no_items = get_comments( [
			'meta_query' => [
				[
					'key' => 'test_field',
					'compare' => 'NOT EXISTS',
				],
			],

			'number' => 0,
			'orderby' => 'comment_ID',
			'order' => 'ASC',
			'fields' => 'ids',
		] );

		$this->assertCount( 0, $no_items );

		// there are N items with the field
		$items = get_comments( [
			'meta_query' => [
				[
					'key' => 'test_field',
					'value' => 'test_value',
				],
			],

			'number' => 0,
			'orderby' => 'comment_ID',
			'order' => 'ASC',
			'fields' => 'ids',
		] );

		$this->assertCount( $items_qty, $items );
	}

	// ::callback() method is tested in other test cases more or less
}
