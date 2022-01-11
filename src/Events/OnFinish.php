<?php

namespace Versusbassz\WpBatcher\Events;

interface OnFinish {
	/**
	 * @return void
	 */
	public function onFinish();
}
