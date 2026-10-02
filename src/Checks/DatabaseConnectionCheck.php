<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminHealth\Checks;

use Dskripchenko\LaravelAdminHealth\HealthCheck;
use Dskripchenko\LaravelAdminHealth\HealthResult;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Checks every database connection from config['connections'] by trying to get a
 * PDO instance. Failing when at least one is unreachable.
 */
final class DatabaseConnectionCheck implements HealthCheck
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(public readonly array $config = []) {}

    public function id(): string
    {
        return 'database.connections';
    }

    public function name(): string
    {
        return __('Соединения с БД');
    }

    public function category(): string
    {
        return 'database';
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
        /** @var array<int, mixed> $connections */
        $connections = (array) ($this->config['connections'] ?? [config('database.default')]);
        $failures = [];
        $checked = [];

        foreach ($connections as $connection) {
            if (! is_string($connection) || $connection === '') {
                continue;
            }
            $checked[] = $connection;
            try {
                DB::connection($connection)->getPdo();
            } catch (Throwable $e) {
                $failures[$connection] = $e->getMessage();
            }
        }

        if ($failures !== []) {
            return HealthResult::failing(
                'Недоступны connection(s): :connections',
                ['failures' => $failures, 'checked' => $checked],
                ['connections' => implode(', ', array_keys($failures))],
            );
        }

        return HealthResult::ok(
            'Все :count соединение(й) активны',
            ['checked' => $checked],
            ['count' => count($checked)],
        );
    }
}
