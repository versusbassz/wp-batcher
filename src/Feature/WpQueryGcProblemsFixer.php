<?php

namespace WpBatcher\Feature;

use WpBatcher\Events\AfterEachChunk;
use WpBatcher\Cleaners;

class WpQueryGcProblemsFixer extends Feature implements AfterEachChunk {
	public function afterEachChunk() {
		Cleaners::fix_wpquery_gc_problems();
	}
}
