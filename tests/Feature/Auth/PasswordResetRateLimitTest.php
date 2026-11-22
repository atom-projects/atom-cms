<?php

use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    installHotel();
    Mail::fake();
    User::factory()->create(['mail' => 'reset-limit@example.com']);
});

test('one address gets only three reset requests per link lifetime, whether or not it has an account', function (string $mail, int $mailsPerRequest) {
    $spellings = [$mail, strtoupper($mail), ucfirst($mail)];

    foreach ($spellings as $attempt => $spelling) {
        $this->withServerVariables(['REMOTE_ADDR' => "203.0.113.{$attempt}"])
            ->postJson(route('forgot.password.post'), ['mail' => $spelling])
            ->assertOk()
            ->assertJsonPath('message', __('We have e-mailed your password reset link!'));
    }

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.99'])
        ->postJson(route('forgot.password.post'), ['mail' => $mail])
        ->assertTooManyRequests();

    Mail::assertQueued(ResetPasswordMail::class, 3 * $mailsPerRequest);

    $this->travel((int) config('habbo.password_reset_token_time') + 1)->minutes();

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.100'])
        ->postJson(route('forgot.password.post'), ['mail' => $mail])
        ->assertOk();

    Mail::assertQueued(ResetPasswordMail::class, 4 * $mailsPerRequest);
})->with([
    'existing account' => ['reset-limit@example.com', 1],
    'unknown address' => ['nobody-here@example.com', 0],
]);

test('an address that is blocked still holds a live reset link', function (int $lifetimeMinutes) {
    config(['habbo.password_reset_token_time' => $lifetimeMinutes]);
    $start = now()->toImmutable();

    foreach (range(0, 2) as $minute) {
        $this->travelTo($start->addMinutes($minute));
        $this->withServerVariables(['REMOTE_ADDR' => "203.0.113.{$minute}"])
            ->postJson(route('forgot.password.post'), ['mail' => 'reset-limit@example.com'])
            ->assertOk();
    }

    $tokens = [];
    Mail::assertQueued(ResetPasswordMail::class, function (ResetPasswordMail $mail) use (&$tokens) {
        $tokens[] = $mail->token;

        return true;
    });

    $this->travelTo($start->addSeconds($lifetimeMinutes * 60 - 1));
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.99'])
        ->postJson(route('forgot.password.post'), ['mail' => 'reset-limit@example.com'])
        ->assertTooManyRequests();
    $this->get(route('reset.password.get', end($tokens)))->assertOk();

    $this->travelTo($start->addSeconds($lifetimeMinutes * 60 + 1));
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.100'])
        ->postJson(route('forgot.password.post'), ['mail' => 'reset-limit@example.com'])
        ->assertOk();
})->with([
    'default lifetime' => [15],
    'longer lifetime' => [30],
]);

test('invalid addresses are rejected without using a shared reset budget', function (mixed $mail) {
    foreach (range(1, 4) as $visitor) {
        $this->withServerVariables(['REMOTE_ADDR' => "203.0.113.{$visitor}"])
            ->postJson(route('forgot.password.post'), ['mail' => $mail])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('mail');
    }

    Mail::assertNothingQueued();
})->with([
    'empty' => [''],
    'not an address' => ['not-an-address'],
    'not a string' => [['reset-limit@example.com']],
]);
