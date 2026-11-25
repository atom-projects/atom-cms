<?php

use App\Models\Miscellaneous\WebsiteInstallation;
use App\Models\Miscellaneous\WebsiteSetting;
use App\Services\InstallationService;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Testing\TestResponse;

/**
 * Puts the hotel into a fresh, not-yet-installed state. The test database
 * seeds a completed installation, so rewind it and drop every cache layer
 * that remembers completion.
 */
function startFreshInstallation(): WebsiteInstallation
{
    $installation = WebsiteInstallation::first();
    $installation->update([
        'step' => 0,
        'completed' => false,
        'installation_key' => 'test-install-key',
        'user_ip' => null,
    ]);

    resetInstallationCaches();

    return $installation->fresh();
}

/**
 * Feature tests reuse one app instance across requests, so both the cache
 * and the singleton's in-memory memo must be cleared between steps.
 */
function resetInstallationCaches(): void
{
    Cache::flush();
    app()->forgetInstance(InstallationService::class);
    app()->forgetInstance(SettingsService::class);
}

function enterInstallationKey(string $installationKey = 'test-install-key'): TestResponse
{
    return test()->post(route('installation.start-installation'), ['installation_key' => $installationKey]);
}

test('the wizard walks from key entry to the hotel without falling back', function () {
    installHotel();
    startFreshInstallation();

    // Step 0: an uninstalled hotel sends visitors to the wizard.
    $this->get('/')->assertRedirect(route('installation.index'));
    $this->get(route('installation.index'))->assertOk();

    // Enter the installation key.
    $this->post(route('installation.start-installation'), ['installation_key' => 'test-install-key'])
        ->assertRedirect(route('installation.show-step', 1));

    resetInstallationCaches();

    // Walk the four settings steps.
    foreach (range(1, 4) as $step) {
        $this->get(route('installation.show-step', $step))->assertOk();

        $this->post(route('installation.save-step'), ['hotel_name' => 'Testaria'])
            ->assertRedirect(route('installation.show-step', $step + 1));

        resetInstallationCaches();
    }

    expect(WebsiteInstallation::first()->step)->toBe(5);

    $this->get(route('installation.show-step', 5))->assertOk();

    // "Take me to the hotel".
    $this->post(route('installation.complete'))->assertRedirect(route('welcome'));

    resetInstallationCaches();

    expect((bool) WebsiteInstallation::first()->completed)->toBeTrue();

    // The hotel now serves visitors instead of bouncing them to the wizard.
    $this->get('/')->assertOk();
    $this->get(route('installation.index'))->assertRedirect(route('welcome'));
});

test('completion works even when the first-visit race left duplicate rows', function () {
    installHotel();
    $installation = startFreshInstallation();
    enterInstallationKey();

    // Concurrent first-ever requests each pass the "no row yet" check in the
    // middleware and create their own record - reproduce that aftermath.
    WebsiteInstallation::create([
        'step' => 0,
        'completed' => false,
        'installation_key' => 'straggler-key',
        'user_ip' => null,
    ]);
    WebsiteInstallation::whereKey(WebsiteInstallation::max('id'))->update(['created_at' => now()->addMinute()]);

    // The wizard progressed on the first row up to the final step.
    $installation->update(['step' => 5, 'user_ip' => '127.0.0.1']);
    resetInstallationCaches();

    // "Take me to the hotel".
    $this->post(route('installation.complete'))->assertRedirect(route('welcome'));

    resetInstallationCaches();

    // The hotel must open - not bounce back into the wizard.
    $this->get('/')->assertOk();
});

test('a wrong installation key is rejected', function () {
    installHotel();
    startFreshInstallation();

    $this->post(route('installation.start-installation'), ['installation_key' => 'wrong'])
        ->assertSessionHasErrors('installation_key');

    expect(WebsiteInstallation::first()->step)->toBe(0);
});

