<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminHealth\Checks;

use Dskripchenko\LaravelAdminHealth\HealthCheck;
use Dskripchenko\LaravelAdminHealth\HealthResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Throwable;

/**
 * The length of every queue (warning/failing by threshold) plus the failed_jobs
 * counter.
 */
final class QueueCheck implements HealthCheck
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(public readonly array $config = []) {}

    public function id(): string
    {
        return 'queue.depth';
    }

    public function name(): string
    {
        return __('Очереди (depth + failed)');
    }

    public function category(): string
    {
        return 'queue';
    }

    public function frequency(): string
    {
        return (string) ($this->config['frequency'] ?? '1m');
    }

    public function timeout(): int
    {
        return (int) ($this->config['timeout'] ?? 5);
    }

    public function run(): HealthResult
    {
        /** @var array<int, mixed> $queues */
        $queues = (array) ($this->config['queues'] ?? ['default']);
        $depthWarning = (int) ($this->config['depth_warning'] ?? 100);
        $depthFailing = (int) ($this->config['depth_failing'] ?? 1000);
        $failedJobsWarn = (int) ($this->config['failed_jobs_warn'] ?? 10);

        $depths = [];
        $worstStatus = 'ok';
        $messages = [];

        foreach ($queues as $queue) {
            if (! is_string($queue) || $queue === '') {
                continue;
            }
            try {
                $size = Queue::size($queue);
            } catch (Throwable $e) {
                return HealthResult::failing(
                    __('Не удалось прочитать длину очереди :queue: :message', ['queue' => $queue, 'message' => $e->getMessage()]),
                );
            }
            $depths[$queue] = $size;

            if ($size >= $depthFailing) {
                $worstStatus = 'failing';
                $messages[] = "$queue: $size (>= $depthFailing)";
            } elseif ($size >= $depthWarning && $worstStatus !== 'failing') {
                $worstStatus = 'warning';
                $messages[] = "$queue: $size (>= $depthWarning)";
            }
        }

        $failedCount = 0;
        try {
            $failedCount = (int) DB::table('failed_jobs')->count();
        } catch (Throwable) {
            // The failed_jobs table may be absent in some setups — we ignore that.
        }

        $meta = ['depths' => $depths, 'failed_jobs_total' => $failedCount];

        if ($failedCount >= $failedJobsWarn && $worstStatus === 'ok') {
            $worstStatus = 'warning';
            $messages[] = "failed_jobs: $failedCount";
        }

        if ($worstStatus === 'failing') {
            return HealthResult::failing(implode('; ', $messages), $meta);
        }
        if ($worstStatus === 'warning') {
            return HealthResult::warning(implode('; ', $messages), $meta);
        }

        return HealthResult::ok(
            __('Очереди в норме (:pending total pending, :failed failed)', ['pending' => array_sum($depths), 'failed' => $failedCount]),
            $meta,
        );
    }
}
