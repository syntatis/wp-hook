<?php

declare(strict_types=1);

namespace Syntatis\WPHook\Contracts;

use Syntatis\WPHook\Registry;

interface Hookable
{
	/**
	 * Add WordPress hooks to run.
	 */
	public function hook(Registry $registry): void;
}
