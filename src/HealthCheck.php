<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminHealth;

/**
 * The contract of a single check.
 *
 * Every check declares:
 *   - id() — a unique slug ('database.default', 'queue.imports')
 *   - name() — a human-readable one ("The database connection")
 *   - category() — for the grouping in the UI (database / cache / queue /
 *     storage / custom)
 *   - frequency() — '1m' | '5m' | '15m' | '1h' — it decides when the runner
 *     should run it again
 *   - timeout() — in seconds (the runner aborts a check that hangs)
 *   - run() — the check itself, returning a HealthResult.
 *
 * The checks themselves are stateless — no fields at all. The state (last_run,
 * status) lives in the admin_health_results table and is updated by the runner.
 */
interface HealthCheck
{
    public function id(): string;

    public function name(): string;

    public function category(): string;

    public function frequency(): string;

    public function timeout(): int;

    public function run(): HealthResult;
}
