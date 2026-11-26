<?php

use App\Actions\Shop\PurchaseShopPackage;
use App\Exceptions\ShopPurchaseException;
use App\Filament\Resources\Shop\WebsiteShopItems\Pages\CreateWebsiteShopItem;
use App\Models\Shop\WebsiteShopItem;
use App\Models\Shop\WebsiteShopPackage;
use App\Models\Shop\WebsiteShopPurchase;
use App\Models\User;
use App\Models\WebsiteHousekeepingPermission;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

/**
 * The roles a fresh PlusEMU install ships with, by id, slug, weight, security
 * level and staff flag.
 */
function seedStockPlusRoles(): void
{
    $roles = [
        [1, 'default', 0, 1, false],
        [2, 'trial_moderator', 20, 2, true],
        [3, 'moderator', 30, 3, true],
        [4, 'senior_moderator', 40, 4, true],
        [5, 'administrator', 50, 5, true],
        [6, 'manager', 60, 6, true],
        [7, 'community_leader', 70, 7, true],
        [8, 'developer', 80, 7, true],
        [9, 'owner', 90, 7, true],
        [11, 'vip', 10, 1, false],
        [12, 'gold_vip', 10, 1, false],
        [13, 'events_staff', 10, 1, false],
    ];

    DB::table('roles')->insert(array_map(fn (array $role): array => [
        'id' => $role[0], 'slug' => $role[1], 'name' => ucwords(str_replace('_', ' ', $role[1])), 'description' => '',
        'weight' => $role[2], 'security_level' => $role[3], 'badge_code' => '', 'is_staff' => $role[4], 'is_hidden' => false,
        'created_at' => now(), 'updated_at' => now(),
    ], $roles));

    setSetting('min_staff_rank', '4');
    grantHousekeepingPermission('can_access_housekeeping', 6);
}

test('a rank package gifted to a higher ranked player leaves their rank and roles unchanged', function () {
    installHotel();
    seedPlusRoles();
    DB::table('roles')->insert(['id' => 2, 'slug' => 'vip', 'name' => 'VIP', 'description' => '', 'weight' => 200, 'security_level' => 2, 'badge_code' => '', 'is_staff' => false, 'is_hidden' => false, 'created_at' => now(), 'updated_at' => now()]);

    $buyer = User::factory()->create(['website_balance' => 10000, 'rank' => 1]);
    $owner = User::factory()->create(['rank' => 7]);
    $package = WebsiteShopPackage::create(['name' => 'VIP Bundle', 'price' => 500, 'is_giftable' => true]);
    $item = WebsiteShopItem::create(['name' => 'VIP Rank', 'type' => 'rank', 'type_value' => '2', 'is_active' => true]);
    $package->items()->attach($item->id, ['quantity' => 1]);
    $roles = fn (): array => DB::table('user_roles')->where('user_id', $owner->id)->pluck('role_id')->sort()->values()->all();

    expect($roles())->toBe([9]);

    expect(fn () => app(PurchaseShopPackage::class)->execute($buyer, $package, $owner->username))
        ->toThrow(ShopPurchaseException::class, 'The account that receives this package already has this rank or a higher one');

    expect((int) $owner->refresh()->rank)->toBe(7)
        ->and($roles())->toBe([9])
        ->and((int) $buyer->refresh()->website_balance)->toBe(10000)
        ->and(WebsiteShopPurchase::count())->toBe(0);
});

test('on a stock Plus hotel a rank item never gives a moderator role', function () {
    installHotel();
    seedStockPlusRoles();

    $buyer = User::factory()->create(['website_balance' => 10000, 'rank' => 1]);
    $package = WebsiteShopPackage::create(['name' => 'VIP Membership', 'price' => 500]);
    $item = WebsiteShopItem::create(['name' => 'VIP Rank', 'type' => 'rank', 'type_value' => '2', 'is_active' => true]);
    $package->items()->attach($item->id, ['quantity' => 1]);

    expect(fn () => app(PurchaseShopPackage::class)->execute($buyer, $package, null))
        ->toThrow(ShopPurchaseException::class, 'This package is currently unavailable');

    expect((int) $buyer->refresh()->rank)->toBe(1)
        ->and(DB::table('user_roles')->where('user_id', $buyer->id)->pluck('role_id')->all())->toBe([1])
        ->and((int) $buyer->website_balance)->toBe(10000)
        ->and(WebsiteShopPurchase::count())->toBe(0);
});

test('on a stock Plus hotel the shop item form refuses a rank that gives a moderator role', function (string $rank) {
    installHotel();
    seedStockPlusRoles();
    setSetting('force_staff_2fa', '0');
    WebsiteHousekeepingPermission::query()->updateOrCreate(['permission' => 'manage_shop'], ['min_rank' => 7, 'description' => '']);
    $this->actingAs(User::factory()->create(['rank' => 7]));
    Filament::setCurrentPanel(Filament::getPanel('housekeeping'));

    Livewire::test(CreateWebsiteShopItem::class)
        ->fillForm(['name' => 'VIP Rank', 'type' => 'rank', 'type_value' => $rank])
        ->call('create')
        ->assertHasFormErrors(['type_value']);

    expect(WebsiteShopItem::query()->count())->toBe(0);
})->with([
    'trial moderator' => '2',
    'moderator' => '3',
]);
