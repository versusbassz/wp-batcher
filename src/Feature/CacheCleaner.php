<?php

namespace Versusbassz\WpBatcher\Feature;

use Versusbassz\WpBatcher\Events\AfterEachChunk;
use Versusbassz\WpBatcher\Cleaners;

class CacheCleaner extends Feature implements AfterEachChunk {
	public function afterEachChunk() {
		Cleaners::clear_object_cache();
	}
}