test('optional settings are not blocked by browser required validation', function () {
    installHotel();

    foreach (WebsiteSetting::OPTIONAL_INSTALLATION_KEYS as $key) {
        $setting = WebsiteSetting::query()->where('key', $key)->firstOrFail();
        $content = view('installation.partials.setting-input', [
            'setting' => $setting,
            'errors' => new ViewErrorBag,
        ])->render();
        preg_match('/<input\b[^>]*\bname="' . preg_quote($key, '/') . '"[^>]*>/i', $content, $matches);

        expect($matches)->toHaveCount(1)
            ->and($matches[0])->not->toContain(' required');
    }

    $setting = WebsiteSetting::query()->where('key', 'hotel_name')->firstOrFail();
    $content = view('installation.partials.setting-input', [
        'setting' => $setting,
        'errors' => new ViewErrorBag,
    ])->render();
    preg_match('/<input\b[^>]*\bname="hotel_name"[^>]*>/i', $content, $matches);

    expect($matches)->toHaveCount(1)
        ->and($matches[0])->toContain(' required');
});

test('every setting without a default value is optional during installation', function () {
    $settingsWithoutDefault = WebsiteSetting::query()->where('value', '')->get();

    expect($settingsWithoutDefault)->not->toBeEmpty();

    foreach ($settingsWithoutDefault as $setting) {
        expect($setting->isRequiredDuringInstallation())->toBeFalse("{$setting->key} has no default value");
    }
});

test('visiting a later step than reached redirects back to the current step', function () {
    installHotel();
    $installation = startFreshInstallation();
    $installation->update(['step' => 2, 'user_ip' => '127.0.0.1']);
    resetInstallationCaches();

    $this->get(route('installation.show-step', 4))
        ->assertRedirect(route('installation.show-step', 2));
});

test('another visitor cannot hijack an installation in progress', function () {
    installHotel();
    $installation = startFreshInstallation();
    $installation->update(['step' => 3, 'user_ip' => '203.0.113.9']);
    resetInstallationCaches();

    $this->get(route('installation.show-step', 3))->assertForbidden();
});

test('a step past the end of the wizard shows the last step instead of an exception', function () {
    installHotel();
    $installation = startFreshInstallation();
    enterInstallationKey();

    // Older builds let a resubmitted form increment the step past the end,
    // and every later request then died on "Step does not exist".
    WebsiteInstallation::query()->update(['step' => 9, 'user_ip' => '127.0.0.1']);
    resetInstallationCaches();

    expect(WebsiteInstallation::first()->step)->toBe(WebsiteInstallation::LAST_STEP);

    $this->get(route('installation.show-step', WebsiteInstallation::LAST_STEP))->assertOk();
    $this->get(route('installation.show-step', 9))
        ->assertRedirect(route('installation.show-step', WebsiteInstallation::LAST_STEP));

    $installation->refresh();
});

test('saving past the last step stays on it rather than running off the end', function () {
    installHotel();
    startFreshInstallation();
    enterInstallationKey();
    WebsiteInstallation::query()->update(['step' => WebsiteInstallation::LAST_STEP, 'user_ip' => '127.0.0.1']);
    resetInstallationCaches();

    $this->post(route('installation.save-step'))
        ->assertRedirect(route('installation.show-step', WebsiteInstallation::LAST_STEP));

    expect(WebsiteInstallation::first()->step)->toBe(WebsiteInstallation::LAST_STEP);
});

test('going back from the first step stays on it', function () {
    installHotel();
    startFreshInstallation();
    enterInstallationKey();
    WebsiteInstallation::query()->update(['step' => 1, 'user_ip' => '127.0.0.1']);
    resetInstallationCaches();

    $this->post(route('installation.previous-step'))
        ->assertRedirect(route('installation.show-step', 1));

    expect(WebsiteInstallation::first()->step)->toBe(1);
});

test('unknown installation steps are bounced back to the current step', function () {
    installHotel();
    $installation = startFreshInstallation();
    $installation->update(['step' => 1, 'user_ip' => '127.0.0.1']);
    resetInstallationCaches();

    // The global installation middleware bounces both before routing; the
    // route's whereNumber constraint and the controller's 404 guard remain
    // as defense in depth should the middleware ever let one through.
    $this->get(route('installation.show-step', 99))
        ->assertRedirect(route('installation.show-step', 1));

    $this->get('/installation/step/not-a-step')
        ->assertRedirect(route('installation.show-step', 1));
});

