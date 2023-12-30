<?php

declare(strict_types=1);

namespace Syntatis\WP\Hook\Attributes;

abstract class Model
{
	/**
	 * The WordPress hook name.
	 */
	protected string $name;

	protected int $priority;

	protected int $acceptedArgs;

	public function getName(): string
	{
		return $this->name;
	}

	public function getPriority(): int
	{
		return $this->priority;
	}

	public function getAcceptedArgs(): int
	{
		return $this->acceptedArgs;
	}
}
