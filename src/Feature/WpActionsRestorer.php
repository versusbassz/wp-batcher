<?php

namespace WpBatcher\Feature;

use WpBatcher\Cleaners;
use WpBatcher\Events\OnStart;
use WpBatcher\Events\AfterEachChunk;
use WpBatcher\Events\OnFinish;

class WpActionsRestorer extends Feature implements OnStart, AfterEachChunk, OnFinish {
	public function onStart() {
		Cleaners::backup_wp_actions();
	}

	public function AfterEachChunk() {
		Cleaners::restore_wp_actions();
	}

	public function onFinish() {
		Cleaners::clear_temporary_wp_actions();
	}
}
