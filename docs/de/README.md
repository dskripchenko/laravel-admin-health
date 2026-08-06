# dskripchenko/laravel-admin-health

> 🌐 [English](../../README.md) · [Русский](../ru/README.md) · **Deutsch** · [中文](../zh/README.md)

Health-Checks-Dashboard. Eigene Implementierung ohne spatie/laravel-health. Eingebaute Checker: Database, Cache, Queue, Storage, Schedule, Disk-space, OPcache + Vertrag für eigene Checks.

Ein Sister-Pack für [`dskripchenko/laravel-admin`](https://github.com/dskripchenko/laravel-admin).

## Installation

```bash
composer require dskripchenko/laravel-admin-health
php artisan migrate
```

Das Plugin registriert sich automatisch über Laravel Package Discovery.

## Dokumentation

- [Erste Schritte](../../docs/en/getting-started.md) (en)
- [Verwendung](../../docs/en/usage.md) (en)

## Lizenz

[MIT](../../LICENSE) © Denis Skripchenko
