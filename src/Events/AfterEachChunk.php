<?php

namespace Versusbassz\WpBatcher\Events;

interface AfterEachChunk {
	/**
	 * @return void
	 */
	public function afterEachChunk();
}
