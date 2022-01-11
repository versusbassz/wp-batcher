<?php

namespace Versusbassz\WpBatcher\Feature;

use Versusbassz\WpBatcher\Events\AfterEachChunk;
use Versusbassz\WpBatcher\Cleaners;

class WpQueryGcProblemsFixer extends Feature implements AfterEachChunk {
	public function afterEachChunk() {
		Cleaners::fix_wpquery_gc_problems();
	}
}
