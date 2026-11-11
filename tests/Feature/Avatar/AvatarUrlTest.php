<?php

use App\Models\User;
use App\Services\Avatar\AvatarImager;

beforeEach(function () {
    installHotel();
    setSetting('avatar_imager', 'https://imager.test/?figure=');
});

function useOwnAvatarImager(): void
{
    config(['habbo.avatar_imager.secret' => 'avatar-secret-avatar-secret-avatar-secret']);
    app()->forgetScopedInstances();
}

it('uses the imager from the website settings by default', function () {
    $user = User::factory()->create(['look' => 'hd-180-1.ch-210-66', 'gender' => 'F']);

    expect(avatar_url($user, ['headonly' => true]))->toBe('https://imager.test/?figure=hd-180-1.ch-210-66&headonly=1&gender=F')
        ->and(avatar_url('hd-190-2', ['size' => 'l']))->toBe('https://imager.test/?figure=hd-190-2&size=l')
        ->and(avatar_url(null))->toBe('https://imager.test/?figure=');
});

it('signs avatar addresses when the hotel has its own imager', function () {
    useOwnAvatarImager();
    $user = User::factory()->create(['look' => 'hd-600-10.ch-665-71', 'gender' => 'F']);
    $signature = substr(hash_hmac('sha256', 'v1/hd-600-10.ch-665-71', 'avatar-secret-avatar-secret-avatar-secret'), 0, 16);

    expect(avatar_url($user, ['action' => 'wav']))->toBe("/avatars/v1/hd-600-10.ch-665-71/{$signature}.f-m-full-2-2-std-wave-nrm-0-0-0-still.png")
        ->and(avatar_url((object) ['look' => 'hd-600-10.ch-665-71']))->toBe("/avatars/v1/hd-600-10.ch-665-71/{$signature}.m-m-full-2-2-std-none-nrm-0-0-0-still.png")
        ->and(avatar_url(User::query()->select(['id', 'look'])->find($user->id)))->toEndWith('.m-m-full-2-2-std-none-nrm-0-0-0-still.png');
});

it('draws every image again after the version setting is raised', function () {
    useOwnAvatarImager();
    setSetting('avatar_imager_version', '4');
    app()->forgetScopedInstances();

    expect(avatar_url('hd-190-2'))->toStartWith('/avatars/v4/hd-190-2/')
        ->and(app(AvatarImager::class)->prefix('hd-190-2'))->toBe('/avatars/v4/hd-190-2/' . substr(hash_hmac('sha256', 'v4/hd-190-2', 'avatar-secret-avatar-secret-avatar-secret'), 0, 16));
});

it('returns signed addresses from the hotel API', function () {
    useOwnAvatarImager();
    $user = User::factory()->create(['username' => 'SignedAvatarUser', 'look' => 'hd-190-2']);
    $prefix = '/avatars/v1/hd-190-2/' . substr(hash_hmac('sha256', 'v1/hd-190-2', 'avatar-secret-avatar-secret-avatar-secret'), 0, 16);

    $this->getJson('/api/user/' . $user->username)->assertOk()->assertJsonPath('data.avatar_url', $prefix . '.m-m-full-4-3-std-wave-nrm-0-0-0-still.png');
    $this->getJson('/api/users/search?q=SignedAvatar')->assertOk()->assertJsonPath('0.avatar_url', $prefix . '.m-s-head-3-3-std-none-sml-0-0-0-still.png');
});
