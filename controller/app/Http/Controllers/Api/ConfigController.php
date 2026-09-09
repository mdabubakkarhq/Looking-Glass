<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FooterSection;
use App\Models\MenuItem;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class ConfigController extends Controller
{
    /**
     * Return public configuration for the frontend.
     */
    public function index(): JsonResponse
    {
        $config = [
            'site_name' => config('looking-glass.site_name'),
            'organization' => config('looking-glass.organization'),
            'test_types' => config('looking-glass.test_types'),
            'ip_families' => config('looking-glass.ip_families'),
            'supported_features' => config('looking-glass.supported_features'),
            'download_sizes' => array_keys(config('looking-glass.download_sizes', [])),
            'site_logo' => '',
            'site_title' => '',
            'meta_description' => '',
            'og_image' => '',
            'favicon' => '',
            'footer_description' => '',
            'footer_links' => null,
            'copyright_text' => '',
            'email' => '',
            'asn' => '',
            'abuse_contact' => '',
        ];

        // Merge in public settings from the database
        $dbSettings = Setting::getPublicSettings();
        $config = array_merge($config, $dbSettings);

        // Map DB setting keys to the config keys the frontend expects
        if (!empty($dbSettings['organization_name'])) {
            $config['organization'] = $dbSettings['organization_name'];
        }

        // Override footer_links with live data from footer_sections table
        $sections = FooterSection::with('links')->ordered()->get()->map(function ($section) {
            return [
                'title' => $section->title,
                'links' => $section->links->map(function ($link) {
                    return [
                        'label' => $link->label,
                        'url' => $link->url,
                        'external' => (bool) $link->external,
                    ];
                })->values()->toArray(),
            ];
        })->values()->toArray();

        if (!empty($sections)) {
            $config['footer_links'] = ['sections' => $sections];
        }

        // Include active menu items
        $config['menu_items'] = MenuItem::active()->ordered()->get()->map(function ($item) {
            return [
                'label' => $item->label,
                'url' => $item->url,
                'open_new_tab' => (bool) $item->open_new_tab,
            ];
        })->values()->toArray();

        return response()->json($config);
    }
}