test('saving a step only writes the settings that belong to it', function () {
    installHotel();
    $installation = startFreshInstallation();
    enterInstallationKey();
    $installation->update(['step' => 1, 'user_ip' => '127.0.0.1']);
    resetInstallationCaches();

    $keys = WebsiteSetting::query()->orderBy('id')->pluck('key');
    $chunkSize = max(1, (int) ceil($keys->count() / WebsiteInstallation::SETTING_STEPS));
    $chunks = $keys->chunk($chunkSize)->values();

    $stepOneKey = $chunks->get(0)->first();
    $laterStepKey = $chunks->get(3)->last();
    $laterStepValue = WebsiteSetting::where('key', $laterStepKey)->value('value');

    $this->post(route('installation.save-step'), [
        $stepOneKey => 'legit step one value',
        $laterStepKey => 'smuggled value',
        'not_a_real_setting' => 'ignored',
        'installation_key' => ['array' => 'values are not settings'],
    ])->assertRedirect(route('installation.show-step', 2));

    expect(WebsiteSetting::where('key', $stepOneKey)->value('value'))->toBe('legit step one value')
        ->and(WebsiteSetting::where('key', $laterStepKey)->value('value'))->toBe($laterStepValue);
});

test('a visitor without the key cannot complete the installation', function () {
    installHotel();
    startFreshInstallation();

    $this->post(route('installation.complete'))->assertRedirect(route('installation.index'));

    resetInstallationCaches();

    expect(WebsiteInstallation::where('completed', true)->exists())->toBeFalse();
    $this->get('/')->assertRedirect(route('installation.index'));
});

test('a visitor without the key cannot save a step', function () {
    installHotel();
    startFreshInstallation();
    $hotelName = WebsiteSetting::where('key', 'hotel_name')->value('value');

    $this->post(route('installation.save-step'), ['hotel_name' => 'Hijacked'])
        ->assertRedirect(route('installation.index'));

    expect(WebsiteInstallation::first()->step)->toBe(0)
        ->and(WebsiteSetting::where('key', 'hotel_name')->value('value'))->toBe($hotelName);
});

test('a visitor without the key cannot go to the previous step', function () {
    installHotel();
    startFreshInstallation();

    $this->post(route('installation.previous-step'))->assertRedirect(route('installation.index'));

    expect(WebsiteInstallation::first()->step)->toBe(0);
});

test('a visitor without the key cannot restart the installation and rotate the key', function () {
    installHotel();
    startFreshInstallation();

    $this->post(route('installation.restart'))->assertRedirect(route('installation.index'));

    expect(WebsiteInstallation::first()->installation_key)->toBe('test-install-key');

    $this->post(route('installation.start-installation'), ['installation_key' => 'test-install-key'])
        ->assertRedirect(route('installation.show-step', 1));
});

test('the first visitor is not bound as the installer before the key is accepted', function () {
    WebsiteInstallation::query()->delete();
    resetInstallationCaches();

    $this->get(route('installation.index'))->assertOk();

    $installation = WebsiteInstallation::sole();
    expect($installation->user_ip)->toBeNull();

    $this->post(route('installation.start-installation'), ['installation_key' => $installation->installation_key])
        ->assertRedirect(route('installation.show-step', 1));

    expect(WebsiteInstallation::sole()->user_ip)->toBe('127.0.0.1');
});

test('after entering the key the owner can step back and restart', function () {
    installHotel();
    startFreshInstallation();

    $this->post(route('installation.start-installation'), ['installation_key' => 'test-install-key'])
        ->assertRedirect(route('installation.show-step', 1));
    $this->post(route('installation.save-step'), ['hotel_name' => 'Testaria'])
        ->assertRedirect(route('installation.show-step', 2));
    $this->post(route('installation.previous-step'))
        ->assertRedirect(route('installation.show-step', 1));
    $this->post(route('installation.restart'))
        ->assertRedirect(route('installation.index'));

    $installation = WebsiteInstallation::first();
    expect($installation->step)->toBe(0)
        ->and($installation->installation_key)->not->toBe('test-install-key')
        ->and($installation->user_ip)->toBeNull();
});

