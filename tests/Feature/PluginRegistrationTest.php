<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminHealth\Tests\Feature;

use Dskripchenko\LaravelAdmin\Admin;
use Dskripchenko\LaravelAdminHealth\AdminHealthPlugin;
use Dskripchenko\LaravelAdminHealth\Resources\HealthResultResource;
use Dskripchenko\LaravelAdminHealth\Tests\TestCase;
use Dskripchenko\LaravelAdminHealth\Widgets\HealthOverviewWidget;

final class PluginRegistrationTest extends TestCase
{
    public function test_plugin_in_admin_plugins_config(): void
    {
        $plugins = (array) config('admin.plugins', []);
        $this->assertContains(AdminHealthPlugin::class, $plugins);
    }

    public function test_resource_registered(): void
    {
        /** @var Admin $admin */
        $admin = app(Admin::class);
        $this->assertContains(HealthResultResource::class, $admin->getResources());
    }

    public function test_permissions_registered(): void
    {
        /** @var Admin $admin */
        $admin = app(Admin::class);
        $registry = $admin->getPermissionRegistry();
        $this->assertTrue($registry->knows('admin.system.health.view'));
        $this->assertTrue($registry->knows('admin.system.health.run'));
    }

    public function test_run_command_exists(): void
    {
        $output = $this->artisan('admin:health:run');
        $output->assertSuccessful();
    }

    public function test_cleanup_command_exists(): void
    {
        $output = $this->artisan('admin:health:cleanup');
        $output->assertSuccessful();
    }

    public function test_version_is_not_hardcoded(): void
    {
        $version = (new AdminHealthPlugin)->version();
        $this->assertNotSame('', $version);
        $this->assertNotSame('0.1.0', $version);
    }

    public function test_english_translations_are_loaded(): void
    {
        app()->setLocale('en');
        $this->assertSame('Healthy', __('В норме'));
        $this->assertSame('3 checks failed', trans_choice(
            ':count проверка не прошла|:count проверки не прошли|:count проверок не прошло',
            3,
            ['count' => 3],
        ));
    }

    public function test_the_section_widget_and_columns_read_in_the_panel_language(): void
    {
        $cases = [
            'ru' => ['label' => 'Проверки состояния', 'created' => 'Запущено', 'check' => 'ID проверки'],
            'en' => ['label' => 'Health checks', 'created' => 'Ran at', 'check' => 'Check ID'],
        ];
        foreach ($cases as $locale => $expected) {
            app()->setLocale($locale);
            $this->assertSame($expected['label'], HealthResultResource::label());
            $this->assertSame($expected['label'], (new HealthOverviewWidget)->toArray()['title']);

            $labels = [];
            foreach ((new HealthResultResource)->columns() as $column) {
                $arr = $column->toArray();
                $labels[$arr['name']] = $arr['label'];
            }
            $this->assertSame($expected['created'], $labels['ran_at']);
            $this->assertSame($expected['check'], $labels['check_id']);
            if ($locale === 'ru') {
                foreach ($labels as $name => $label) {
                    $this->assertMatchesRegularExpression('/\p{Cyrillic}|^ID$/u', (string) $label, (string) $name);
                }
            }
        }
    }
}
