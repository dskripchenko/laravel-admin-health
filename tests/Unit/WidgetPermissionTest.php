<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminHealth\Tests\Unit;

use Dskripchenko\LaravelAdminHealth\Tests\TestCase;
use Dskripchenko\LaravelAdminHealth\Widgets\HealthOverviewWidget;

final class WidgetPermissionTest extends TestCase
{
    public function test_the_widget_requires_the_view_permission_by_default(): void
    {
        $this->assertSame('admin.system.health.view', (new HealthOverviewWidget)->getPermission());
    }
}
