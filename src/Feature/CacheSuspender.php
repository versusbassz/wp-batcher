<?php

namespace WpBatcher\Feature;

use WpBatcher\Events\OnStart;
use WpBatcher\Events\OnFinish;

class CacheSuspender extends Feature implements OnStart, OnFinish {
	public function onStart() {
		wp_suspend_cache_addition( true );
	}

	public function onFinish() {
		wp_suspend_cache_addition( false );
	}
}
