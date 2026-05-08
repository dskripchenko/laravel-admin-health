# dskripchenko/laravel-admin-health

> 🌐 [English](README.md) · [Русский](README.ru.md) · [Deutsch](README.de.md) · **中文**

健康检查仪表板。无 spatie/laravel-health 依赖的自有实现。内置检查器：Database、Cache、Queue、Storage、Schedule、Disk-space、OPcache，以及自定义检查的契约。

[`dskripchenko/laravel-admin`](https://github.com/dskripchenko/laravel-admin) 的姐妹包。

## 安装

```bash
composer require dskripchenko/laravel-admin-health
php artisan migrate
```

插件通过 Laravel package discovery 自动注册。

## 文档

- [快速开始](docs/en/getting-started.md) (en)
- [使用](docs/en/usage.md) (en)

## 许可证

[MIT](LICENSE) © Denis Skripchenko
