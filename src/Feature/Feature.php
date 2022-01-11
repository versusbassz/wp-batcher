<?php

namespace WpBatcher\Feature;

abstract class Feature {
	/**
	 * @return string
	 */
	public function get_name() {
		return static::class;
	}
}
