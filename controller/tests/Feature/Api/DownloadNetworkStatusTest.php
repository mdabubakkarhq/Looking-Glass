<?php

namespace Tests\Feature\Api;

use App\Models\Node;
use App\Models\DownloadFile;
use App\Models\Setting;
use Tests\TestCase;

class DownloadNetworkStatusTest extends TestCase
{
    public function test_list_downloads(): void
    {
        $node = Node::factory()->create();
        DownloadFile::factory()->create(['node_id' => $node->id, 'enabled' => true]);
        DownloadFile::factory()->create(['enabled' => false]);

        $response = $this->getJson('/api/v1/downloads');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'node_id', 'name', 'size_bytes', 'size_label', 'url']],
            ])
            ->assertJsonCount(1, 'data');
    }

    public function test_get_network_info(): void
    {
        Setting::factory()->create([
            'group' => 'network', 'key' => 'network.asn',
            'value' => 'AS64500', 'type' => 'string',
        ]);

        $response = $this->getJson('/api/v1/network');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'asn', 'network_name', 'ipv4_prefixes',
                    'ipv6_prefixes', 'abuse_contact',
                ],
            ]);
    }

    public function test_get_status(): void
    {
        Node::factory()->create(['public' => true, 'status' => 'online']);
        Node::factory()->offline()->create(['public' => true]);

        $response = $this->getJson('/api/v1/status');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'controller', 'database', 'redis',
                    'nodes' => ['total', 'online', 'offline', 'maintenance'],
                    'tests_today', 'version', 'generated_at',
                ],
            ])
            ->assertJsonPath('data.controller', 'ok')
            ->assertJsonPath('data.database', 'ok');
    }
}
