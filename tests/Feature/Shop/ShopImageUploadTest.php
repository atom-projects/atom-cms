<?php

use App\Filament\Resources\Shop\WebsiteShopItems\Pages\CreateWebsiteShopItem;
use App\Filament\Resources\Shop\WebsiteShopPackages\Pages\CreateWebsiteShopPackage;
use App\Models\Shop\WebsiteShopItem;
use App\Models\Shop\WebsiteShopPackage;
use App\Models\User;
use App\Models\WebsiteHousekeepingPermission;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function scriptedSvg(): UploadedFile
{
    return UploadedFile::fake()->createWithContent(
        'icon.svg',
        '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(document.domain)</script></svg>',
    );
}

beforeEach(function () {
    installHotel();
    setSetting('force_staff_2fa', '0');
    grantHousekeepingPermission('can_access_housekeeping', 6);
    WebsiteHousekeepingPermission::query()->updateOrCreate(['permission' => 'manage_shop'], ['min_rank' => 7, 'description' => '']);
    $this->actingAs(User::factory()->create(['rank' => 7]));
    Filament::setCurrentPanel(Filament::getPanel('housekeeping'));
    Storage::fake('public');
});

test('a shop item image refuses an svg file', function () {
    Livewire::test(CreateWebsiteShopItem::class)
        ->fillForm([
            'name' => '100 Credits',
            'type' => 'currency',
            'type_value' => 'credits:100',
            'image' => [scriptedSvg()],
        ])
        ->call('create')
        ->assertHasFormErrors(['image']);

    expect(WebsiteShopItem::query()->count())->toBe(0)
        ->and(Storage::disk('public')->allFiles())->toBe([]);
});

test('a shop package image refuses an svg file', function () {
    Livewire::test(CreateWebsiteShopPackage::class)
        ->fillForm([
            'name' => 'Starter Bundle',
            'price' => 500,
            'image' => [scriptedSvg()],
        ])
        ->call('create')
        ->assertHasFormErrors(['image']);

    expect(WebsiteShopPackage::query()->count())->toBe(0)
        ->and(Storage::disk('public')->allFiles())->toBe([]);
});

test('a shop item image still accepts a png file', function () {
    Livewire::test(CreateWebsiteShopItem::class)
        ->fillForm([
            'name' => '100 Credits',
            'type' => 'currency',
            'type_value' => 'credits:100',
            'image' => [UploadedFile::fake()->image('credits.png', 40, 40)],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $item = WebsiteShopItem::query()->sole();

    expect($item->image)->toStartWith('shop-items/')
        ->and(Storage::disk('public')->exists((string) $item->image))->toBeTrue();
});
