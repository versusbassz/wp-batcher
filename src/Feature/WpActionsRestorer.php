<?php

namespace Versusbassz\WpBatcher\Feature;

use Versusbassz\WpBatcher\Cleaners;
use Versusbassz\WpBatcher\Events\OnStart;
use Versusbassz\WpBatcher\Events\AfterEachChunk;
use Versusbassz\WpBatcher\Events\OnFinish;

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
