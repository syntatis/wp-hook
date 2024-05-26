<div align="center">
  <strong>🪝 wp-hook</strong>
  <p>WordPress hook with object-oriented programming</p>

  ![Packagist Dependency Version](https://img.shields.io/packagist/dependency-v/syntatis/wp-hook/php?color=%237A86B8) [![wp](https://github.com/syntatis/wp-hook/actions/workflows/wp.yml/badge.svg)](https://github.com/syntatis/wp-hook/actions/workflows/wp.yml) [![codecov](https://codecov.io/gh/syntatis/wp-hook/graph/badge.svg?token=04HZ3BRM19)](https://codecov.io/gh/syntatis/wp-hook)
</div>

---

A lightweight class wrapper designed to enable the use of WordPress hooks in an object-oriented manner. Inspired by the [WordPress Plugin Boilerplate](https://wppb.me/), this package introduces a cleaner syntax and enhanced features for managing hooks in your WordPress plugin or theme.

## Motivation

Managing a complex WordPress site often involves dealing with numerous hooks, which can quickly become unmanageable and prone to errors. A common issue is the nesting of hooks, which can lead to scenarios where certain hooks are never executed due to their initialization timing:

```php
add_action('init', 'initialise');

function initialise(): void
{
    add_action('after_setup_theme', 'hello_world');
}

function hello_world(): void
{
    echo 'Hello World';
}
```

In the above example, `hello_world` may never be executed if `after_setup_theme` has already been fired before the `init` hook. In extreme cases, such nested hooks can cause errors like [maximum function nesting level reached](https://wordpress.stackexchange.com/questions/147505/wp-insert-posts-fatal-error-maximum-function-nesting-level-of-100-reached-ab). This package aims to minimize these pitfalls by providing a more structured approach to managing hooks.

## Installation

Install the package via Composer:

```sh
composer require syntatis/wp-hook
```

## Usage

Create a new instance of the `Hook` class and register your hooks:

```php
use Syntatis\WPHook\Hook;

$hook = new Hook();
$hook->addAction('init', 'initialise');
$hook->addFilter('the_content', 'content', 100);
$hook->addAction('add_option', 'option', 100, 2);
$hook->register();
```

### Using PHP Attributes

If your theme or plugin runs on PHP 8.0 or later, you can leverage [PHP Attributes](https://www.php.net/manual/en/language.attributes.overview.php) to define hooks directly within your classes:

```php
use Syntatis\WPHook\Action;
use Syntatis\WPHook\Filter;
use Syntatis\WPHook\Hook;

#[Action(name: "wp")]
class HelloWorld
{
    #[Action(name: "init")]
    public function initialise(): void
    {
        echo 'initialise';
    }

    #[Filter(name: "the_content", priority: 100)]
    public function content(string $content): string
    {
        return $content . "\ncontent";
    }

    #[Action(name: "the_content", priority: 100, acceptedArgs: 2)]
    public function option(string $optionName, mixed $value): void
    {
        echo $optionName . $value;
    }

    public function __invoke(): void
    {
        echo 'wp';
    }
}

$hook = new Hook();
$hook->parse(new HelloWorld());
$hook->register();
```

### Deregistering Hooks

You can also deregister hooks, which will remove all the hooks that have been registered in the `Hook` instance:

```php
$hook = new Hook();
$hook->addAction('init', 'initialise');
$hook->addFilter('the_content', 'content', 100);
$hook->parse(new HelloWorld());
$hook->register();

// ...later in the code...
$hook->deregister();
```

## References

- [WordPress Plugin Boilerplate](https://wppb.me/)
- [Maximum function nesting level of '100' reached, aborting!](https://wordpress.stackexchange.com/questions/147505/wp-insert-posts-fatal-error-maximum-function-nesting-level-of-100-reached-ab)
- [PHP OOP: Introduction](https://phptherightway.com/#object-oriented-programming)
