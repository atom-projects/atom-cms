<?php

use App\Models\User;

const AVATAR_TEST_SECRET = 'avatar-test-secret-with-at-least-32-characters';
const AVATAR_TEST_LOOK = 'hd-180-1.ch-210-66.lg-270-82.sh-290-80.hr-828-61';

beforeEach(function () {
    installHotel();
    config([
        'habbo.avatar_imager.secret' => AVATAR_TEST_SECRET,
        'habbo.avatar_imager.url' => '/avatars',
        'habbo.avatar_imager.version' => 1,
    ]);
});

function signedAvatar(string $figure, string $variant, int $version = 1, string $base = 'http://localhost/avatars'): string
{
    $signature = substr(hash_hmac('sha256', "v{$version}/{$figure}/{$variant}", AVATAR_TEST_SECRET), 0, 16);

    return "{$base}/v{$version}/{$figure}/{$signature}.{$variant}.png";
}

function avatarVariantFor(string $query, string $look = AVATAR_TEST_LOOK): string
{
    $location = test()->get('/api/avatar-image?figure=' . $look . $query)->assertRedirect()->headers->get('Location');

    expect($location)->toMatch('#/avatars/v1/[a-z0-9.-]+/[a-f0-9]{16}\.[a-z0-9-]+\.png$#');

    return explode('.', basename((string) $location), 3)[1];
}

test('is not found while the hotel draws no avatar images', function () {
    config(['habbo.avatar_imager.secret' => null]);
    User::factory()->create(['look' => AVATAR_TEST_LOOK]);

    $this->get('/api/avatar-image?figure=' . AVATAR_TEST_LOOK)->assertNotFound();
});

test('redirects a user look to its signed image with the Habbo imager defaults', function () {
    User::factory()->create(['look' => AVATAR_TEST_LOOK, 'gender' => 'M']);

    $response = $this->get('/api/avatar-image?figure=' . AVATAR_TEST_LOOK);

    $response->assertRedirect('http://localhost/avatars/v1/' . AVATAR_TEST_LOOK . '/f027f812b6aa636c.m-m-full-2-2-std-none-nrm-0-0-0-still.png');
    expect($response->headers->get('Cache-Control'))->toContain('public')->toContain('max-age=86400');
});

test('uses the gender of the user who wears the look', function () {
    User::factory()->create(['look' => AVATAR_TEST_LOOK, 'gender' => 'F']);

    expect(avatarVariantFor(''))->toBe('f-m-full-2-2-std-none-nrm-0-0-0-still');
});

