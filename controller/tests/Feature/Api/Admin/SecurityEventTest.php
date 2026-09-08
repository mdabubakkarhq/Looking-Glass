<?php

namespace Tests\Feature\Api\Admin;

use App\Models\Node;
use App\Models\SecurityEvent;
use Tests\TestCase;

class SecurityEventTest extends TestCase
{
    public function test_list_security_events_requires_auth(): void
    {
        $this->getJson('/api/v1/admin/security-events')->assertStatus(401);
    }

    public function test_list_security_events(): void
    {
        $this->actingAsAdmin();
        SecurityEvent::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/admin/security-events');
        $response->assertOk()->assertJsonPath('total', 3);
    }

    public function test_show_security_event(): void
    {
        $this->actingAsAdmin();
        $event = SecurityEvent::factory()->create();

        $response = $this->getJson("/api/v1/admin/security-events/{$event->id}");
        $response->assertOk()->assertJsonPath('data.id', $event->id);
    }

    public function test_filter_security_events_by_type(): void
    {
        $this->actingAsAdmin();
        SecurityEvent::factory()->create(['event_type' => 'agent_auth_failure']);
        SecurityEvent::factory()->create(['event_type' => 'rate_limit']);

        $response = $this->getJson('/api/v1/admin/security-events?event_type=agent_auth_failure');
        $response->assertOk()->assertJsonPath('total', 1);
    }
}
