<?php

use App\Emulator\Contracts\BadgeRepository;
use App\Emulator\Contracts\BanRepository;
use App\Emulator\Contracts\CurrencyRepository;
use App\Emulator\Contracts\FurnitureRepository;
use App\Emulator\Contracts\PlayerStatsRepository;
use App\Emulator\Contracts\RoomRepository;
use App\Emulator\Data\Stat;
use App\Enums\CurrencyTypes;
use App\Models\User;
use App\Services\Auth\PasswordVerifier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    DB::table('roles')->insert(['id' => 1, 'slug' => 'default', 'name' => 'Default', 'description' => '', 'weight' => 1, 'security_level' => 1, 'badge_code' => '', 'is_staff' => false, 'is_hidden' => false, 'created_at' => now(), 'updated_at' => now()]);
    setSetting('start_duckets', '0');
    setSetting('start_diamonds', '0');
    setSetting('start_points', '0');
});

test('registration writes native plus identity and aggregate rows', function () {
    $owner = User::factory()->create();
    makePlusRoom($owner->id, ['id' => 44]);
    makePlusRoom($owner->id, ['id' => 45]);
    $user = User::factory()->create(['credits' => 1500, 'home_room' => 44]);
    $homeless = User::factory()->create(['home_room' => 4040]);

    expect(DB::table('users')->where('id', $user->id)->value('username'))->toBe($user->username)
        ->and(DB::table('users')->where('id', $user->id)->value('password'))->toStartWith('$argon2id$')
        ->and(DB::table('users_settings')->where('user_id', $user->id)->exists())->toBeTrue()
        ->and(DB::table('users_settings')->where('user_id', $user->id)->value('home_room'))->toBe(44)
        ->and(DB::table('user_statistics')->where('id', $user->id)->exists())->toBeTrue()
        ->and(DB::table('user_roles')->where('user_id', $user->id)->value('role_id'))->toBe(1);

    $user->forceFill(['home_room' => 45])->save();
    expect(DB::table('users_settings')->where('user_id', $user->id)->value('home_room'))->toBe(45)
        ->and(DB::table('users_settings')->where('user_id', $homeless->id)->value('home_room'))->toBe(0);
});

test('plus furniture is limited when an offer selling it has a limited stock', function () {
    $limited = DB::table('furniture')->insertGetId(['item_name' => 'ltd_chair']);
    $plain = DB::table('furniture')->insertGetId(['item_name' => 'chair']);
    DB::table('catalog_offers')->insert([['id' => 1, 'localization_key' => 'ltd_chair'], ['id' => 2, 'localization_key' => 'chair']]);
    DB::table('catalog_offer_products')->insert([
        ['offer_id' => 1, 'position' => 0, 'product_type' => 'furni', 'furniture_id' => $limited],
        ['offer_id' => 2, 'position' => 0, 'product_type' => 'furni', 'furniture_id' => $plain],
    ]);
    DB::table('catalog_offer_limited')->insert(['offer_id' => 1, 'stack' => 10]);

    expect(app(FurnitureRepository::class)->isLimitedEdition($limited))->toBeTrue()
        ->and(app(FurnitureRepository::class)->isLimitedEdition($plain))->toBeFalse();
});

test('direct model password assignments use native Plus argon2id', function () {
    $user = User::factory()->create();
    $user->forceFill(['password' => 'Direct-password!123'])->save();

    expect(DB::table('users')->where('id', $user->id)->value('password'))->toStartWith('$argon2id$')
        ->and(Hash::check('Direct-password!123', $user->fresh()->password))->toBeTrue();
});

test('plus repositories use final native columns', function () {
    $user = User::factory()->create();
    app(CurrencyRepository::class)->give($user, CurrencyTypes::Duckets, 25);
    app(BadgeRepository::class)->grant($user, 'ACH_Test1');
    $item = DB::table('furniture')->insertGetId(['item_name' => 'chair']);
    app(FurnitureRepository::class)->grant($user, $item, 2);
    makePlusRoom($user->id, ['caption' => 'Suite', 'description' => 'Plus room', 'state' => 'locked']);
    DB::table('user_statistics')->where('id', $user->id)->update(['AchievementScore' => 99]);

    expect(app(CurrencyRepository::class)->balance($user, CurrencyTypes::Duckets))->toBe(25)
        ->and(app(BadgeRepository::class)->codes($user))->toBe(['ACH_Test1'])
        ->and(app(FurnitureRepository::class)->holdings($item)->first()->item_count)->toBe(2)
        ->and(app(RoomRepository::class)->forHome($user)->first()->name)->toBe('Suite')
        ->and(app(PlayerStatsRepository::class)->topBy(Stat::AchievementScore, 1)->first()->value)->toBe(99);
});

