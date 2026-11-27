<?php

namespace App\Http\Controllers\Miscellaneous;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Serves the same manifest as the Nuxt frontend, so a hotel installs the same way from either site.
 */
class WebAppManifestController extends Controller
{
    private const string BACKGROUND_COLOR = '#0e151c';

    public function __invoke(): JsonResponse
    {
        $name = trim((string) setting('hotel_name', config('app.name'))) ?: 'Hotel';
        $description = trim((string) setting('hotel_description', ''));

        $manifest = array_filter([
            'id' => '/',
            'name' => $name,
            'short_name' => $name,
            'description' => $description,
            'start_url' => '/game/nitro',
            'scope' => '/',
            'display' => 'standalone',
            'display_override' => ['fullscreen', 'standalone'],
            'orientation' => 'any',
            'background_color' => self::BACKGROUND_COLOR,
            'theme_color' => self::BACKGROUND_COLOR,
            'icons' => [
                ['src' => '/pwa/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => '/pwa/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
                ['src' => '/pwa/icon-maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
        ], fn (mixed $value): bool => $value !== '');

        return response()->json($manifest, headers: [
            'Content-Type' => 'application/manifest+json; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