test('maps the Habbo imager parameters to the variant', function (string $query, string $variant) {
    User::factory()->create(['look' => AVATAR_TEST_LOOK, 'gender' => 'M']);

    expect(avatarVariantFor($query))->toBe($variant);
})->with([
    'site default pose' => ['&direction=2&head_direction=3&gesture=sml&size=m', 'm-m-full-2-3-std-none-sml-0-0-0-still'],
    'small' => ['&size=s', 'm-s-full-2-2-std-none-nrm-0-0-0-still'],
    'large' => ['&size=l', 'm-l-full-2-2-std-none-nrm-0-0-0-still'],
    'size b is normal' => ['&size=b', 'm-m-full-2-2-std-none-nrm-0-0-0-still'],
    'unknown size is normal' => ['&size=n', 'm-m-full-2-2-std-none-nrm-0-0-0-still'],
    'head keeps its own default' => ['&direction=4', 'm-m-full-4-4-std-none-nrm-0-0-0-still'],
    'directions wrap at eight' => ['&direction=10&head_direction=9', 'm-m-full-2-1-std-none-nrm-0-0-0-still'],
    'invalid directions use the default' => ['&direction=-1&head_direction=abc', 'm-m-full-2-2-std-none-nrm-0-0-0-still'],
    'turned body mirrors head 1' => ['&direction=4&head_direction=1', 'm-m-full-4-5-std-none-nrm-0-0-0-still'],
    'turned body mirrors head 0' => ['&direction=5&head_direction=0', 'm-m-full-5-6-std-none-nrm-0-0-0-still'],
    'turned body mirrors head 2' => ['&direction=6&head_direction=2', 'm-m-full-6-4-std-none-nrm-0-0-0-still'],
    'turned body keeps head 3' => ['&direction=4&head_direction=3', 'm-m-full-4-3-std-none-nrm-0-0-0-still'],
    'gesture' => ['&gesture=agr', 'm-m-full-2-2-std-none-agr-0-0-0-still'],
    'unknown gesture' => ['&gesture=xyz', 'm-m-full-2-2-std-none-nrm-0-0-0-still'],
    'sit' => ['&action=sit', 'm-m-full-2-2-sit-none-nrm-0-0-0-still'],
    'lay' => ['&action=lay', 'm-m-full-2-2-lay-none-nrm-0-0-0-still'],
    'walk' => ['&action=wlk', 'm-m-full-2-2-wlk-none-nrm-0-0-0-anim'],
    'wave' => ['&action=wav', 'm-m-full-2-2-std-wave-nrm-0-0-0-anim'],
    'actions ignore case' => ['&action=WAV', 'm-m-full-2-2-std-wave-nrm-0-0-0-still'],
    'blow a kiss' => ['&action=blw', 'm-m-full-2-2-std-kiss-nrm-0-0-0-still'],
    'laugh' => ['&action=lgh', 'm-m-full-2-2-std-laugh-nrm-0-0-0-still'],
    'carry an item' => ['&action=crr=1', 'm-m-full-2-2-std-none-nrm-0-0-1-still'],
    'walk and wave' => ['&action=wlk,wav', 'm-m-full-2-2-wlk-wave-nrm-0-0-0-anim'],
    'unknown actions are ignored' => ['&action=sml,drk=1,dance=1', 'm-m-full-2-2-std-none-nrm-0-0-0-still'],
    'head' => ['&headonly=1', 'm-m-head-2-2-std-none-nrm-0-0-0-still'],
    'head looks one way and ignores the body direction' => ['&headonly=1&direction=4&head_direction=3', 'm-m-head-3-3-std-none-nrm-0-0-0-still'],
    'head keeps the gesture and size' => ['&headonly=1&gesture=sml&size=s', 'm-s-head-2-2-std-none-sml-0-0-0-still'],
    'head ignores the posture and item' => ['&headonly=1&action=sit,crr=1', 'm-m-head-2-2-std-none-nrm-0-0-0-still'],
    'a wave shows the whole body' => ['&headonly=1&action=wav&size=s', 'm-s-full-2-2-std-wave-nrm-0-0-0-anim'],
    'walking shows the whole body' => ['&headonly=1&action=wlk', 'm-m-full-2-2-wlk-none-nrm-0-0-0-anim'],
    'an explicit frame keeps a wave still' => ['&action=wav&frame=0', 'm-m-full-2-2-std-wave-nrm-0-0-0-still'],
    'an explicit frame keeps a head cropped' => ['&headonly=1&action=wav&frame=0', 'm-m-head-2-2-std-none-nrm-0-0-0-still'],
    'a later wave leaves a head still' => ['&headonly=1&action=sit,wav', 'm-m-head-2-2-std-none-nrm-0-0-0-still'],
    'a leading wave animates with an item' => ['&headonly=1&action=wav,crr=1', 'm-m-full-2-2-std-wave-nrm-0-0-1-anim'],
    'a wave with an item stays a head' => ['&headonly=1&action=crr=1,wav', 'm-m-head-2-2-std-none-nrm-0-0-0-still'],
]);

test('draws the starting look for a figure no user wears', function () {
    setSetting('start_look', 'hr-100-61.hd-180-1.ch-210-66.lg-270-110.sh-305-62');

    $this->get('/api/avatar-image?figure=hd-99999-1&headonly=1')
        ->assertRedirect(signedAvatar('hr-100-61.hd-180-1.ch-210-66.lg-270-110.sh-305-62', 'm-m-head-2-2-std-none-nrm-0-0-0-still'));
});

