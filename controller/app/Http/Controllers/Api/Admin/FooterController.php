<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\FooterLink;
use App\Models\FooterSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FooterController extends Controller
{
    // ── Sections ──────────────────────────────────────────────

    public function index(): JsonResponse
    {
        $sections = FooterSection::with('links')->ordered()->get();

        return response()->json(['data' => $sections]);
    }

    public function storeSection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'sort_order' => 'integer|min:0',
        ]);

        $section = FooterSection::create($validated);

        return response()->json(['data' => $section->load('links')], 201);
    }

    public function updateSection(Request $request, FooterSection $footerSection): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'sort_order' => 'integer|min:0',
        ]);

        $footerSection->update($validated);

        return response()->json(['data' => $footerSection->fresh()->load('links')]);
    }

    public function destroySection(FooterSection $footerSection): JsonResponse
    {
        $footerSection->delete();

        return response()->json(['message' => 'Footer section deleted']);
    }

    // ── Links ─────────────────────────────────────────────────

    public function storeLink(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'footer_section_id' => 'required|exists:footer_sections,id',
            'label' => 'required|string|max:255',
            'url' => 'required|string|max:500',
            'external' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $link = FooterLink::create($validated);

        return response()->json(['data' => $link], 201);
    }

    public function updateLink(Request $request, FooterLink $footerLink): JsonResponse
    {
        $validated = $request->validate([
            'label' => 'sometimes|string|max:255',
            'url' => 'sometimes|string|max:500',
            'external' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $footerLink->update($validated);

        return response()->json(['data' => $footerLink->fresh()]);
    }

    public function destroyLink(FooterLink $footerLink): JsonResponse
    {
        $footerLink->delete();

        return response()->json(['message' => 'Footer link deleted']);
    }
}