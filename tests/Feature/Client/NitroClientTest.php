<?php

use App\Models\User;

test('the nitro client hides the client buttons by default', function () {
    installHotel();

    $this->actingAs(User::factory()->create())
        ->get(route('nitro-client'))
        ->assertOk()
        ->assertSee('id="nitro"', false)
        ->assertDontSee('data-client-buttons', false);
});

test('the nitro client shows the client buttons when they are enabled', function () {
    installHotel();
    setSetting('enable_client_buttons', '1');

    $this->actingAs(User::factory()->create())
        ->get(route('nitro-client'))
        ->assertOk()
        ->assertSee('data-client-buttons', false);
});

test('the nitro client can show the website while the hotel keeps running', function (string $theme) {
    installHotel();
    setSetting('theme', $theme);
    setSetting('enable_client_buttons', '1');

    $this->actingAs(User::factory()->create())
        ->get(route('nitro-client'))
        ->assertOk()
        ->assertSee('data-client-home', false)
        ->assertSee('data-website-frame', false)
        ->assertSee('data-return-to-hotel', false)
        ->assertSee('data-exit-urls="' . route('logout') . ' ' . route('flash-client') . '"', false);
})->with(['atom', 'dusk']);
