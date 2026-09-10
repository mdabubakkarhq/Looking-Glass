<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| The SPA frontend handles all client-side routing.
| The controller only serves the API and this health check.
|
*/

Route::get('/up', function () {
    return response()->json(['status' => 'ok', 'version' => config('app.version', '1.0.0')]);
});

// SPA catch-all: serve the Vue frontend with dynamic meta tags for SEO/OG
Route::fallback(function () {
    $indexPath = public_path('spa.html');
    if (!file_exists($indexPath)) {
        abort(404, 'Frontend not built. Run: cd frontend && npm run build');
    }

    // Cache the rendered HTML; invalidated via Setting::setValue whenever a setting changes
    $html = Cache::remember('spa_index_html', 3600, function () use ($indexPath) {
        $html = file_get_contents($indexPath);

        // Pull settings from DB (same keys as in the seeder)
        $siteName    = Setting::getValue('site_name', config('looking-glass.site_name', 'Open Looking Glass'));
        $siteTitle   = Setting::getValue('site_title', '');
        $description = Setting::getValue('meta_description', '');
        $ogImage     = Setting::getValue('og_image', '');
        $favicon     = Setting::getValue('favicon', '');

        // Resolve effective values
        $effectiveTitle = $siteTitle ?: $siteName;
        $effectiveDesc  = $description ?: 'Multi-location network diagnostics platform';

        // ── Title ──
        $html = preg_replace(
            '/<title>.*?<\/title>/s',
            '<title>' . e($effectiveTitle) . '</title>',
            $html
        );

        // ── Meta description ──
        $html = preg_replace(
            '/<meta\s+name="description"\s+content="[^"]*"\s*\/?>/i',
            '<meta name="description" content="' . e($effectiveDesc) . '" />',
            $html
        );

        // ── OG tags ──
        $html = preg_replace(
            '/<meta\s+property="og:title"\s+content="[^"]*"\s*\/?>/i',
            '<meta property="og:title" content="' . e($effectiveTitle) . '" />',
            $html
        );
        $html = preg_replace(
            '/<meta\s+property="og:description"\s+content="[^"]*"\s*\/?>/i',
            '<meta property="og:description" content="' . e($effectiveDesc) . '" />',
            $html
        );
        $html = preg_replace(
            '/<meta\s+property="og:url"\s+content="[^"]*"\s*\/?>/i',
            '<meta property="og:url" content="__OG_URL__" />',
            $html
        );

        // og:image — only inject if set (use __BASE_URL__ placeholder for scheme-aware absolute URL)
        if ($ogImage) {
            $absoluteOgImage = str_starts_with($ogImage, 'http')
                ? $ogImage
                : '__BASE_URL__/' . ltrim($ogImage, '/');
            $html = preg_replace(
                '/<meta\s+property="og:image"\s+content="[^"]*"\s*\/?>/i',
                '<meta property="og:image" content="' . e($absoluteOgImage) . '" />',
                $html
            );
            // If og:image wasn't in the template, insert it
            if (!str_contains($html, 'og:image')) {
                $html = str_replace(
                    '</head>',
                    '    <meta property="og:image" content="' . e($absoluteOgImage) . '" />' . "\n  </head>",
                    $html
                );
            }
        }

        // ── Twitter tags ──
        $twitterCard = $ogImage ? 'summary_large_image' : 'summary';
        $html = preg_replace(
            '/<meta\s+name="twitter:card"\s+content="[^"]*"\s*\/?>/i',
            '<meta name="twitter:card" content="' . e($twitterCard) . '" />',
            $html
        );
        $html = preg_replace(
            '/<meta\s+name="twitter:title"\s+content="[^"]*"\s*\/?>/i',
            '<meta name="twitter:title" content="' . e($effectiveTitle) . '" />',
            $html
        );
        $html = preg_replace(
            '/<meta\s+name="twitter:description"\s+content="[^"]*"\s*\/?>/i',
            '<meta name="twitter:description" content="' . e($effectiveDesc) . '" />',
            $html
        );

        if ($ogImage) {
            $html = preg_replace(
                '/<meta\s+name="twitter:image"\s+content="[^"]*"\s*\/?>/i',
                '<meta name="twitter:image" content="' . e($absoluteOgImage) . '" />',
                $html
            );
            if (!str_contains($html, 'twitter:image')) {
                $html = str_replace(
                    '</head>',
                    '    <meta name="twitter:image" content="' . e($absoluteOgImage) . '" />' . "\n  </head>",
                    $html
                );
            }
        }

        // ── Favicon ──
        if ($favicon) {
            if (preg_match('/<link\s+rel="icon"[^>]*\/?>/i', $html)) {
                $html = preg_replace(
                    '/<link\s+rel="icon"[^>]*\/?>/i',
                    '<link rel="icon" href="' . e($favicon) . '" />',
                    $html
                );
            } else {
                $html = str_replace(
                    '</head>',
                    '    <link rel="icon" href="' . e($favicon) . '" />' . "\n  </head>",
                    $html
                );
            }
        }

        return $html;
    });

    // ── Per-request URL injection (not cached — varies by route/scheme) ──
    $origin  = request()->getSchemeAndHttpHost();
    $fullUrl = request()->fullUrl();

    // Replace scheme-aware placeholders
    $html = str_replace('__BASE_URL__', e($origin), $html);
    $html = str_replace('__OG_URL__', e($fullUrl), $html);

    // Inject / replace canonical URL
    if (str_contains($html, '<link rel="canonical"')) {
        $html = preg_replace(
            '/<link\s+rel="canonical"\s+href="[^"]*"\s*\/?>/i',
            '<link rel="canonical" href="' . e($fullUrl) . '" />',
            $html
        );
    } else {
        $html = str_replace(
            '</head>',
            '    <link rel="canonical" href="' . e($fullUrl) . '" />' . "\n  </head>",
            $html
        );
    }

    return response($html, 200)->header('Content-Type', 'text/html');
});
