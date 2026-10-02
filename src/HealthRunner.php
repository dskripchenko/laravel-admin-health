<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminHealth;

use Dskripchenko\LaravelAdminHealth\Events\HealthCheckStatusChanged;
use Dskripchenko\LaravelAdminHealth\Models\HealthResultRecord;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;

/**
 * Runs the registered health checks and persists the results.
 *
 * It does not track the frequency itself — the runner always performs ALL of the
 * checks; respecting `frequency()` is the caller's job (a scheduler with a
 * cron-like interval).
 *
 * On a status change (ok → failing/warning or back) it emits the
 * HealthCheckStatusChanged event.
 */
final class HealthRunner
{
    public function __construct(
        private readonly HealthRegistry $registry,
        private readonly Dispatcher $events,
        private readonly HealthSummary $summary,
    ) {}

    /**
     * Run every check.
     *
     * @return list<array{check: HealthCheck, result: HealthResult, duration_ms: int}>
     */
    public function runAll(): array
    {
        $report = [];

        foreach ($this->registry->all() as $check) {
            $report[] = $this->runOne($check);
        }

        return $report;
    }

    /**
     * Run a single check and save the result.
     *
     * @return array{check: HealthCheck, result: HealthResult, duration_ms: int}
     */
    public function runOne(HealthCheck $check): array
    {
        $previousStatus = $this->lastStatusFor($check->id());

        $start = (int) (microtime(true) * 1000);
        try {
            $result = $check->run();
        } catch (\Throwable $e) {
            $result = HealthResult::failing(
                'Exception во время run(): :message',
                ['exception' => get_class($e)],
                ['message' => $e->getMessage()],
            );
        }
        $duration = (int) (microtime(true) * 1000) - $start;

        $this->persist($check, $result, $duration);

        // The summary is cached for a few seconds so a wall of open tabs does
        // not turn a diagnostic into load. A manual run is the one case where
        // that delay would be visible as a lie — the button says "done" and the
        // header still shows the old answer.
        $this->summary->forget();

        if ($previousStatus !== null && $previousStatus !== $result->status) {
            $this->events->dispatch(new HealthCheckStatusChanged($check, $result, $previousStatus));
        }

        return ['check' => $check, 'result' => $result, 'duration_ms' => $duration];
    }

    /**
     * Get the latest status (or null when it has never been run).
     */
    public function lastStatusFor(string $checkId): ?string
    {
        $row = HealthResultRecord::query()
            ->where('check_id', $checkId)
            ->orderByDesc('ran_at')
            ->first();

        return $row?->status;
    }

    private function persist(HealthCheck $check, HealthResult $result, int $durationMs): void
    {
        // The message is kept as a source string; its placeholders ride along
        // in meta, so a reader translates it in its own locale.
        $meta = $result->replace === []
            ? $result->meta
            : [...$result->meta, HealthResultRecord::REPLACE_KEY => $result->replace];

        HealthResultRecord::query()->create([
            'check_id' => $check->id(),
            'status' => $result->status,
            'message' => $result->message,
            'meta' => $meta,
            'duration_ms' => $durationMs,
            'ran_at' => Carbon::now(),
        ]);
    }

    /**
     * Delete the old results — the cleanup runs once a day.
     */
    public function cleanupOlderThan(int $days): int
    {
        return HealthResultRecord::query()
            ->where('ran_at', '<', Carbon::now()->subDays($days))
            ->delete();
    }
}
