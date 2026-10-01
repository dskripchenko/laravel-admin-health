<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminHealth;

use Composer\InstalledVersions;
use Dskripchenko\LaravelAdmin\Admin;
use Dskripchenko\LaravelAdmin\Permission\ItemPermission;
use Dskripchenko\LaravelAdmin\Plugin\AdminPlugin;
use Dskripchenko\LaravelAdminHealth\Resources\HealthResultResource;
use Dskripchenko\LaravelAdminHealth\Status\HealthStatusIndicator;
use Dskripchenko\LaravelAdminHealth\Widgets\HealthOverviewWidget;

final class AdminHealthPlugin implements AdminPlugin
{
    public function name(): string
    {
        return 'health';
    }

    public function version(): string
    {
        return InstalledVersions::getPrettyVersion('dskripchenko/laravel-admin-health') ?? 'dev';
    }

    public function register(): void
    {
        // No-op.
    }

    public function boot(Admin $admin): void
    {
        $admin->resources([HealthResultResource::class]);

        // The two surfaces the pack was specified with and shipped without: a
        // dot in the header and a card on the dashboard. Until they existed,
        // the checks answered only when someone remembered to go and ask —
        // which is the opposite of what a health check is for.
        if ((bool) config('admin-health.topbar_indicator', true)) {
            $admin->statusIndicators([HealthStatusIndicator::class]);
        }

        $admin->widgets([HealthOverviewWidget::class]);

        $admin->permissions(
            ItemPermission::group(__('Системные'))
                ->addPermission('admin.system.health.view', __('Health-check: просмотр'))
                ->addPermission('admin.system.health.run', __('Health-check: ручной запуск')),
        );
    }
}
