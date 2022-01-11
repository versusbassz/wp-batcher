<?php

namespace WpBatcher\Events;

interface OnFinish {
	/**
	 * @return void
	 */
	public function onFinish();
}
