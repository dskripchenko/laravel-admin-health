<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminHealth\Tests\Feature;

use Dskripchenko\LaravelAdminHealth\Checks\ClosureCheck;
use Dskripchenko\LaravelAdminHealth\HealthRegistry;
use Dskripchenko\LaravelAdminHealth\HealthResult;
use Dskripchenko\LaravelAdminHealth\HealthSummary;
use Dskripchenko\LaravelAdminHealth\Models\HealthResultRecord;
use Dskripchenko\LaravelAdminHealth\Status\HealthStatusIndicator;
use Dskripchenko\LaravelAdminHealth\Tests\TestCase;
use Dskripchenko\LaravelAdminHealth\Widgets\HealthOverviewWidget;
use Illuminate\Support\Carbon;

final class HealthSummaryTest extends TestCase
{
    /**
     * ClosureCheck takes a closure, so it cannot come through a class-string —
     * the registry's property is set in place, as HealthRunnerTest does.
     *
     * @param  array<string, ClosureCheck>  $checks
     */
    private function registerChecks(array $checks): void
    {
        /** @var HealthRegistry $registry */
        $registry = $this->app->make(HealthRegistry::class);
        $property = (new \ReflectionClass($registry))->getProperty('checks');
        $property->setValue($registry, $checks);

        $this->app->make(HealthSummary::class)->forget();
    }

    private function record(string $checkId, string $status, string $ranAt): void
    {
        HealthResultRecord::query()->create([
            'check_id' => $checkId,
            'status' => $status,
            'message' => $status,
            'meta' => null,
            'duration_ms' => 1,
            'ran_at' => Carbon::parse($ranAt),
        ]);
    }

    public function test_latest_takes_the_newest_row_of_each_check(): void
    {
        $this->registerChecks([
            'a' => new ClosureCheck('a', 'A', fn () => true),
            'b' => new ClosureCheck('b', 'B', fn () => true),
        ]);

        $this->record('a', 'failing', '2026-08-01 10:00:00');
        $this->record('a', 'ok', '2026-08-01 11:00:00');
        $this->record('b', 'ok', '2026-08-01 09:00:00');

        $latest = $this->app->make(HealthSummary::class)->latest();

        // The table keeps history; the answer is the last row, not the worst
        // one ever seen.
        $this->assertSame('ok', $latest['a']['status']);
        $this->assertSame('ok', $latest['b']['status']);
    }

    public function test_a_check_that_never_ran_is_null_rather_than_absent(): void
    {
        $this->registerChecks([
            'a' => new ClosureCheck('a', 'A', fn () => true),
            'never' => new ClosureCheck('never', 'Never', fn () => true),
        ]);
        $this->record('a', 'ok', '2026-08-01 10:00:00');

        $summary = $this->app->make(HealthSummary::class);

        $this->assertNull($summary->latest()['never']);
        $this->assertSame(1, $summary->counts()['never']);
        // Registered but never run usually means nobody wired up the scheduler
        // — the very failure a health pack is for.
        $this->assertSame('ok', $summary->overall());
    }

    public function test_overall_is_the_worst_of_the_checks(): void
    {
        $this->registerChecks([
            'a' => new ClosureCheck('a', 'A', fn () => true),
            'b' => new ClosureCheck('b', 'B', fn () => true),
        ]);
        $this->record('a', 'ok', '2026-08-01 10:00:00');
        $this->record('b', 'warning', '2026-08-01 10:00:00');

        $this->assertSame('warning', $this->app->make(HealthSummary::class)->overall());

        $this->record('b', 'failing', '2026-08-01 12:00:00');
        $this->app->make(HealthSummary::class)->forget();

        $this->assertSame('failing', $this->app->make(HealthSummary::class)->overall());
    }

    public function test_overall_is_unknown_without_any_result(): void
    {
        $this->registerChecks([]);

        $this->assertSame('unknown', $this->app->make(HealthSummary::class)->overall());
    }

    public function test_a_run_drops_the_cached_summary(): void
    {
        $this->registerChecks(['a' => new ClosureCheck('a', 'A', fn () => true)]);
        $this->record('a', 'failing', '2026-08-01 10:00:00');

        $summary = $this->app->make(HealthSummary::class);
        $this->assertSame('failing', $summary->overall());

        $this->app->make(\Dskripchenko\LaravelAdminHealth\HealthRunner::class)
            ->runOne(new ClosureCheck('a', 'A', fn () => HealthResult::ok()));

        // Without the invalidation the manual run would report success while
        // the header kept showing the old answer for another ten seconds.
        $this->assertSame('ok', $summary->overall());
    }

    public function test_indicator_translates_failing_into_the_core_vocabulary(): void
    {
        $this->registerChecks([
            'a' => new ClosureCheck('a', 'A', fn () => true),
            'b' => new ClosureCheck('b', 'B', fn () => true),
        ]);
        $this->record('a', 'ok', '2026-08-01 10:00:00');
        $this->record('b', 'failing', '2026-08-01 10:00:00');

        $state = $this->app->make(HealthStatusIndicator::class)->state();

        $this->assertSame('admin.health', $this->app->make(HealthStatusIndicator::class)->key());
        // The checks say `failing`, the core's top bar says `error`.
        $this->assertSame('error', $state['status']);
        $this->assertSame('/r/system-health-results', $state['url']);
        $this->assertStringContainsString('1', (string) $state['detail']);
    }

    public function test_indicator_is_ok_when_every_check_passes(): void
    {
        $this->registerChecks(['a' => new ClosureCheck('a', 'A', fn () => true)]);
        $this->record('a', 'ok', '2026-08-01 10:00:00');

        // `ok` is what the panel draws as nothing at all — the header speaks
        // only when something is off.
        $this->assertSame('ok', $this->app->make(HealthStatusIndicator::class)->state()['status']);
    }

    public function test_the_plugin_registers_both_surfaces(): void
    {
        $admin = $this->app->make(\Dskripchenko\LaravelAdmin\Admin::class);

        $this->assertContains(HealthStatusIndicator::class, $admin->getStatusIndicators());
        $this->assertContains(HealthOverviewWidget::class, $admin->getWidgets());
    }

    public function test_widget_counts_the_states(): void
    {
        $this->registerChecks([
            'a' => new ClosureCheck('a', 'A', fn () => true),
            'b' => new ClosureCheck('b', 'B', fn () => true),
            'never' => new ClosureCheck('never', 'Never', fn () => true),
        ]);
        $this->record('a', 'ok', '2026-08-01 10:00:00');
        $this->record('b', 'failing', '2026-08-01 10:00:00');

        $data = $this->app->make(HealthOverviewWidget::class)->data();
        $values = [];
        foreach ($data['stats'] as $stat) {
            $values[$stat['label']] = $stat['value'];
        }

        $this->assertSame(1, $values[__('В норме')]);
        $this->assertSame(1, $values[__('Не прошли')]);
        $this->assertSame(1, $values[__('Ни разу не запускались')]);
    }

    public function test_widget_hides_the_never_run_card_when_there_is_none(): void
    {
        $this->registerChecks(['a' => new ClosureCheck('a', 'A', fn () => true)]);
        $this->record('a', 'ok', '2026-08-01 10:00:00');

        $labels = array_column($this->app->make(HealthOverviewWidget::class)->data()['stats'], 'label');

        // A zero of failures is worth seeing; a zero of never-run checks is
        // just a card that says nothing.
        $this->assertNotContains(__('Ни разу не запускались'), $labels);
    }
}
