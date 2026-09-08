<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DownloadFile;
use Illuminate\Http\JsonResponse;

class DownloadController extends Controller
{
    public function index(): JsonResponse
    {
        $downloads = DownloadFile::enabled()
            ->with('node:id,slug,name,city,country_code,download_host')
            ->ordered()
            ->get()
            ->map(fn (DownloadFile $file) => [
                'id' => $file->id,
                'node_id' => $file->node->slug,
                'name' => $file->name,
                'size_bytes' => $file->size_bytes,
                'size_label' => $file->size_label,
                'url' => $file->url,
            ]);

        return response()->json(['data' => $downloads]);
    }
}
