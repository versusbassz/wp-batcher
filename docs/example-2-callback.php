<?php

exit;

use Versusbassz\WpBatcher\CallbackIterator;

// Interface v1
$items = ( new CallbackIterator(function ( $paged, $posts_per_page ) {
	return new WP_Query( [
		'posts_per_page' => $posts_per_page,
		'paged' => $paged,
	] );
}) )
	->set_items_per_page( 100 )
	->enable_wp_loop();

$posts_with_thumbnails = 0;

foreach ( $items as $item ) {
	if ( has_post_thumbnail( get_the_ID() ) ) {
		++$posts_with_thumbnails;
	}
}

// Interface v2
$items = ( new CallbackIterator() )
	->set_fetcher( function ( $paged, $posts_per_page ) {
		return new WP_Query( [
			'posts_per_page' => $posts_per_page,
			'paged' => $paged,
		] );

		// or like this (the current callbackIterator implementation)
//		return (new WP_Query( [
//			'posts_per_page' => $posts_per_page,
//			'paged' => $paged,
//		] ))->posts;
	} )
	->set_items_per_page( 100 )
	->enable_wp_loop();

$posts_with_thumbnails = 0;

foreach ( $items as $item ) {
	if ( has_post_thumbnail( get_the_ID() ) ) {
		++$posts_with_thumbnails;
	}
}
