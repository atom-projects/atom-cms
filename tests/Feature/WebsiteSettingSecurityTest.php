<?php

use App\Filament\Resources\Atom\CmsSettings\Pages\ManageCmsSettings;
use App\Models\Miscellaneous\WebsiteInstallation;
use App\Models\Miscellaneous\WebsiteSetting;
use App\Models\User;
use App\Models\WebsiteHousekeepingPermission;
use App\Services\Badge\FlashExternalTexts;
use App\Services\HousekeepingPermissionsService;
use App\Services\InstallationService;
use App\Services\SettingsService;
use App\Support\PermissionRanks;
use Database\Seeders\WebsiteSettingsSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

const SENSITIVE_SETTINGS_PERMISSION = 'manage_sensitive_website_settings';

/**
 * Rank 6 is the lowest staff rank and manages ordinary settings. Only rank 7
 * may change the settings that decide where tickets, files and RCON go.
 */
function settingsStaff(int $rank): User
{
    installHotel();

    foreach (['can_access_housekeeping' => 6, 'manage_website_settings' => 6, 'delete_website_settings' => 6, SENSITIVE_SETTINGS_PERMISSION => 7] as $permission => $minRank) {
        WebsiteHousekeepingPermission::query()->updateOrCreate(
            ['permission' => $permission],
            ['min_rank' => $minRank, 'description' => "Testing {$permission}"],
        );
    }

    app(HousekeepingPermissionsService::class)->refresh();
    Filament::setCurrentPanel(Filament::getPanel('housekeeping'));

    $staff = User::factory()->create(['rank' => $rank]);
    test()->actingAs($staff);

    return $staff;
}

function settingRecord(string $key): WebsiteSetting
{
    return WebsiteSetting::query()->where('key', $key)->firstOrFail();
}

function editSetting(string $key, string $value): mixed
{
    $record = settingRecord($key);

    return Livewire::test(ManageCmsSettings::class)
        ->callAction(TestAction::make('edit')->table($record), data: ['key' => $key, 'value' => $value, 'comment' => 'Testing']);
}

function writeSettingDirectly(string $key, string $value): void
{
    DB::table('website_settings')->where('key', $key)->update(['value' => $value]);

    app(SettingsService::class)->refresh();
}

function outsideHotelFile(): string
{
    $path = storage_path('framework/outside-hotel-files.txt');
    file_put_contents($path, "landing.view.me=Welcome back\n");

    return $path;
}

test('a nitro path on another origin is refused when saved', function (string $value) {
    settingsStaff(7);
    $before = settingRecord('nitro_path')->value;

    editSetting('nitro_path', $value)->assertHasActionErrors(['value']);

    expect(settingRecord('nitro_path')->value)->toBe($before);
})->with([
    'another origin' => 'https://attacker.example/c',
    'scheme relative' => '//attacker.example/c',
    'backslash' => '/\\attacker.example/c',
    'javascript' => 'javascript:alert(1)',
]);

test('a nitro path on this site or an allowed origin is accepted', function (string $value) {
    settingsStaff(7);
    config(['habbo.client.nitro_allowed_origins' => ['https://cdn.example.test']]);

    editSetting('nitro_path', $value)->assertHasNoActionErrors();

    expect(settingRecord('nitro_path')->value)->toBe($value);
})->with([
    'site path' => '/client/nitro/nitro-react/dist',
    'installer value' => fn () => rtrim((string) config('app.url'), '/') . '/client',
    'allowed origin' => 'https://cdn.example.test/nitro',
]);

test('a bad nitro path already in the table never sends the ticket to that origin', function () {
    installHotel();
    writeSettingDirectly('nitro_path', 'https://attacker.example/c');
    $player = User::factory()->create();

    $this->actingAs($player)
        ->get(route('nitro-client'))
        ->assertServiceUnavailable()
        ->assertDontSee('attacker.example');

    expect((string) $player->fresh()->auth_ticket)->toBe((string) $player->auth_ticket);
});

test('the launch api gives no nitro url for a bad nitro path already in the table', function () {
    installHotel();
    config(['atom.mode' => 'headless']);
    setSetting('findretros_enabled', '0');
    writeSettingDirectly('nitro_path', 'https://attacker.example/c');

    $this->actingAs(User::factory()->create())
        ->postJson('/api/v1/client/launch', ['client' => 'nitro'])
        ->assertServiceUnavailable()
        ->assertDontSee('attacker.example');
});

test('the client page only lets its frame load this site and the allowed origins', function () {
    installHotel();
    config(['habbo.client.nitro_allowed_origins' => ['https://cdn.example.test']]);
    setSetting('nitro_path', 'https://cdn.example.test/nitro');

    $response = $this->actingAs(User::factory()->create())
        ->get(route('nitro-client'))
        ->assertOk()
        ->assertSee('src="https://cdn.example.test/nitro/index.html?sso=', false);

    expect($response->headers->get('Content-Security-Policy'))
        ->toContain("frame-src 'self' ")
        ->toContain('https://cdn.example.test');
});