test('sso tickets use current plus credential fields', function () {
    $user = User::factory()->create();
    $ticket = $user->ssoTicket();
    $native = DB::table('users')->where('id', $user->id)->first();

    expect(strlen($ticket))->toBeGreaterThanOrEqual(43)
        ->and($native->auth_ticket)->toBe($ticket)
        ->and($native->auth_ticket_expires_at)->not->toBeNull()
        ->and((int) $native->auth_ticket_exchanged)->toBe(0)
        ->and($native->auth_ticket_session)->toBeNull();
});

test('password changes revoke every native plus credential', function () {
    $user = User::factory()->create();
    DB::table('users')->where('id', $user->id)->update(['auth_ticket' => 'old-ticket', 'auth_ticket_expires_at' => now()->addMinute()]);
    DB::table('user_access_tokens')->insert(['user_id' => $user->id, 'token_hash' => str_repeat('a', 64)]);
    DB::table('user_sessions')->insert([['id' => str_repeat('c', 32), 'user_id' => $user->id], ['id' => str_repeat('f', 32), 'user_id' => $user->id]]);
    DB::table('user_remember_tokens')->insert(['user_id' => $user->id, 'family_id' => str_repeat('f', 32), 'token_hash' => str_repeat('b', 64)]);

    $user->changePassword('A-new-password!123');
    $native = DB::table('users')->where('id', $user->id)->first();

    expect($native->password)->toStartWith('$argon2id$')
        ->and((int) $native->credential_generation)->toBe(1)
        ->and($native->auth_ticket)->toBeNull()
        ->and(DB::table('user_access_tokens')->where('user_id', $user->id)->value('revoked_at'))->not->toBeNull()
        ->and(DB::table('user_remember_tokens')->where('user_id', $user->id)->value('revoked_at'))->not->toBeNull()
        ->and(DB::table('user_sessions')->where('user_id', $user->id)->value('revoked_at'))->not->toBeNull();
});

test('cms rank is the highest active native security level, not a role id', function () {
    DB::table('roles')->insert([
        ['id' => 99, 'slug' => 'helper', 'name' => 'Helper', 'description' => '', 'weight' => 500, 'security_level' => 3, 'badge_code' => '', 'is_staff' => true, 'is_hidden' => false, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 7, 'slug' => 'owner', 'name' => 'Owner', 'description' => '', 'weight' => 900, 'security_level' => 7, 'badge_code' => '', 'is_staff' => true, 'is_hidden' => false, 'created_at' => now(), 'updated_at' => now()],
    ]);
    $user = User::factory()->create();
    DB::table('user_roles')->insert([
        ['user_id' => $user->id, 'role_id' => 99, 'expires_at' => null, 'created_at' => now()],
        ['user_id' => $user->id, 'role_id' => 7, 'expires_at' => now()->subMinute(), 'created_at' => now()],
    ]);

    expect($user->fresh()->rank)->toBe(3);

    DB::table('user_roles')->where('user_id', $user->id)->where('role_id', 7)->update(['expires_at' => now()->addMinute()]);

    expect($user->fresh()->rank)->toBe(7);
});

test('changing staff rank preserves independent member roles', function () {
    DB::table('roles')->insert([
        ['id' => 2, 'slug' => 'vip', 'name' => 'VIP', 'description' => '', 'weight' => 10, 'security_level' => 1, 'badge_code' => '', 'is_staff' => false, 'is_hidden' => false, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 30, 'slug' => 'moderator', 'name' => 'Moderator', 'description' => '', 'weight' => 300, 'security_level' => 3, 'badge_code' => '', 'is_staff' => true, 'is_hidden' => false, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 50, 'slug' => 'manager', 'name' => 'Manager', 'description' => '', 'weight' => 500, 'security_level' => 5, 'badge_code' => '', 'is_staff' => true, 'is_hidden' => false, 'created_at' => now(), 'updated_at' => now()],
    ]);
    $user = User::factory()->create();
    DB::table('user_roles')->where('user_id', $user->id)->delete();
    DB::table('user_roles')->insert([
        ['user_id' => $user->id, 'role_id' => 1, 'created_at' => now()],
        ['user_id' => $user->id, 'role_id' => 2, 'created_at' => now()],
        ['user_id' => $user->id, 'role_id' => 30, 'created_at' => now()],
    ]);

    $user->forceFill(['rank' => 5])->save();

    expect(DB::table('user_roles')->where('user_id', $user->id)->pluck('role_id')->sort()->values()->all())
        ->toBe([1, 2, 50]);
});

