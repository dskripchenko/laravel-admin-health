<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminHealth;

use Dskripchenko\LaravelAdminHealth\Models\HealthResultRecord;
use Illuminate\Support\Facades\Cache;

/**
 * The current picture: the latest result of every registered check.
 *
 * The table stores history — one row per run — and everything that wants to
 * answer "is the installation alive right now" needs the last row of each
 * check. Written once here rather than in both the widget and the top-bar
 * indicator: two copies of this query would drift apart, and the header
 * disagreeing with the dashboard is worse than either being wrong alone.
 *
 * The result is cached for a few seconds. The indicator polls once a minute
 * per open tab, and an operations room with the panel on a wall would
 * otherwise turn a diagnostic into a load source of its own.
 */
final class HealthSummary
{
    private const CACHE_KEY = 'admin-health:summary';

    /** Long enough to absorb a burst of tabs, short enough not to lie. */
    private const CACHE_TTL = 10;

    public function __construct(private readonly HealthRegistry $registry) {}

    /**
     * The latest result of every check, keyed by check id.
     *
     * A registered check that has never run is `null` rather than absent: "not
     * run yet" is a state worth showing — the scheduler may not be wired up at
     * all, which is exactly the failure a health pack ought to catch.
     *
     * The message is translated into the current locale on every call: the
     * cache holds the source string and its placeholders, not one language.
     *
     * @return array<string, array{status: string, message: string|null, ran_at: string|null}|null>
     */
    public function latest(): array
    {
        $latest = [];
        foreach ($this->cached() as $id => $result) {
            if ($result === null) {
                $latest[$id] = null;

                continue;
            }

            $latest[$id] = [
                'status' => $result['status'],
                'message' => $result['message'] === null ? null : HealthResult::translate($result['message'], $result['replace']),
                'ran_at' => $result['ran_at'],
            ];
        }

        return $latest;
    }

    /**
     * @return array<string, array{status: string, message: string|null, replace: array<string, mixed>, ran_at: string|null}|null>
     */
    private function cached(): array
    {
        /** @var array<string, array{status: string, message: string|null, replace: array<string, mixed>, ran_at: string|null}|null> $summary */
        $summary = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function (): array {
            $ids = $this->registry->ids();
            $latest = array_fill_keys($ids, null);

            if ($ids === []) {
                return $latest;
            }

            // One pass over the rows of the registered checks, newest first: the
            // first row seen for an id is its current state. A GROUP BY with a
            // correlated max(ran_at) would be tidier SQL and would need the
            // portability of it across sqlite/mysql/pgsql to be worth the
            // trouble — the table is small by construction, the cleanup command
            // sees to that.
            $rows = HealthResultRecord::query()
                ->whereIn('check_id', $ids)
                ->orderByDesc('ran_at')
                ->orderByDesc('id')
                ->get(['check_id', 'status', 'message', 'meta', 'ran_at']);

            foreach ($rows as $row) {
                if (($latest[$row->check_id] ?? null) !== null) {
                    continue;
                }

                $latest[$row->check_id] = [
                    'status' => $row->status,
                    'message' => $row->messageSource(),
                    'replace' => $row->messageReplace(),
                    'ran_at' => $row->ran_at->toIso8601String(),
                ];
            }

            return $latest;
        });

        return $summary;
    }

    /**
     * How many checks sit in each state, plus `never` for those that have not
     * run at all.
     *
     * @return array{ok: int, warning: int, failing: int, never: int}
     */
    public function counts(): array
    {
        $counts = ['ok' => 0, 'warning' => 0, 'failing' => 0, 'never' => 0];

        foreach ($this->latest() as $result) {
            if ($result === null) {
                $counts['never']++;

                continue;
            }

            $status = $result['status'];
            $counts[$status] = ($counts[$status] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * The state of the installation as a whole — the worst of its checks.
     *
     * `unknown` covers both "nothing registered" and "registered but never
     * run": in both cases the honest answer is that nobody has looked.
     *
     * @return 'ok'|'warning'|'failing'|'unknown'
     */
    public function overall(): string
    {
        $counts = $this->counts();

        if ($counts['failing'] > 0) {
            return 'failing';
        }

        if ($counts['warning'] > 0) {
            return 'warning';
        }

        if ($counts['ok'] > 0) {
            return 'ok';
        }

        return 'unknown';
    }

    /**
     * Drops the cached picture — the runner calls this after a run so a manual
     * "run the checks" button does not leave the header showing the old answer
     * for another ten seconds.
     */
    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
