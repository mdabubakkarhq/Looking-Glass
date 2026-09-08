<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\RateLimitEvent;
use App\Models\SecurityEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function rateLimits(Request $request): JsonResponse
    {
        $query = RateLimitEvent::with('node:id,slug,name');

        if ($request->filled('reason')) {
            $query->where('reason', $request->input('reason'));
        }

        if ($request->filled('node_id')) {
            $query->where('node_id', $request->input('node_id'));
        }

        $events = $query->orderByDesc('created_at')
            ->paginate($request->input('per_page', 50));

        return response()->json($events);
    }

    public function security(Request $request): JsonResponse
    {
        $query = SecurityEvent::with('node:id,slug,name');

        if ($request->filled('severity')) {
            $query->where('severity', $request->input('severity'));
        }

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->input('event_type'));
        }

        $events = $query->orderByDesc('created_at')
            ->paginate($request->input('per_page', 50));

        return response()->json($events);
    }
}
