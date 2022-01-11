<?php

namespace Versusbassz\WpBatcher;

use Exception;
use Iterator;

use Versusbassz\WpBatcher\Events\OnStart;
use Versusbassz\WpBatcher\Events\AfterEachChunk;
use Versusbassz\WpBatcher\Events\OnFinish;
use Versusbassz\WpBatcher\Feature\Feature;
use Versusbassz\WpBatcher\Feature\CacheCleaner;
use Versusbassz\WpBatcher\Feature\CacheSuspender;

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
	 * @var Feature[]
	 */
	protected $features = [];

	/**
	 * @var array
	 */
	protected $handlers = [
		'onStart' => [],
		'afterEachChunk' => [],
		'onFinish' => [],
	];

	/**
	 * Dumps the internal state of the object (for debugging/dev purposes)
	 *
	 * We don't use foreach here, because it uses same methods of "Iterator" interface and outputs items of $this->chunk[]
	 * instead of outputting the internal properties of the object
	 *
	 * @return array
	 */
	public function dump() {
		$get_name = function ( Feature $item ) {
			return $item->get_name();
		};

		$handlers = [];

		foreach ( $this->handlers as $event => $handlers_list ) {
			$handlers[ $event ] = array_values( array_map( $get_name, $handlers_list ) );
		}

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
			'features' => array_values( array_map( $get_name, $this->features ) ),
			'handlers' => $handlers,
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

		$this->do_event( 'onStart' );

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
				$this->do_event( 'afterEachChunk' );
			}

			$this->chunk = $this->fetch_chunk();
			$this->chunk_position = 0;
		}

		// wrap up if chunk/item//position are not valid
		$is_valid = isset( $this->chunk[ $this->chunk_position ] );

		if ( ! $is_valid ) {
			$this->loop_finished = true;

			$this->do_event( 'afterEachChunk' );
			$this->do_event( 'onFinish' );
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

	protected function do_event( $event_name ) {
		if ( ! isset( $this->handlers[ $event_name ] ) || ! count( $this->handlers[ $event_name ] ) ) {
			return;
		}

		foreach ( $this->handlers[ $event_name ] as $handler ) {
			call_user_func( [ $handler, $event_name ] );
		}
	}

	/**
	 * @param Feature $feature
	 *
	 * @return self
	 */
	public function add_feature( Feature $feature ) {
		$feature_name = $feature->get_name();

		if ( isset( $this->features[ $feature_name ] ) ) {
			return $this;
		}

		$events = self::get_events_info();

		$at_least_one_event_added = false;

		foreach ( $events as $event ) {
			if ( ! is_a( $feature, $event['interface'] ) ) {
				continue;
			}

			if ( ! isset( $this->handlers[ $event['method'] ] ) ) {
				$this->handlers[ $event['method'] ] = [];
			}

			$at_least_one_event_added = true;
			$this->handlers[ $event['method'] ][ $feature_name ] = $feature;
		}

		if ( ! $at_least_one_event_added ) {
			throw new Exception( 'There are no event handlers in the provided Feature object' );
		}

		$this->features[ $feature_name ] = $feature;

		return $this;
	}

	/**
	 * @param string $feature_name
	 *
	 * @return self
	 */
	public function remove_feature( $feature_name ) {
		if ( ! isset( $this->features[ $feature_name ] ) ) {
			return $this;
		}

		$events = self::get_events_info();

		foreach ( $events as $event ) {
			if ( isset( $this->handlers[ $event['method'] ][ $feature_name ] ) ) {
				unset( $this->handlers[ $event['method'] ][ $feature_name ] );
			}
		}

		unset( $this->features[ $feature_name ] );

		return $this;
	}

	/**
	 * @return self
	 */
	public function use_cache_suspending() {
		$this->remove_feature( CacheCleaner::class );
		$this->add_feature( new CacheSuspender() );

		return $this;
	}

	/**
	 * @return self
	 */
	public function use_cache_clearing() {
		$this->remove_feature( CacheSuspender::class );
		$this->add_feature( new CacheCleaner() );

		return $this;
	}

	protected static function get_events_info() {
		return [
			[ 'interface' => OnStart::class, 'method' => 'onStart', ],
			[ 'interface' => OnFinish::class, 'method' => 'onFinish', ],
			[ 'interface' => AfterEachChunk::class, 'method' => 'afterEachChunk', ],
		];
	}
}
