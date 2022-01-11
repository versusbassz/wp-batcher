<?php

namespace Versusbassz\WpBatcher\Tests;

const DEBUG = false;

/**
 * dump_method( __METHOD__ );
 *
 * @param $method string
 *
 * @return void
 */
function dump_method( $method ) {
	if ( php_sapi_name() !== 'cli' ) {
		return;
	}

	echo PHP_EOL;
	var_dump( $method );
	echo PHP_EOL;
}

/**
 * @param $paged int
 * @param $posts_per_page int
 *
 * @return int[]
 */
function paged_range( $paged, $posts_per_page ) {
	$start = ( $posts_per_page * ( $paged - 1 ) ) + 1;
	$end = $start + ( $posts_per_page - 1 );

	return range( $start, $end );
}

/**
 * @param $paged int
 * @param $posts_per_page int
 *
 * @return int[]
 */
function get_filled_array( $paged, $posts_per_page ) {
	return array_fill( 0, $posts_per_page, 1 );
}

function log_value( $message, $value_raw ) {
	if ( ! DEBUG ) {
		return;
	}

	$value = round( $value_raw / ( 1 * 1000 * 1000 ), 3 );

	dump( "{$message} = {$value} MB" );
}

function log( $message ) {
	DEBUG && dump( $message );
}

function get_posts_count() {
	global $wpdb;
	return $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->posts}" );
}
