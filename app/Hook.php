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
	 * @var array<array{name:string,callback:callable,priority:int,accepted_args:int}>
	 */
	private array $actions = [];

	/**
	 * The array of filters registered with WordPress.
	 *
	 * @var array<array{name:string,callback:callable,priority:int,accepted_args:int}>
	 */
	private array $filters = [];

	/**
	 * Add a new action to the collection to be registered with WordPress.
	 *
	 * @param string   $name         The name of the WordPress action that is being registered.
	 * @param callable $callback     The name of the function to be called with Action hook.
	 * @param int      $priority     Optional. The priority at which the function should be fired. Default is 10.
	 * @param int      $acceptedArgs Optional. The number of arguments that should be passed to the $callback. Default is 1.
	 */
	public function addAction(string $name, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
	{
		$this->actions = $this->add($this->actions, $name, $callback, $priority, $acceptedArgs);
	}

	/**
	 * Add a new filter to the collection to be registered with WordPress.
	 *
	 * @param string   $name         The name of the WordPress filter that is being registered.
	 * @param callable $callback     The name of the function to be called with Filter hook.
	 * @param int      $priority     Optional. The priority at which the function should be fired. Default is 10.
	 * @param int      $acceptedArgs Optional. The number of arguments that should be passed to the $callback. Default is 1.
	 */
	public function addFilter(string $name, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
	{
		$this->filters = $this->add($this->filters, $name, $callback, $priority, $acceptedArgs);
	}

	/**
	 * Add the filters and actions in WordPress.
	 */
	public function register(): void
	{
		foreach ($this->filters as $hook) {
			add_filter($hook['name'], $hook['callback'], $hook['priority'], $hook['accepted_args']);
		}

		foreach ($this->actions as $hook) {
			add_action($hook['name'], $hook['callback'], $hook['priority'], $hook['accepted_args']);
		}
	}

	/**
	 * Remove all actions and filters from WordPress.
	 */
	public function deregister(): void
	{
		foreach ($this->actions as $hook) {
			remove_action($hook['name'], $hook['callback'], $hook['priority']);
		}

		foreach ($this->filters as $hook) {
			remove_filter($hook['name'], $hook['callback'], $hook['priority']);
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
	 * @param array<array{name:string,callback:callable,priority:int,accepted_args:int}> $hooks        The current collection of hooks.
	 * @param string                                                                     $name         The name of the hook being registered.
	 * @param callable                                                                   $callback     The function to be called when the hook is triggered.
	 * @param int                                                                        $priority     The priority at which the function should be fired.
	 * @param int                                                                        $acceptedArgs The number of arguments that should be passed to the callback.
	 * @return array<array{name:string,callback:callable,priority:int,accepted_args:int}>
	 */
	private function add(array $hooks, string $name, callable $callback, int $priority, int $acceptedArgs): array
	{
		$hooks[] = [
			'accepted_args' => $acceptedArgs,
			'callback' => $callback,
			'name' => $name,
			'priority' => $priority,
		];

		return $hooks;
	}
}
