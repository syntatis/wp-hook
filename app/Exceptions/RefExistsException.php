<?php

declare(strict_types=1);

namespace Syntatis\WPHook\Exceptions;

use Exception;

use function sprintf;

class RefExistsException extends Exception
{
	public function __construct(string $ref)
	{
		parent::__construct(sprintf('Reference "%s" already exists on the registry.', $ref));
	}
}
