<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\NetworkTest;
use App\Models\NetworkTestEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = NetworkTest::with('node:id,slug,name');

        if ($request->filled('test_type')) {
            $query->where('test_type', $request->input('test_type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('node_id')) {
            $query->whereHas('node', fn ($q) => $q->where('slug', $request->input('node_id')));
        }

        if ($request->filled('target')) {
            $query->where('target', 'like', '%' . $request->input('target') . '%');
        }

        $tests = $query->orderByDesc('created_at')
            ->paginate($request->input('per_page', 25));

        return response()->json($tests);
    }

    public function show(NetworkTest $test): JsonResponse
    {
        return response()->json([
            'data' => $test->load(['node:id,slug,name', 'events']),
        ]);
    }

    public function destroy(NetworkTest $test): JsonResponse
    {
        $test->delete();

        return response()->json(['message' => 'Test deleted']);
    }

    /**
     * Bulk purge old completed/failed tests.
     */
    public function purge(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'days' => 'sometimes|integer|min:1|max:365',
            'status' => 'sometimes|string|in:completed,failed,all',
        ]);

        $days = $validated['days'] ?? 7;
        $status = $validated['status'] ?? 'all';
        $cutoff = now()->subDays($days);

        $query = NetworkTest::where('created_at', '<', $cutoff);

        if ($status !== 'all') {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', ['completed', 'failed']);
        }

        $testIds = $query->pluck('id');

        if ($testIds->isEmpty()) {
            return response()->json(['message' => 'No tests to purge.', 'purged' => 0]);
        }

        // Delete events first (foreign key)
        $eventsDeleted = NetworkTestEvent::whereIn('network_test_id', $testIds)->delete();
        $testsDeleted = NetworkTest::whereIn('id', $testIds)->delete();

        return response()->json([
            'message' => "Purged {$testsDeleted} test(s) and {$eventsDeleted} event(s).",
            'purged' => $testsDeleted,
        ]);
    }
}
