<?php

declare(strict_types=1);

namespace Syntatis\WPHook;

use Closure;
use InvalidArgumentException;
use Syntatis\WPHook\Exceptions\RefNotFoundException;
use Syntatis\WPHook\Support\Parser;

use function count;
use function get_class;
use function gettype;
use function is_array;
use function is_callable;
use function is_string;
use function preg_match;
use function spl_object_hash;
use function strncmp;
use function trim;

/**
 * This class manages the registration of all actions and filters for the plugin.
 *
 * It maintains a list of all hooks to be registered with the WordPress API.
 * Call the `register` method to execute the registration of these actions
 * and filters.
 */
final class Registry
{
	/**
	 * Holds reference to callbacks.
	 *
	 * @var array<string,callable|string>
	 */
	private array $refs = [];

	/**
	 * The array of actions registered with WordPress.
	 *
	 * @var array<array{tag:string,callback:callable,priority:int,accepted_args:int}>
	 */
	private array $actions = [];

	/**
	 * The array of filters registered with WordPress.
	 *
	 * @var array<array{tag:string,callback:callable,priority:int,accepted_args:int}>
	 */
	private array $filters = [];

	/**
	 * Add a new action to the collection to be registered with WordPress.
	 *
	 * @param string               $tag          The name of the WordPress action that is being registered.
	 * @param callable             $callback     The name of the function to be called with Action hook.
	 * @param int                  $priority     Optional. The priority at which the function should be fired. Default is 10.
	 * @param int                  $acceptedArgs Optional. The number of arguments that should be passed to the $callback. Default is 1.
	 * @param array<string, mixed> $options      Optional. Additional options for the action.
	 */
	public function addAction(
		string $tag,
		callable $callback,
		int $priority = 10,
		int $acceptedArgs = 1,
		array $options = []
	): void {
		add_action($tag, $callback, $priority, $acceptedArgs);

		$this->addRef($this->getRef($callback, $options), $callback);

		$this->actions = $this->add($this->actions, $tag, $callback, $priority, $acceptedArgs);
	}

	/**
	 * Add a new filter to the collection to be registered with WordPress.
	 *
	 * @param string               $tag          The name of the WordPress filter that is being registered.
	 * @param callable             $callback     The name of the function to be called with Filter hook.
	 * @param int                  $priority     Optional. The priority at which the function should be fired. Default is 10.
	 * @param int                  $acceptedArgs Optional. The number of arguments that should be passed to the $callback. Default is 1.
	 * @param array<string, mixed> $options      Optional. Additional options for the action.
	 */
	public function addFilter(
		string $tag,
		callable $callback,
		int $priority = 10,
		int $acceptedArgs = 1,
		array $options = []
	): void {
		add_filter($tag, $callback, $priority, $acceptedArgs);

		$this->addRef($this->getRef($callback, $options), $callback);

		$this->filters = $this->add($this->filters, $tag, $callback, $priority, $acceptedArgs);
	}

	/**
	 * Removes an action callback function from a specified hook.
	 *
	 * @param string          $tag      The name of the action hook to remove the callback from.
	 * @param string|callable $callback The callback or ref to remove from the action hook.
	 * @param int             $priority Optional. The priority of the callback function. Default is 10.
	 */
	public function removeAction(string $tag, $callback, int $priority = 10): void
	{
		$callback = is_string($callback) ?
			$this->getCallbackFromRef($callback) :
			$callback;

		remove_action($tag, $callback, $priority);
	}

	/**
	 * Removes a filter callback function from a specified hook.
	 *
	 * @param string          $tag      The name of the filter hook to remove the callback from.
	 * @param string|callable $callback The callback or ref to remove from the filter hook.
	 * @param int             $priority Optional. The priority of the callback function. Default is 10.
	 */
	public function removeFilter(string $tag, $callback, int $priority = 10): void
	{
		$callback = is_string($callback) ?
			$this->getCallbackFromRef($callback) :
			$callback;

		remove_filter($tag, $callback, $priority);
	}

	/**
	 * Remove all actions and filters from WordPress.
	 */
	public function removeAll(): void
	{
		foreach ($this->actions as $hook) {
			remove_action($hook['tag'], $hook['callback'], $hook['priority']);
		}

		foreach ($this->filters as $hook) {
			remove_filter($hook['tag'], $hook['callback'], $hook['priority']);
		}
	}

	/**
	 * Parse and register hooks annotated with attributes in the given object.
	 *
	 * @param object $obj The object containing annotated hooks.
	 */
	public function parse(object $obj): void
	{
		$parser = new Parser($obj);
		$parser->hook($this);
		$parser->parse();
	}

	/**
	 * Add a new hook (action or filter) to the collection.
	 *
	 * @param array<array{tag:string,callback:callable,priority:int,accepted_args:int}> $hooks        The current collection of hooks.
	 * @param string                                                                    $tag          The name of the hook being registered.
	 * @param callable                                                                  $callback     The function to be called when the hook is triggered.
	 * @param int                                                                       $priority     The priority at which the function should be fired.
	 * @param int                                                                       $acceptedArgs The number of arguments that should be passed to the callback.
	 * @return array<array{tag:string,callback:callable,priority:int,accepted_args:int}>
	 */
	private function add(array $hooks, string $tag, callable $callback, int $priority, int $acceptedArgs): array
	{
		$hooks[] = [
			'accepted_args' => $acceptedArgs,
			'callback' => $callback,
			'tag' => $tag,
			'priority' => $priority,
		];

		return $hooks;
	}

	private function addRef(string $ref, callable $callback): void
	{
		$namedRef = $this->getNamedRef($callback);

		if ($namedRef !== $ref) {
			$this->refs[$namedRef] = '@' . $ref;
			$this->refs['@' . $ref] = $callback;
		} else {
			$this->refs[$ref] = $callback;
		}
	}

	/** @param array<string, mixed> $options */
	private function getRef(callable $callback, array $options = []): string
	{
		if (isset($options['ref']) && is_string($options['ref']) && trim($options['ref']) !== '') {
			preg_match('/^[a-z0-9\.\-\_\\\]+/', $options['ref'], $matches);

			if (count($matches) === 0) {
				throw new InvalidArgumentException('Ref should only contains letters, numbers, hyphens, dots, underscores, and backslashes.');
			}

			return $options['ref'];
		}

		return $this->getNamedRef($callback);
	}

	private function getNamedRef(callable $callback): string
	{
		if (gettype($callback) === 'string') {
			return $callback;
		}

		if (is_array($callback)) {
			return get_class($callback[0]) . '::' . $callback[1];
		}

		return spl_object_hash(Closure::fromCallable($callback));
	}

	/** @param string $callback The callback or ref to remove from the action hook. */
	private function getCallbackFromRef(string $callback): callable
	{
		if (isset($this->refs[$callback]) && is_callable($this->refs[$callback])) {
			return $this->refs[$callback];
		}

		if (strncmp($callback, '@', 1) === 0) {
			if (! isset($this->refs[$callback])) {
				throw new RefNotFoundException($callback);
			}

			if (is_callable($this->refs[$callback])) {
				return $this->refs[$callback];
			}
		}

		$atRef = $this->refs[$callback] ?? null;

		if (is_string($atRef) && strncmp($atRef, '@', 1) === 0) {
			if (! isset($this->refs[$atRef])) {
				throw new RefNotFoundException($callback);
			}

			if (is_callable($this->refs[$atRef])) {
				return $this->refs[$atRef];
			}
		}

		throw new RefNotFoundException($callback);
	}
}
