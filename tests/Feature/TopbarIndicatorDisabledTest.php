<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminHealth\Tests\Feature;

use Dskripchenko\LaravelAdmin\Admin;
use Dskripchenko\LaravelAdminHealth\Status\HealthStatusIndicator;
use Dskripchenko\LaravelAdminHealth\Tests\TestCase;

/**
 * A case of its own because the flag is read while the plugin boots: by the
 * time a test body runs, the registration has already happened, and setting
 * the config then would prove nothing.
 */
final class TopbarIndicatorDisabledTest extends TestCase
{
    protected function defineAdditionalEnvironment($app): void
    {
        parent::defineAdditionalEnvironment($app);

        $app['config']->set('admin-health.topbar_indicator', false);
    }

    public function test_the_indicator_is_not_registered(): void
    {
        // The flag had been in the shipped config from the start, describing a
        // component that did not exist. Now that it does, it has to mean
        // something.
        $indicators = $this->app->make(Admin::class)->getStatusIndicators();

        $this->assertNotContains(HealthStatusIndicator::class, $indicators);
    }
}
