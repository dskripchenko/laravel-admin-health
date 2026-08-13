<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminHealth\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An Eloquent wrapper over `admin_health_results`.
 *
 * The persistent state of the health-check runner: every row is one run of a
 * single check with its result and its duration.
 *
 * @property int $id
 * @property string $check_id
 * @property string $status ok|warning|failing
 * @property string|null $message
 * @property array<string, mixed>|null $meta
 * @property int $duration_ms
 * @property \Illuminate\Support\Carbon $ran_at
 */
final class HealthResultRecord extends Model
{
    protected $table = 'admin_health_results';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'meta' => 'array',
        'ran_at' => 'datetime',
        'duration_ms' => 'integer',
    ];
}
