<?php

namespace Tests\Feature\Api\Admin;

use App\Models\NetworkTest;
use App\Models\Node;
use Tests\TestCase;

class AdminTestManagementTest extends TestCase
{
    public function test_list_tests_requires_auth(): void
    {
        $this->getJson('/api/v1/admin/tests')->assertStatus(401);
    }

    public function test_list_tests(): void
    {
        $this->actingAsAdmin();
        $node = Node::factory()->create();
        NetworkTest::factory()->count(3)->create(['node_id' => $node->id]);

        $response = $this->getJson('/api/v1/admin/tests');
        $response->assertOk()->assertJsonPath('total', 3);
    }

    public function test_list_tests_filter_by_type(): void
    {
        $this->actingAsAdmin();
        $node = Node::factory()->create();
        NetworkTest::factory()->create(['node_id' => $node->id, 'test_type' => 'ping']);
        NetworkTest::factory()->create(['node_id' => $node->id, 'test_type' => 'dns']);

        $response = $this->getJson('/api/v1/admin/tests?test_type=ping');
        $response->assertOk()->assertJsonPath('total', 1);
    }

    public function test_show_test(): void
    {
        $this->actingAsAdmin();
        $node = Node::factory()->create();
        $test = NetworkTest::factory()->completed()->create(['node_id' => $node->id]);

        $response = $this->getJson("/api/v1/admin/tests/{$test->uuid}");
        $response->assertOk()->assertJsonPath('data.id', $test->id);
    }

    public function test_destroy_test(): void
    {
        $this->actingAsAdmin();
        $node = Node::factory()->create();
        $test = NetworkTest::factory()->create(['node_id' => $node->id]);

        $this->deleteJson("/api/v1/admin/tests/{$test->uuid}")
            ->assertOk()
            ->assertJsonPath('message', 'Test deleted');
    }
}
