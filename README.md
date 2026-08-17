# dskripchenko/laravel-admin-health

> 🌐 **English** · [Русский](docs/ru/README.md) · [Deutsch](docs/de/README.md) · [中文](docs/zh/README.md)

Health-checks for the admin panel. Its own implementation, with no dependency on
spatie/laravel-health. Built-in checks: database connection, cache, queue depth
and disk space, plus a closure check and a contract for your own.

The state reaches you rather than waiting to be looked up: a dot in the top bar
the moment something fails, a counts card on the dashboard, and the full history
in its own section.

A sister-pack for [`dskripchenko/laravel-admin`](https://github.com/dskripchenko/laravel-admin).

[![Packagist](https://img.shields.io/packagist/v/dskripchenko/laravel-admin-health)](https://packagist.org/packages/dskripchenko/laravel-admin-health)
[![License](https://img.shields.io/packagist/l/dskripchenko/laravel-admin-health)](LICENSE)

## Install

```bash
composer require dskripchenko/laravel-admin-health
php artisan migrate
```

Nothing runs the checks by itself — add the runner to the scheduler:

```php
// routes/console.php
Schedule::command('admin:health:run')->everyMinute();
Schedule::command('admin:health:cleanup')->daily();
```

The plugin auto-registers via Laravel package discovery. To publish the
config:

```bash
php artisan vendor:publish --tag=admin-health-config
```

## Documentation

- [Getting started](docs/en/getting-started.md)
- [Usage](docs/en/usage.md)

## License

[MIT](LICENSE) © Denis Skripchenko
