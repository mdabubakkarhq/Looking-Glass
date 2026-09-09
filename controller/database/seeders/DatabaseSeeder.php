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
            // ── General ──
            ['group' => 'general', 'key' => 'site_name', 'label' => 'Site Name', 'value' => 'Open Looking Glass', 'type' => 'string', 'public' => true, 'description' => 'The name displayed in the header, footer, and browser tab.'],
            ['group' => 'general', 'key' => 'site_title', 'label' => 'Site Title', 'value' => 'Open Looking Glass — Network Testing Platform', 'type' => 'string', 'public' => true, 'description' => 'HTML title tag and OG title.'],
            ['group' => 'general', 'key' => 'meta_description', 'label' => 'Meta Description', 'value' => 'Test network connectivity, latency, and routing from multiple global locations.', 'type' => 'text', 'public' => true, 'description' => 'SEO meta description and OG description.'],
            ['group' => 'general', 'key' => 'organization_name', 'label' => 'Organization Name', 'value' => 'Example Corp', 'type' => 'string', 'public' => true, 'description' => 'Organization name shown alongside the site name.'],
            ['group' => 'general', 'key' => 'email', 'label' => 'Contact Email', 'value' => 'admin@example.com', 'type' => 'string', 'public' => true, 'description' => 'Public contact email address.'],
            ['group' => 'general', 'key' => 'asn', 'label' => 'ASN', 'value' => 'AS64500', 'type' => 'string', 'public' => true, 'description' => 'Autonomous System Number displayed in the network section.'],
            ['group' => 'general', 'key' => 'abuse_contact', 'label' => 'Abuse Contact', 'value' => 'abuse@example.com', 'type' => 'string', 'public' => true, 'description' => 'Abuse contact email address.'],

            // ── Branding & Media ──
            ['group' => 'branding', 'key' => 'site_logo', 'label' => 'Site Logo', 'value' => '', 'type' => 'string', 'public' => true, 'description' => 'URL to a logo image (PNG/SVG). Leave empty to use the site name text.'],
            ['group' => 'branding', 'key' => 'og_image', 'label' => 'OG Image', 'value' => '', 'type' => 'string', 'public' => true, 'description' => 'Open Graph image URL (1200x630 recommended).'],
            ['group' => 'branding', 'key' => 'favicon', 'label' => 'Favicon', 'value' => '', 'type' => 'string', 'public' => true, 'description' => 'Favicon URL (.ico, .png, or .svg).'],
            ['group' => 'branding', 'key' => 'footer_description', 'label' => 'Footer Description', 'value' => 'Network connectivity and performance testing platform. Fast, reliable, and secure.', 'type' => 'text', 'public' => true, 'description' => 'Short tagline shown in the footer next to the logo.'],
            ['group' => 'branding', 'key' => 'copyright_text', 'label' => 'Copyright Text', 'value' => '', 'type' => 'string', 'public' => true, 'description' => 'Copyright text in footer bottom bar. Leave empty to auto-generate.'],

            // ── Limits ──
            ['group' => 'limits', 'key' => 'rate_limit_per_minute', 'label' => 'Rate Limit', 'value' => '30', 'type' => 'integer', 'public' => false, 'description' => 'Max API requests per minute per IP.'],

            // ── Testing ──
            ['group' => 'testing', 'key' => 'well_known_targets', 'label' => 'Well-Known Targets', 'value' => json_encode([
                ['ip' => '1.1.1.1', 'family' => 'ipv4', 'port' => 80, 'label' => 'one.one.one.one'],
                ['ip' => '8.8.8.8', 'family' => 'ipv4', 'port' => 80, 'label' => 'dns.google'],
                ['ip' => '2001:4860:4860::8888', 'family' => 'ipv6', 'port' => 80, 'label' => 'dns.google'],
                ['ip' => '2606:4700:4700::1111', 'family' => 'ipv6', 'port' => 80, 'label' => 'one.one.one.one'],
            ]), 'type' => 'json', 'public' => true, 'description' => 'Well-known test targets shown in the UI.'],

            // ── SMTP ──
            ['group' => 'smtp', 'key' => 'smtp_host', 'label' => 'SMTP Host', 'value' => '', 'type' => 'string', 'public' => false, 'description' => 'Mail server hostname (e.g., smtp.gmail.com).'],
            ['group' => 'smtp', 'key' => 'smtp_port', 'label' => 'SMTP Port', 'value' => '587', 'type' => 'string', 'public' => false, 'description' => 'Mail server port (usually 587 for TLS, 465 for SSL, or 25).'],
            ['group' => 'smtp', 'key' => 'smtp_username', 'label' => 'SMTP Username', 'value' => '', 'type' => 'string', 'public' => false, 'description' => 'SMTP authentication username (often your email address).'],
            ['group' => 'smtp', 'key' => 'smtp_password', 'label' => 'SMTP Password', 'value' => '', 'type' => 'string', 'public' => false, 'description' => 'SMTP authentication password or app-specific password.'],
            ['group' => 'smtp', 'key' => 'smtp_encryption', 'label' => 'Encryption', 'value' => 'tls', 'type' => 'string', 'public' => false, 'description' => 'Transport encryption (tls or ssl). Leave empty for no encryption.'],
            ['group' => 'smtp', 'key' => 'smtp_from_address', 'label' => 'From Address', 'value' => '', 'type' => 'string', 'public' => false, 'description' => 'Email address that messages are sent from.'],
            ['group' => 'smtp', 'key' => 'smtp_from_name', 'label' => 'From Name', 'value' => '', 'type' => 'string', 'public' => false, 'description' => 'Display name for the sender.'],
        ];

        // Use updateOrCreate so re-running the seeder syncs everything.
        // In dev/testing this resets values to defaults; fine for iterative testing.
        foreach ($settings as $s) {
            Setting::updateOrCreate(['key' => $s['key']], $s);
        }

        // Remove legacy settings that are now handled by normalized tables
        Setting::where('key', 'footer_text')->delete();
        Setting::where('key', 'footer_links')->delete();

        $this->seedMenuItems();
        $this->seedFooter();
    }

    private function seedMenuItems(): void
    {
        $items = [
            ['label' => 'Looking Glass', 'url' => '/', 'sort_order' => 0, 'open_new_tab' => false, 'active' => true],
            ['label' => 'Compare', 'url' => '/compare', 'sort_order' => 1, 'open_new_tab' => false, 'active' => true],
            ['label' => 'Locations', 'url' => '/locations', 'sort_order' => 2, 'open_new_tab' => false, 'active' => true],
            ['label' => 'Network', 'url' => '/network', 'sort_order' => 3, 'open_new_tab' => false, 'active' => true],
            ['label' => 'Status', 'url' => '/status', 'sort_order' => 4, 'open_new_tab' => false, 'active' => true],
            ['label' => 'Peering', 'url' => '/peering', 'sort_order' => 5, 'open_new_tab' => false, 'active' => true],
            ['label' => 'About', 'url' => '/about', 'sort_order' => 6, 'open_new_tab' => false, 'active' => true],
        ];
        foreach ($items as $item) {
            \App\Models\MenuItem::firstOrCreate(['url' => $item['url']], $item);
        }
    }

    private function seedFooter(): void
    {
        if (\App\Models\FooterSection::count() > 0) {
            return;
        }

        $services = \App\Models\FooterSection::create(['title' => 'Services', 'sort_order' => 0]);
        $services->links()->createMany([
            ['label' => 'Looking Glass', 'url' => '/', 'external' => false, 'sort_order' => 0],
            ['label' => 'Compare Nodes', 'url' => '/compare', 'external' => false, 'sort_order' => 1],
            ['label' => 'Downloads', 'url' => '/downloads', 'external' => false, 'sort_order' => 2],
        ]);

        $resources = \App\Models\FooterSection::create(['title' => 'Resources', 'sort_order' => 1]);
        $resources->links()->createMany([
            ['label' => 'Network Info', 'url' => '/network', 'external' => false, 'sort_order' => 0],
            ['label' => 'Peering', 'url' => '/peering', 'external' => false, 'sort_order' => 1],
            ['label' => 'Status', 'url' => '/status', 'external' => false, 'sort_order' => 2],
        ]);

        $support = \App\Models\FooterSection::create(['title' => 'Support', 'sort_order' => 2]);
        $support->links()->createMany([
            ['label' => 'About', 'url' => '/about', 'external' => false, 'sort_order' => 0],
            ['label' => 'Contact Us', 'url' => 'mailto:admin@example.com', 'external' => true, 'sort_order' => 1],
        ]);
    }
}