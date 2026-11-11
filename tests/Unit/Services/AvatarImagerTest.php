<?php

use App\Services\Avatar\AvatarImager;

const AVATAR_SECRET = 'avatar-secret-avatar-secret-avatar-secret';
const AVATAR_FIGURE = 'hd-180-1.hr-100-61.ch-210-66.lg-270-82.sh-290-80';
const AVATAR_OFFICIAL = 'https://www.habbo.com/habbo-imaging/avatarimage?figure=';

function selfHostedImager(int $version = 1, string $baseUrl = '/avatars'): AvatarImager
{
    return new AvatarImager(AVATAR_SECRET, $baseUrl, $version, AVATAR_OFFICIAL, 'hd-180-1.ch-210-66.lg-270-82.sh-290-80');
}

function officialImager(string $url = AVATAR_OFFICIAL): AvatarImager
{
    return new AvatarImager(null, '/avatars', 1, $url, 'hd-180-1.ch-210-66.lg-270-82.sh-290-80');
}

function selfHostedVariant(array $options, ?string $gender = null): string
{
    $url = selfHostedImager()->url(AVATAR_FIGURE, $options, $gender);

    return substr($url, strlen('/avatars/v1/' . AVATAR_FIGURE . '/6356236d790847bb.'), -strlen('.png'));
}

it('signs a figure the way the renderer checks it', function () {
    expect(selfHostedImager()->url(AVATAR_FIGURE, ['direction' => 2, 'head_direction' => 3, 'gesture' => 'sml', 'action' => 'wav']))
        ->toBe('/avatars/v1/' . AVATAR_FIGURE . '/6356236d790847bb.m-m-full-2-3-std-wave-sml-0-0-0-still.png')
        ->and(selfHostedImager(7)->url('hd-600-10.ch-665-71'))
        ->toBe('/avatars/v7/hd-600-10.ch-665-71/e2beff07265f7b3c.m-m-full-2-2-std-none-nrm-0-0-0-still.png');
});

it('builds the signature from the version and the figure', function () {
    $signature = substr(hash_hmac('sha256', 'v3/hd-190-2', AVATAR_SECRET), 0, 16);

    expect(selfHostedImager(3)->prefix('hd-190-2'))->toBe("/avatars/v3/hd-190-2/{$signature}");
});

it('maps sizes, genders, postures and actions to the variant', function () {
    expect(selfHostedVariant(['size' => 's']))->toBe('m-s-full-2-2-std-none-nrm-0-0-0-still')
        ->and(selfHostedVariant(['size' => 'l']))->toBe('m-l-full-2-2-std-none-nrm-0-0-0-still')
        ->and(selfHostedVariant(['size' => 'b']))->toBe('m-m-full-2-2-std-none-nrm-0-0-0-still')
        ->and(selfHostedVariant([], 'F'))->toBe('f-m-full-2-2-std-none-nrm-0-0-0-still')
        ->and(selfHostedVariant(['direction' => 4]))->toBe('m-m-full-4-4-std-none-nrm-0-0-0-still')
        ->and(selfHostedVariant(['direction' => 4, 'head_direction' => 3]))->toBe('m-m-full-4-3-std-none-nrm-0-0-0-still')
        ->and(selfHostedVariant(['action' => 'sit']))->toBe('m-m-full-2-2-sit-none-nrm-0-0-0-still')
        ->and(selfHostedVariant(['action' => 'lay']))->toBe('m-m-full-2-2-lay-none-nrm-0-0-0-still')
        ->and(selfHostedVariant(['action' => 'wlk']))->toBe('m-m-full-2-2-wlk-none-nrm-0-0-0-still')
        ->and(selfHostedVariant(['action' => 'kiss']))->toBe('m-m-full-2-2-std-kiss-nrm-0-0-0-still')
        ->and(selfHostedVariant(['action' => 'laugh']))->toBe('m-m-full-2-2-std-laugh-nrm-0-0-0-still')
        ->and(selfHostedVariant(['dance' => 3, 'effect' => 102, 'item' => 2, 'animated' => true]))->toBe('m-m-full-2-2-std-none-nrm-3-102-2-anim');
});

