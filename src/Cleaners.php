<?php

/**
 * Grouped into the class just for autoloading.
 */

namespace WpBatcher;

class Cleaners {
	/**
	 * @_from ElasticPress -> \ElasticPress\Command::stop_the_insanity()
	 *
	 * WP_Query class adds filter get_term_metadata using its own instance
	 * what prevents WP_Query class from being destructed by PHP gc.
	 *
	 * if ( $q['update_post_term_cache'] ) {
	 *     add_filter( 'get_term_metadata', array( $this, 'lazyload_term_meta' ), 10, 2 );
	 * }
	 *
	 * It's high memory consuming as WP_Query instance holds all query results inside itself
	 * and in theory $wp_filter will not stop growing until Out Of Memory exception occurs.
	 *
	 *
	 * Upd: it seems an outdated issue. WP_Query doesn't contain add_filter() calls anymore.
	 * @see WP_Metadata_Lazyloader Sinse WP v4.5.0
	 *
	 * @return void
	 */
	public static function fix_wpquery_gc_problems() {
		global $wp_filter;

		if ( isset( $wp_filter['get_term_metadata'] ) ) {
			/*
			 * WordPress 4.7 has a new Hook infrastructure, so we need to make sure
			 * we're accessing the global array properly
			 */
			if ( class_exists( 'WP_Hook' ) && $wp_filter['get_term_metadata'] instanceof \WP_Hook ) {
				$filter_callbacks = &$wp_filter['get_term_metadata']->callbacks;
			} else {
				$filter_callbacks = &$wp_filter['get_term_metadata'];
			}
			if ( isset( $filter_callbacks[10] ) ) {
				foreach ( $filter_callbacks[10] as $hook => $content ) {
					if ( preg_match( '#^[0-9a-f]{32}lazyload_term_meta$#', $hook ) ) {
						unset( $filter_callbacks[10][ $hook ] );
					}
				}
			}
		}
	}
}
