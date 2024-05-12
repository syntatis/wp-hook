<?php

declare(strict_types=1);

namespace Syntatis\WPHook;

use Attribute;
use Syntatis\WPHook\Attributes\Model;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Filter extends Model
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
}
