<?php

declare(strict_types=1);

namespace Syntatis\WPHook;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Filter
{
	/**
	 * The WordPress hook name.
	 *
	 * @phpstan-var non-empty-string
	 */
	protected string $name;

	protected int $priority;

	protected int $acceptedArgs;

	/** @phpstan-param non-empty-string $name */
	public function __construct(
		string $name,
		int $priority = 10,
		int $acceptedArgs = 1
	) {
		$this->name = $name;
		$this->priority = $priority;
		$this->acceptedArgs = $acceptedArgs;
	}

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
