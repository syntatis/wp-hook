<?php

declare(strict_types=1);

namespace Syntatis\WPHook\Tests;

use ArgumentCountError;
use InvalidArgumentException;
use Syntatis\WPHook\Exceptions\RefExistsException;
use Syntatis\WPHook\Exceptions\RefNotFoundException;
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

	/** @group test-here */
	public function testRemoveActionNamedFunction(): void
	{
		$hook = new Registry();
		$hook->addAction('get_sidebar', '__return_false', 39, 1);

		$this->assertSame(39, has_action('get_sidebar', '__return_false'));

		$hook->removeAction('get_sidebar', '__return_false', 39);

		$this->assertFalse(has_action('get_sidebar', '__return_false'));
	}

	public function testRemoveActionInvalidCallback(): void
	{
		$hook = new Registry();
		$hook->addAction('get_sidebar', '__return_true', 190);

		$this->assertSame(190, has_action('get_sidebar', '__return_true'));

		$this->expectException(RefNotFoundException::class);

		$hook->removeAction('get_sidebar', '__invalid_function__', 190);
	}

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

		$hook->addAction('wp_footer', $func, 70, 1, ['id' => '@bar']);
	}

	/** @group with-ref */
	public function testRemoveActionAnonymousFunction(): void
	{
		$hook = new Registry();
		$func = static fn ($value) => null;
		$hook->addAction('register_sidebar', $func, 50, 1, ['id' => 'bar']);

		$this->assertSame(50, has_action('register_sidebar', $func));

		$hook->removeAction('register_sidebar', '@bar', 50);

		$this->assertFalse(has_action('register_sidebar', $func));

		// With invalid ref.
		$hook = new Registry();
		$func = static fn ($value) => null;
		$hook->addAction('register_sidebar', $func, 51, 1, ['id' => 'bar']);

		$this->expectException(RefNotFoundException::class);

		$hook->removeAction('register_sidebar', '@no-bar', 50);
	}

	/** @group with-ref */
	public function testRemoveActionNamedFunctionWithRef(): void
	{
		$hook = new Registry();
		$hook->addAction('get_sidebar', '__return_false', 39, 1, ['id' => 'false']);

		$this->assertSame(39, has_action('get_sidebar', '__return_false'));

		$hook->removeAction('get_sidebar', '__return_false', 39);

		$this->assertFalse(has_action('get_sidebar', '__return_false'));

		// Remove with ref.
		$hook = new Registry();
		$hook->addAction('get_sidebar', '__return_false', 40, 1, ['id' => 'false']);

		$this->assertSame(40, has_action('get_sidebar', '__return_false'));

		$hook->removeAction('get_sidebar', '@false', 40);

		$this->assertFalse(has_action('get_sidebar', '__return_false'));
	}

	/** @group with-ref */
	public function testRemoveActionNamedFunctionWithInvalidRef(): void
	{
		$hook = new Registry();
		$hook->addAction('get_sidebar', '__return_false', 40, 1, ['id' => 'false']);

		$this->expectException(RefNotFoundException::class);

		$hook->removeAction('get_sidebar', '@no-false', 40);
	}

	/**
	 * @group with-ref
	 * @group test-here
	 */
	public function testRemoveActionClassMethodWithRef(): void
	{
		$hook = new Registry();
		$callback = new CallbackTest();
		$hook->addAction('wp_head', [$callback, 'init'], 33, 1, ['id' => 'foo']);

		$this->assertSame(33, has_action('wp_head', [$callback, 'init']));

		$hook->removeAction('wp_head', 'Syntatis\WPHook\Tests\CallbackTest::init', 33);

		$this->assertFalse(has_action('wp_head', [$callback, 'init']));

		// Remove with ref.
		$hook = new Registry();
		$callback = new CallbackTest();
		$hook->addAction('wp_head', [$callback, 'init'], 34, 1, ['id' => 'foo']);

		$this->assertSame(34, has_action('wp_head', [$callback, 'init']));

		$hook->removeAction('wp_head', '@foo', 34);

		$this->assertFalse(has_action('wp_head', [$callback, 'init']));
	}

	/** @group with-ref */
	public function testRemoveFilterAnonymousFunction(): void
	{
		$hook = new Registry();
		$func = static fn ($value) => null;
		$hook->addFilter('icon_dir', $func, 10, 1, ['id' => 'body']);

		$this->assertSame(10, has_filter('icon_dir', $func));

		$hook->removeFilter('icon_dir', '@body', 10);

		$this->assertFalse(has_filter('icon_dir', $func));
	}

	/** @group with-ref */
	public function testRemoveFilterNamedFunctionWithRef(): void
	{
		$hook = new Registry();
		$hook->addFilter('get_the_excerpt', '__return_empty_string', 28, 1, ['id' => 'ret-false']);

		$this->assertSame(28, has_action('get_the_excerpt', '__return_empty_string'));

		$hook->removeFilter('get_the_excerpt', '__return_empty_string', 28);

		$this->assertFalse(has_action('get_the_excerpt', '__return_empty_string'));

		// Remove with ref.
		$hook = new Registry();
		$hook->addFilter('get_the_excerpt', '__return_empty_string', 200, 1, ['id' => 'ret-false']);

		$this->assertSame(200, has_action('get_the_excerpt', '__return_empty_string'));

		$hook->removeFilter('get_the_excerpt', '@ret-false', 200);

		$this->assertFalse(has_action('get_the_excerpt', '__return_empty_string'));
	}

	/** @group with-ref */
	public function testRemoveFilterNamedFunctionWithInvalidRef(): void
	{
		$hook = new Registry();
		$hook->addFilter('get_the_archive_title', '__return_empty_string', 280, 1, ['id' => 'ret-false']);

		$this->assertSame(280, has_action('get_the_archive_title', '__return_empty_string'));

		$this->expectException(RefNotFoundException::class);
		$hook->removeFilter('get_the_archive_title', '@no-ret-false', 280);
	}

	/** @group with-ref */
	public function testAddRefExists(): void
	{
		$hook = new Registry();
		$hook->addFilter('the_content', static fn () => true, 320, 1, ['id' => 'ref-true']);

		$this->expectException(RefExistsException::class);

		$hook->addFilter('the_content_rss', static fn () => true, 320, 1, ['id' => 'ref-true']);
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

	public function change(): string
	{
		return '';
	}
}
