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
