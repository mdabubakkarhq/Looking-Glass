<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuItemController extends Controller
{
    public function index(): JsonResponse
    {
        $items = MenuItem::ordered()->get();

        return response()->json(['data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'url' => 'required|string|max:500',
            'sort_order' => 'integer|min:0',
            'open_new_tab' => 'boolean',
            'active' => 'boolean',
        ]);

        $item = MenuItem::create($validated);

        return response()->json(['data' => $item], 201);
    }

    public function show(MenuItem $menuItem): JsonResponse
    {
        return response()->json(['data' => $menuItem]);
    }

    public function update(Request $request, MenuItem $menuItem): JsonResponse
    {
        $validated = $request->validate([
            'label' => 'sometimes|string|max:255',
            'url' => 'sometimes|string|max:500',
            'sort_order' => 'integer|min:0',
            'open_new_tab' => 'boolean',
            'active' => 'boolean',
        ]);

        $menuItem->update($validated);

        return response()->json(['data' => $menuItem->fresh()]);
    }

    public function destroy(MenuItem $menuItem): JsonResponse
    {
        $menuItem->delete();

        return response()->json(['message' => 'Menu item deleted']);
    }

    /**
     * Bulk reorder menu items.
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:menu_items,id',
        ]);

        foreach ($validated['order'] as $index => $id) {
            MenuItem::where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json(['message' => 'Menu items reordered']);
    }
}