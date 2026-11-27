<?php

use App\Models\User;

beforeEach(function () {
    installHotel();
});

test('the web app manifest describes the hotel', function () {
    setSetting('hotel_name', ' Habbo Retro ');
    setSetting('hotel_description', 'A pixel hotel.');

    $this->get('/manifest.webmanifest')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/manifest+json; charset=utf-8')
        ->assertHeader('Cache-Control', 'max-age=3600, public')
        ->assertExactJson([
            'id' => '/',
            'name' => 'Habbo Retro',
            'short_name' => 'Habbo Retro',
            'description' => 'A pixel hotel.',
            'start_url' => '/game/nitro',
            'scope' => '/',
            'display' => 'standalone',
            'display_override' => ['fullscreen', 'standalone'],
            'orientation' => 'any',
            'background_color' => '#0e151c',
            'theme_color' => '#0e151c',
            'icons' => [
                ['src' => '/pwa/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => '/pwa/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
                ['src' => '/pwa/icon-maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
        ]);
});

test('the web app manifest leaves out an empty description and names a nameless hotel', function () {
    setSetting('hotel_name', ' ');
    setSetting('hotel_description', '');

    $manifest = $this->get('/manifest.webmanifest')->assertOk()->json();

    expect($manifest)->not->toHaveKey('description')
        ->and($manifest['name'])->toBe('Hotel')
        ->and($manifest['short_name'])->toBe('Hotel');
});

test('the web app files the manifest and service worker need are public', function () {
    $icons = collect($this->get('/manifest.webmanifest')->json('icons'))->pluck('src');

    foreach ([...$icons, '/pwa/apple-touch-icon.png', '/sw.js', '/offline.html'] as $path) {
        expect(public_path(ltrim($path, '/')))->toBeFile();
    }
});

test('website pages link the manifest and offer the install banner', function (string $theme) {
    setSetting('theme', $theme);
    setSetting('hotel_name', 'Habbo Retro');

    $this->get(route('article.index'))
        ->assertOk()
        ->assertSee('<link rel="manifest" href="/manifest.webmanifest">', false)
        ->assertSee('<meta name="apple-mobile-web-app-title" content="Habbo Retro">', false)
        ->assertSee('viewport-fit=cover', false)
        ->assertSee('data-install-app="page"', false)
        ->assertSee('Play Habbo Retro as an app');
})->with(['atom', 'dusk']);

test('the nitro client offers the install banner and fits the phone screen', function (string $theme) {
    setSetting('theme', $theme);

    $this->actingAs(User::factory()->create())
        ->get(route('nitro-client'))
        ->assertOk()
        ->assertSee('<link rel="manifest" href="/manifest.webmanifest">', false)
        ->assertSee('data-install-app="game"', false)
        ->assertSee('data-client-viewport', false)
        ->assertSee('allow="fullscreen; autoplay; clipboard-read; clipboard-write; screen-wake-lock; web-share"', false);
})->with(['atom', 'dusk']);
