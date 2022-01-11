<?php

namespace WpBatcher\Feature;

use WpBatcher\Events\AfterEachChunk;
use WpBatcher\Cleaners;

class WpdbQueriesLogCleaner extends Feature implements AfterEachChunk {
	public function afterEachChunk() {
		Cleaners::clear_wpdb_queries_log();
	}
}
