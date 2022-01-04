<?php

/**
 * Grouped into the class just for autoloading.
 */

namespace WpBatcher;

class Cleaners {
	/**
	 * @_from ElasticPress -> \ElasticPress\Command::stop_the_insanity()
	 *
	 * @return void
	 */
	public static function clear_wpdb_queries_log() {
		global $wpdb;

		$wpdb->queries = [];
	}

	/**
	 * The storage for preserving a state of global variable $wp_actions
	 *
	 * @var array
	 *
	 * @_from ElasticPress -> \ElasticPress\Command::stop_the_insanity()\
	 */
	protected static $temporary_wp_actions = [];

	/**
	 * @return void
	 */
	public static function backup_wp_actions() {
		global $wp_actions;

		self::$temporary_wp_actions = $wp_actions;
	}

	/**
	 * @return void
	 */
	public static function restore_wp_actions() {
		global $wp_actions;

		$wp_actions = self::$temporary_wp_actions;
	}

	/**
	 * @return array
	 */
	public static function get_temporary_wp_actions() {
		return self::$temporary_wp_actions;
	}

	/**
	 * Use it only after a loop is finished!
	 *
	 * @return void
	 */
	public static function clear_temporary_wp_actions() {
		self::$temporary_wp_actions = [];
	}

	/**
	 * @_from ElasticPress -> \ElasticPress\Command::stop_the_insanity()
	 *
	 * @return void
	 */
	public static function clear_object_cache() {
		global $wp_object_cache;

		if ( is_object( $wp_object_cache ) ) {
			// TODO what is about PHP v8.1 ? (dynamic props deprecation)
			$wp_object_cache->group_ops = [];
			$wp_object_cache->stats = [];
			$wp_object_cache->memcache_debug = [];

			// Make sure this is a public property, before trying to clear it.
			try {
				$cache_property = new \ReflectionProperty( $wp_object_cache, 'cache' );
				if ( $cache_property->isPublic() ) {
					$wp_object_cache->cache = [];
				}
				unset( $cache_property );
			} catch ( \ReflectionException $e ) {
				// No need to catch.
			}

			/*
			 * In the case where we're not using an external object cache, we need to call flush on the default
			 * WordPress object cache class to clear the values from the cache property
			 */
			if ( ! wp_using_ext_object_cache() ) {
				wp_cache_flush();
			}

			// @see https://core.trac.wordpress.org/ticket/31463#comment:4
			if ( method_exists( $wp_object_cache, '__remoteset' ) ) {
				call_user_func( [ $wp_object_cache, '__remoteset' ] );
			}
		}
	}

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
