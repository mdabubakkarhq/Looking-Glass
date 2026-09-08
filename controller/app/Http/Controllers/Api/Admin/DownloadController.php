<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DownloadFile;
use App\Models\Node;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DownloadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $downloads = DownloadFile::with('node:id,slug,name')
            ->ordered()
            ->paginate($request->input('per_page', 25));

        return response()->json($downloads);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'node_id' => 'required|exists:nodes,id',
            'name' => 'required|string|max:255',
            'filename' => 'required|string|max:255',
            'size_bytes' => 'required|integer|min:1',
            'size_label' => 'required|string|max:20',
            'url' => 'required|url',
            'enabled' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $download = DownloadFile::create($validated);

        return response()->json(['data' => $download], 201);
    }

    public function show(DownloadFile $download): JsonResponse
    {
        return response()->json(['data' => $download->load('node:id,slug,name')]);
    }

    public function update(Request $request, DownloadFile $download): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'filename' => 'sometimes|string|max:255',
            'size_bytes' => 'sometimes|integer|min:1',
            'size_label' => 'sometimes|string|max:20',
            'url' => 'sometimes|url',
            'enabled' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $download->update($validated);

        return response()->json(['data' => $download->fresh()]);
    }

    public function destroy(DownloadFile $download): JsonResponse
    {
        $download->delete();

        return response()->json(['message' => 'Download file deleted']);
    }
}
