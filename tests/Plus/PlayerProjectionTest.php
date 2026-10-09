<?php

use App\Actions\Fortify\CreateNewUser;
use App\Emulator\Contracts\CurrencyRepository;
use App\Emulator\Contracts\PlayerRepository;
use App\Enums\CurrencyTypes;
use App\Filament\Resources\User\Users\Pages\EditUser;
use App\Models\User;
use App\Services\Community\StaffService;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    DB::table('roles')->insert([
        ['id' => 1, 'slug' => 'default', 'name' => 'Default', 'description' => '', 'weight' => 1, 'security_level' => 1, 'badge_code' => '', 'is_staff' => false, 'is_hidden' => false, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 4, 'slug' => 'moderator', 'name' => 'Moderator', 'description' => '', 'weight' => 400, 'security_level' => 4, 'badge_code' => '', 'is_staff' => true, 'is_hidden' => false, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 7, 'slug' => 'owner', 'name' => 'Owner', 'description' => '', 'weight' => 900, 'security_level' => 7, 'badge_code' => '', 'is_staff' => true, 'is_hidden' => false, 'created_at' => now(), 'updated_at' => now()],
    ]);
    setSetting('start_duckets', '0');
    setSetting('start_diamonds', '0');
    setSetting('start_points', '0');
});

/** @return list<string> */
function projectionStatements(Closure $callback): array
{
    DB::flushQueryLog();
    DB::enableQueryLog();
    $callback();
    DB::disableQueryLog();

    return array_map(fn (array $query): string => $query['query'], DB::getQueryLog());
}

/** @param  array<string, mixed>  $attributes  Extra users columns. */
function nativePlayer(int $id, string $username, array $attributes = []): void
{
    DB::table('users')->insert(['id' => $id, 'username' => $username, 'password' => null, 'mail' => "{$username}@example.test", ...$attributes]);
}

test('reading a player refreshes only that player and costs the same at any hotel size', function () {
    $player = User::factory()->create(['credits' => 10]);
    $others = User::factory()->count(3)->create(['credits' => 10]);
    $this->travel(1)->minute();
    User::query()->count();
    DB::table('users')->update(['credits' => 9000]);
    $this->travel(1)->minute();

    $small = projectionStatements(fn () => User::query()->find($player->id));

    expect((int) DB::table('website_users')->where('id', $player->id)->value('credits'))->toBe(9000)
        ->and(DB::table('website_users')->whereIn('id', $others->modelKeys())->pluck('credits')->unique()->all())->toBe([10])
        ->and(collect($small)->filter(fn (string $sql): bool => str_contains($sql, 'insert into `website_users`') && str_contains($sql, 'select'))->all())->toBe([]);

    User::factory()->count(30)->create();
    $this->travel(1)->minute();
    User::query()->count();
    $this->travel(1)->minute();

    expect(projectionStatements(fn () => User::query()->find($player->id)))->toHaveCount(count($small));
});

test('players registered by the emulator are projected for lists and counts', function () {
    $member = User::factory()->create();
    $this->travel(1)->minute();
    nativePlayer(700, 'EmulatorSignup', ['online' => true]);
    DB::table('users')->where('id', $member->id)->update(['online' => true]);

    expect(app(PlayerRepository::class)->whereOnline(User::query())->count())->toBe(2)
        ->and(DB::table('website_users')->where('id', 700)->value('username'))->toBe('EmulatorSignup');
});

test('an emulator account is projected even after a later CMS registration takes a higher id', function () {
    User::factory()->create();
    $this->travel(1)->minute();
    User::query()->count();
    nativePlayer(1700, 'EmulatorFirst', ['online' => true]);
    User::factory()->create(['id' => 1701]);
    $this->travel(1)->minute();

    expect(app(PlayerRepository::class)->whereOnline(User::query())->pluck('id')->all())->toBe([1700]);
});

test('deleting a native player removes its website row', function () {
    $player = User::factory()->create();

    DB::table('users')->where('id', $player->id)->delete();

    expect(DB::table('website_users')->where('id', $player->id)->exists())->toBeFalse()
        ->and(User::query()->find($player->id))->toBeNull();
});

test('a renamed player is found by the new name and the old name reaches its new holder', function () {
    $renamed = User::factory()->create(['username' => 'OldName']);
    DB::table('users')->where('id', $renamed->id)->update(['username' => 'NewName']);
    nativePlayer(800, 'OldName');

    expect(User::query()->where('username', 'NewName')->first()?->id)->toBe($renamed->id)
        ->and(User::query()->where('username', 'OldName')->first()?->id)->toBe(800)
        ->and(User::query()->where('username', 'like', 'Old%')->pluck('id')->all())->toBe([800]);
});

