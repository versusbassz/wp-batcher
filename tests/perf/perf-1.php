<?php

// time php ./tests/perf/perf-1.php custom|regular

use WpBatcher\CallbackIterator;

require dirname( __DIR__, 2 ) . '/vendor/autoload.php';
require dirname( __DIR__, 2 ) . '/tests/helpers.php';

if ( ! in_array( $argv[1], ['custom', 'regular'] ) ) {
	exit( 'Incorrect type' );
}

$limit = 1000 * 1000 * 1000;

$countdown_start = microtime( true );

switch ( $argv[1] ) {
	case 'custom':
		$iterable = (new CallbackIterator())
			->set_fetcher( 'WpBatcher\\Tests\\paged_range' )
//			->set_fetcher( 'WpBatcher\\Tests\\get_filled_array' )
			->set_limit( $limit )
			->set_items_per_page( 100 );
		break;

	case 'regular':
		$iterable = range( 1, $limit );
		break;
}

$count = 0;

foreach ( $iterable as $item ) {
	++$count;
}

$last_item = $item;

$countdown_end = microtime( true );


$min_int = PHP_INT_MIN;
$max_int = PHP_INT_MAX;

dump( "Limit: {$limit}" );
dump( "PHP_INT_MIN: {$min_int}" );
dump( "PHP_INT_MAX: {$max_int}" );

dump( 'Last item =====' );
dump( $last_item );
dump( 'Count =====' );
dump( $count );
dump( 'Memory =====' );
dump( memory_get_peak_usage() );
dump( ( round( memory_get_peak_usage() / 1000000, 2 ) ) . ' MB' );
dump( 'Time =====' );
dump( ( round( $countdown_end - $countdown_start, 3 ) ) . ' sec' );