test('a file path setting outside the hotel file roots is refused when saved', function (string $key, Closure $value) {
    settingsStaff(7);
    $before = settingRecord($key)->value;

    editSetting($key, $value())->assertHasActionErrors(['value']);

    expect(settingRecord($key)->value)->toBe($before);
})->with([
    'flash texts outside the roots' => ['flash_external_texts_file', fn () => outsideHotelFile()],
    'flash texts through a parent segment' => ['flash_external_texts_file', fn () => storage_path('framework/testing/../outside-hotel-files.txt')],
    'nitro texts outside the roots' => ['nitro_external_texts_file', fn () => base_path('composer.json')],
    'relative nitro texts' => ['nitro_external_texts_file', fn () => 'storage/app/ExternalTexts.json'],
    'badge folder outside the roots' => ['badge_path_filesystem', fn () => base_path('public')],
    'ads folder outside the roots' => ['ads_path_filesystem', fn () => '/etc'],
]);

test('a flash texts path already in the table outside the roots is never written', function () {
    installHotel();
    $outside = outsideHotelFile();
    writeSettingDirectly('flash_external_texts_file', $outside);

    expect(fn () => app(FlashExternalTexts::class)->add('EVIL01', 'Title', 'Description'))
        ->toThrow(RuntimeException::class)
        ->and(file_get_contents($outside))->toBe("landing.view.me=Welcome back\n");
});

test('a flash texts path that links out of the roots is never written', function () {
    installHotel();
    $outside = outsideHotelFile();
    $link = storage_path('framework/testing/flash-texts-link.txt');
    @unlink($link);
    @mkdir(dirname($link), 0777, true);
    symlink($outside, $link);
    writeSettingDirectly('flash_external_texts_file', $link);

    expect(fn () => app(FlashExternalTexts::class)->add('EVIL02', 'Title', 'Description'))
        ->toThrow(RuntimeException::class)
        ->and(file_get_contents($outside))->toBe("landing.view.me=Welcome back\n");

    unlink($link);
});

test('a link setting must be a site path or an https url', function (string $key, string $value) {
    settingsStaff(6);
    $before = settingRecord($key)->value;

    editSetting($key, $value)->assertHasActionErrors(['value']);

    expect(settingRecord($key)->value)->toBe($before);
})->with([
    'housekeeping script' => ['housekeeping_url', 'javascript:alert(document.cookie)'],
    'housekeeping plain http' => ['housekeeping_url', 'http://phish.example/housekeeping'],
    'discord script' => ['discord_invitation_link', 'javascript:alert(1)'],
    'discord data url' => ['discord_invitation_link', 'data:text/html,<script>alert(1)</script>'],
    'logo breaking out of the attribute' => ['cms_logo', '/logo.png" onerror="alert(1)'],
    'header breaking out of css url' => ['cms_header', '/header.png);background:url(https://track.example/x'],
]);

test('a link setting accepts site paths and https urls', function (string $key, string $value) {
    settingsStaff(6);

    editSetting($key, $value)->assertHasNoActionErrors();

    expect(settingRecord($key)->value)->toBe($value);
})->with([
    ['housekeeping_url', '/housekeeping'],
    ['housekeeping_url', 'https://housekeeping.example.test/admin'],
    ['discord_invitation_link', 'https://discord.gg/rX3aShUHdg'],
    ['avatar_imager', 'https://www.habbo.com/habbo-imaging/avatarimage?figure='],
]);

test('the lowest staff rank cannot change a sensitive setting', function (string $key) {
    $staff = settingsStaff(6);
    $record = settingRecord($key);

    Livewire::test(ManageCmsSettings::class)
        ->assertActionHidden(TestAction::make('edit')->table($record))
        ->assertActionHidden(TestAction::make('delete')->table($record));

    expect($staff->can('update', $record))->toBeFalse()
        ->and($staff->can('delete', $record))->toBeFalse();
})->with([
    'nitro path' => 'nitro_path',
    'flash texts' => 'flash_external_texts_file',
    'nitro texts' => 'nitro_external_texts_file',
    'badge folder' => 'badge_path_filesystem',
    'ads folder' => 'ads_path_filesystem',
    'rcon host' => 'rcon_ip',
    'rcon port' => 'rcon_port',
    'forced two-factor' => 'force_staff_2fa',
    'staff rank' => 'min_staff_rank',
]);

test('the lowest staff rank can still change ordinary settings', function () {
    settingsStaff(6);

    editSetting('hotel_name', 'Testaria')->assertHasNoActionErrors();

    expect(settingRecord('hotel_name')->value)->toBe('Testaria');
});

