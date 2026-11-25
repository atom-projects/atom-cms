<?php

use App\Filament\Resources\Shop\WebsiteShopItems\Pages\CreateWebsiteShopItem;
use App\Filament\Resources\Shop\WebsiteShopItems\Pages\EditWebsiteShopItem;
use App\Models\Shop\WebsiteShopItem;
use App\Models\User;
use App\Models\WebsiteHousekeepingPermission;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    installHotel();
    setSetting('force_staff_2fa', '0');
    setSetting('min_staff_rank', '4');
    grantHousekeepingPermission('can_access_housekeeping', 6);
    WebsiteHousekeepingPermission::query()->updateOrCreate(['permission' => 'manage_shop'], ['min_rank' => 7, 'description' => '']);
    $this->actingAs(User::factory()->create(['rank' => 7]));
    Filament::setCurrentPanel(Filament::getPanel('housekeeping'));
});

test('a rank item below the staff rank can be created', function () {
    Livewire::test(CreateWebsiteShopItem::class)
        ->fillForm(['name' => 'VIP Rank', 'type' => 'rank', 'type_value' => '2'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(WebsiteShopItem::query()->sole()->type_value)->toBe('2');
});

test('a rank item is refused at creation unless it is an existing rank below the staff rank', function (string $rank) {
    Livewire::test(CreateWebsiteShopItem::class)
        ->fillForm(['name' => 'Rank', 'type' => 'rank', 'type_value' => $rank])
        ->call('create')
        ->assertHasFormErrors(['type_value']);

    expect(WebsiteShopItem::query()->count())->toBe(0);
})->with([
    'the staff rank' => '4',
    'the top rank' => '7',
    'a rank that does not exist' => '99',
    'not a number' => 'vip',
]);

test('a rank item is refused at creation when its rank opens housekeeping', function () {
    setSetting('min_staff_rank', '7');
    WebsiteHousekeepingPermission::query()->where('permission', 'can_access_housekeeping')->update(['min_rank' => 5]);

    Livewire::test(CreateWebsiteShopItem::class)
        ->fillForm(['name' => 'Rank', 'type' => 'rank', 'type_value' => '5'])
        ->call('create')
        ->assertHasFormErrors(['type_value']);

    expect(WebsiteShopItem::query()->count())->toBe(0);
});

test('a rank item cannot be edited to a staff rank', function () {
    $item = WebsiteShopItem::create(['name' => 'VIP Rank', 'type' => 'rank', 'type_value' => '2', 'is_active' => true]);

    Livewire::test(EditWebsiteShopItem::class, ['record' => $item->getRouteKey()])
        ->fillForm(['type_value' => '7'])
        ->call('save')
        ->assertHasFormErrors(['type_value']);

    expect($item->refresh()->type_value)->toBe('2');
});

test('other item types keep their own values', function () {
    Livewire::test(CreateWebsiteShopItem::class)
        ->fillForm(['name' => 'Big Credits', 'type' => 'currency', 'type_value' => 'credits:5000'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(WebsiteShopItem::query()->sole()->type_value)->toBe('credits:5000');
});