it('gives a head one spelling', function () {
    expect(selfHostedVariant(['headonly' => true, 'direction' => 2, 'head_direction' => 3, 'gesture' => 'sml', 'action' => 'wav', 'dance' => 2, 'effect' => 4, 'item' => 1, 'animated' => true, 'size' => 's']))
        ->toBe('m-s-head-3-3-std-none-sml-0-0-0-still')
        ->and(selfHostedVariant(['headonly' => 1, 'direction' => 4]))->toBe('m-m-head-4-4-std-none-nrm-0-0-0-still');
});

it('falls back to the default for a value the renderer does not know', function () {
    expect(selfHostedVariant(['direction' => 9, 'head_direction' => -1, 'gesture' => 'grin', 'action' => 'fly', 'size' => 'x', 'dance' => 9, 'effect' => -4, 'item' => 100000], 'X'))
        ->toBe('m-m-full-2-2-std-none-nrm-0-0-0-still');
});

it('cleans the figure and uses the default figure when there is none', function () {
    $imager = selfHostedImager();

    expect($imager->prefix('/HD-180-1.CH-210-66.'))->toBe($imager->prefix('hd-180-1.ch-210-66'))
        ->and($imager->prefix('hd-180-1..ch-210-66 '))->toBe($imager->prefix('hd-180-1.ch-210-66'))
        ->and($imager->prefix('hd-180-1.ch-210-<script>'))->toBe($imager->prefix('hd-180-1.ch-210-script'))
        ->and($imager->prefix(''))->toBe($imager->prefix('hd-180-1.ch-210-66.lg-270-82.sh-290-80'))
        ->and($imager->prefix(null))->toBe($imager->prefix('hd-180-1.ch-210-66.lg-270-82.sh-290-80'))
        ->and($imager->prefix(str_repeat('hd-180-1.', 40)))->toBe($imager->prefix('hd-180-1.ch-210-66.lg-270-82.sh-290-80'));
});

it('accepts a base address on another host', function () {
    expect(selfHostedImager(1, 'https://cdn.example.com/avatars/')->prefix('hd-190-2'))
        ->toStartWith('https://cdn.example.com/avatars/v1/hd-190-2/');
});

it('keeps the official imager address when no secret is set', function () {
    $imager = officialImager();

    expect($imager->isSelfHosted())->toBeFalse()
        ->and(selfHostedImager()->isSelfHosted())->toBeTrue()
        ->and($imager->prefix(AVATAR_FIGURE))->toBeNull()
        ->and($imager->url('hd-180-1.ch-210-66'))->toBe(AVATAR_OFFICIAL . 'hd-180-1.ch-210-66')
        ->and($imager->url('hd-180-1.ch-210-66', ['direction' => 2, 'head_direction' => 3, 'gesture' => 'sml', 'action' => 'wav', 'size' => 'l', 'headonly' => true]))
        ->toBe(AVATAR_OFFICIAL . 'hd-180-1.ch-210-66&direction=2&head_direction=3&gesture=sml&action=wav&size=l&headonly=1')
        ->and($imager->url('hd-180-1.ch-210-66', ['dance' => 2, 'effect' => 4, 'animated' => true], 'F'))
        ->toBe(AVATAR_OFFICIAL . 'hd-180-1.ch-210-66&dance=2&effect=4&gender=F&img_format=gif')
        ->and($imager->url(null, ['headonly' => true]))->toBe(AVATAR_OFFICIAL . '&headonly=1')
        ->and($imager->url('hd 180"1'))->toBe(AVATAR_OFFICIAL . 'hd%20180%221');
});

it('knows when images have the official sizes', function () {
    expect(selfHostedImager()->usesOfficialSizes())->toBeTrue()
        ->and(officialImager()->usesOfficialSizes())->toBeTrue()
        ->and(officialImager('https://imager.example.com/?figure=')->usesOfficialSizes())->toBeFalse();
});
