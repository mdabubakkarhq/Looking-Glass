<?php

namespace Tests\Feature\Api\Admin;

use App\Models\Node;
use App\Models\NetworkTest;
use App\Models\RateLimitEvent;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    public function test_dashboard_requires_auth(): void
    {
        $this->getJson('/api/v1/admin/dashboard')->assertStatus(401);
    }

    public function test_dashboard_returns_data(): void
    {
        $this->actingAsAdmin();

        Node::factory()->create(['status' => 'online']);
        Node::factory()->offline()->create();
        NetworkTest::factory()->completed()->create();

        $response = $this->getJson('/api/v1/admin/dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'nodes' => ['active', 'offline', 'maintenance', 'total'],
                    'tests' => ['today', 'this_hour', 'running', 'failed_today'],
                    'rate_limits' => ['today'],
                    'top_targets',
                    'system' => ['database', 'redis', 'queue'],
                ],
            ])
            ->assertJsonPath('data.system.database', 'ok');
    }
}
