<?php

declare(strict_types=1);

namespace Syntatis\WP\Hook\Attributes;

use ReflectionClass;
use Syntatis\WP\Hook\Action;
use Syntatis\WP\Hook\Filter;
use Syntatis\WP\Hook\Hook;

use function is_callable;

final class Parser
{
	private Hook $hook;

	private object $obj;

	private ReflectionClass $ref;

	public function __construct(object $obj, Hook $hook)
	{
		$this->hook = $hook;
		$this->obj = $obj;
		$this->ref = new ReflectionClass($this->obj);

		$this->parseClassAttrs();
		$this->parseMethodAttrs();
	}

	private function parseClassAttrs(): void
	{
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
