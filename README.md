<div align="center">
  <strong>🪝 wp-hook</strong>
  <p>WordPress hook with object-oriented programming</p>

  ![Packagist Dependency Version](https://img.shields.io/packagist/dependency-v/syntatis/wp-hook/php?color=%237A86B8) [![wp](https://github.com/syntatis/wp-hook/actions/workflows/wp.yml/badge.svg)](https://github.com/syntatis/wp-hook/actions/workflows/wp.yml) [![codecov](https://codecov.io/gh/syntatis/wp-hook/graph/badge.svg?token=04HZ3BRM19)](https://codecov.io/gh/syntatis/wp-hook)
</div>

---

> [!CAUTION]
> This package is currently in active development. Please be aware that there may be some changes as the package continue to evolve.

A class wrapper designed to use WordPress hooks with object-oriented programming (OOP), inspired from [WordPress Plugin Boilerplate](https://wppb.me/). This class helps you getting more organized and enforce structure when managing hooks in your WordPress plugin or theme.

## Installation

Install the package via Composer:

```sh
composer require syntatis/wp-hook
```

## Usage

Create a new instance of the `Registry` class and register your hooks:

```php
use Syntatis\WPHook\Registry;

$registry = new Registry();
$registry->addAction('init', 'initialise');
$registry->addFilter('the_content', 'content', 100);
$registry->addAction('add_option', 'option', 100, 2);
$registry->register();
```

### Using PHP Attributes

If your theme or plugin runs on PHP 8.0 or later, you can leverage [PHP Attributes](https://www.php.net/manual/en/language.attributes.overview.php) to define hooks directly within your classes:

```php
use Syntatis\WPHook\Action;
use Syntatis\WPHook\Filter;
use Syntatis\WPHook\Registry;

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

$registry = new Registry();
$registry->parse(new HelloWorld());
$registry->register();
```

> [!NOTE]
> Attributes will only be applied to non-abstract public methods that are not PHP native methods or any methods that begin with `__` like `__constructor`, `__clone`, and `__callStatic`.
> If you add the Attributes at the class level, the class should implement the `__invoke` method, as shown in the above example.

### Deregistering Hooks

You can also deregister hooks, which will remove all the actions and filters that have been registered in the `Hook` instance:

```php
$registry = new Registry();
$registry->addAction('init', 'initialise');
$registry->addFilter('the_content', 'content', 100);
$registry->register();

// ...later in the code...
$registry->deregister();
```

## References

- [WordPress Plugin Boilerplate](https://wppb.me/)
- [Maximum function nesting level of '100' reached, aborting!](https://wordpress.stackexchange.com/questions/147505/wp-insert-posts-fatal-error-maximum-function-nesting-level-of-100-reached-ab)
- [PHP OOP: Introduction](https://phptherightway.com/#object-oriented-programming)
