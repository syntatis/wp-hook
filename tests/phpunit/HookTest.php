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

		// No-run.
		$hook->addAction('wp', $func);
		$this->assertFalse(has_action('wp', $func));

		// Run.
		$hook->addAction('init', $func);
		$hook->run();

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
		$hook->run();

		$actual = has_action('init', $func);
		$expect = 100;

		$this->assertSame($expect, $actual);
	}

	public function testAddActionAcceptedArgs(): void
	{
		$hook = new Hook();

		$hook->addAction('auth_cookie_malformed', static function ($cookie, $scheme): void {}, 100, 2);
		$hook->run();

		do_action('auth_cookie_malformed', '123', 'auth');

		$hook->addAction('auth_cookie_malformed', static function ($cookie, $scheme): void {}, 100);
		$hook->run();

		$this->expectException(ArgumentCountError::class);
		do_action('auth_cookie_malformed', '123', 'auth');
	}

	public function testAddFilter(): void
	{
		$func = static function ($value) {
			return $value;
		};

		$hook = new Hook();

		// No-run.
		$hook->addFilter('the_content', $func);
		$this->assertFalse(has_filter('the_content', $func));

		// Run.
		$hook->addFilter('all_plugins', $func);
		$hook->run();

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

		// Run.
		$hook->addFilter('all_plugins', $func, 100);
		$hook->run();

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
		$hook->run();

		apply_filters('allow_empty_comment', false, []);

		$hook->addFilter('allow_empty_comment', static function ($allowEmptyComment, $commentData) {
			return $allowEmptyComment;
		}, 100);

		$hook->run();

		$this->expectException(ArgumentCountError::class);
		apply_filters('allow_empty_comment', false, []);
	}

	public function testRemoveAllActions(): void
	{
		$hook = new Hook();
		$func = static function ($value): void {
		};

		$funcNative = static function ($value): void {
		};

		add_action('wp', $funcNative);
		add_action('init', $funcNative);

		$hook->addAction('wp', $func);
		$hook->addAction('init', $func);
		$hook->run();

		$this->assertSame(10, has_action('wp', $func));
		$this->assertSame(10, has_action('init', $func));
		$this->assertSame(10, has_action('wp', $funcNative));
		$this->assertSame(10, has_action('init', $funcNative));

		$hook->removeAllFilters(); // These methods should de-register all actions.

		$this->assertSame(10, has_action('wp', $func));
		$this->assertSame(10, has_action('init', $func));
		$this->assertSame(10, has_action('wp', $funcNative));
		$this->assertSame(10, has_action('init', $funcNative));

		$hook->removeAllActions();

		$this->assertFalse(has_action('wp', $func));
		$this->assertFalse(has_action('init', $func));
		$this->assertSame(10, has_action('wp', $funcNative));
		$this->assertSame(10, has_action('init', $funcNative));
	}

	public function testRemoveAllFilters(): void
	{
		$hook = new Hook();
		$func = static function ($value): void {
		};

		$funcNative = static function ($value): void {
		};

		add_filter('the_content', $funcNative);
		add_filter('all_plugins', $funcNative);

		$hook->addFilter('the_content', $func);
		$hook->addFilter('all_plugins', $func);
		$hook->run();

		$this->assertSame(10, has_filter('the_content', $func));
		$this->assertSame(10, has_filter('all_plugins', $func));
		$this->assertSame(10, has_filter('the_content', $funcNative));
		$this->assertSame(10, has_filter('all_plugins', $funcNative));

		$hook->removeAllActions(); // This method should not de-register all filters.

		$this->assertSame(10, has_filter('the_content', $func));
		$this->assertSame(10, has_filter('all_plugins', $func));
		$this->assertSame(10, has_filter('the_content', $funcNative));
		$this->assertSame(10, has_filter('all_plugins', $funcNative));

		$hook->removeAllFilters();

		$this->assertFalse(has_filter('the_content', $func));
		$this->assertFalse(has_filter('all_plugins', $func));
		$this->assertSame(10, has_filter('the_content', $funcNative));
		$this->assertSame(10, has_filter('all_plugins', $funcNative));
	}
}
