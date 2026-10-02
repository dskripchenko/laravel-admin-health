<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminHealth;

/**
 * The result of a single run of a check.
 *
 * An immutable value object. Created through the ok/warning/failing factories.
 *
 * The message is a source string and `$replace` its placeholders, the way
 * `__()` takes them: the runner stores both, and every reader translates the
 * message in its own locale — the history is read in whatever language the
 * reader has, not in the scheduler's. A message that is already translated,
 * or not meant to be, works too: with no translation for it, `__()` returns
 * it as it is (with the placeholders filled in).
 *
 * @phpstan-type HealthStatus 'ok'|'warning'|'failing'
 */
final class HealthResult
{
    /** @phpstan-var HealthStatus */
    public readonly string $status;

    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, scalar|null>  $replace  the message's placeholders
     *
     * @phpstan-param HealthStatus $status
     */
    public function __construct(
        string $status,
        public readonly string $message = '',
        public readonly array $meta = [],
        public readonly array $replace = [],
    ) {
        $this->status = $status;
    }

    /**
     * The message translated into the current locale.
     */
    public function text(): string
    {
        return self::translate($this->message, $this->replace);
    }

    /**
     * Translates a stored message: a source string with its placeholders, or
     * a plain string written by an older version.
     *
     * @param  array<array-key, mixed>  $replace
     */
    public static function translate(?string $message, array $replace = []): string
    {
        if ($message === null || $message === '') {
            return '';
        }

        $replace = array_map(static fn (mixed $v): string => is_scalar($v) ? (string) $v : '', $replace);
        $text = __($message, $replace);

        return is_string($text) ? $text : $message;
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, scalar|null>  $replace
     */
    public static function ok(string $message = 'OK', array $meta = [], array $replace = []): self
    {
        return new self('ok', $message, $meta, $replace);
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, scalar|null>  $replace
     */
    public static function warning(string $message, array $meta = [], array $replace = []): self
    {
        return new self('warning', $message, $meta, $replace);
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, scalar|null>  $replace
     */
    public static function failing(string $message, array $meta = [], array $replace = []): self
    {
        return new self('failing', $message, $meta, $replace);
    }

    public function isOk(): bool
    {
        return $this->status === 'ok';
    }

    public function isWarning(): bool
    {
        return $this->status === 'warning';
    }

    public function isFailing(): bool
    {
        return $this->status === 'failing';
    }
}