test('ban lookup matches plus expiry and target semantics', function () {
    $user = User::factory()->create();
    DB::table('bans')->insert([
        ['bantype' => 'ip', 'value' => '10.0.0.1', 'reason' => 'Longest active', 'expire' => now()->addMinutes(5), 'added_by' => 'system'],
        ['bantype' => 'ip', 'value' => '10.0.0.1', 'reason' => 'Active', 'expire' => now()->addMinute(), 'added_by' => 'system'],
        ['bantype' => 'machine', 'value' => '10.0.0.2', 'reason' => 'Not an IP', 'expire' => now()->addMinute(), 'added_by' => 'system'],
        ['bantype' => 'user', 'value' => $user->username, 'reason' => 'Null expiry', 'expire' => null, 'added_by' => 'system'],
    ]);

    expect(app(BanRepository::class)->activeIpBan('10.0.0.1')?->ban_reason)->toBe('Longest active')
        ->and(app(BanRepository::class)->activeIpBan('10.0.0.2'))->toBeNull()
        ->and(app(BanRepository::class)->activeAccountBan($user))->toBeNull();
});

test('ban expiry comparisons retain native datetime microseconds', function () {
    Carbon::setTestNow('2026-10-06 12:00:00.500000 UTC');
    DB::table('bans')->insert([
        ['bantype' => 'ip', 'value' => '10.0.0.3', 'reason' => 'Expired this second', 'expire' => '2026-10-06 12:00:00.400000', 'added_by' => 'system'],
        ['bantype' => 'ip', 'value' => '10.0.0.3', 'reason' => 'Active this second', 'expire' => '2026-10-06 12:00:00.600000', 'added_by' => 'system'],
    ]);

    expect(app(BanRepository::class)->activeIpBan('10.0.0.3')?->ban_reason)->toBe('Active this second');
    Carbon::setTestNow();
});

test('legacy plaintext password is accepted once and upgraded to argon2id', function () {
    $user = User::factory()->create();
    DB::table('users')->where('id', $user->id)->update(['password' => 'legacy-secret']);
    $legacy = User::query()->findOrFail($user->id);

    expect(app(PasswordVerifier::class)->verify($legacy, 'wrong'))->toBeFalse()
        ->and(app(PasswordVerifier::class)->verify($legacy, 'legacy-secret'))->toBeTrue()
        ->and(DB::table('users')->where('id', $user->id)->value('password'))->toStartWith('$argon2id$');
});

test('dollar-prefixed legacy plaintext upgrades but malformed argon2id fails closed', function () {
    $legacy = User::factory()->create();
    DB::table('users')->where('id', $legacy->id)->update(['password' => '$Dollar-secret123']);
    $malformed = User::factory()->create();
    DB::table('users')->where('id', $malformed->id)->update(['password' => '$argon2id$malformed']);

    expect(app(PasswordVerifier::class)->verify($legacy->fresh(), '$Dollar-secret123'))->toBeTrue()
        ->and(DB::table('users')->where('id', $legacy->id)->value('password'))->toStartWith('$argon2id$')
        ->and(app(PasswordVerifier::class)->verify($malformed->fresh(), '$argon2id$malformed'))->toBeFalse();
});

test('a nullable native password never authenticates', function () {
    $user = User::factory()->create();
    DB::table('users')->where('id', $user->id)->update(['password' => null]);

    expect(app(PasswordVerifier::class)->verify(User::query()->findOrFail($user->id), 'anything'))->toBeFalse();
});

test('native users added after installation are projected without id collisions', function () {
    $hash = Hash::make('Native-password!123');
    DB::table('users')->insert(['id' => 500, 'username' => 'NativeLater', 'password' => $hash, 'mail' => 'native-later@example.test']);
    DB::table('users_settings')->insert(['user_id' => 500]);
    DB::table('user_statistics')->insert(['id' => 500]);
    DB::table('user_roles')->insert(['user_id' => 500, 'role_id' => 1, 'created_at' => now()]);

    $native = User::query()->where('username', 'NativeLater')->first();
    $cms = User::factory()->create();

    expect($native?->id)->toBe(500)
        ->and(app(PasswordVerifier::class)->verify($native, 'Native-password!123'))->toBeTrue()
        ->and($cms->id)->toBeGreaterThan(500)
        ->and(DB::table('users')->where('id', $cms->id)->value('username'))->toBe($cms->username);
});

test('queries read native inserts updates and deletes before filtering and counting', function () {
    $first = User::factory()->create(['credits' => 10]);
    $deleted = User::factory()->create();
    DB::table('users')->where('id', $first->id)->update(['credits' => 9000, 'online' => true]);
    DB::table('users')->insert(['id' => 700, 'username' => 'NativeQuery', 'password' => null, 'mail' => $first->mail, 'credits' => 8000, 'online' => true]);
    DB::table('users')->where('id', $deleted->id)->delete();

    $richest = User::query()->where('credits', '>=', 8000)->orderByDesc('credits')->pluck('id')->all();

    expect($richest)->toBe([$first->id, 700])
        ->and(User::query()->where('online', true)->count())->toBe(2)
        ->and(User::query()->find($deleted->id))->toBeNull();
});
