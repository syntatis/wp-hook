<?php

declare(strict_types=1);

namespace Syntatis\WPHook;

use ReflectionClass;
use Syntatis\WPHook\Contract\WithHook;

use function is_callable;

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

		$classAttrs = $this->ref->getAttributes();

		foreach ($classAttrs as $classAttr) {
			$instance = $classAttr->newInstance();

			if ($instance instanceof Action) {
				$this->hook->addAction(
					$instance->getName(),
					$this->obj,
					$instance->getPriority(),
					$instance->getAcceptedArgs(),
				);

				continue;
			}

			if ($instance instanceof Filter) {
				$this->hook->addFilter(
					$instance->getName(),
					$this->obj,
					$instance->getPriority(),
					$instance->getAcceptedArgs(),
				);
				continue;
			}
		}
	}

	private function parseMethodAttrs(): void
	{
		$methods = $this->ref->getMethods();

		foreach ($methods as $method) {
			$methodAttrs = $method->getAttributes();

			if (! $methodAttrs) {
				continue;
			}

			foreach ($methodAttrs as $methodAttr) {
				$callback = [$this->obj, $method->getName()];

				if (! is_callable($callback)) {
					continue;
				}

				$instance = $methodAttr->newInstance();

				if ($instance instanceof Action) {
					$this->hook->addAction(
						$instance->getName(),
						$callback,
						$instance->getPriority(),
						$instance->getAcceptedArgs(),
					);
					continue;
				}

				if ($instance instanceof Filter) {
					$this->hook->addFilter(
						$instance->getName(),
						$callback,
						$instance->getPriority(),
						$instance->getAcceptedArgs(),
					);
					continue;
				}
			}
		}
	}
}
