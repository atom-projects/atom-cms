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
use Illuminate\Support\Facades\Schema;
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

test('atom users live on the plus users row with no second copy', function () {
    $user = User::factory()->create(['ip_register' => '203.0.113.1', 'ip_current' => '203.0.113.2', 'last_login' => 1700000000]);
    $user->forceFill(['hidden_staff' => true, 'website_balance' => 250])->save();
    $native = DB::table('users')->where('id', $user->id)->first();

    expect(Schema::hasTable('website_users'))->toBeFalse()
        ->and($user->getTable())->toBe('users')
        ->and($native->ip_reg)->toBe('203.0.113.1')
        ->and($native->ip_last)->toBe('203.0.113.2')
        ->and((int) $native->last_login)->toBe(1700000000)
        ->and((bool) $native->hidden_staff)->toBeTrue()
        ->and((int) $native->website_balance)->toBe(250)
        ->and($native->referral_code)->toBeNull();
});

test('a loaded player speaks atom attribute names over plus columns and tables', function () {
    $user = User::factory()->create(['ip_register' => '203.0.113.1']);
    DB::table('users')->where('id', $user->id)->update(['account_created' => '2026-01-02 03:04:05', 'last_online' => null]);
    DB::table('user_roles')->insert(['user_id' => $user->id, 'role_id' => 4, 'created_at' => now()]);
    DB::table('users_settings')->where('user_id', $user->id)->update(['home_room' => 0]);
    app(CurrencyRepository::class)->give($user, CurrencyTypes::Points, 9);

    $loaded = User::query()->findOrFail($user->id);

    expect($loaded->ip_register)->toBe('203.0.113.1')
        ->and($loaded->account_created)->toBe(strtotime('2026-01-02 03:04:05 UTC'))
        ->and($loaded->last_online)->toBe(0)
        ->and($loaded->rank)->toBe(4)
        ->and($loaded->native_role_id)->toBe(4)
        ->and($loaded->points)->toBe(9)
        ->and($loaded->home_room)->toBe(0)
        ->and($loaded->toArray())->not->toHaveKeys(['ip_reg', 'ip_last', 'credential_generation', 'auth_ticket_session']);
});

test('filters sorts selections and plucks on atom names reach the plus schema', function () {
    $staff = User::factory()->create(['ip_register' => '198.51.100.9']);
    $member = User::factory()->create(['ip_register' => '198.51.100.9']);
    DB::table('user_roles')->insert(['user_id' => $staff->id, 'role_id' => 7, 'created_at' => now()]);
    app(CurrencyRepository::class)->give($member, CurrencyTypes::Duckets, 40);

    expect(User::query()->where('ip_register', '198.51.100.9')->orderByDesc('pixels')->pluck('id')->all())->toBe([$member->id, $staff->id])
        ->and(User::query()->whereKey([$staff->id, $member->id])->where('rank', '>=', 4)->pluck('id')->all())->toBe([$staff->id])
        ->and(User::query()->whereKey($staff->id)->pluck('rank')->all())->toBe([7])
        ->and(User::query()->whereKey($member->id)->pluck('ip_register')->all())->toBe(['198.51.100.9'])
        ->and(User::query()->select(['username', 'rank', 'ip_current'])->findOrFail($staff->id)->only(['rank', 'ip_current']))->toBe(['rank' => 7, 'ip_current' => '127.0.0.1'])
        ->and(app(StaffService::class)->fetchEmployeeIds())->toBe([$staff->id]);
});

test('writes to attributes plus keeps elsewhere go through its tables in the same save', function () {
    $owner = User::factory()->create();
    $room = makePlusRoom($owner->id);
    $user = User::factory()->create(['credits' => 100]);

    $user->forceFill(['credits' => 4242, 'pixels' => 15, 'home_room' => $room, 'rank' => 4, 'ip_current' => '192.0.2.4'])->save();
    $native = DB::table('users')->where('id', $user->id)->first();

    expect((int) $native->credits)->toBe(4242)
        ->and($native->ip_last)->toBe('192.0.2.4')
        ->and((int) $native->rank)->toBe(4)
        ->and(app(CurrencyRepository::class)->balance($user, CurrencyTypes::Duckets))->toBe(15)
        ->and(DB::table('users_settings')->where('user_id', $user->id)->value('home_room'))->toBe($room)
        ->and(DB::table('user_roles')->where('user_id', $user->id)->pluck('role_id')->sort()->values()->all())->toBe([1, 4])
        ->and($user->fresh()->only(['credits', 'pixels', 'home_room', 'rank']))->toBe(['credits' => 4242, 'pixels' => 15, 'home_room' => $room, 'rank' => 4]);
});

test('registration writes one users row and the plus rows it needs', function () {
    installHotel();
    $before = DB::table('users')->count();

    $user = app(CreateNewUser::class)->createWithIp([
        'username' => 'SingleRow', 'mail' => 'single@example.test', 'password' => 'Sup3rSecret!',
        'password_confirmation' => 'Sup3rSecret!', 'terms' => 'on',
    ], '203.0.113.20');

    expect(DB::table('users')->count())->toBe($before + 1)
        ->and(DB::table('users')->where('id', $user->id)->value('referral_code'))->toStartWith((string) $user->id)
        ->and(DB::table('users_settings')->where('user_id', $user->id)->exists())->toBeTrue()
        ->and(DB::table('user_statistics')->where('id', $user->id)->exists())->toBeTrue()
        ->and(DB::table('user_roles')->where('user_id', $user->id)->value('role_id'))->toBe(1);
});

test('the registration ip limit counts accounts the emulator registered from that address', function () {
    installHotel();
    setSetting('max_accounts_per_ip', '1');
    DB::table('users')->insert(['username' => 'SameAddress', 'mail' => 'same@example.test', 'ip_reg' => '203.0.113.7', 'ip_last' => '198.51.100.1']);

    expect(fn () => app(CreateNewUser::class)->createWithIp([
        'username' => 'SecondAccount', 'mail' => 'second@example.test', 'password' => 'Sup3rSecret!',
        'password_confirmation' => 'Sup3rSecret!', 'terms' => 'on',
    ], '203.0.113.7'))->toThrow(ValidationException::class);
});

test('online friends come from plus, newest first', function () {
    $viewer = User::factory()->create();
    $friends = User::factory()->count(3)->create();
    foreach ($friends as $index => $friend) {
        DB::table('messenger_friendships')->insert(['user_one_id' => $viewer->id, 'user_two_id' => $friend->id]);
        DB::table('users')->where('id', $friend->id)->update(['online' => $index !== 1, 'last_online' => now()->subMinutes(10 - $index)]);
    }

    expect($viewer->getOnlineFriends()->pluck('id')->all())->toBe([$friends[2]->id, $friends[0]->id])
        ->and(app(PlayerRepository::class)->whereOnline(User::query())->count())->toBe(2);
});

test('housekeeping deletes a player, or refuses while plus keeps rows that reference them', function () {
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
        ->and(DB::table('user_currencies')->where('user_id', $deletable->id)->exists())->toBeFalse()
        ->and(DB::table('users')->whereIn('id', [$paying->id, $landlord->id])->count())->toBe(2);
});
