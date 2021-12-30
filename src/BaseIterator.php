<?php

namespace WpBatcher;

use Exception;
use Iterator;

abstract class BaseIterator implements Iterator {
	/**
	 * @var callable
	 */
	protected $fetcher;

	/**
	 * @var Iterator
	 */
	protected $chunk = [];

	/**
	 * @var int
	 */
	protected $chunk_position = 0;

	/**
	 * @var int
	 */
	protected $total_position = 0;

	/**
	 * @var bool
	 */
	protected $first_iteration_executed = false;

	/**
	 * @var int
	 */
	protected $paged = 0;

	/**
	 * @var int
	 */
	protected $items_per_page = 100;

	/**
	 * @var int
	 */
	protected $limit = 0;

	/**
	 * @var bool
	 */
	protected $changes_locked = false;

	/**
	 * Dumps the internal state of the object (for debugging/dev purposes)
	 *
	 * We don't use foreach here, because it uses same methods of "Iterator" interface and outputs chunks content
	 * instead of the own internal properties of the object
	 *
	 * @return array
	 */
	public function dump() {
		return [
			'chunk' => $this->chunk,
			'chunk_position' => $this->chunk_position,
			'total_position' => $this->total_position,
			'first_iteration_executed' => $this->first_iteration_executed,
			'paged' => $this->paged,
			'items_per_page' => $this->items_per_page,
			'limit' => $this->limit,
			'changes_locked' => $this->changes_locked,
		];
	}

	public function current() {
		if ( ! isset( $this->chunk[ $this->chunk_position ] ) ) {
			return null;
		}

		return $this->chunk[ $this->chunk_position ];
	}

	/**
	 * @return void
	 * @throws Exception
	 */
	public function next() {
		if ( $this->limit_exceed() ) {
			return;
		}

		if ( $this->first_iteration_executed ) {
			++$this->chunk_position;
		} else {
			$this->first_iteration_executed = true;
		}

		++$this->total_position;

		if ( ! isset( $this->chunk[ $this->chunk_position ] ) ) {
			++$this->paged;

			$this->chunk = $this->fetch_chunk();
			$this->chunk_position = 0;
		}
	}

	public function key() {
		return $this->total_position;
	}

	public function valid() {
		if ( $this->limit_exceed() ) {
			return false;
		}

		return isset( $this->chunk[ $this->chunk_position ] );
	}

	/**
	 * @return void
	 * @throws Exception
	 */
	public function rewind() {
		if ( $this->changes_locked ) {
			throw new Exception( 'It can be used only as generator, no rewind functionality' );
		}

		$this->changes_locked = true;
		$this->next();
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
	protected function limit_exceed() {
		return $this->has_limit() && $this->total_position > $this->limit;
	}
}
