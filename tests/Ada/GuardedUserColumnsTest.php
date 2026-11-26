<?php

use App\Emulator\Contracts\CurrencyRepository;
use App\Emulator\Contracts\PlayerRepository;
use App\Enums\CurrencyTypes;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Guarding columns on the User model must not stop anything reaching Ada's
 * own tables.
 */
test('ada still receives the starting balances after the fillable change', function () {
    installHotel();
    setSetting('start_duckets', '250');

    $user = User::factory()->create();

    expect(app(CurrencyRepository::class)->balance($user, CurrencyTypes::Duckets))
        ->toBe((int) DB::table('player_data')->where('player_id', $user->id)->value('pixel_balance'));
});

test('ada issues an sso token into player_sso_tokens', function () {
    $user = User::factory()->create();

    $token = app(PlayerRepository::class)->issueSso($user);

    expect($token)->not->toBeEmpty()
        ->and(DB::table('player_sso_tokens')->where('player_id', $user->id)->where('token', $token)->exists())
        ->toBeTrue();
});

test('ada orders online friends by player_data.last_online', function () {
    $user = User::factory()->create();
    $stale = User::factory()->create();
    $recent = User::factory()->create();

    DB::table('player_data')->where('player_id', $stale->id)
        ->update(['is_online' => true, 'last_online' => now()->subDays(3)]);
    DB::table('player_data')->where('player_id', $recent->id)
        ->update(['is_online' => true, 'last_online' => now()]);

    DB::table('player_friendships')->insert([
        ['origin_player_id' => $user->id, 'target_player_id' => $stale->id, 'status' => 2, 'created_at' => now()],
        ['origin_player_id' => $user->id, 'target_player_id' => $recent->id, 'status' => 2, 'created_at' => now()],
    ]);

    expect(app(PlayerRepository::class)->onlineFriends($user, 10)->pluck('id')->all())
        ->toBe([$recent->id, $stale->id]);
});
