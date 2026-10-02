<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminHealth\Console;

use Dskripchenko\LaravelAdminHealth\HealthRunner;
use Illuminate\Console\Command;

/**
 * `php artisan admin:health:run`
 *
 * Runs every registered health check, saves the results into
 * `admin_health_results` and emits HealthCheckStatusChanged when a status
 * changes.
 *
 * Used from the scheduler:
 *   $schedule->command('admin:health:run')->everyMinute()->withoutOverlapping();
 */
final class RunHealthChecksCommand extends Command
{
    protected $signature = 'admin:health:run';

    protected $description = 'Run all registered admin health-checks';

    public function handle(HealthRunner $runner): int
    {
        $report = $runner->runAll();

        if ($report === []) {
            $this->info('No health checks registered.');

            return self::SUCCESS;
        }

        $hasFail = false;
        foreach ($report as $row) {
            $status = $row['result']->status;
            $line = "[$status] {$row['check']->id()} ({$row['duration_ms']}ms): {$row['result']->text()}";
            if ($status === 'ok') {
                $this->info($line);
            } elseif ($status === 'warning') {
                $this->warn($line);
            } else {
                // 'failing' is the only remaining option of the
                // HealthResult::$status union type. PHPStan is sure of the
                // exhaustiveness.
                $this->error($line);
                $hasFail = true;
            }
        }

        return $hasFail ? self::FAILURE : self::SUCCESS;
    }
}