test('after the key step only the session that entered the key can use the wizard', function (string $method, string $route, array $parameters, array $server) {
    config(['habbo.trusted_proxies' => '*']);
    installHotel();
    startFreshInstallation();

    enterInstallationKey()->assertRedirect(route('installation.show-step', 1));
    $this->post(route('installation.save-step'), ['hotel_name' => 'Testaria'])
        ->assertRedirect(route('installation.show-step', 2));
    $settings = WebsiteSetting::query()->pluck('value', 'key')->all();

    $this->flushSession();
    $this->call($method, route($route, $parameters), ['hotel_name' => 'Hijacked'], [], [], $server)
        ->assertRedirect(route('installation.index'));

    resetInstallationCaches();

    $installation = WebsiteInstallation::first();
    expect(WebsiteInstallation::where('completed', true)->exists())->toBeFalse()
        ->and($installation->step)->toBe(2)
        ->and($installation->installation_key)->toBe('test-install-key')
        ->and(WebsiteSetting::query()->pluck('value', 'key')->all())->toBe($settings);
})->with([
    'view the current step' => ['GET', 'installation.show-step', ['step' => 2]],
    'save a step' => ['POST', 'installation.save-step', []],
    'go back a step' => ['POST', 'installation.previous-step', []],
    'restart the installation' => ['POST', 'installation.restart', []],
    'complete the installation' => ['POST', 'installation.complete', []],
])->with([
    'from the owner address' => [[]],
    'with a forged forwarded owner address' => [['REMOTE_ADDR' => '198.51.100.7', 'HTTP_X_FORWARDED_FOR' => '127.0.0.1']],
]);

test('restarting the installation ends every earlier key acceptance', function () {
    installHotel();
    startFreshInstallation();

    enterInstallationKey()->assertRedirect(route('installation.show-step', 1));
    $this->post(route('installation.restart'))->assertRedirect(route('installation.index'));
    $staleSession = session()->all();

    $newKey = WebsiteInstallation::first()->installation_key;
    $this->flushSession();
    enterInstallationKey($newKey)->assertRedirect(route('installation.show-step', 1));

    $this->flushSession();
    $this->withSession($staleSession);
    $this->get(route('installation.show-step', 1))->assertRedirect(route('installation.index'));
    $this->post(route('installation.save-step'), ['hotel_name' => 'Hijacked'])
        ->assertRedirect(route('installation.index'));

    expect(WebsiteInstallation::first()->step)->toBe(1);

    enterInstallationKey($newKey)->assertRedirect(route('installation.show-step', 1));
    $this->get(route('installation.show-step', 1))->assertOk();
});

test('an owner whose session ended enters the key again and continues at the same step', function () {
    installHotel();
    startFreshInstallation();

    enterInstallationKey()->assertRedirect(route('installation.show-step', 1));
    $this->post(route('installation.save-step'), ['hotel_name' => 'Testaria'])
        ->assertRedirect(route('installation.show-step', 2));

    $this->flushSession();
    $this->get(route('installation.show-step', 2))->assertRedirect(route('installation.index'));
    $this->get(route('installation.index'))->assertOk();

    enterInstallationKey()->assertRedirect(route('installation.show-step', 2));
    $this->get(route('installation.show-step', 2))->assertOk();
    $this->get(route('installation.index'))->assertRedirect(route('installation.show-step', 2));
});

test('an installation without a step cannot be completed without the key', function () {
    installHotel();
    startFreshInstallation();
    WebsiteInstallation::query()->update(['step' => null]);
    resetInstallationCaches();

    $this->post(route('installation.complete'))->assertRedirect(route('installation.index'));

    expect(WebsiteInstallation::where('completed', true)->exists())->toBeFalse();
});
