<?php

declare(strict_types=1);

namespace Syntatis\WPHook\Support;

use ReflectionClass;
use Syntatis\WPHook\Action;
use Syntatis\WPHook\Contracts\Hookable;
use Syntatis\WPHook\Filter;
use Syntatis\WPHook\Registry;

use function is_callable;
use function str_starts_with;

/** @internal */
final class Parser implements Hookable
{
	private Registry $registry;

	private object $obj;

	// @phpstan-ignore-next-line
	private ReflectionClass $ref;

	public function __construct(object $obj)
	{
		$this->obj = $obj;
		$this->ref = new ReflectionClass($this->obj);
	}

	public function hook(Registry $registry): void
	{
		$this->registry = $registry;
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

			$this->registry->addAction(
				$instance->getName(),
				$this->obj,
				$instance->getPriority(),
				$instance->getAcceptedArgs(),
			);
		}

		foreach ($filters as $filter) {
			$instance = $filter->newInstance();

			$this->registry->addFilter(
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
			if (! $method->isPublic() || $method->isAbstract()) {
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

				$this->registry->addAction(
					$instance->getName(),
					$callback,
					$instance->getPriority(),
					$instance->getAcceptedArgs(),
				);
			}

			foreach ($filters as $filter) {
				$instance = $filter->newInstance();

				$this->registry->addFilter(
					$instance->getName(),
					$callback,
					$instance->getPriority(),
					$instance->getAcceptedArgs(),
				);
			}
		}
	}
}
