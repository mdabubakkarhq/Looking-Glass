<?php

namespace Tests\Feature\Api\Admin;

use App\Models\RateLimitEvent;
use App\Models\SecurityEvent;
use Tests\TestCase;

class LogViewingTest extends TestCase
{
    public function test_rate_limits_requires_auth(): void
    {
        $this->getJson('/api/v1/admin/logs/rate-limits')->assertStatus(401);
    }

    public function test_list_rate_limit_logs(): void
    {
        $this->actingAsAdmin();
        RateLimitEvent::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/admin/logs/rate-limits');
        $response->assertOk()->assertJsonPath('total', 3);
    }

    public function test_list_security_logs(): void
    {
        $this->actingAsAdmin();
        SecurityEvent::factory()->count(2)->create();

        $response = $this->getJson('/api/v1/admin/logs/security');
        $response->assertOk()->assertJsonPath('total', 2);
    }

    public function test_security_logs_filter_by_severity(): void
    {
        $this->actingAsAdmin();
        SecurityEvent::factory()->create(['severity' => 'critical']);
        SecurityEvent::factory()->create(['severity' => 'info']);

        $response = $this->getJson('/api/v1/admin/logs/security?severity=critical');
        $response->assertOk()->assertJsonPath('total', 1);
    }
}
