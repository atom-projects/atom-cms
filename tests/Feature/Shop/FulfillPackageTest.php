<?php

use App\Actions\Shop\FulfillPackage;
use App\Emulator\Contracts\BadgeRepository;
use App\Exceptions\ShopPurchaseException;
use App\Models\Game\Furniture\Item;
use App\Models\Shop\WebsiteShopItem;
use App\Models\Shop\WebsiteShopPackage;
use App\Models\User;

function packageWithItem(string $type, string $typeValue, int $quantity = 1): WebsiteShopPackage
{
    $package = WebsiteShopPackage::create([
        'name' => "Package {$type}",
        'price' => 100,
    ]);

    $item = WebsiteShopItem::create([
        'name' => "Item {$type}",
        'type' => $type,
        'type_value' => $typeValue,
        'is_active' => true,
    ]);

    $package->items()->attach($item->id, ['quantity' => $quantity]);

    return $package;
}

test('a currency item credits the amount times the quantity', function () {
    $user = User::factory()->create();
    $startCredits = (int) $user->credits;
    $package = packageWithItem('currency', 'credits:50', quantity: 2);

    app(FulfillPackage::class)->execute($user, $package);

    expect((int) $user->refresh()->credits)->toBe($startCredits + 100);
});

test('a furniture item grants one inventory row per unit', function () {
    $user = User::factory()->create();
    $package = packageWithItem('furniture', '230', quantity: 3);

    app(FulfillPackage::class)->execute($user, $package);

    expect(Item::where('user_id', $user->id)->where('item_id', 230)->count())->toBe(3);
});

test('a badge item grants every listed badge code', function () {
    $user = User::factory()->create();
    $package = packageWithItem('badge', 'TSTA;TSTB');

    app(FulfillPackage::class)->execute($user, $package);

    expect(app(BadgeRepository::class)->codes($user))->toContain('TSTA', 'TSTB');
});

test('a rank item promotes the user', function () {
    $user = User::factory()->create(['rank' => 1]);
    $package = packageWithItem('rank', '3');

    app(FulfillPackage::class)->execute($user, $package);

    expect((int) $user->refresh()->rank)->toBe(3);
});

test('a rank item never lowers or keeps the rank of its receiver', function (int $rank) {
    $user = User::factory()->create(['rank' => $rank]);
    $package = packageWithItem('rank', '2');

    expect(fn () => app(FulfillPackage::class)->execute($user, $package))
        ->toThrow(ShopPurchaseException::class, 'The account that receives this package already has this rank or a higher one');

    expect((int) $user->refresh()->rank)->toBe($rank);
})->with([
    'the same rank' => 2,
    'a higher rank' => 7,
]);

test('a rank item never grants a staff rank', function (string $staffRank, int $housekeepingRank, string $itemRank) {
    setSetting('min_staff_rank', $staffRank);
    grantHousekeepingPermission('can_access_housekeeping', $housekeepingRank);
    $user = User::factory()->create(['rank' => 1]);
    $package = packageWithItem('rank', $itemRank);

    expect(fn () => app(FulfillPackage::class)->execute($user, $package))
        ->toThrow(ShopPurchaseException::class, 'This package is currently unavailable');

    expect((int) $user->refresh()->rank)->toBe(1);
})->with([
    'the staff rank' => ['4', 6, '4'],
    'the top rank' => ['4', 6, '7'],
    'the housekeeping rank below the staff rank' => ['7', 5, '5'],
]);
