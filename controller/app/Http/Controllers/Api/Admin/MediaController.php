<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    /**
     * Upload a media file (logo, OG image, favicon, etc.)
     * Returns the public URL.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|max:5120|mimes:png,jpg,jpeg,svg,webp,ico',
            'folder' => 'sometimes|string|max:100',
        ]);

        $folder = $validated['folder'] ?? 'media';
        $file = $validated['file'];
        $extension = $file->getClientOriginalExtension();
        $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
            . '-' . Str::random(8) . '.' . $extension;

        $path = $file->storeAs("{$folder}", $filename, 'public');

        // Generate relative URL so it works with any protocol/host
        // When using the 'public' disk, $path is like 'media/filename.ext'
        $publicUrl = '/storage/' . $path;

        return response()->json([
            'data' => [
                'url' => $publicUrl,
                'path' => $path,
                'filename' => $filename,
                'size_bytes' => $file->getSize(),
            ],
        ], 201);
    }
}