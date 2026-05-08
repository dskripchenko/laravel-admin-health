---
title: Getting Started
locale: en
status: stable
---

# Getting Started

`dskripchenko/laravel-admin-health` is a sister-pack of `dskripchenko/laravel-admin`.
Install once — it auto-registers and surfaces in your admin.

## Install

```bash
composer require dskripchenko/laravel-admin-health
php artisan migrate
```

## Configure

```bash
php artisan vendor:publish --tag=health-config
```

Edit `config/health.php`.


## What it adds

A `/admin/dashboard/health` dashboard with built-in checkers and a
`HealthCheckRunner` that you can hook into your scheduler:

```php
// app/Console/Kernel.php
$schedule->call(fn () => app(HealthCheckRunner::class)->runAll())
    ->everyFiveMinutes();
```

Built-in checkers:

- DatabaseConnectionChecker
- CacheReachableChecker
- QueueWorkingChecker
- StorageWritableChecker
- ScheduleStuckChecker
- DiskSpaceChecker
- OPcacheStatusChecker

## See also

- [Usage](usage.md)
- [Glossary](https://github.com/dskripchenko/laravel-admin/blob/main/docs/en/glossary.md)
