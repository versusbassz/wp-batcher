<?php

require dirname( __DIR__ ) . '/vendor/autoload.php';

$limit = 1000 * 1000 * 1000;

$countdown_start = microtime( true );

$count = 0;

while ( $count <= $limit ) {
	++$count;
}

$countdown_end = microtime( true );

$min_int = PHP_INT_MIN;
$max_int = PHP_INT_MAX;

dump( "Limit: {$limit}" );
dump( "PHP_INT_MIN: {$min_int}" );
dump( "PHP_INT_MAX: {$max_int}" );

dump( 'Count value =====' );
dump( $count );
dump( 'Memory =====' );
dump( memory_get_peak_usage() );
dump( ( round( memory_get_peak_usage() / 1000000, 2 ) ) . ' MB' );
dump( 'Time =====' );
dump( ( round( $countdown_end - $countdown_start, 3 ) ) . ' sec' );