test('the lowest staff rank cannot create a sensitive setting', function () {
    settingsStaff(6);
    settingRecord('rcon_ip')->delete();

    Livewire::test(ManageCmsSettings::class)
        ->callAction('create', data: ['key' => 'rcon_ip', 'value' => '10.0.0.5', 'comment' => 'Testing'])
        ->assertHasActionErrors(['key']);

    expect(WebsiteSetting::query()->where('key', 'rcon_ip')->exists())->toBeFalse();
});

test('the lowest staff rank cannot rename an ordinary setting into a sensitive one', function () {
    settingsStaff(6);
    settingRecord('rcon_ip')->delete();
    $record = WebsiteSetting::query()->create(['key' => 'custom_note', 'value' => '10.0.0.5', 'comment' => '']);

    Livewire::test(ManageCmsSettings::class)
        ->callAction(TestAction::make('edit')->table($record), data: ['key' => 'rcon_ip', 'value' => '10.0.0.5', 'comment' => 'Testing'])
        ->assertHasActionErrors(['key']);

    expect(WebsiteSetting::query()->where('key', 'rcon_ip')->exists())->toBeFalse()
        ->and($record->fresh()->key)->toBe('custom_note');
});

test('the top rank can change sensitive settings to valid values', function (string $key, string $value) {
    settingsStaff(7);

    editSetting($key, $value)->assertHasNoActionErrors();

    expect(settingRecord($key)->value)->toBe($value);
})->with([
    ['nitro_path', '/client/nitro'],
    ['rcon_ip', '10.0.0.5'],
    ['rcon_ip', 'emulator.internal'],
    ['rcon_port', '30001'],
    ['min_staff_rank', '5'],
]);

test('rcon, two-factor, rank and count settings must have the right type and range', function (string $key, string $value) {
    settingsStaff(7);
    $before = settingRecord($key)->value;

    editSetting($key, $value)->assertHasActionErrors(['value']);

    expect(settingRecord($key)->value)->toBe($before);
})->with([
    ['rcon_ip', 'not a host!'],
    ['rcon_ip', 'tcp://10.0.0.5'],
    ['rcon_port', '0'],
    ['rcon_port', '70000'],
    ['rcon_port', '30001abc'],
    ['force_staff_2fa', 'yes'],
    ['force_staff_2fa', '2'],
    ['min_staff_rank', 'staff'],
    ['min_staff_rank', '0'],
    ['max_accounts_per_ip', '-1'],
    ['max_accounts_per_ip', 'lots'],
]);

test('the theme must be one of the theme folders', function () {
    settingsStaff(6);

    editSetting('theme', '../../vendor')->assertHasActionErrors(['value']);
    expect(settingRecord('theme')->value)->not->toBe('../../vendor');

    editSetting('theme', 'atom')->assertHasNoActionErrors();
    expect(settingRecord('theme')->value)->toBe('atom');
});

test('the installer refuses a bad value for a setting on its step', function () {
    installHotel();
    $keys = WebsiteSetting::query()->orderBy('id')->pluck('key')->all();
    $chunkSize = max(1, (int) ceil(count($keys) / WebsiteInstallation::SETTING_STEPS));
    $step = intdiv((int) array_search('nitro_path', $keys, true), $chunkSize) + 1;
    $before = settingRecord('nitro_path')->value;

    WebsiteInstallation::query()->first()->update(['step' => $step, 'completed' => false, 'installation_key' => 'test-install-key', 'user_ip' => '127.0.0.1']);
    Cache::flush();
    app()->forgetInstance(InstallationService::class);
    app()->forgetInstance(SettingsService::class);

    $this->post(route('installation.save-step'), ['nitro_path' => 'https://attacker.example/c'])
        ->assertSessionHasErrors('nitro_path');

    expect(settingRecord('nitro_path')->value)->toBe($before);
});

test('every seeded default and installer value passes validation', function () {
    installHotel();

    foreach (WebsiteSetting::query()->get() as $setting) {
        $setting->save();
    }

    WebsiteSetting::query()->delete();
    $this->seed(WebsiteSettingsSeeder::class);

    $site = rtrim((string) config('app.url'), '/');
    setSetting('nitro_path', $site . '/client');
    setSetting('rcon_ip', '127.0.0.1');
    setSetting('rcon_port', '30001');
    setSetting('badges_path', $site . '/hotel-files/c_images/album1584');
    setSetting('furniture_icons_path', $site . '/hotel-files/c_images/hof_furni/icons');

    expect(WebsiteSetting::query()->count())->toBeGreaterThan(50)
        ->and(settingRecord('nitro_path')->value)->toBe($site . '/client');
});

test('existing hotels receive the sensitive settings permission for the top rank', function () {
    $permission = WebsiteHousekeepingPermission::query()->where('permission', SENSITIVE_SETTINGS_PERMISSION)->first();

    expect($permission)->not->toBeNull()
        ->and($permission->min_rank)->toBe(PermissionRanks::ceiling());
});
