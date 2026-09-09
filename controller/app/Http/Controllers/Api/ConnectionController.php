<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ConnectionController extends Controller
{
    /**
     * Detect the visitor's connection info: IPv4/IPv6 addresses,
     * reverse DNS, and network/ASN information.
     */
    public function index(Request $request): JsonResponse
    {
        $clientIp = $request->ip();

        $ipv4 = null;
        $ipv6 = null;

        // Determine IP family of the direct client IP
        if (filter_var($clientIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ipv4 = $clientIp;
        } elseif (filter_var($clientIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $ipv6 = $clientIp;
        }

        // Also check X-Forwarded-For for dual-stack detection
        $forwardedFor = $request->header('X-Forwarded-For');
        if ($forwardedFor) {
            foreach (array_map('trim', explode(',', $forwardedFor)) as $ip) {
                if (!$ipv4 && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $ipv4 = $ip;
                }
                if (!$ipv6 && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    $ipv6 = $ip;
                }
            }
        }

        // Check if IPs are private/local
        $ipv4IsPublic = $ipv4 && $this->isPublicIp($ipv4);
        $ipv6IsPublic = $ipv6 && $this->isPublicIp($ipv6);

        // Reverse DNS (only for public IPs)
        $ipv4Rdns = null;
        $ipv6Rdns = null;

        if ($ipv4IsPublic) {
            $rdns = @gethostbyaddr($ipv4);
            $ipv4Rdns = ($rdns !== $ipv4) ? $rdns : null;
        }

        if ($ipv6IsPublic) {
            $rdns = @gethostbyaddr($ipv6);
            $ipv6Rdns = ($rdns !== $ipv6) ? $rdns : null;
        }

        // Network lookup (prefer IPv4, fall back to IPv6)
        $lookupIp = $ipv4IsPublic ? $ipv4 : ($ipv6IsPublic ? $ipv6 : null);
        $network = $lookupIp ? $this->lookupNetwork($lookupIp) : null;

        // Detect primary IP version
        $ipVersion = null;
        if ($ipv4 && $ipv6) {
            $ipVersion = 'dual-stack';
        } elseif ($ipv4) {
            $ipVersion = 'ipv4';
        } elseif ($ipv6) {
            $ipVersion = 'ipv6';
        }

        // Connection type note
        $connectionNote = $this->detectConnectionNote($clientIp, $network);

        return response()->json([
            'data' => [
                'ipv4' => [
                    'address' => $ipv4,
                    'working' => (bool) $ipv4,
                    'is_public' => $ipv4IsPublic,
                    'reverse_dns' => $ipv4Rdns,
                ],
                'ipv6' => [
                    'address' => $ipv6,
                    'working' => (bool) $ipv6,
                    'is_public' => $ipv6IsPublic,
                    'reverse_dns' => $ipv6Rdns,
                ],
                'ip_version' => $ipVersion,
                'network' => $network,
                'connection_note' => $connectionNote,
                'detected_at' => now()->toISOString(),
            ],
        ])->header('Cache-Control', 'private, no-store');
    }

    /**
     * Check if an IP address is publicly routable.
     */
    private function isPublicIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
            !== false;
    }

    /**
     * Detect connection type note based on IP and network data.
     */
    private function detectConnectionNote(?string $clientIp, ?array $network): ?string
    {
        if ($clientIp && !$this->isPublicIp($clientIp)) {
            return 'Private/local IP detected — running in development or behind NAT.';
        }

        if (!$network) {
            return null;
        }

        $org = strtolower($network['org'] ?? '');
        $isp = strtolower($network['isp'] ?? '');

        $vpnKeywords = ['vpn', 'proxy', 'tunnel', 'nordvpn', 'expressvpn', 'surfshark', 'cloudflare warp', 'mullvad', 'protonvpn', 'private internet access'];
        foreach ($vpnKeywords as $keyword) {
            if (str_contains($org, $keyword) || str_contains($isp, $keyword)) {
                return 'VPN or proxy detected — latency may reflect the VPN endpoint, not your ISP.';
            }
        }

        $torKeywords = ['tor', 'torproject', 'torservers'];
        foreach ($torKeywords as $keyword) {
            if (str_contains($org, $keyword) || str_contains($isp, $keyword)) {
                return 'Tor exit node detected — latency and path data reflect the Tor network.';
            }
        }

        return null;
    }

    /**
     * Look up network/ASN information for an IP using ip-api.com.
     * Results are cached for 1 hour per IP.
     */
    private function lookupNetwork(string $ip): ?array
    {
        $cacheKey = 'connection:network:' . md5($ip);

        return Cache::remember($cacheKey, 3600, function () use ($ip) {
            try {
                $response = Http::timeout(3)->get("http://ip-api.com/json/{$ip}", [
                    'fields' => 'status,isp,org,as,asname,country,countryCode,region,regionName,city,timezone',
                ]);

                if ($response->successful() && $response->json('status') === 'success') {
                    $data = $response->json();

                    return [
                        'isp' => $data['isp'] ?? null,
                        'org' => $data['org'] ?? null,
                        'asn' => $data['as'] ?? null,
                        'as_name' => $data['asname'] ?? null,
                        'country' => $data['country'] ?? null,
                        'country_code' => $data['countryCode'] ?? null,
                        'region' => $data['regionName'] ?? null,
                        'city' => $data['city'] ?? null,
                        'timezone' => $data['timezone'] ?? null,
                    ];
                }
            } catch (\Exception $e) {
                // Silently fail — network lookup is best-effort
            }

            return null;
        });
    }
}
