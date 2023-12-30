<div align="center">
  <strong>🪝 wp-hook</strong>
  <p>WordPress hook with object-oriented programming</p>

  [![wp](https://github.com/syntatis/wp-hook/actions/workflows/wp.yml/badge.svg)](https://github.com/syntatis/wp-hook/actions/workflows/wp.yml) [![codecov](https://codecov.io/gh/syntatis/wp-hook/graph/badge.svg?token=04HZ3BRM19)](https://codecov.io/gh/syntatis/wp-hook)
</div>

---

A small class wrapper to allow using WordPress hooks with OOP, inspired from the [WordPress Plugin Boilerplate](https://wppb.me/), and added with a few syntactic features.

## Why?

One common pitfall that I often encounter when managing a large complex site with the plugins and themes is that we could easily fall into nesting multiple hooks:

```php
add_action( 'init', 'initialise' );

function initialise(): void
{
	add_action( 'after_setup_theme', 'hello_world' );
}

function hello_world(): void
{
    echo 'Hello World';
}
```

The problem with the above example is that WordPress may never execute the `hello_world` function since the `after_setup_theme` would have already done executing before the `init` hook. In some extreme cases, nested hooks [may cause an error](https://wordpress.stackexchange.com/questions/147505/wp-insert-posts-fatal-error-maximum-function-nesting-level-of-100-reached-ab).

This library aims to help minimising this pitfall.

## Installation

```sh
composer require syntatis/wp-hook
```

## Usage

```php
use Syntatis\WP\Hook\Hook;

$hook = new Hook();
$hook->addAction( 'init', 'initialise' );
$hook->addFilter( 'after_setup_theme', 'hello_world' );
$hook->run();
```

If your WordPress theme or plugin is using PHP 8.0, you can use [PHP Attributes](https://www.php.net/manual/en/language.attributes.overview.php) to add the hooks.

```php
use Syntatis\WP\Hook\Action;
use Syntatis\WP\Hook\Filter;
use Syntatis\WP\Hook\Hook;

#[Action(tag: "wp")]
class HelloWorld
{
    #[Action(tag: "init")]
    public function initialise(): void
    {
      echo 'initialise';
    }

    #[Filter(tag: "the_content", priority: 100)]
    public function content(string $content): string
    {
      return $content . "\ncontent";
    }

    public function __invoke(): void
    {
      echo 'wp';
    }
}

$hook = new Hook();
$hook->annotated(new HelloWorld());
$hook->run();
```

## References

- [WordPress Plugin Boilerplate](https://wppb.me/)
- [Maximum function nesting level of '100' reached, aborting!](https://wordpress.stackexchange.com/questions/147505/wp-insert-posts-fatal-error-maximum-function-nesting-level-of-100-reached-ab)
- [PHP OOP: Introduction](https://phptherightway.com/#object-oriented-programming)
