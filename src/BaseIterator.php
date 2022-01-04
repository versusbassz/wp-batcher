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

			$this->free_memory();
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
		Cleaners::clear_wpdb_queries_log();
		$this->restore_wp_actions();
		Cleaners::fix_wpquery_gc_problems();

		// TODO should we launch this method if $this->use_suspend_cache_addition === true ???
		Cleaners::clear_object_cache();
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
}