test('the registration ip limit counts accounts the emulator registered from that address', function () {
    installHotel();
    setSetting('max_accounts_per_ip', '1');
    User::factory()->create();
    nativePlayer(900, 'SameAddress', ['ip_reg' => '203.0.113.7', 'ip_last' => '198.51.100.1']);

    expect(fn () => app(CreateNewUser::class)->createWithIp([
        'username' => 'SecondAccount', 'mail' => 'second@example.test', 'password' => 'Sup3rSecret!',
        'password_confirmation' => 'Sup3rSecret!', 'terms' => 'on',
    ], '203.0.113.7'))->toThrow(ValidationException::class);
});

test('staff promoted inside the emulator are excluded from leaderboards', function () {
    setSetting('min_staff_rank', '4');
    $player = User::factory()->create();
    User::query()->find($player->id);
    DB::table('user_roles')->insert(['user_id' => $player->id, 'role_id' => 4, 'created_at' => now()]);

    expect(app(StaffService::class)->fetchEmployeeIds())->toBe([$player->id]);
});

test('an offline credit edit is written to the emulator and survives the next read', function () {
    $player = User::factory()->create(['credits' => 100]);

    $player->forceFill(['credits' => 4242])->save();

    expect((int) DB::table('users')->where('id', $player->id)->value('credits'))->toBe(4242)
        ->and((int) User::query()->find($player->id)->credits)->toBe(4242);
});

test('online friends come from the emulator, newest first', function () {
    $viewer = User::factory()->create();
    $friends = User::factory()->count(3)->create();
    foreach ($friends as $index => $friend) {
        DB::table('messenger_friendships')->insert(['user_one_id' => $viewer->id, 'user_two_id' => $friend->id]);
        DB::table('users')->where('id', $friend->id)->update(['online' => $index !== 1, 'last_online' => now()->subMinutes(10 - $index)]);
    }

    expect($viewer->getOnlineFriends()->pluck('id')->all())->toBe([$friends[2]->id, $friends[0]->id]);
});

test('housekeeping deletes a player and the emulator account together, or neither', function () {
    installHotel();
    grantHousekeepingPermission('can_access_housekeeping', 6);
    grantHousekeepingPermission('edit_user', 6);
    grantHousekeepingPermission('delete_user', 6);
    $this->actingAs(User::factory()->create(['rank' => 7]));
    Filament::setCurrentPanel(Filament::getPanel('housekeeping'));
    $deletable = User::factory()->create();
    app(CurrencyRepository::class)->give($deletable, CurrencyTypes::Duckets, 10);
    $paying = User::factory()->create();
    DB::table('website_paypal_transactions')->insert(['user_id' => $paying->id, 'transaction_id' => 'ORDER-1', 'amount' => 500]);
    $landlord = User::factory()->create();
    makePlusRoom($landlord->id);

    Livewire::test(EditUser::class, ['record' => $deletable->getRouteKey()])->callAction(DeleteAction::class);
    foreach ([$paying, $landlord] as $kept) {
        session()->forget(['filament.notifications', 'filament.claimed_notifications']);
        Livewire::test(EditUser::class, ['record' => $kept->getRouteKey()])->callAction(DeleteAction::class)
            ->assertNotified('This user still owns rooms or groups, or has payment records, and cannot be deleted. Transfer or remove those first.');
    }

    expect(DB::table('users')->where('id', $deletable->id)->exists())->toBeFalse()
        ->and(DB::table('website_users')->where('id', $deletable->id)->exists())->toBeFalse()
        ->and(DB::table('user_currencies')->where('user_id', $deletable->id)->exists())->toBeFalse();
    foreach ([$paying, $landlord] as $kept) {
        expect(DB::table('users')->where('id', $kept->id)->exists())->toBeTrue()
            ->and(DB::table('website_users')->where('id', $kept->id)->exists())->toBeTrue();
    }
});

test('the sync command refreshes every player', function () {
    $players = User::factory()->count(2)->create(['credits' => 1]);
    DB::table('users')->update(['credits' => 77]);

    $this->artisan('atom:sync-players')->expectsOutputToContain('Synchronized')->assertSuccessful();

    expect(DB::table('website_users')->whereIn('id', $players->modelKeys())->pluck('credits')->unique()->all())->toBe([77]);
});
