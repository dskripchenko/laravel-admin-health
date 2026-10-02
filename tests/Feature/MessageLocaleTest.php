<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminHealth\Tests\Feature;

use Dskripchenko\LaravelAdmin\Testing\Concerns\ActsAsAdmin;
use Dskripchenko\LaravelAdminHealth\Checks\ClosureCheck;
use Dskripchenko\LaravelAdminHealth\Checks\DiskSpaceCheck;
use Dskripchenko\LaravelAdminHealth\HealthRegistry;
use Dskripchenko\LaravelAdminHealth\HealthResult;
use Dskripchenko\LaravelAdminHealth\HealthRunner;
use Dskripchenko\LaravelAdminHealth\HealthSummary;
use Dskripchenko\LaravelAdminHealth\Models\HealthResultRecord;
use Dskripchenko\LaravelAdminHealth\Tests\TestCase;
use Illuminate\Support\Carbon;

/**
 * A check runs in the scheduler's locale; its message is read in the
 * reader's. The runner stores the source string and its placeholders, and
 * every reader translates them.
 */
final class MessageLocaleTest extends TestCase
{
    use ActsAsAdmin;

    private function queueResult(): HealthResult
    {
        return HealthResult::ok('Очереди в норме (:pending total pending, :failed failed)', ['depths' => ['default' => 3]], ['pending' => 3, 'failed' => 1]);
    }

    public function test_the_runner_stores_the_source_and_the_record_translates_it_per_locale(): void
    {
        app()->setLocale('en');
        app(HealthRunner::class)->runOne(new ClosureCheck('queue.test', 'Queue', fn () => $this->queueResult()));

        $record = HealthResultRecord::query()->firstOrFail();
        $this->assertSame('Очереди в норме (:pending total pending, :failed failed)', $record->messageSource());
        $this->assertSame(['pending' => 3, 'failed' => 1], $record->messageReplace());
        $this->assertSame(['depths' => ['default' => 3]], $record->meta);

        $this->assertSame('Queues healthy (3 total pending, 1 failed)', $record->message);
        app()->setLocale('ru');
        $this->assertSame('Очереди в норме (3 total pending, 1 failed)', $record->fresh()?->message);
        $this->assertSame('Очереди в норме (3 total pending, 1 failed)', $record->fresh()?->toArray()['message']);
    }

    public function test_a_row_written_before_still_reads_as_it_was_stored(): void
    {
        HealthResultRecord::query()->create([
            'check_id' => 'legacy',
            'status' => 'ok',
            'message' => 'Queues healthy (11 total pending, 8 failed)',
            'meta' => ['failed_jobs_total' => 8],
            'duration_ms' => 0,
            'ran_at' => Carbon::now(),
        ]);

        app()->setLocale('ru');
        $record = HealthResultRecord::query()->firstOrFail();
        $this->assertSame('Queues healthy (11 total pending, 8 failed)', $record->message);
        $this->assertSame(['failed_jobs_total' => 8], $record->meta);
    }

    public function test_the_summary_translates_after_the_cache(): void
    {
        $registry = app(HealthRegistry::class);
        $property = (new \ReflectionClass($registry))->getProperty('checks');
        $property->setValue($registry, ['queue.test' => new ClosureCheck('queue.test', 'Queue', fn () => $this->queueResult())]);
        app(HealthRunner::class)->runAll();

        app()->setLocale('ru');
        $this->assertSame('Очереди в норме (3 total pending, 1 failed)', app(HealthSummary::class)->latest()['queue.test']['message'] ?? null);
        app()->setLocale('en');
        $this->assertSame('Queues healthy (3 total pending, 1 failed)', app(HealthSummary::class)->latest()['queue.test']['message'] ?? null);
    }

    public function test_the_disk_space_check_says_ok_in_the_readers_language(): void
    {
        $result = (new DiskSpaceCheck(['paths' => [sys_get_temp_dir()], 'warn_below_pct' => 0, 'fail_below_pct' => 0]))->run();

        $this->assertTrue($result->isOk());
        app()->setLocale('en');
        $this->assertSame('Disk space OK', $result->text());
        app()->setLocale('ru');
        $this->assertSame('Свободного места достаточно', $result->text());
    }

    public function test_the_list_shows_messages_in_the_request_locale(): void
    {
        app(HealthRunner::class)->runOne(new ClosureCheck('queue.test', 'Queue', fn () => $this->queueResult()));
        $this->actingAsSuperAdmin();

        foreach (['en' => 'Queues healthy (3 total pending, 1 failed)', 'ru' => 'Очереди в норме (3 total pending, 1 failed)'] as $locale => $message) {
            $this->withHeader('Accept-Language', $locale)
                ->postJson('/api/admin/system-health-results/search', [])
                ->assertOk()
                ->assertJsonPath('payload.data.0.message', $message);
        }
    }
}
