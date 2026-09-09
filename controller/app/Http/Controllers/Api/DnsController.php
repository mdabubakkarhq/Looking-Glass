<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DnsController extends Controller
{
    /**
     * Resolve A and AAAA records for a hostname.
     * Used by the admin form to suggest IPv4/IPv6 addresses.
     */
    public function resolve(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'hostname' => 'required|string|max:255',
        ]);

        $hostname = $validated['hostname'];

        // Validate hostname format
        if (!filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            return response()->json(['message' => 'Invalid hostname format.'], 422);
        }

        $ipv4 = [];
        $ipv6 = [];

        // A records (IPv4)
        $records4 = @dns_get_record($hostname, DNS_A);
        if (is_array($records4)) {
            foreach ($records4 as $record) {
                if (!empty($record['ip'])) {
                    $ipv4[] = $record['ip'];
                }
            }
        }

        // AAAA records (IPv6)
        $records6 = @dns_get_record($hostname, DNS_AAAA);
        if (is_array($records6)) {
            foreach ($records6 as $record) {
                if (!empty($record['ipv6'])) {
                    $ipv6[] = $record['ipv6'];
                }
            }
        }

        return response()->json([
            'data' => [
                'hostname' => $hostname,
                'ipv4' => array_values(array_unique($ipv4)),
                'ipv6' => array_values(array_unique($ipv6)),
                'has_ipv4' => count($ipv4) > 0,
                'has_ipv6' => count($ipv6) > 0,
            ],
        ]);
    }
}
