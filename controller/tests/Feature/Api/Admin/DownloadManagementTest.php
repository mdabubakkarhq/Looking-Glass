<?php

namespace Tests\Feature\Api\Admin;

use App\Models\DownloadFile;
use App\Models\Node;
use Tests\TestCase;

class DownloadManagementTest extends TestCase
{
    public function test_list_downloads_requires_auth(): void
    {
        $this->getJson('/api/v1/admin/downloads')->assertStatus(401);
    }

    public function test_list_downloads(): void
    {
        $this->actingAsAdmin();
        $node = Node::factory()->create();
        DownloadFile::factory()->count(2)->create(['node_id' => $node->id]);

        $response = $this->getJson('/api/v1/admin/downloads');
        $response->assertOk()->assertJsonPath('total', 2);
    }

    public function test_store_download(): void
    {
        $this->actingAsAdmin();
        $node = Node::factory()->create();

        $response = $this->postJson('/api/v1/admin/downloads', [
            'node_id' => $node->id, 'name' => 'Test File',
            'filename' => 'test.bin', 'size_bytes' => 104857600,
            'size_label' => '100MB', 'url' => 'http://example.com/test.bin',
        ]);

        $response->assertStatus(201);
    }

    public function test_show_download(): void
    {
        $this->actingAsAdmin();
        $node = Node::factory()->create();
        $file = DownloadFile::factory()->create(['node_id' => $node->id]);

        $this->getJson("/api/v1/admin/downloads/{$file->id}")
            ->assertOk()->assertJsonPath('data.id', $file->id);
    }

    public function test_update_download(): void
    {
        $this->actingAsAdmin();
        $node = Node::factory()->create();
        $file = DownloadFile::factory()->create(['node_id' => $node->id, 'name' => 'Old']);

        $this->putJson("/api/v1/admin/downloads/{$file->id}", ['name' => 'New'])
            ->assertOk()->assertJsonPath('data.name', 'New');
    }

    public function test_destroy_download(): void
    {
        $this->actingAsAdmin();
        $node = Node::factory()->create();
        $file = DownloadFile::factory()->create(['node_id' => $node->id]);

        $this->deleteJson("/api/v1/admin/downloads/{$file->id}")
            ->assertOk()->assertJsonPath('message', 'Download file deleted');
    }
}
