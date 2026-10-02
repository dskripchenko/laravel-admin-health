<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminHealth\Resources;

use Dskripchenko\LaravelAdmin\Action\Button;
use Dskripchenko\LaravelAdmin\Filter\InputFilter;
use Dskripchenko\LaravelAdmin\Filter\OptionsFilter;
use Dskripchenko\LaravelAdmin\Resource\Resource;
use Dskripchenko\LaravelAdmin\Table\TableColumn;
use Dskripchenko\LaravelAdminHealth\HealthRunner;
use Dskripchenko\LaravelAdminHealth\Models\HealthResultRecord;
use Illuminate\Database\Eloquent\Builder;

/**
 * A resource for browsing the history of the health checks.
 *
 * Read-only: list plus view, with no create/update. Latest first. One
 * page-level action runs every check at once.
 *
 * Permissions:
 *   - admin.system.health.view
 *   - admin.system.health.run  — the "run the checks now" action
 */
final class HealthResultResource extends Resource
{
    public static string $model = HealthResultRecord::class;

    public static string $icon = 'activity';

    public static ?string $group = 'Системные';

    public static function slug(): string
    {
        return 'system-health-results';
    }

    public static function permission(): string
    {
        return 'admin.system.health';
    }

    public static function label(): string
    {
        return __('Проверки состояния');
    }

    public function columns(): array
    {
        return [
            TableColumn::make('id')->label(__('ID'))->sort()->width('60px'),
            TableColumn::make('check_id')->label(__('ID проверки'))->sort()->search()->copyable(),
            TableColumn::make('status')->label(__('Статус'))->sort()->asBadge([
                'ok' => 'success',
                'warning' => 'warning',
                'failing' => 'danger',
            ], self::statuses()),
            TableColumn::make('message')->label(__('Сообщение'))->search(),
            TableColumn::make('duration_ms')
                ->label(__('Длит. (ms)'))
                ->align('right')
                ->sort(),
            TableColumn::make('ran_at')->label(__('Запущено'))->sort()->asDateTime(),
        ];
    }

    public function filters(): array
    {
        return [
            InputFilter::for('check_id')->label(__('ID проверки')),
            OptionsFilter::for('status')->label(__('Статус'))->options(self::statuses()),
        ];
    }

    public function actions(): array
    {
        return [
            // A command-bar button needs no selected rows: core shows it in
            // the list's header menu and sends no ids.
            Button::make('Запустить проверки')->withName('run_checks')
                ->method('runChecks')
                ->permission('admin.system.health.run'),
        ];
    }

    /**
     * Runs every registered check now — what `admin:health:run` does on
     * schedule — and says how it went.
     *
     * @param  list<int|string>  $ids  ignored: the action is not about rows
     * @param  array<string, mixed>  $payload
     */
    public function runChecks(array $ids = [], array $payload = []): string
    {
        $report = app(HealthRunner::class)->runAll();

        if ($report === []) {
            return __('Ни одной проверки не зарегистрировано');
        }

        $counts = ['ok' => 0, 'warning' => 0, 'failing' => 0];
        foreach ($report as $row) {
            $counts[$row['result']->status]++;
        }

        return __('Проверки выполнены: :ok в норме, :warning с замечаниями, :failing не прошли', $counts);
    }

    /**
     * The statuses and their captions — source strings, translated per
     * request by core, in the badges and in the filter alike.
     *
     * @return array<string, string>
     */
    private static function statuses(): array
    {
        return [
            'ok' => 'В норме',
            'warning' => 'Замечания',
            'failing' => 'Не прошли',
        ];
    }

    public function indexQuery(): Builder
    {
        return $this->modelQuery()->orderByDesc('ran_at');
    }
}
