<?php

declare(strict_types=1);

namespace Syntatis\WPHook\Tests;

use ArgumentCountError;
use InvalidArgumentException;
use Syntatis\WPHook\Registry;

class RegistryTest extends WPTestCase
{
	public function testAddAction(): void
	{
		$func = static function (): bool {
			return true;
		};
		$hook = new Registry();
		$hook->addAction('wp', $func);

		$this->assertSame(10, has_action('wp', $func));
	}

	public function testAddActionPriority(): void
	{
		$func = static function (): bool {
			return true;
		};
		$hook = new Registry();
		$hook->addAction('init', $func, 100);

		$this->assertSame(100, has_action('init', $func));
	}

	public function testAddActionAcceptedArgs(): void
	{
		$hook = new Registry();
		$hook->addAction(
			'auth_cookie_malformed',
			static function ($cookie, $scheme): void {
			},
			100,
			2,
		);

		do_action('auth_cookie_malformed', '123', 'auth');

		$hook->addAction(
			'auth_cookie_malformed',
			static function ($cookie, $scheme): void {
			},
			100,
		);

		$this->expectException(ArgumentCountError::class);

		do_action('auth_cookie_malformed', '123', 'auth');
	}

	public function testAddFilter(): void
	{
		$func = static function ($value) {
			return $value;
		};

		$hook = new Registry();
		$hook->addFilter('all_plugins', $func);

		$this->assertSame(10, has_filter('all_plugins', $func));
	}

	public function testAddFilterPriority(): void
	{
		$func = static function ($value) {
			return $value;
		};
		$hook = new Registry();
		$hook->addFilter('all_plugins', $func, 100);

		$this->assertSame(100, has_filter('all_plugins', $func));
	}

	public function testAddFilterAcceptedArgs(): void
	{
		$hook = new Registry();
		$hook->addFilter('allow_empty_comment', static function ($allowEmptyComment, $commentData) {
			return $allowEmptyComment;
		}, 100, 2);

		apply_filters('allow_empty_comment', false, []);

		$hook->addFilter('allow_empty_comment', static function ($allowEmptyComment, $commentData) {
			return $allowEmptyComment;
		}, 100);

		$this->expectException(ArgumentCountError::class);

		apply_filters('allow_empty_comment', false, []);
	}

	public function testRemoveAction(): void
	{
		$hook = new Registry();
		$func1 = static function ($value): void {
		};
		$func2 = static function ($value): void {
		};
		$hook->addAction('wp', $func1, 30);
		$hook->addAction('wp', $func2, 30);

		$this->assertSame(30, has_action('wp', $func1));
		$this->assertSame(30, has_action('wp', $func2));

		$hook->removeAction('wp', $func2, 30);

		$this->assertSame(30, has_action('wp', $func1));
		$this->assertFalse(has_action('wp', $func2));
	}

	public function testRemoveActionNamedFunction(): void
	{
		$hook = new Registry();
		$hook->addAction('get_sidebar', '__return_false', 39, 1);

		$this->assertSame(39, has_action('get_sidebar', '__return_false'));

		$hook->removeAction('get_sidebar', '__return_false', 39);

		$this->assertFalse(has_action('get_sidebar', '__return_false'));
	}

	/** @group debug */
	public function testRemoveActionClassMethod(): void
	{
		$hook = new Registry();
		$callback = new CallbackTest();
		$hook->addAction('admin_bar_init', [$callback, 'init'], 25);

		$this->assertSame(25, has_action('admin_bar_init', [$callback, 'init']));

		$hook->removeAction('admin_bar_init', 'Syntatis\WPHook\Tests\CallbackTest::init', 25);

		$this->assertFalse(has_action('admin_bar_init', [$callback, 'init']));
	}

	/** @group with-ref */
	public function testSetInvalidRef(): void
	{
		$hook = new Registry();
		$func = static fn ($value) => null;

		$this->expectException(InvalidArgumentException::class);

		$hook->addAction('wp_footer', $func, 70, 1, ['ref' => '@bar']);
	}

