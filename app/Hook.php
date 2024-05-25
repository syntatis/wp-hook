<?php

declare(strict_types=1);

namespace Syntatis\WPHook;

/**
 * This class manages the registration of all actions and filters for the plugin.
 *
 * It maintains a list of all hooks to be registered with the WordPress API.
 * Call the `register` method to execute the registration of these actions
 * and filters.
 */
final class Hook
{
	/**
	 * The array of actions registered with WordPress.
	 *
	 * @var array<array{hook:string,callback:callable,priority:int,accepted_args:int}>
	 */
	private array $actions = [];

	/**
	 * The array of filters registered with WordPress.
	 *
	 * @var array<array{hook:string,callback:callable,priority:int,accepted_args:int}>
	 */
	private array $filters = [];

	/**
	 * Add a new action to the collection to be registered with WordPress.
	 *
	 * @param string   $hook         The name of the WordPress action that is being registered.
	 * @param callable $callback     The name of the function to be called with Action hook.
	 * @param int      $priority     Optional. The priority at which the function should be fired. Default is 10.
	 * @param int      $acceptedArgs Optional. The number of arguments that should be passed to the $callback. Default is 1.
	 */
	public function addAction(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
	{
		$this->actions = $this->add($this->actions, $hook, $callback, $priority, $acceptedArgs);
	}

	/**
	 * Add a new filter to the collection to be registered with WordPress.
	 *
	 * @param string   $hook         The name of the WordPress filter that is being registered.
	 * @param callable $callback     The name of the function to be called with Filter hook.
	 * @param int      $priority     Optional. The priority at which the function should be fired. Default is 10.
	 * @param int      $acceptedArgs Optional. The number of arguments that should be passed to the $callback. Default is 1.
	 */
	public function addFilter(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
	{
		$this->filters = $this->add($this->filters, $hook, $callback, $priority, $acceptedArgs);
	}

	/**
	 * Add the filters and actions in WordPress.
	 */
	public function register(): void
	{
		foreach ($this->filters as $hook) {
			add_filter($hook['hook'], $hook['callback'], $hook['priority'], $hook['accepted_args']);
		}

		foreach ($this->actions as $hook) {
			add_action($hook['hook'], $hook['callback'], $hook['priority'], $hook['accepted_args']);
		}
	}

	/**
	 * Remove all actions and filters from WordPress.
	 */
	public function deregister(): void
	{
		foreach ($this->actions as $hook) {
			remove_action($hook['hook'], $hook['callback'], $hook['priority']);
		}

		foreach ($this->filters as $hook) {
			remove_filter($hook['hook'], $hook['callback'], $hook['priority']);
		}
	}

	/**
	 * Parse and register hooks annotated with attributes in the given object.
	 *
	 * @param object $obj The object containing annotated hooks.
	 */
	public function parse(object $obj): void
	{
		new Parser($obj, $this);
	}

	/**
	 * Add a new hook (action or filter) to the collection.
	 *
	 * @param array<array{hook:string,callback:callable,priority:int,accepted_args:int}> $hooks        The current collection of hooks.
	 * @param string                                                                     $hook         The name of the hook being registered.
	 * @param callable                                                                   $callback     The function to be called when the hook is triggered.
	 * @param int                                                                        $priority     The priority at which the function should be fired.
	 * @param int                                                                        $acceptedArgs The number of arguments that should be passed to the callback.
	 * @return array<array{hook:string,callback:callable,priority:int,accepted_args:int}>
	 */
	private function add(array $hooks, string $hook, callable $callback, int $priority, int $acceptedArgs): array
	{
		$hooks[] = [
			'accepted_args' => $acceptedArgs,
			'callback' => $callback,
			'hook' => $hook,
			'priority' => $priority,
		];

		return $hooks;
	}
}
