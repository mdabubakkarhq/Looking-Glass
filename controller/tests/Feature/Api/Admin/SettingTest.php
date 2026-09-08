<?php

namespace Tests\Feature\Api\Admin;

use App\Models\Setting;
use Tests\TestCase;

class SettingTest extends TestCase
{
    public function test_list_settings_requires_auth(): void
    {
        $this->getJson('/api/v1/admin/settings')->assertStatus(401);
    }

    public function test_list_all_settings(): void
    {
        $this->actingAsAdmin();
        Setting::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/admin/settings');
        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_show_settings_by_group(): void
    {
        $this->actingAsAdmin();
        Setting::factory()->create(['group' => 'branding', 'key' => 'brand.test']);
        Setting::factory()->create(['group' => 'network', 'key' => 'net.test']);

        $response = $this->getJson('/api/v1/admin/settings/branding');
        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_update_settings(): void
    {
        $this->actingAsAdmin();
        Setting::factory()->create([
            'group' => 'branding', 'key' => 'site_name',
            'value' => 'Old Name', 'type' => 'string',
        ]);

        $response = $this->putJson('/api/v1/admin/settings', [
            'settings' => [
                ['key' => 'site_name', 'value' => 'New Name', 'type' => 'string'],
            ],
        ]);

        $response->assertOk()->assertJsonPath('message', 'Settings updated');
        $this->assertDatabaseHas('settings', ['key' => 'site_name', 'value' => 'New Name']);
    }

    public function test_update_settings_validates(): void
    {
        $this->actingAsAdmin();
        $this->putJson('/api/v1/admin/settings', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['settings']);
    }
}
