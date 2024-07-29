<?php

declare(strict_types=1);

namespace Syntatis\WPHook\Exceptions;

use Exception;

use function sprintf;

class RefNotFoundException extends Exception
{
	public function __construct(string $ref)
	{
		parent::__construct(sprintf('Reference "%s" not found on the registry.', $ref));
	}
}
