<?php

declare(strict_types=1);

namespace Syntatis\WP\Hook\Contract;

use Syntatis\WP\Hook\Hook;

interface WithHook
{
	/**
	 * Add WordPress hooks to run.
	 */
	public function hook(Hook $hook): void;
}
