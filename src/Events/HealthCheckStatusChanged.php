<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminHealth\Events;

use Dskripchenko\LaravelAdminHealth\HealthCheck;
use Dskripchenko\LaravelAdminHealth\HealthResult;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Emitted by the runner on an ok ↔ warning/failing transition.
 *
 * A host subscribes through its EventServiceProvider:
 *
 *     Event::listen(HealthCheckStatusChanged::class, function ($e) {
 *         if ($e->isFailing()) {
 *             SlackNotification::send("🚨 {$e->check->name()}: {$e->result->text()}");
 *         }
 *     });
 */
final class HealthCheckStatusChanged
{
    use Dispatchable;

    public function __construct(
        public readonly HealthCheck $check,
        public readonly HealthResult $result,
        public readonly string $previousStatus,
    ) {}

    public function isFailing(): bool
    {
        return $this->result->isFailing();
    }

    public function isWarning(): bool
    {
        return $this->result->isWarning();
    }

    public function isRecovered(): bool
    {
        return $this->result->isOk()
            && in_array($this->previousStatus, ['warning', 'failing'], true);
    }
}
