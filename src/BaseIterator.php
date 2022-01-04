<?php

namespace WpBatcher;

use Exception;
use Iterator;

abstract class BaseIterator implements Iterator {
	/**
	 * The callback (callable) for fetching chunks of items
	 *
	 * @var callable
	 */
	protected $fetcher;

	/**
	 * The current chunk of items we iterate over
	 * "chunk" is a part of "global list"
	 *
	 * @var Iterator
	 */
	protected $chunk = [];

	/**
	 * The position in the current chunk of items we iterate over
	 *
	 * @var int
	 */
	protected $chunk_position = 0;

	/**
	 * The position in the global list of items we iterate over
	 *
	 * @var int
	 */
	protected $total_position = 0;

	/**
	 * Has iteration over the object been started
	 *
	 * @var bool
	 */
	protected $loop_started = false;

	/**
	 * Has iteration over the object been finished
	 *
	 * @var bool
	 */
	protected $loop_finished = false;

	/**
	 * The current number of a chunk we fetch
	 *
	 * @var int
	 */
	protected $paged = 0;

	/**
	 * Length of each chunk (except the last one, probably)
	 *
	 * @var int
	 */
	protected $items_per_page = 100;

	/**
	 * The optional hard restriction of the total possible quantity of items in the "global list".
	 *
	 * @var int
	 */
	protected $limit = 0;

	/**
	 * If equals true it means that an iteration over items has started
	 * and it won't be possible to change settings of the iterator (via set*-methods)
	 *
	 * @var bool
	 */
	protected $changes_locked = false;

	/**
	 * The storage for preserving a state of global variable $wp_actions
	 *
	 * @var array
	 */
	protected $temporary_wp_actions = [];

	/**
	 * If true -> disable wp cache addition via wp_suspend_cache_addition() function
	 * during iterating over items (till a last item)
	 *
	 * @var bool
	 */
	protected $use_suspend_cache_addition = false;

	/**
	 * Dumps the internal state of the object (for debugging/dev purposes)
	 *
	 * We don't use foreach here, because it uses same methods of "Iterator" interface and outputs items of $this->chunk[]
	 * instead of outputting the internal properties of the object
	 *
	 * @return array
	 */
	public function dump() {
		return [
			'chunk' => $this->chunk,
			'chunk_position' => $this->chunk_position,
			'total_position' => $this->total_position,
			'loop_started' => $this->loop_started,
			'loop_finished' => $this->loop_finished,
			'paged' => $this->paged,
			'items_per_page' => $this->items_per_page,
			'limit' => $this->limit,
			'changes_locked' => $this->changes_locked,
			'temporary_wp_actions' => $this->temporary_wp_actions,
			'use_suspend_cache_addition' => $this->use_suspend_cache_addition,
		];
	}

	/**
	 * @return void
	 * @throws Exception
	 */
	public function rewind() {
		if ( $this->changes_locked ) {
			throw new Exception( 'Changes were locked before. Note: this object can be used only as a generator, no rewind functionality after 1st foreach iteration' );
		}

		$this->changes_locked = true;

		// onStart
		$this->backup_wp_actions();

		if ( $this->use_suspend_cache_addition ) {
			wp_suspend_cache_addition( true );
		}

		$this->next();
	}

	/**
	 * @return void
	 * @throws Exception
	 */
	public function next() {
		// handle N-th or 1st iteration
		if ( $this->loop_started ) {
			++$this->chunk_position;
			++$this->total_position;
		} else {
			$this->loop_started = true;
		}

		// check limit
		if ( $this->limit_exceeded() ) {
			$this->loop_finished = true;
			return;
		}

		// fetch a new chunk if it's necessary
		if ( ! isset( $this->chunk[ $this->chunk_position ] ) ) {
			++$this->paged;

			// don't run memory clearing on 1st chunk
			if ( $this->total_position >= 1 ) {
				$this->free_memory();
			}

			$this->chunk = $this->fetch_chunk();
			$this->chunk_position = 0;
		}

		// check that a new item/chunk is valid
		$is_valid = isset( $this->chunk[ $this->chunk_position ] );

		if ( ! $is_valid ) {
			$this->loop_finished = true;

			// onFinish
			if ( $this->use_suspend_cache_addition ) {
				wp_suspend_cache_addition( false );
			}
		}
	}

	public function valid() {
		return ! $this->loop_finished;
	}

	public function current() {
		if ( $this->loop_finished ) {
			return null;
		}

		return $this->chunk[ $this->chunk_position ];
	}

	public function key() {
		return $this->total_position;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	protected function fetch_chunk() {
		if ( ! is_callable( $this->fetcher ) ) {
			throw new Exception( 'Fetched is not provided' );
		}

		return call_user_func( $this->fetcher, $this->paged, $this->items_per_page );
	}

	/**
	 * @param $number int
	 *
	 * @return self
	 * @throws Exception
	 */
	public function set_items_per_page( $number ) {
		if ( $this->changes_locked ) {
			throw new Exception( 'The object can\'t be changed after first iteration' );
		}

		$this->items_per_page = $number;
		return $this;
	}

	/**
	 * @param $number int
	 *
	 * @return self
	 * @throws Exception
	 */
	public function set_limit( $number ) {
		if ( $this->changes_locked ) {
			throw new Exception( 'The object can\'t be changed after first iteration' );
		}

		$this->limit = $number;
		return $this;
	}

	/**
	 * @return bool
	 */
	protected function has_limit() {
		return (bool) $this->limit;
	}

	/**
	 * @return bool
	 */
	protected function limit_exceeded() {
		return $this->has_limit() && $this->total_position >= $this->limit;
	}

	/**
	 * Enable/disable applying of wp_suspend_cache_addition() function on start/end of a loop.
	 *
	 * @param bool $use
	 *
	 * @return self
	 */
	public function use_suspend_cache_addition( $use = true ) {
		$this->use_suspend_cache_addition = (bool) $use;
		return $this;
	}

	protected function free_memory() {
		$this->clear_wpdb_queries_log();
		$this->restore_wp_actions();
		$this->fix_wpquery_gc_problems();

		// TODO should we launch this method if $this->use_suspend_cache_addition === true ???
		$this->clear_object_cache();
	}

	/**
	 * @_from ElasticPress -> \ElasticPress\Command::stop_the_insanity()
	 *
	 * @return void
	 */
	protected function clear_wpdb_queries_log() {
		global $wpdb;

		$wpdb->queries = [];
	}

	protected function backup_wp_actions() {
		global $wp_actions;

		$this->temporary_wp_actions = $wp_actions;
	}

	/**
	 * @_from ElasticPress -> \ElasticPress\Command::stop_the_insanity()
	 *
	 * @return void
	 */
	protected function restore_wp_actions() {
		global $wp_actions;

		$wp_actions = $this->temporary_wp_actions;
	}

	/**
	 * @_from ElasticPress -> \ElasticPress\Command::stop_the_insanity()
	 *
	 * @return void
	 */
	protected function clear_object_cache() {
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
	protected function fix_wpquery_gc_problems() {
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
