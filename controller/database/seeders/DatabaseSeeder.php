<?php

namespace Database\Seeders;

use App\Models\ApiKey;
use App\Models\DownloadFile;
use App\Models\NetworkTest;
use App\Models\Node;
use App\Models\NodeCapability;
use App\Models\NodeCredential;
use App\Models\NodeHeartbeat;
use App\Models\RegistrationToken;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin', 'password' => Hash::make('password'),
             'email_verified_at' => now()]
        );
        $this->seedNodes($admin);
        $this->seedSettings();
        ApiKey::firstOrCreate(
            ['user_id' => $admin->id, 'name' => 'Default Admin Key'],
            ['token' => Str::random(64), 'abilities' => ['*'],
             'rate_limit_per_minute' => 120]
        );
        $this->command->info('Database seeded!');
    }

    private function seedNodes(User $admin): void
    {
        $usEast = Node::firstOrCreate(['slug' => 'us-east-1'], [
            'uuid' => (string) Str::uuid(), 'name' => 'US East (New York)',
            'city' => 'New York', 'country_code' => 'US', 'provider' => 'Vultr',
            'asn' => 'AS20473', 'ipv4' => '203.0.113.10', 'ipv6' => '2001:db8::10',
            'latitude' => 40.7127753, 'longitude' => -74.0059728,
            'uplink_mbps' => 10000, 'status' => 'online', 'public' => true,
            'sort_order' => 1, 'agent_version' => '1.0.0', 'last_seen_at' => now(),
        ]);
        $euWest = Node::firstOrCreate(['slug' => 'eu-west-1'], [
            'uuid' => (string) Str::uuid(), 'name' => 'EU West (London)',
            'city' => 'London', 'country_code' => 'GB', 'provider' => 'Hetzner',
            'asn' => 'AS24940', 'ipv4' => '198.51.100.20', 'ipv6' => '2001:db8::20',
            'latitude' => 51.5073509, 'longitude' => -0.1277583,
            'uplink_mbps' => 10000, 'status' => 'online', 'public' => true,
            'sort_order' => 2, 'agent_version' => '1.0.0',
            'last_seen_at' => now()->subMinutes(2),
        ]);
        $apSouth = Node::firstOrCreate(['slug' => 'ap-south-1'], [
            'uuid' => (string) Str::uuid(), 'name' => 'Asia Pacific (Singapore)',
            'city' => 'Singapore', 'country_code' => 'SG', 'provider' => 'DigitalOcean',
            'asn' => 'AS14061', 'ipv4' => '192.0.2.30', 'ipv6' => null,
            'latitude' => 1.3520830, 'longitude' => 103.8198360,
            'uplink_mbps' => 1000, 'status' => 'offline', 'public' => true,
            'sort_order' => 3, 'last_seen_at' => now()->subHours(2),
        ]);
        $this->seedCapabilities([$usEast, $euWest, $apSouth]);
        $this->seedCredentials([$usEast, $euWest], $admin, $apSouth);
        $this->seedDownloads($usEast);
        $this->seedSampleTest($usEast);
    }

    private function seedCapabilities(array $nodes): void
    {
        foreach ($nodes as $node) {
            foreach (['ping', 'traceroute', 'mtr', 'dns'] as $f) {
                NodeCapability::firstOrCreate(
                    ['node_id' => $node->id, 'feature' => $f],
                    ['enabled' => true,
                     'max_concurrent' => ['ping'=>20,'traceroute'=>8,'mtr'=>5,'dns'=>20][$f],
                     'timeout_seconds' => ['ping'=>30,'traceroute'=>60,'mtr'=>60,'dns'=>15][$f]]
                );
            }
        }
    }

    private function seedCredentials(array $nodes, User $admin, Node $apSouth): void
    {
        $secret = 'test-secret-' . Str::random(32);
        foreach ($nodes as $node) {
            NodeCredential::firstOrCreate(['node_id' => $node->id], [
                'node_key_id' => 'lk_' . Str::random(32),
                'node_secret' => hash('sha256', $secret), 'active' => true,
            ]);
            NodeHeartbeat::create([
                'node_id' => $node->id,
                'load_average' => [0.5, 0.3, 0.2],
                'cpu_usage_percent' => 12,
                'memory_usage_percent' => 45,
                'disk_usage_percent' => 32,
                'uptime_seconds' => 86400,
                'agent_version' => '1.0.0',
                'sent_at' => $node->last_seen_at,
            ]);
        }
        RegistrationToken::firstOrCreate(['node_id' => $apSouth->id], [
            'token' => Str::random(64), 'expires_at' => now()->addDays(7),
            'created_by' => $admin->id,
        ]);
    }

    private function seedDownloads(Node $node): void
    {
        DownloadFile::firstOrCreate(
            ['node_id' => $node->id, 'filename' => '100MB.bin'],
            ['name' => '100MB Test File', 'size_bytes' => 104857600,
             'size_label' => '100MB', 'enabled' => true, 'sort_order' => 1,
             'url' => "http://{$node->ipv4}:8080/downloads/100MB.bin"]
        );
    }

    private function seedSampleTest(Node $node): void
    {
        NetworkTest::firstOrCreate(
            ['uuid' => '00000000-0000-0000-0000-000000000001'],
            ['node_id' => $node->id, 'test_type' => 'ping', 'target' => '1.1.1.1',
             'ip_family' => 'auto', 'status' => 'completed',
             'started_at' => now()->subMinutes(5),
             'completed_at' => now()->subMinutes(5)->addSeconds(3),
             'runtime_ms' => 3120, 'packet_loss' => 0.0,
             'latency_min' => 1.23, 'latency_avg' => 2.45, 'latency_max' => 4.67,
             'visitor_hash' => hash('sha256', 'seed-data')]
        );
    }

    private function seedSettings(): void
    {
        $settings = [
            ['group' => 'branding', 'key' => 'site_name', 'value' => 'Open Looking Glass', 'type' => 'string', 'public' => true, 'description' => 'Site name'],
            ['group' => 'branding', 'key' => 'organization_name', 'value' => 'Example Corp', 'type' => 'string', 'public' => true, 'description' => 'Org name'],
            ['group' => 'branding', 'key' => 'footer_text', 'value' => 'Powered by Open Looking Glass', 'type' => 'string', 'public' => true, 'description' => 'Footer'],
            ['group' => 'network', 'key' => 'asn', 'value' => 'AS64500', 'type' => 'string', 'public' => true, 'description' => 'ASN'],
            ['group' => 'network', 'key' => 'abuse_contact', 'value' => 'abuse@example.com', 'type' => 'string', 'public' => true, 'description' => 'Abuse'],
            ['group' => 'limits', 'key' => 'rate_limit_per_minute', 'value' => '30', 'type' => 'integer', 'public' => false, 'description' => 'Rate limit'],
        ];
        foreach ($settings as $s) {
            Setting::firstOrCreate(['key' => $s['key']], $s);
        }
    }
}