<?php

use App\Filament\Resources\Hotel\WebsiteAds\Pages\CreateWebsiteAd;
use App\Models\User;
use App\Models\WebsiteAd;
use App\Models\WebsiteHousekeepingPermission;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    installHotel();
    setSetting('force_staff_2fa', '0');
    grantHousekeepingPermission('can_access_housekeeping', 6);
    WebsiteHousekeepingPermission::query()->updateOrCreate(['permission' => 'manage_website_ads'], ['min_rank' => 6, 'description' => '']);
    $this->actingAs(User::factory()->create(['rank' => 6]));
    Filament::setCurrentPanel(Filament::getPanel('housekeeping'));
    Storage::fake('ads');
});

test('an ad upload is stored with the extension of its detected image type', function () {
    // The server reads the type from the content, so a GIF header reports image/gif whatever the name says.
    $file = UploadedFile::fake()
        ->createWithContent('banner.html', 'GIF89a<script>alert(document.domain)</script>')
        ->mimeType('image/gif');

    Livewire::test(CreateWebsiteAd::class)
        ->fillForm(['image' => [$file]])
        ->call('create')
        ->assertHasNoFormErrors();

    $ad = WebsiteAd::query()->sole();

    expect(pathinfo($ad->image, PATHINFO_EXTENSION))->toBe('gif')
        ->and(Storage::disk('ads')->allFiles())->toBe([$ad->image]);
});
