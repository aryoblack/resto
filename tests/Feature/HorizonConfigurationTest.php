<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HorizonConfigurationTest extends TestCase
{
    #[Test]
    public function horizon_config_is_loaded(): void
    {
        $this->assertNotNull(config('horizon'));
    }

    #[Test]
    public function horizon_uses_redis_connection(): void
    {
        $this->assertEquals('default', config('horizon.use'));
    }

    #[Test]
    public function horizon_has_correct_prefix(): void
    {
        $prefix = config('horizon.prefix');
        $this->assertStringContainsString('horizon:', $prefix);
    }

    #[Test]
    public function horizon_has_all_required_queue_supervisors(): void
    {
        $defaults = config('horizon.defaults');

        $this->assertArrayHasKey('supervisor-default', $defaults);
        $this->assertArrayHasKey('supervisor-notifications', $defaults);
        $this->assertArrayHasKey('supervisor-reports', $defaults);
        $this->assertArrayHasKey('supervisor-emails', $defaults);
    }

    #[Test]
    public function each_supervisor_targets_correct_queue(): void
    {
        $defaults = config('horizon.defaults');

        $this->assertContains('default', $defaults['supervisor-default']['queue']);
        $this->assertContains('notifications', $defaults['supervisor-notifications']['queue']);
        $this->assertContains('reports', $defaults['supervisor-reports']['queue']);
        $this->assertContains('emails', $defaults['supervisor-emails']['queue']);
    }

    #[Test]
    public function all_supervisors_use_redis_connection(): void
    {
        $defaults = config('horizon.defaults');

        foreach ($defaults as $name => $config) {
            $this->assertEquals('redis', $config['connection'], "Supervisor [{$name}] should use redis connection.");
        }
    }

    #[Test]
    public function reports_supervisor_has_higher_memory_and_timeout(): void
    {
        $reports = config('horizon.defaults.supervisor-reports');

        // Reports need more memory for Excel/PDF generation
        $this->assertGreaterThanOrEqual(256, $reports['memory']);

        // Reports need longer timeout (up to 30 seconds per requirement 15.4)
        $this->assertGreaterThanOrEqual(300, $reports['timeout']);
    }

    #[Test]
    public function wait_time_thresholds_are_configured_for_all_queues(): void
    {
        $waits = config('horizon.waits');

        $this->assertArrayHasKey('redis:default', $waits);
        $this->assertArrayHasKey('redis:notifications', $waits);
        $this->assertArrayHasKey('redis:reports', $waits);
        $this->assertArrayHasKey('redis:emails', $waits);
    }

    #[Test]
    public function horizon_environments_are_configured(): void
    {
        $environments = config('horizon.environments');

        $this->assertArrayHasKey('production', $environments);
        $this->assertArrayHasKey('local', $environments);
    }

    #[Test]
    public function production_environment_has_scaled_up_workers(): void
    {
        $production = config('horizon.environments.production');

        // Production should have more workers than local
        $this->assertGreaterThan(1, $production['supervisor-default']['maxProcesses']);
    }

    #[Test]
    public function horizon_dashboard_is_accessible_at_horizon_path(): void
    {
        $this->assertEquals('horizon', config('horizon.path'));
    }
}
