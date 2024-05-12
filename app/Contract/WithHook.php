<?php

declare(strict_types=1);

namespace Syntatis\WPHook\Contract;

use Syntatis\WPHook\Hook;

interface WithHook
{
	/**
	 * Add WordPress hooks to run.
	 */
	public function hook(Hook $hook): void;
}
