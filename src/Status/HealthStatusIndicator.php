<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminHealth\Status;

use Dskripchenko\LaravelAdmin\Status\StatusIndicator;
use Dskripchenko\LaravelAdminHealth\HealthSummary;
use Dskripchenko\LaravelAdminHealth\Resources\HealthResultResource;

/**
 * The health of the installation, in the top bar.
 *
 * The package was specified with this from the start and shipped without it,
 * which made the health checks something one had to remember to go and look at
 * — the opposite of what a health check is for. The point of monitoring is to
 * find you, not to wait to be found.
 *
 * The panel shows nothing while everything is `ok`: the header speaks only when
 * something is off. So a failing check is the only thing that ever appears, and
 * it appears everywhere in the panel at once.
 */
final class HealthStatusIndicator implements StatusIndicator
{
    public function __construct(private readonly HealthSummary $summary) {}

    public function key(): string
    {
        return 'admin.health';
    }

    /**
     * @return array{status: 'ok'|'warning'|'error'|'unknown', label: string, detail?: string, url?: string}
     */
    public function state(): array
    {
        $counts = $this->summary->counts();
        $overall = $this->summary->overall();

        return [
            // The core's vocabulary calls a red state `error`; the checks call
            // it `failing`. Translated here rather than renamed there: the word
            // in the table is what four released versions have been writing.
            'status' => match ($overall) {
                'failing' => 'error',
                'warning' => 'warning',
                'ok' => 'ok',
                default => 'unknown',
            },
            'label' => $this->label($overall, $counts),
            'detail' => $this->detail($counts),
            'url' => $this->url(),
        ];
    }

    /**
     * @param  array{ok: int, warning: int, failing: int, never: int}  $counts
     */
    private function label(string $overall, array $counts): string
    {
        return match ($overall) {
            'failing' => trans_choice(':count проверка не прошла|:count проверки не прошли|:count проверок не прошло', $counts['failing'], ['count' => $counts['failing']]),
            'warning' => trans_choice(':count проверка с замечанием|:count проверки с замечаниями|:count проверок с замечаниями', $counts['warning'], ['count' => $counts['warning']]),
            'ok' => __('Проверки в норме'),
            default => __('Проверки не запускались'),
        };
    }

    /**
     * @param  array{ok: int, warning: int, failing: int, never: int}  $counts
     */
    private function detail(array $counts): string
    {
        $total = array_sum($counts);

        if ($total === 0) {
            return __('Ни одной проверки не зарегистрировано');
        }

        // "Never run" is worth naming separately: a check that was registered
        // and never ran usually means the scheduler is not wired up, and that
        // reads as silence rather than as a problem.
        if ($counts['never'] > 0) {
            return __(':ok из :total в норме, :never ни разу не запускалась', [
                'ok' => $counts['ok'],
                'total' => $total,
                'never' => $counts['never'],
            ]);
        }

        return __(':ok из :total в норме', ['ok' => $counts['ok'], 'total' => $total]);
    }

    /**
     * Where a click goes — the in-panel address of the results, which the panel
     * follows through its router rather than by reloading. Built from the
     * resource's own slug so renaming it in one place stays enough.
     */
    private function url(): string
    {
        return '/r/'.HealthResultResource::slug();
    }
}