test('draws the starting look when the figure is missing', function () {
    setSetting('start_look', 'hr-100-61.hd-180-1.ch-210-66.lg-270-110.sh-305-62');

    $this->get('/api/avatar-image')
        ->assertRedirect(signedAvatar('hr-100-61.hd-180-1.ch-210-66.lg-270-110.sh-305-62', 'm-m-full-2-2-std-none-nrm-0-0-0-still'));
});

test('draws the starting look for a user look the imager cannot read', function () {
    setSetting('start_look', 'hr-100-61.hd-180-1.ch-210-66.lg-270-110.sh-305-62');
    $look = str_repeat('hd-180-1.', 27) . 'hr-1-1';
    User::factory()->create(['look' => $look]);

    $this->get('/api/avatar-image?figure=' . $look)
        ->assertRedirect(signedAvatar('hr-100-61.hd-180-1.ch-210-66.lg-270-110.sh-305-62', 'm-m-full-2-2-std-none-nrm-0-0-0-still'));
});

test('signs the configured address and version', function () {
    config(['habbo.avatar_imager.url' => 'https://images.example/avatars/', 'habbo.avatar_imager.version' => 3]);
    User::factory()->create(['look' => AVATAR_TEST_LOOK, 'gender' => 'M']);

    $this->get('/api/avatar-image?figure=' . AVATAR_TEST_LOOK)
        ->assertRedirect(signedAvatar(AVATAR_TEST_LOOK, 'm-m-full-2-2-std-none-nrm-0-0-0-still', 3, 'https://images.example/avatars'));
});

test('serves every avatar of a busy page without the API rate limit', function () {
    User::factory()->create(['look' => AVATAR_TEST_LOOK]);

    foreach (range(1, 80) as $request) {
        $this->get('/api/avatar-image?figure=' . AVATAR_TEST_LOOK . '&direction=' . ($request % 8))->assertRedirect();
    }
});

test('keeps the configured renderer origin when the request host changes', function () {
    config(['app.url' => 'https://hotel.example']);

    $this->get('https://other.example/api/avatar-image')
        ->assertRedirectContains('https://hotel.example/avatars/');
});

test('uses a drawable default when the starting look is invalid', function () {
    setSetting('start_look', 'invalid/figure');

    $this->get('/api/avatar-image')->assertRedirect(signedAvatar(
        'hr-100-61.hd-180-1.ch-210-66.lg-270-110.sh-305-62',
        'm-m-full-2-2-std-none-nrm-0-0-0-still',
    ));
});

test('does not cache a missing look while a user can still adopt it', function () {
    $response = $this->get('/api/avatar-image?figure=' . AVATAR_TEST_LOOK)->assertRedirect();

    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});

test('requires a byte-exact match for the worn look', function () {
    User::factory()->create(['look' => strtoupper(AVATAR_TEST_LOOK), 'gender' => 'F']);
    setSetting('start_look', 'hd-180-1');

    $this->get('/api/avatar-image?figure=' . AVATAR_TEST_LOOK)
        ->assertRedirect(signedAvatar('hd-180-1', 'm-m-full-2-2-std-none-nrm-0-0-0-still'));
});

test('disables signing for settings the renderer rejects', function (string $key, mixed $value) {
    config(["habbo.avatar_imager.{$key}" => $value]);

    $this->get('/api/avatar-image')->assertNotFound();
})->with([['secret', 'too-short'], ['version', 0], ['version', 1000000]]);

test('limits avatar requests separately from the API', function () {
    $this->freezeTime();

    foreach (range(1, 300) as $request) {
        $this->get('/api/avatar-image')->assertRedirect();
    }

    $this->get('/api/avatar-image')->assertTooManyRequests();
});