	/** @group with-ref */
	public function testRemoveActionAnonymousFunction(): void
	{
		$hook = new Registry();
		$func = static fn ($value) => null;
		$hook->addAction('register_sidebar', $func, 50, 1, ['ref' => 'bar']);

		$this->assertSame(50, has_action('register_sidebar', $func));

		$hook->removeAction('register_sidebar', '@bar', 50);

		$this->assertFalse(has_action('register_sidebar', $func));
	}

	/** @group with-ref */
	public function testRemoveActionNamedFunctionWithRef(): void
	{
		$hook = new Registry();
		$hook->addAction('get_sidebar', '__return_false', 39, 1, ['ref' => 'false']);

		$this->assertSame(39, has_action('get_sidebar', '__return_false'));

		$hook->removeAction('get_sidebar', '__return_false', 39);

		$this->assertFalse(has_action('get_sidebar', '__return_false'));

		// Remove with ref.
		$hook = new Registry();
		$hook->addAction('get_sidebar', '__return_false', 40, 1, ['ref' => 'false']);

		$this->assertSame(40, has_action('get_sidebar', '__return_false'));

		$hook->removeAction('get_sidebar', '@false', 40);

		$this->assertFalse(has_action('get_sidebar', '__return_false'));
	}

	/** @group with-ref */
	public function testRemoveActionClassMethodWithRef(): void
	{
		$hook = new Registry();
		$callback = new CallbackTest();
		$hook->addAction('wp_head', [$callback, 'init'], 33, 1, ['ref' => 'foo']);

		$this->assertSame(33, has_action('wp_head', [$callback, 'init']));

		$hook->removeAction('wp_head', 'Syntatis\WPHook\Tests\CallbackTest::init', 33);

		$this->assertFalse(has_action('wp_head', [$callback, 'init']));

		// Remove with ref.
		$hook = new Registry();
		$callback = new CallbackTest();
		$hook->addAction('wp_head', [$callback, 'init'], 34, 1, ['ref' => 'foo']);

		$this->assertSame(34, has_action('wp_head', [$callback, 'init']));

		$hook->removeAction('wp_head', '@foo', 34);

		$this->assertFalse(has_action('wp_head', [$callback, 'init']));
	}

	public function testRemoveAll(): void
	{
		$hook = new Registry();
		$func = static function ($value): void {
		};
		$funcNative = static function ($value): void {
		};

		add_action('wp', $funcNative);
		add_action('init', $funcNative);
		add_filter('the_content', $funcNative);
		add_filter('all_plugins', $funcNative);

		$hook->addAction('wp', $func);
		$hook->addAction('init', $func);
		$hook->addFilter('the_content', $func);
		$hook->addFilter('all_plugins', $func);

		// Actions.
		$this->assertSame(10, has_action('wp', $func));
		$this->assertSame(10, has_action('init', $func));
		$this->assertSame(10, has_action('wp', $funcNative));
		$this->assertSame(10, has_action('init', $funcNative));

		// Filters.
		$this->assertSame(10, has_filter('the_content', $func));
		$this->assertSame(10, has_filter('all_plugins', $func));
		$this->assertSame(10, has_filter('the_content', $funcNative));
		$this->assertSame(10, has_filter('all_plugins', $funcNative));

		// These methods should de-register all actions and filters.
		$hook->removeAll();

		// List of actions and filters, added with `add_action` and `add_filter`.
		$this->assertSame(10, has_action('wp', $funcNative));
		$this->assertSame(10, has_action('init', $funcNative));
		$this->assertSame(10, has_filter('the_content', $funcNative));
		$this->assertSame(10, has_filter('all_plugins', $funcNative));

		// List of actions and filters, added with `addAction` and `addFilter` from `Hook`.
		$this->assertFalse(has_action('wp', $func));
		$this->assertFalse(has_action('init', $func));
		$this->assertFalse(has_filter('the_content', $func));
		$this->assertFalse(has_filter('all_plugins', $func));
	}
}

// phpcs:disable
class CallbackTest {
	public function init(): void
	{
	}
}
