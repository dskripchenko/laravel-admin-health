<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminHealth\Checks;

use Dskripchenko\LaravelAdminHealth\HealthCheck;
use Dskripchenko\LaravelAdminHealth\HealthResult;

/**
 * The free space on the disks (a warning below X%, failing below Y%).
 *
 * It uses disk_free_space and disk_total_space against the root of every
 * filesystem disk (reading storage/app/{disk-name} or public_path).
 */
final class DiskSpaceCheck implements HealthCheck
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(public readonly array $config = []) {}

    public function id(): string
    {
        return 'storage.disk_space';
    }

    public function name(): string
    {
        return __('Свободное место');
    }

    public function category(): string
    {
        return 'storage';
    }

    public function frequency(): string
    {
        return (string) ($this->config['frequency'] ?? '5m');
    }

    public function timeout(): int
    {
        return (int) ($this->config['timeout'] ?? 3);
    }

    public function run(): HealthResult
    {
        /** @var array<int, mixed> $paths */
        $paths = (array) ($this->config['paths'] ?? [storage_path()]);
        $warnPct = (int) ($this->config['warn_below_pct'] ?? 15);
        $failPct = (int) ($this->config['fail_below_pct'] ?? 5);

        $report = [];
        $worstStatus = 'ok';
        $messages = [];

        foreach ($paths as $path) {
            if (! is_string($path) || ! is_dir($path)) {
                continue;
            }
            $free = @disk_free_space($path);
            $total = @disk_total_space($path);
            if ($free === false || $total === false || $total === 0.0) {
                $report[$path] = ['error' => 'cannot read disk space'];
                $worstStatus = 'failing';
                $messages[] = "$path: read error";

                continue;
            }
            $freePct = round(($free / $total) * 100, 1);
            $report[$path] = [
                'free_bytes' => (int) $free,
                'total_bytes' => (int) $total,
                'free_pct' => $freePct,
            ];

            if ($freePct < $failPct) {
                $worstStatus = 'failing';
                $messages[] = "$path: $freePct% free (< $failPct%)";
            } elseif ($freePct < $warnPct && $worstStatus !== 'failing') {
                $worstStatus = 'warning';
                $messages[] = "$path: $freePct% free (< $warnPct%)";
            }
        }

        $meta = ['paths' => $report];

        if ($worstStatus === 'failing') {
            return HealthResult::failing(implode('; ', $messages), $meta);
        }
        if ($worstStatus === 'warning') {
            return HealthResult::warning(implode('; ', $messages), $meta);
        }

        return HealthResult::ok('Свободного места достаточно', $meta);
    }
}
