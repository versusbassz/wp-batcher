<?php

exit;

/**
 * Just ideas about a possible interface of the library
 */

use WpBatcher\CallbackIterator;
use WpBatcher\WpBatcher;

// ==============================================================================
// The example of code without using the library

$paged = 1;

wp_suspend_cache_addition( true );

while ( true ) {
	$items = get_posts( [
		'posts_per_page' => 100,
		'paged' => $paged,
		'orderby' => 'ID',
		'order' => 'ASC',
	] );

	if ( ! count( $items ) ) {
		break;
	}

	foreach ( $items as $item ) {
		// Payload
	}

	++$paged;
}

wp_suspend_cache_addition( false );

// ==============================================================================
// Raw usage of CallbackIterator class

$iterator = (new CallbackIterator())
	->set_fetcher( function ( $paged, $items_per_page ) {
		return get_posts( [
			'paged' => $paged,
			'posts_per_page' => $items_per_page,
			'orderby' => 'ID',
			'order' => 'ASC',
		] );
	} )
	->set_items_per_page( 500 )
	->use_suspend_cache_addition();

foreach ( $items as $item ) {
	// Payload
}

// ==============================================================================
// Trying to build CallbackIterator using the helper (-1 line)

$iterator = callback_iterator( function ( $paged, $items_per_page ) {
		return get_posts( [
			'paged' => $paged,
			'posts_per_page' => $items_per_page,
			'orderby' => 'ID',
			'order' => 'ASC',
		] );
	} )
	->set_items_per_page( 500 )
	->use_suspend_cache_addition();

foreach ( $items as $item ) {
	// Payload
}

// ==============================================================================
// Building CallbackIterator using the shorthand helper (-3 lines), ugly...

$iterator = callback_iterator( fn ( $paged, $items_per_page ) => get_posts( [
		'paged' => $paged,
		'posts_per_page' => $items_per_page,
		'orderby' => 'ID',
		'order' => 'ASC',
	] ) )
	->set_items_per_page( 500 )
	->use_suspend_cache_addition();

foreach ( $items as $item ) {
	// Payload
}

// ==============================================================================
// Building CallbackIterator using the Fabric Method

$iterator = WpBatcher::get_posts( [
	'orderby' => 'ID',
	'order' => 'ASC',
] )
                     ->set_items_per_page( 500 )
                     ->use_suspend_cache_addition();

foreach ( $items as $item ) {
	// Payload
}

// ==============================================================================
// Prev + simplify (shorten) names of the methods (remove "use/set")

$iterator = WpBatcher::get_posts( [
	'orderby' => 'ID',
	'order' => 'ASC',
] )
                     ->items_per_page( 500 )
                     ->suspend_cache_addition();

foreach ( $items as $item ) {
	// Payload
}

// ==============================================================================
// Prev + default args

$iterator = WpBatcher::get_posts( [
] );

foreach ( $items as $item ) {
	// Payload
}

// ==============================================================================
// Prev + saving 1 extra line (the N1 sample for promotion)

$iterator = WpBatcher::get_posts();

foreach ( $items as $item ) {
	// Payload
}
