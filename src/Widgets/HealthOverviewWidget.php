<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminHealth\Widgets;

use Dskripchenko\LaravelAdmin\Widget\StatsOverviewWidget;
use Dskripchenko\LaravelAdminHealth\HealthSummary;

/**
 * The state of the checks, on the dashboard.
 *
 * The package shipped with a list of results and nothing above it, so the
 * question people actually ask — "is anything broken right now" — was answered
 * by reading a table and comparing timestamps by eye. The counts belong on the
 * first screen; the table is for when one of them is not zero.
 *
 * The cards it shows depend on what there is to say: a zero of failures is
 * worth seeing, a zero of never-run checks is not — that one appears only when
 * some check has never run, which almost always means the scheduler was never
 * wired up.
 */
class HealthOverviewWidget extends StatsOverviewWidget
{
    public function __construct()
    {
        // A title and a width the host has not asked for, but a widget that
        // lands on someone else's dashboard has to introduce itself: the
        // numbers alone say nothing about what was counted.
        $this->title(__('Health-checks'))->size(4);
    }

    public static function slug(): string
    {
        return 'admin.health.overview';
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        // Resolved here rather than injected: the base Widget::make() calls
        // `new static`, so a required constructor argument would break every
        // host that places the widget by hand.
        $counts = app(HealthSummary::class)->counts();

        $this->stat(__('В норме'), $counts['ok'], 'success');
        $this->stat(__('Замечания'), $counts['warning'], $counts['warning'] > 0 ? 'warning' : null);
        $this->stat(__('Не прошли'), $counts['failing'], $counts['failing'] > 0 ? 'danger' : null);

        if ($counts['never'] > 0) {
            // Not a failure and not health: a check that has never run means
            // nobody scheduled it. Shown only when it happens, so that it reads
            // as the anomaly it is.
            $this->stat(__('Ни разу не запускались'), $counts['never'], 'warning');
        }

        return parent::data();
    }
}
