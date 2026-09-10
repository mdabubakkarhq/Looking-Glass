<?php

namespace Tests\Feature\Api\Admin;

use App\Models\Node;
use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
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

    public function test_failed_login_ban_after_max_attempts(): void
    {
        Cache::flush();

        // Create the admin user (let hashed cast handle hashing)
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        // Create security settings (boot already ran, so use defaults from config fallback)
        \App\Models\Setting::create(['key' => 'login_max_attempts', 'group' => 'security', 'value' => '5', 'type' => 'integer', 'label' => 'Max Login Attempts', 'public' => false]);
        \App\Models\Setting::create(['key' => 'login_ban_minutes', 'group' => 'security', 'value' => '15', 'type' => 'integer', 'label' => 'Login Ban Duration', 'public' => false]);

        // Attempt 1-4: should return 422 (invalid credentials)
        for ($i = 1; $i <= 4; $i++) {
            $this->postJson('/api/v1/admin/login', [
                'email' => 'admin@example.com',
                'password' => 'wrong-password',
            ])->assertStatus(422);
        }

        // Attempt 5: should ban the IP (429)
        $response = $this->postJson('/api/v1/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);
        $response->assertStatus(429);
        $response->assertJsonFragment(['message' => 'Too many failed login attempts. Try again in 15 minutes.']);

        // Verify security events were logged
        $this->assertDatabaseHas('security_events', [
            'event_type' => 'failed_login_attempt',
        ]);
        $this->assertDatabaseHas('security_events', [
            'event_type' => 'admin_login_banned',
        ]);
    }

    public function test_successful_login_clears_failed_attempts(): void
    {
        Cache::flush();

        // Create the admin user (let hashed cast handle hashing)
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        \App\Models\Setting::create(['key' => 'login_max_attempts', 'group' => 'security', 'value' => '5', 'type' => 'integer', 'label' => 'Max Login Attempts', 'public' => false]);
        \App\Models\Setting::create(['key' => 'login_ban_minutes', 'group' => 'security', 'value' => '15', 'type' => 'integer', 'label' => 'Login Ban Duration', 'public' => false]);

        // 2 failed attempts
        for ($i = 0; $i < 2; $i++) {
            $this->postJson('/api/v1/admin/login', [
                'email' => 'admin@example.com',
                'password' => 'wrong-password',
            ])->assertStatus(422);
        }

        // Successful login should clear the counter
        $this->postJson('/api/v1/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertOk();

        // Next failed attempt should count as attempt #1 (not #3 which would ban)
        $this->postJson('/api/v1/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(422);
    }
}
