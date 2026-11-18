<?php

use App\Models\Miscellaneous\WebsiteMaintenanceTask;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;

const AVATAR_TEST_SECRET = 'avatar-secret-avatar-secret-avatar-secret';

function signedPrefix(string $look, string $base = '/avatars'): string
{
    return $base . "/v1/{$look}/" . substr(hash_hmac('sha256', "v1/{$look}", AVATAR_TEST_SECRET), 0, 16);
}

function useSignedAvatars(?string $imagerUrl = null): void
{
    config(['habbo.avatar_imager.secret' => AVATAR_TEST_SECRET]);
    if ($imagerUrl !== null) {
        config(['habbo.avatar_imager.url' => $imagerUrl]);
    }
    app()->forgetScopedInstances();
}

function expectNullAvatar(TestResponse $response, string $path): void
{
    expect(Arr::has($response->assertOk()->json(), $path))->toBeTrue()->and(Arr::get($response->json(), $path))->toBeNull();
}

beforeEach(function () {
    installHotel();
    config(['atom.mode' => 'headless']);
    setSetting('avatar_imager', 'https://imager.test/?figure=');
    $this->member = User::factory()->create(['rank' => 1, 'look' => 'hd-190-2.ch-210-66', 'online' => '1']);
});

test('every user projection carries a null avatar when the hotel has no imager of its own', function () {
    WebsiteMaintenanceTask::create(['user_id' => $this->member->id, 'task' => 'Task', 'completed' => false]);
    $this->actingAs($this->member);

    expectNullAvatar($this->getJson('/api/v1/users/' . $this->member->username), 'data.avatar');
    expectNullAvatar($this->getJson('/api/v1/users/online'), 'data.0.avatar');
    expectNullAvatar($this->getJson('/api/v1/users?q=' . $this->member->username), 'data.0.avatar');
    expectNullAvatar($this->getJson('/api/v1/me'), 'data.avatar');
    setSetting('maintenance_enabled', '1');
    expectNullAvatar($this->getJson('/api/v1/status'), 'data.tasks.items.0.user.avatar');
});

test('user projections carry the signed prefix as an absolute address', function () {
    useSignedAvatars();
    WebsiteMaintenanceTask::create(['user_id' => $this->member->id, 'task' => 'Task', 'completed' => false]);
    $friend = User::factory()->create(['look' => 'hd-600-10.ch-665-71', 'online' => '1']);
    DB::table('messenger_friendships')->insert(['user_one_id' => $this->member->id, 'user_two_id' => $friend->id, 'relation' => 0, 'friends_since' => time(), 'category' => 0]);
    $expected = 'http://localhost' . signedPrefix('hd-190-2.ch-210-66');
    $this->actingAs($this->member);

    $this->getJson('/api/v1/users/' . $this->member->username)->assertOk()->assertJsonPath('data.avatar', $expected);
    $this->getJson('/api/v1/users/online')->assertOk()->assertJsonFragment(['avatar' => $expected]);
    $this->getJson('/api/v1/users?q=' . $this->member->username)->assertOk()->assertJsonPath('data.0.avatar', $expected);
    $this->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.avatar', $expected)
        ->assertJsonPath('data.online_friends.0.avatar', 'http://localhost' . signedPrefix('hd-600-10.ch-665-71'));
    setSetting('maintenance_enabled', '1');
    $this->getJson('/api/v1/status')->assertOk()->assertJsonPath('data.tasks.items.0.user.avatar', $expected);
});

test('an absolute imager address stays as it is', function () {
    useSignedAvatars('https://images.hotel.test/avatars');

    $this->getJson('/api/v1/users/' . $this->member->username)->assertOk()
        ->assertJsonPath('data.avatar', signedPrefix('hd-190-2.ch-210-66', 'https://images.hotel.test/avatars'));
});

test('the bootstrap answer keeps the imager address of the website settings', function () {
    useSignedAvatars();

    $this->getJson('/api/v1/bootstrap')->assertOk()->assertJsonPath('data.assets.avatar', 'https://imager.test/?figure=');
});
