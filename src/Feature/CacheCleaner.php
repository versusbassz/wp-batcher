<?php

namespace WpBatcher\Feature;

use WpBatcher\Events\AfterEachChunk;
use WpBatcher\Cleaners;

class CacheCleaner extends Feature implements AfterEachChunk {
	public function afterEachChunk() {
		Cleaners::clear_object_cache();
	}
}
