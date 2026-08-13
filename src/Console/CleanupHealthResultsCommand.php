<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminHealth\Console;

use Dskripchenko\LaravelAdminHealth\HealthRunner;
use Illuminate\Console\Command;

/**
 * `php artisan admin:health:cleanup`
 *
 * Deletes the old rows from `admin_health_results` (the TTL comes from the
 * config). Run it once a day from the scheduler.
 */
final class CleanupHealthResultsCommand extends Command
{
    protected $signature = 'admin:health:cleanup';

    protected $description = 'Cleanup old health-check results (TTL from config)';

    public function handle(HealthRunner $runner): int
    {
        $days = (int) config('admin-health.history_days', 7);
        $deleted = $runner->cleanupOlderThan($days);

        $this->info("Deleted $deleted health-result row(s) older than $days days");

        return self::SUCCESS;
    }
}
