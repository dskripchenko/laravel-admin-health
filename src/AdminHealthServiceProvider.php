<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminHealth;

use Dskripchenko\LaravelAdmin\Plugin\Concerns\RegistersAdminPlugin;
use Dskripchenko\LaravelAdminHealth\Console\CleanupHealthResultsCommand;
use Dskripchenko\LaravelAdminHealth\Console\RunHealthChecksCommand;
use Illuminate\Support\ServiceProvider;

/**
 * The package's service provider.
 *
 * - mergeConfigFrom — admin-health.php
 * - binds the HealthRegistry and HealthRunner singletons
 * - registers the checks from config('admin-health.checks') in the
 *   HealthRegistry during the boot() phase
 * - registers AdminHealthPlugin in config('admin.plugins')
 * - wires in the migrations and the artisan commands
 */
final class AdminHealthServiceProvider extends ServiceProvider
{
    use RegistersAdminPlugin;

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/admin-health.php', 'admin-health');

        $this->app->singleton(HealthRegistry::class);
        $this->app->singleton(HealthRunner::class);

        $this->registerAdminPlugin(AdminHealthPlugin::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/admin-health.php' => config_path('admin-health.php'),
        ], 'admin-health-config');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                RunHealthChecksCommand::class,
                CleanupHealthResultsCommand::class,
            ]);
        }

        $this->registerHealthChecks();
    }

    /**
     * Reads config('admin-health.checks') and registers every class in the
     * HealthRegistry with the config array it was given.
     */
    private function registerHealthChecks(): void
    {
        /** @var array<class-string<HealthCheck>, array<string, mixed>> $defs */
        $defs = (array) config('admin-health.checks', []);
        if ($defs === []) {
            return;
        }

        /** @var HealthRegistry $registry */
        $registry = $this->app->make(HealthRegistry::class);
        foreach ($defs as $class => $config) {
            if (! class_exists($class)) {
                continue;
            }
            $registry->register($class, $config);
        }
    }
}
