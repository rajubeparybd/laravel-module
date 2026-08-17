# Laravel Module

[![Latest Version on Packagist](https://img.shields.io/packagist/v/rajubepary/laravel-module.svg?style=flat-square)](https://packagist.org/packages/rajubepary/laravel-module)
[![GitHub Tests Action Status](https://github.com/spatie/package-laravel-module-laravel/actions/workflows/run-tests.yml/badge.svg)](https://github.com/rajubeparybd/laravel-module/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://github.com/spatie/package-laravel-module-laravel/actions/workflows/fix-php-code-style-issues.yml/badge.svg)](https://github.com/rajubeparybd/laravel-module/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/rajubepary/laravel-module.svg?style=flat-square)](https://packagist.org/packages/rajubepary/laravel-module)

A powerful and flexible modular architecture package for Laravel applications. This package allows you to seamlessly create, manage, and organize your Laravel application into modular components.

## Installation

You can install the package via composer:

```bash
composer require rajubepary/laravel-module
```

After installing the package, run the setup command. This will publish the configuration file and run the necessary migrations:

```bash
php artisan lm:setup
```

If you prefer to do this manually, you can publish the config file and run migrations:

```bash
php artisan vendor:publish --tag="module-config"
php artisan migrate
```

## Available Commands

The package provides several Artisan commands to manage your modules. By default, they are prefixed with `lm:` (you can change this in the `laravel-module.php` config).

### Scaffolding

- **Create a Module**: Generate a new module skeleton.
    ```bash
    php artisan lm:module:make {slug}
    ```
    _(Example: `php artisan lm:module:make customers` will create a `Customers` module with controllers, views, config, and routes)._

### Management

- **List Modules**: View all discovered modules and their statuses.
    ```bash
    php artisan lm:module:list
    ```
- **Install a Module**: Install a specific module.
    ```bash
    php artisan lm:module:install {slug}
    ```
- **Activate a Module**: Enable an installed module.
    ```bash
    php artisan lm:module:activate {slug}
    ```
- **Deactivate a Module**: Disable an active module.
    ```bash
    php artisan lm:module:deactivate {slug}
    ```
- **Uninstall a Module**: Remove a module entirely.
    ```bash
    php artisan lm:module:uninstall {slug}
    ```

## Usage

When you create a module, a structured directory is created in `app/Modules` (or your configured path) with a `module.json` manifest, a service provider, controllers, routes, and views.

Active modules are automatically discovered and loaded into your Laravel application, registering their routes, views, and navigation menus.

You can customize the module path, navigation settings, and more in the published `config/laravel-module.php` file.

## Hooks & Filters

The package includes a built-in WordPress-style hook system to let modules interact without tight coupling.
You can use `add_action`, `do_action`, `add_filter`, and `apply_filters` inside your modules.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Raju Bepary](https://github.com/rajubeparybd)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
