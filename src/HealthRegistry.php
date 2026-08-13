<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminHealth;

use Illuminate\Contracts\Container\Container;

/**
 * The registry of the registered health checks.
 *
 * It is filled by the service provider from `config('admin-health.checks')`.
 * Every element is a class-string plus a config array. The registration is
 * eager: the instance is created in register() through
 * container->make($class, ['config' => $config]).
 */
final class HealthRegistry
{
    /** @var array<string, HealthCheck> */
    private array $checks = [];

    public function __construct(private readonly Container $container) {}

    /**
     * @param  class-string<HealthCheck>  $class
     * @param  array<string, mixed>  $config
     */
    public function register(string $class, array $config = []): void
    {
        /** @var HealthCheck $instance */
        $instance = $this->container->make($class, $config !== [] ? ['config' => $config] : []);
        $this->checks[$instance->id()] = $instance;
    }

    /**
     * @return array<string, HealthCheck>
     */
    public function all(): array
    {
        return $this->checks;
    }

    public function get(string $id): ?HealthCheck
    {
        return $this->checks[$id] ?? null;
    }

    /**
     * @return list<string>
     */
    public function ids(): array
    {
        return array_keys($this->checks);
    }
}
