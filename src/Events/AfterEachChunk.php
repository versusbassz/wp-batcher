<?php

namespace WpBatcher\Events;

interface AfterEachChunk {
	/**
	 * @return void
	 */
	public function afterEachChunk();
}
