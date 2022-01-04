<?php

/**
 * Just ideas about a possible interface of the library
 */

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

$iterator = (new CallbackBatcher())
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

$iterator = BulkIterator::get_posts( [
	'orderby' => 'ID',
	'order' => 'ASC',
] )
	->set_items_per_page( 500 )
	->use_suspend_cache_addition();

foreach ( $items as $item ) {
	// Payload
}

// ==============================================================================

$iterator = BulkIterator::get_posts( [
	'orderby' => 'ID',
	'order' => 'ASC',
] )
	->items_per_page( 500 )
	->suspend_cache_addition();

foreach ( $items as $item ) {
	// Payload
}

// ==============================================================================

$iterator = BulkIterator::get_posts( [
	'orderby' => 'ID',
	'order' => 'ASC',
] );

foreach ( $items as $item ) {
	// Payload
}
