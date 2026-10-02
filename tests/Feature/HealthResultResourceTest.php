<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminHealth\Tests\Feature;

use Dskripchenko\LaravelAdmin\Testing\Concerns\ActsAsAdmin;
use Dskripchenko\LaravelAdminHealth\Checks\ClosureCheck;
use Dskripchenko\LaravelAdminHealth\HealthRegistry;
use Dskripchenko\LaravelAdminHealth\HealthResult;
use Dskripchenko\LaravelAdminHealth\Models\HealthResultRecord;
use Dskripchenko\LaravelAdminHealth\Resources\HealthResultResource;
use Dskripchenko\LaravelAdminHealth\Tests\TestCase;

final class HealthResultResourceTest extends TestCase
{
    use ActsAsAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $registry = app(HealthRegistry::class);
        $property = (new \ReflectionClass($registry))->getProperty('checks');
        $property->setValue($registry, [
            'test.ok' => new ClosureCheck('test.ok', 'OK', fn () => true),
            'test.warn' => new ClosureCheck('test.warn', 'Warn', fn () => HealthResult::warning('high')),
        ]);
    }

    public function test_the_status_badges_carry_the_filter_captions(): void
    {
        $captions = [
            'ru' => ['ok' => 'В норме', 'warning' => 'Замечания', 'failing' => 'Не прошли'],
            'en' => ['ok' => 'Healthy', 'warning' => 'Warnings', 'failing' => 'Failing'],
        ];

        foreach ($captions as $locale => $labels) {
            app()->setLocale($locale);
            $status = collect((new HealthResultResource)->columns())->first(fn ($c) => $c->name() === 'status');
            $this->assertNotNull($status);
            $this->assertSame($labels, $status->toArray()['meta']['labels'] ?? null, $locale);
        }
    }

    public function test_run_checks_runs_every_check_and_reports_the_outcome(): void
    {
        $this->actingAsSuperAdmin();

        $this->withHeader('Accept-Language', 'en')
            ->postJson('/api/admin/system-health-results/action', ['key' => 'run_checks', 'ids' => []])
            ->assertOk()
            ->assertJsonPath('payload.message', 'Checks done: 1 healthy, 1 with warnings, 0 failing');

        $this->assertSame(2, HealthResultRecord::query()->count());
    }

    public function test_run_checks_needs_the_run_permission(): void
    {
        $this->actingAsAdmin(permissions: ['admin.system.health.view']);

        $this->postJson('/api/admin/system-health-results/action', ['key' => 'run_checks', 'ids' => []])
            ->assertForbidden();

        $this->assertSame(0, HealthResultRecord::query()->count());
    }
}
