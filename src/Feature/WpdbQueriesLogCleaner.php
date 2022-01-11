<?php

namespace Versusbassz\WpBatcher\Feature;

use Versusbassz\WpBatcher\Events\AfterEachChunk;
use Versusbassz\WpBatcher\Cleaners;

class WpdbQueriesLogCleaner extends Feature implements AfterEachChunk {
	public function afterEachChunk() {
		Cleaners::clear_wpdb_queries_log();
	}
}
