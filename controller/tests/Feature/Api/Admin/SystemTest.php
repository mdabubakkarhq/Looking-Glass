<?php

namespace Tests\Feature\Api\Admin;

use App\Models\Node;
use App\Models\NetworkTest;
use Tests\TestCase;

class SystemTest extends TestCase
{
    public function test_system_info_requires_auth(): void
    {
        $this->getJson('/api/v1/admin/system/info')->assertStatus(401);
    }

    public function test_system_info(): void
    {
        $this->actingAsAdmin();

        $response = $this->getJson('/api/v1/admin/system/info');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'version', 'php_version', 'laravel_version',
                    'environment', 'debug_mode', 'timezone',
                    'database_driver', 'queue_connection', 'cache_driver',
                    'nodes_total', 'tests_total', 'last_update',
                ],
            ]);
    }

    public function test_system_health(): void
    {
        $this->actingAsAdmin();

        $response = $this->getJson('/api/v1/admin/system/health');

        $response->assertOk()
            ->assertJsonStructure([
                'healthy', 'checks' => ['database', 'redis', 'queue', 'nodes_online', 'nodes_offline'],
            ])
            ->assertJsonPath('checks.database', 'ok');
    }

    public function test_system_update(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/v1/admin/system/update');
        $response->assertOk()->assertJsonPath('message', 'Migrations completed successfully.');
    }

    public function test_system_rollback(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/v1/admin/system/rollback');
        $response->assertOk()->assertJsonPath('message', 'Rollback completed successfully.');
    }
}
