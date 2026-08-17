---
title: Getting Started
locale: en
status: stable
---

# Getting Started

`dskripchenko/laravel-admin-health` is a sister-pack of `dskripchenko/laravel-admin`.
Install it once — it registers itself through package discovery and appears in
the panel.

## Install

```bash
composer require dskripchenko/laravel-admin-health
php artisan migrate
```

Nothing runs the checks on its own. Add the runner to the scheduler, or the
panel will keep showing the last answer it was given, however old that is:

```php
// routes/console.php
Schedule::command('admin:health:run')->everyMinute();
Schedule::command('admin:health:cleanup')->daily();
```

`admin:health:run` runs every registered check; `admin:health:cleanup` deletes
the results older than `admin-health.history_days`.

## Configure

```bash
php artisan vendor:publish --tag=admin-health-config
```

Edit `config/admin-health.php`. The `checks` key maps a check's class to the
config array its constructor receives:

```php
'checks' => [
    DatabaseConnectionCheck::class => [
        'connections' => [env('DB_CONNECTION', 'mysql')],
        'frequency' => '1m',
        'timeout' => 5,
    ],
],
```

Removing a class from that array is how a check is turned off.

## What it adds

- **A status indicator in the top bar** — visible on every page of the panel the
  moment something fails, and invisible while everything passes.
- **A dashboard card** with the counts per state.
- **A "Health-checks" section** with the history of the runs.
- **Two permissions**: `admin.system.health.view` and `admin.system.health.run`.
- **`HealthSummary`**, if you want the same picture in your own code.

The indicator and the card need `dskripchenko/laravel-admin` ^1.30.

## The built-in checks

- `DatabaseConnectionCheck` — every listed connection answers.
- `CacheCheck` — a write and a read-back through every listed store.
- `QueueCheck` — the depth of the queues and the number of failed jobs, with
  separate warning and failing thresholds.
- `DiskSpaceCheck` — the free space of every listed path, as a percentage.
- `ClosureCheck` — for a one-off check that does not deserve a class of its own;
  it takes a closure, so it is registered in code rather than in the config.

Anything else is a class of your own — see [Usage](usage.md).

## See also

- [Usage](usage.md)
- [Glossary](https://github.com/dskripchenko/laravel-admin/blob/main/docs/en/glossary.md)
