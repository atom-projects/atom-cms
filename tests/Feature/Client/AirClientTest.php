<?php

use App\Models\User;
use App\Services\Client\ClientLaunchService;

test('the air launcher link carries the hotel host and the ticket', function () {
    config(['app.url' => 'https://hotel.example/sub/path']);

    expect(app(ClientLaunchService::class)->airUrl('abc 123/+'))
        ->toBe('habbo://hab?server=hotel.example&token=abc%20123%2F%2B');
});

test('guests are sent to the login page instead of the air client', function () {
    installHotel();
    config(['habbo.client.air_enabled' => true]);

    $this->get(route('air-client'))->assertRedirect(route('login'));
});

test('the air client is not found when it is disabled', function () {
    installHotel();
    config(['habbo.client.air_enabled' => false]);
    $user = User::factory()->create(['auth_ticket' => '']);

    $this->actingAs($user)->get(route('air-client'))->assertNotFound();

    expect($user->fresh()->auth_ticket)->toBe('');
});

test('the air client redirects to the launcher link with a fresh ticket', function () {
    installHotel();
    config(['habbo.client.air_enabled' => true, 'app.url' => 'https://hotel.example']);
    $user = User::factory()->create(['auth_ticket' => '']);

    $response = $this->actingAs($user)->get(route('air-client'));

    $ticket = $user->fresh()->auth_ticket;
    expect($ticket)->not->toBe('');
    $response->assertRedirect('habbo://hab?server=hotel.example&token=' . rawurlencode($ticket));
    expect($response->headers->get('Cache-Control'))->toContain('no-store')->toContain('private');
});

test('the air client limits how often a ticket can be issued', function () {
    installHotel();
    config(['habbo.client.air_enabled' => true]);
    $user = User::factory()->create();

    foreach (range(1, 10) as $attempt) {
        $this->actingAs($user)->get(route('air-client'))->assertRedirect();
    }

    $this->actingAs($user)->get(route('air-client'))->assertTooManyRequests();
});

test('the me page shows the air client button only when it is enabled', function (string $theme) {
    installHotel();
    setSetting('theme', $theme);
    $user = User::factory()->create();

    config(['habbo.client.air_enabled' => false]);
    $this->actingAs($user)->get(route('me.show'))->assertOk()->assertDontSee(route('air-client'), false);

    config(['habbo.client.air_enabled' => true]);
    $this->actingAs($user)->get(route('me.show'))->assertOk()->assertSee('href="' . route('air-client') . '"', false)->assertSee('Open AIR client');
})->with(['atom', 'dusk']);
