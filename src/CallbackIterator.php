<?php

namespace WpBatcher;

class CallbackIterator extends BaseIterator {
	/**
	 * @param callable $fetcher
	 *
	 * @return self
	 */
	public function set_fetcher( callable $fetcher ) {
		$this->fetcher = $fetcher;
		return $this;
	}
}
