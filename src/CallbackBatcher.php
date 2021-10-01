<?php

namespace WpBatcher;

class CallbackBatcher extends BaseIterator {
	/**
	 * @var callable|null
	 */
	protected $fetcher;

	public function set_fetcher( callable $fetcher ) {
		$this->fetcher = $fetcher;
		return $this;
	}
}
