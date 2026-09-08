<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\NetworkTest;
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
}
