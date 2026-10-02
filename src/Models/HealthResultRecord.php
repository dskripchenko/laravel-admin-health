<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminHealth\Models;

use Dskripchenko\LaravelAdminHealth\HealthResult;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * An Eloquent wrapper over `admin_health_results`.
 *
 * The persistent state of the health-check runner: every row is one run of a
 * single check with its result and its duration.
 *
 * The message is stored as the check wrote it — a source string — with its
 * placeholders under a reserved key of `meta`, and `message` reads back
 * translated into the current locale. `meta` reads back without that key.
 * Rows written before the placeholders were stored hold a finished string and
 * read back as they are.
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
    /** The key of `meta` that holds the message's placeholders. */
    public const REPLACE_KEY = '_message_replace';

    protected $table = 'admin_health_results';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'ran_at' => 'datetime',
        'duration_ms' => 'integer',
    ];

    /**
     * @return Attribute<string|null, never>
     */
    protected function message(): Attribute
    {
        return Attribute::get(function (?string $value, array $attributes): ?string {
            if ($value === null) {
                return null;
            }

            return HealthResult::translate($value, self::replaceFrom($attributes['meta'] ?? null));
        });
    }

    /**
     * @return Attribute<array<string, mixed>|null, array<string, mixed>|null>
     */
    protected function meta(): Attribute
    {
        return Attribute::make(
            get: function (?string $value): ?array {
                $decoded = self::decode($value);
                if ($decoded === null) {
                    return null;
                }
                unset($decoded[self::REPLACE_KEY]);

                return $decoded;
            },
            set: fn (?array $value): ?string => $value === null ? null : (string) json_encode($value, JSON_UNESCAPED_UNICODE),
        );
    }

    /** The stored message, untranslated. */
    public function messageSource(): ?string
    {
        $raw = $this->getAttributes()['message'] ?? null;

        return is_string($raw) ? $raw : null;
    }

    /**
     * The message's placeholders.
     *
     * @return array<string, mixed>
     */
    public function messageReplace(): array
    {
        return self::replaceFrom($this->getAttributes()['meta'] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    private static function replaceFrom(mixed $meta): array
    {
        $decoded = is_array($meta) ? $meta : self::decode(is_string($meta) ? $meta : null);
        $replace = $decoded[self::REPLACE_KEY] ?? [];

        return is_array($replace) ? $replace : [];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function decode(?string $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }
        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }
}
