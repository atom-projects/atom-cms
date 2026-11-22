<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    installHotel();
    $this->user = User::factory()->create(['online' => '0']);
});

dataset('current password checks', [
    'web password change' => ['PUT', '/user/settings/password', ['current_password' => 'wrong-password', 'password' => 'ChangedPassword123!', 'password_confirmation' => 'ChangedPassword123!']],
    'web email change' => ['PUT', '/user/settings/account', ['mail' => 'changed@example.com', 'current_password' => 'wrong-password']],
    'api password change' => ['PUT', '/api/v1/me/password', ['current_password' => 'wrong-password', 'password' => 'ChangedPassword123!', 'password_confirmation' => 'ChangedPassword123!']],
    'api email change' => ['PUT', '/api/v1/me/account', ['mail' => 'changed@example.com', 'current_password' => 'wrong-password']],
    'password confirmation' => ['POST', '/user/confirm-password', ['password' => 'wrong-password']],
    'password confirmation with only spaces' => ['POST', '/user/confirm-password', ['password' => '      ']],
    'two-factor enable' => ['POST', '/user/settings/two-factor-authentication', ['current_password' => 'wrong-password']],
    'two-factor disable' => ['DELETE', '/user/settings/two-factor-authentication', ['current_password' => 'wrong-password']],
]);

test('a session gets only five current password guesses per minute', function (string $method, string $uri, array $payload) {
    $this->actingAs($this->user);

    foreach (range(1, 5) as $attempt) {
        $this->json($method, $uri, $payload)->assertUnprocessable();
    }

    $this->json($method, $uri, $payload)->assertTooManyRequests();
})->with('current password checks');

test('current password guesses share one budget per account across endpoints and addresses', function () {
    $this->actingAs($this->user);
    $passwordChange = ['current_password' => 'wrong-password', 'password' => 'ChangedPassword123!', 'password_confirmation' => 'ChangedPassword123!'];

    foreach (range(1, 3) as $attempt) {
        $this->withServerVariables(['REMOTE_ADDR' => "203.0.113.{$attempt}"])
            ->putJson('/api/v1/me/password', $passwordChange)
            ->assertUnprocessable();
    }

    foreach (range(4, 5) as $attempt) {
        $this->withServerVariables(['REMOTE_ADDR' => "203.0.113.{$attempt}"])
            ->postJson('/user/confirm-password', ['password' => 'wrong-password'])
            ->assertUnprocessable();
    }

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.99'])
        ->putJson('/api/v1/me/password', $passwordChange)
        ->assertTooManyRequests()
        ->assertJsonPath('code', 'rate_limited');
});

test('settings saves without a password do not use the current password budget', function () {
    $this->actingAs($this->user);

    foreach (range(1, 2) as $attempt) {
        $this->put('/user/settings/account', ['mail' => $this->user->mail, 'motto' => "Motto {$attempt}"])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('settings.account.show'));
    }

    foreach (range(3, 5) as $attempt) {
        $this->putJson('/api/v1/me/account', ['mail' => $this->user->mail, 'motto' => "Motto {$attempt}"])->assertNoContent();
    }

    $this->putJson('/api/v1/me/password', ['current_password' => 'password', 'password' => 'ChangedPassword123!', 'password_confirmation' => 'ChangedPassword123!'])
        ->assertNoContent();
    expect(Hash::check('ChangedPassword123!', $this->user->fresh()->password))->toBeTrue();
});

test('the right password changes the password again once the limit resets', function () {
    $this->actingAs($this->user);
    $change = fn (string $currentPassword): array => ['current_password' => $currentPassword, 'password' => 'ChangedPassword123!', 'password_confirmation' => 'ChangedPassword123!'];

    foreach (range(1, 5) as $attempt) {
        $this->putJson('/api/v1/me/password', $change('wrong-password'))->assertUnprocessable();
    }

    $this->putJson('/api/v1/me/password', $change('password'))->assertTooManyRequests();
    expect(Hash::check('password', $this->user->fresh()->password))->toBeTrue();

    $this->travel(61)->seconds();

    $this->putJson('/api/v1/me/password', $change('password'))->assertNoContent();
    expect(Hash::check('ChangedPassword123!', $this->user->fresh()->password))->toBeTrue();
});
