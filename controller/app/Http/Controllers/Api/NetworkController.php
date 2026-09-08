<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class NetworkController extends Controller
{
    public function index(): JsonResponse
    {
        $network = [
            'asn' => Setting::getValue('network.asn'),
            'network_name' => Setting::getValue('network.name'),
            'ipv4_prefixes' => Setting::getValue('network.ipv4_prefixes', []),
            'ipv6_prefixes' => Setting::getValue('network.ipv6_prefixes', []),
            'peeringdb_url' => Setting::getValue('network.peeringdb_url'),
            'internet_exchanges' => Setting::getValue('network.internet_exchanges', []),
            'peering_policy' => Setting::getValue('network.peering_policy'),
            'noc_contact' => Setting::getValue('network.noc_contact'),
            'abuse_contact' => Setting::getValue('network.abuse_contact'),
        ];

        return response()->json(['data' => $network]);
    }
}
