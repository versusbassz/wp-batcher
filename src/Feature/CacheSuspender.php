<?php

namespace Versusbassz\WpBatcher\Feature;

use Versusbassz\WpBatcher\Events\OnStart;
use Versusbassz\WpBatcher\Events\OnFinish;

class CacheSuspender extends Feature implements OnStart, OnFinish {
	public function onStart() {
		wp_suspend_cache_addition( true );
	}

	public function onFinish() {
		wp_suspend_cache_addition( false );
	}
}
