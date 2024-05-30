<?php

declare(strict_types=1);

namespace Syntatis\WPHook;

use ReflectionClass;
use Syntatis\WPHook\Contract\WithHook;

use function is_callable;
use function str_starts_with;

/** @internal */
final class Parser implements WithHook
{
	private Hook $hook;

	private object $obj;

	// @phpstan-ignore-next-line
	private ReflectionClass $ref;

	public function __construct(object $obj)
	{
		$this->obj = $obj;
		$this->ref = new ReflectionClass($this->obj);
	}

	public function hook(Hook $hook): void
	{
		$this->hook = $hook;
	}

	public function parse(): void
	{
		$this->parseClassAttrs();
		$this->parseMethodAttrs();
	}

	private function parseClassAttrs(): void
	{
		/**
		 * A callable object is a class with an __invoke method.
		 */
		if (! is_callable($this->obj)) {
			return;
		}

		$actions = $this->ref->getAttributes(Action::class);
		$filters = $this->ref->getAttributes(Filter::class);

		foreach ($actions as $action) {
			$instance = $action->newInstance();

			$this->hook->addAction(
				$instance->getName(),
				$this->obj,
				$instance->getPriority(),
				$instance->getAcceptedArgs(),
			);
		}

		foreach ($filters as $filter) {
			$instance = $filter->newInstance();

			$this->hook->addFilter(
				$instance->getName(),
				$this->obj,
				$instance->getPriority(),
				$instance->getAcceptedArgs(),
			);
		}
	}

	private function parseMethodAttrs(): void
	{
		$methods = $this->ref->getMethods();

		foreach ($methods as $method) {
			if (! $method->isPublic()) {
				continue;
			}

			if ($method->isConstructor() || $method->isDestructor() || str_starts_with($method->getName(), '__')) {
				continue;
			}

			$callback = [$this->obj, $method->getName()];

			if (! is_callable($callback)) {
				continue;
			}

			$actions = $method->getAttributes(Action::class);
			$filters = $method->getAttributes(Filter::class);

			foreach ($actions as $action) {
				$instance = $action->newInstance();

				$this->hook->addAction(
					$instance->getName(),
					$callback,
					$instance->getPriority(),
					$instance->getAcceptedArgs(),
				);
			}

			foreach ($filters as $filter) {
				$instance = $filter->newInstance();

				$this->hook->addFilter(
					$instance->getName(),
					$callback,
					$instance->getPriority(),
					$instance->getAcceptedArgs(),
				);
			}
		}
	}
}
