<?php

use App\Emulator\Contracts\CurrencyRepository;
use App\Emulator\Drivers\Plus\PlusPlayerProjection;
use App\Enums\CurrencyTypes;
use App\Models\User;
use App\Services\Community\CommunityReadService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    DB::table('roles')->insert(['id' => 1, 'slug' => 'default', 'name' => 'Default', 'description' => '', 'weight' => 1, 'security_level' => 1, 'badge_code' => '', 'is_staff' => false, 'is_hidden' => false, 'created_at' => now(), 'updated_at' => now()]);
    setSetting('start_duckets', '0');
    setSetting('start_diamonds', '0');
    setSetting('start_points', '0');
});

function plusCurrencyRow(int $userId, int $type): ?int
{
    $amount = DB::table('user_currencies')->where('user_id', $userId)->where('type', $type)->value('amount');

    return $amount === null ? null : (int) $amount;
}

test('the plus users table no longer carries activity point columns', function () {
    expect(Schema::hasColumns('users', ['activity_points']))->toBeFalse()
        ->and(Schema::hasColumns('users', ['vip_points']))->toBeFalse()
        ->and(Schema::hasColumns('users', ['gotw_points']))->toBeFalse()
        ->and(Schema::hasColumns('users', ['credits']))->toBeTrue();
});

test('a missing currency row reads as zero', function () {
    $user = User::factory()->create();

    expect(DB::table('user_currencies')->where('user_id', $user->id)->exists())->toBeFalse()
        ->and($user->currency('duckets'))->toBe(0)
        ->and($user->currency('diamonds'))->toBe(0)
        ->and($user->currency('points'))->toBe(0);
});

test('gives upsert duckets diamonds and gotw points into their plus types', function () {
    $user = User::factory()->create();
    $currencies = app(CurrencyRepository::class);

    $currencies->give($user, CurrencyTypes::Duckets, 40);
    $currencies->give($user, CurrencyTypes::Duckets, 2);
    $currencies->give($user, CurrencyTypes::Diamonds, 7);
    $currencies->give($user, CurrencyTypes::Points, 3);
    $currencies->give($user, CurrencyTypes::Diamonds, -2);

    expect(plusCurrencyRow($user->id, 0))->toBe(42)
        ->and(plusCurrencyRow($user->id, 5))->toBe(5)
        ->and(plusCurrencyRow($user->id, 103))->toBe(3)
        ->and($currencies->balance($user, CurrencyTypes::Duckets))->toBe(42)
        ->and($currencies->balance($user, CurrencyTypes::Diamonds))->toBe(5)
        ->and($currencies->balance($user, CurrencyTypes::Points))->toBe(3);
});

test('credits stay on the users row', function () {
    $user = User::factory()->create(['credits' => 100]);

    app(CurrencyRepository::class)->give($user, CurrencyTypes::Credits, 50);

    expect((int) DB::table('users')->where('id', $user->id)->value('credits'))->toBe(150)
        ->and(app(CurrencyRepository::class)->deduct($user, CurrencyTypes::Credits, 200))->toBeFalse()
        ->and(app(CurrencyRepository::class)->deduct($user, CurrencyTypes::Credits, 150))->toBeTrue()
        ->and(DB::table('user_currencies')->where('user_id', $user->id)->exists())->toBeFalse();
});

test('deducting refuses to overdraw and never creates a row', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $currencies = app(CurrencyRepository::class);
    $currencies->give($user, CurrencyTypes::Diamonds, 30);

    expect($currencies->deduct($user, CurrencyTypes::Diamonds, 31))->toBeFalse()
        ->and($currencies->deduct($user, CurrencyTypes::Diamonds, 30))->toBeTrue()
        ->and(plusCurrencyRow($user->id, 5))->toBe(0)
        ->and($currencies->deduct($other, CurrencyTypes::Diamonds, 1))->toBeFalse()
        ->and(plusCurrencyRow($other->id, 5))->toBeNull();
});

test('giving to a player missing from plus users is a no-op', function () {
    $ghost = (new User)->forceFill(['id' => 999999]);

    app(CurrencyRepository::class)->give($ghost, CurrencyTypes::Duckets, 10);

    expect(DB::table('user_currencies')->where('user_id', 999999)->exists())->toBeFalse();
});

test('registration grants starting balances through user_currencies', function () {
    setSetting('start_duckets', '5000');
    setSetting('start_diamonds', '25');
    setSetting('start_points', '0');

    $user = User::factory()->create();

    expect(plusCurrencyRow($user->id, 0))->toBe(5000)
        ->and(plusCurrencyRow($user->id, 5))->toBe(25)
        ->and(plusCurrencyRow($user->id, 103))->toBeNull()
        ->and($user->fresh()->currency('duckets'))->toBe(5000);
});

test('currency leaderboards rank by user_currencies and include players without a row', function () {
    $rich = User::factory()->create();
    $poor = User::factory()->create();
    $none = User::factory()->create();
    DB::table('user_currencies')->insert([
        ['user_id' => $rich->id, 'type' => 5, 'amount' => 900],
        ['user_id' => $poor->id, 'type' => 5, 'amount' => 100],
        ['user_id' => $poor->id, 'type' => 0, 'amount' => 800],
    ]);

    $diamonds = app(CurrencyRepository::class)->topBy(CurrencyTypes::Diamonds, 3);
    $duckets = app(CurrencyRepository::class)->topBy(CurrencyTypes::Duckets, 1);

    expect($diamonds->map(fn ($entry) => [$entry->user->id, $entry->value])->all())
        ->toBe([[$rich->id, 900], [$poor->id, 100], [$none->id, 0]])
        ->and($duckets->first()->user->id)->toBe($poor->id)
        ->and($duckets->first()->value)->toBe(800)
        ->and(app(CommunityReadService::class)->leaderboards()['diamonds']->first()->value)->toBe(900);
});

test('player hydration and the projection read duckets and gotw points from user_currencies', function () {
    $user = User::factory()->create();
    DB::table('user_currencies')->insert([
        ['user_id' => $user->id, 'type' => 0, 'amount' => 321],
        ['user_id' => $user->id, 'type' => 103, 'amount' => 12],
    ]);

    $fresh = User::query()->findOrFail($user->id);
    app(PlusPlayerProjection::class)->synchronize();
    $projected = DB::table('website_users')->where('id', $user->id)->first();

    expect((int) $fresh->pixels)->toBe(321)
        ->and((int) $fresh->points)->toBe(12)
        ->and((int) $projected->pixels)->toBe(321)
        ->and((int) $projected->points)->toBe(12);
});

test('native users imported after installation bring their user_currencies balances', function () {
    $id = DB::table('users')->insertGetId(['username' => 'NativeRich', 'password' => null, 'mail' => 'native-rich@example.test']);
    DB::table('user_currencies')->insert([
        ['user_id' => $id, 'type' => 0, 'amount' => 55],
        ['user_id' => $id, 'type' => 103, 'amount' => 6],
    ]);

    app(PlusPlayerProjection::class)->import('NativeRich');
    $projected = DB::table('website_users')->where('id', $id)->first();

    expect((int) $projected->pixels)->toBe(55)
        ->and((int) $projected->points)->toBe(6);
});

test('deleting a player removes their currency rows', function () {
    $user = User::factory()->create();
    app(CurrencyRepository::class)->give($user, CurrencyTypes::Duckets, 10);

    $user->delete();

    expect(DB::table('user_currencies')->where('user_id', $user->id)->exists())->toBeFalse();
});
