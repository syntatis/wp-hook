<?php

declare(strict_types=1);

namespace Syntatis\WPHook\Tests;

use Syntatis\WPHook\Action;
use Syntatis\WPHook\Contract\WithHook;
use Syntatis\WPHook\Filter;
use Syntatis\WPHook\Hook;

use function array_key_first;

/** @requires PHP 8.0 */
class ParserTest extends WPTestCase
{
	public function testActionOnMethod(): void
	{
		$hasActions = new class implements WithHook
		{
			public function hook(Hook $hook): void
			{
				$hook->addAction('init', [$this, 'bar'], 124);
				$hook->parse($this);
			}

			public function bar(): void
			{
			}

			#[Action(name: 'init', priority: 123, acceptedArgs: 2)]
			public function foo(): void
			{
			}
		};

		$hook = new Hook();
		$hasActions->hook($hook);
		$hook->register();

		$this->assertEquals(123, has_action('init', [$hasActions, 'foo']));
		$this->assertEquals(124, has_action('init', [$hasActions, 'bar']));

		$hooks = $GLOBALS['wp_filter']['init'][123];
		$added = $hooks[array_key_first($hooks)];

		$this->assertEquals([$hasActions, 'foo'], $added['function']);
		$this->assertEquals(2, $added['accepted_args']);

		$hooks = $GLOBALS['wp_filter']['init'][124];
		$added = $hooks[array_key_first($hooks)];

		$this->assertEquals([$hasActions, 'bar'], $added['function']);
		$this->assertEquals(1, $added['accepted_args']);
	}

	public function testFilterOnMethod(): void
	{
		$hasFilters = new class implements WithHook
		{
			public function hook(Hook $hook): void
			{
				$hook->addFilter('the_content', [$this, 'bar'], 224);
				$hook->parse($this);
			}

			public function bar(): void
			{
			}

			#[Filter(name: 'the_content', priority: 223, acceptedArgs: 2)]
			public function foo(): void
			{
			}
		};

		$hook = new Hook();
		$hasFilters->hook($hook);
		$hook->register();

		$this->assertEquals(223, has_filter('the_content', [$hasFilters, 'foo']));
		$this->assertEquals(224, has_filter('the_content', [$hasFilters, 'bar']));

		$hooks = $GLOBALS['wp_filter']['the_content'][223];
		$added = $hooks[array_key_first($hooks)];

		$this->assertEquals([$hasFilters, 'foo'], $added['function']);
		$this->assertEquals(2, $added['accepted_args']);

		$hooks = $GLOBALS['wp_filter']['the_content'][224];
		$added = $hooks[array_key_first($hooks)];

		$this->assertEquals([$hasFilters, 'bar'], $added['function']);
		$this->assertEquals(1, $added['accepted_args']);
	}

	public function testActionOnClass(): void
	{
		$foo = new Foo();
		$hook = new Hook();
		$hook->parse($foo);
		$hook->register();

		$hooks = $GLOBALS['wp_filter']['init'][234];
		$added = $hooks[array_key_first($hooks)];

		$this->assertIsCallable($added['function']);
		$this->assertEquals(2, $added['accepted_args']);
	}

	public function testFilterOnClass(): void
	{
		$bar = new Bar();
		$hook = new Hook();
		$hook->parse($bar);
		$hook->register();

		$hooks = $GLOBALS['wp_filter']['the_title'][432];
		$added = $hooks[array_key_first($hooks)];

		$this->assertIsCallable($added['function']);
		$this->assertEquals(1, $added['accepted_args']);
	}
}

// phpcs:disable
#[Action(name: 'init', priority: 234, acceptedArgs: 2)]
class Foo
{
	public function __invoke(): void
	{
	}
}

#[Filter(name: 'the_title', priority: 432)]
class Bar
{
	public function __invoke(): string
	{
		return '';
	}
}
