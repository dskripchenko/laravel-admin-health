# dskripchenko/laravel-admin-health

> 🌐 **English** · [Русский](README.ru.md) · [Deutsch](README.de.md) · [中文](README.zh.md)

Health-checks dashboard. Own implementation without spatie/laravel-health. Built-in checkers: Database, Cache, Queue, Storage, Schedule, Disk-space, OPcache, plus a contract for custom checks.

A sister-pack for [`dskripchenko/laravel-admin`](https://github.com/dskripchenko/laravel-admin).

[![Packagist](https://img.shields.io/packagist/v/dskripchenko/laravel-admin-health)](https://packagist.org/packages/dskripchenko/laravel-admin-health)
[![License](https://img.shields.io/packagist/l/dskripchenko/laravel-admin-health)](LICENSE)

## Install

```bash
composer require dskripchenko/laravel-admin-health
php artisan migrate
```

The plugin auto-registers via Laravel package discovery. To publish the
config:

```bash
php artisan vendor:publish --tag=health-config
```

## Documentation

- [Getting started](docs/en/getting-started.md)
- [Usage](docs/en/usage.md)

## License

[MIT](LICENSE) © Denis Skripchenko
