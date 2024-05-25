<?php

declare(strict_types=1);

namespace Syntatis\WPHook\Tests;

use ArgumentCountError;
use Syntatis\WPHook\Hook;

class HookTest extends TestCase
{
	public function testAddAction(): void
	{
		$func = static function (): bool {
			return true;
		};

		$hook = new Hook();

		// No-register yet.
		$hook->addAction('wp', $func);
		$this->assertFalse(has_action('wp', $func));

		// Register.
		$hook->addAction('init', $func);
		$hook->register();

		$actual = has_action('init', $func);
		$expect = 10;

		$this->assertSame($expect, $actual);
	}

	public function testAddActionPriority(): void
	{
		$func = static function (): bool {
			return true;
		};

		$hook = new Hook();

		$hook->addAction('init', $func, 100);
		$hook->register();

		$actual = has_action('init', $func);
		$expect = 100;

		$this->assertSame($expect, $actual);
	}

	public function testAddActionAcceptedArgs(): void
	{
		$hook = new Hook();

		$hook->addAction('auth_cookie_malformed', static function ($cookie, $scheme): void {
		}, 100, 2);
		$hook->register();

		do_action('auth_cookie_malformed', '123', 'auth');

		$hook->addAction('auth_cookie_malformed', static function ($cookie, $scheme): void {
		}, 100);
		$hook->register();

		$this->expectException(ArgumentCountError::class);
		do_action('auth_cookie_malformed', '123', 'auth');
	}

	public function testAddFilter(): void
	{
		$func = static function ($value) {
			return $value;
		};

		$hook = new Hook();

		// No-register yet.
		$hook->addFilter('the_content', $func);
		$this->assertFalse(has_filter('the_content', $func));

		// Register.
		$hook->addFilter('all_plugins', $func);
		$hook->register();

		$actual = has_filter('all_plugins', $func);
		$expect = 10;

		$this->assertSame($expect, $actual);
	}

	public function testAddFilterPriority(): void
	{
		$func = static function ($value) {
			return $value;
		};

		$hook = new Hook();

		// Register.
		$hook->addFilter('all_plugins', $func, 100);
		$hook->register();

		$actual = has_filter('all_plugins', $func);
		$expect = 100;

		$this->assertSame($expect, $actual);
	}

	public function testAddFilterAcceptedArgs(): void
	{
		$hook = new Hook();

		$hook->addFilter('allow_empty_comment', static function ($allowEmptyComment, $commentData) {
			return $allowEmptyComment;
		}, 100, 2);
		$hook->register();

		apply_filters('allow_empty_comment', false, []);

		$hook->addFilter('allow_empty_comment', static function ($allowEmptyComment, $commentData) {
			return $allowEmptyComment;
		}, 100);

		$hook->register();

		$this->expectException(ArgumentCountError::class);
		apply_filters('allow_empty_comment', false, []);
	}

	public function testUnregister(): void
	{
		$hook = new Hook();

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
		$hook->register();

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

		$hook->unregister(); // These methods should de-register all actions and filters.

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
