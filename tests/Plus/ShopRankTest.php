<?php

use App\Actions\Shop\PurchaseShopPackage;
use App\Exceptions\ShopPurchaseException;
use App\Models\Shop\WebsiteShopItem;
use App\Models\Shop\WebsiteShopPackage;
use App\Models\Shop\WebsiteShopPurchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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
