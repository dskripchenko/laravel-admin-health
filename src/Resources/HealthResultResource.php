<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminHealth\Resources;

use Dskripchenko\LaravelAdmin\Filter\InputFilter;
use Dskripchenko\LaravelAdmin\Filter\OptionsFilter;
use Dskripchenko\LaravelAdmin\Resource\Resource;
use Dskripchenko\LaravelAdmin\Table\TableColumn;
use Dskripchenko\LaravelAdminHealth\Models\HealthResultRecord;
use Illuminate\Database\Eloquent\Builder;

/**
 * A resource for browsing the history of the health checks.
 *
 * Read-only: list plus view, with no create/update. Latest first.
 *
 * Permissions:
 *   - admin.system.health.view
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
            ]),
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
            OptionsFilter::for('status')->label(__('Статус'))->options([
                'ok' => __('В норме'),
                'warning' => __('Замечания'),
                'failing' => __('Не прошли'),
            ]),
        ];
    }

    public function indexQuery(): Builder
    {
        return $this->modelQuery()->orderByDesc('ran_at');
    }
}
