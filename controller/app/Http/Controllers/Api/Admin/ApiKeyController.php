<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiKeyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $keys = ApiKey::with('user:id,name,email')
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 25));

        return response()->json($keys);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'abilities' => 'nullable|array',
            'abilities.*' => 'string',
            'rate_limit_per_minute' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $key = ApiKey::create(array_merge($validated, [
            'user_id' => auth()->id(),
        ]));

        return response()->json([
            'data' => $key,
            'token' => $key->token, // Only shown once at creation
            'message' => 'Store this token securely. It will not be shown again.',
        ], 201);
    }

    public function show(ApiKey $apiKey): JsonResponse
    {
        return response()->json(['data' => $apiKey->load('user:id,name,email')]);
    }

    public function update(Request $request, ApiKey $apiKey): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'abilities' => 'nullable|array',
            'abilities.*' => 'string',
            'rate_limit_per_minute' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date',
        ]);

        $apiKey->update($validated);

        return response()->json(['data' => $apiKey->fresh()]);
    }

    public function destroy(ApiKey $apiKey): JsonResponse
    {
        $apiKey->delete();

        return response()->json(['message' => 'API key revoked']);
    }
}
