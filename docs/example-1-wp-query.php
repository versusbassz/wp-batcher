<?php

exit;

// Regular usage

$items = new WP_Query( [
	'nopaging' => true,
] );

$posts_with_thumbnails = 0;

while ( $items->have_posts() ) {
	the_post();

	if ( has_post_thumbnail( get_the_ID() ) ) {
		++$posts_with_thumbnails;
	}
}

// Interface
// Parameters: the props "nopaging & paged" are rewritten internally
// to nopaging=false and current page

$items = (new WpQueryBatcher( [
	'posts_per_page' => 100,
] ))
->enable_wp_loop();

$posts_with_thumbnails = 0;

foreach ( $items as $item ) {
	if ( has_post_thumbnail( get_the_ID() ) ) {
		++$posts_with_thumbnails;
	}
}
