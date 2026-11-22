<?php

use App\Mail\ResetPasswordMail;
use App\Models\PasswordResetToken;
use App\Models\User;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    installHotel();
    Mail::fake();
    $this->withoutMiddleware(ThrottleRequests::class);
});

/** @return list<string> */
function queuedResetRecipients(): array
{
    return Mail::queued(ResetPasswordMail::class)
        ->flatMap(fn (ResetPasswordMail $mail): array => array_column($mail->to, 'address'))
        ->values()
        ->all();
}

function queuedResetToken(): string
{
    return Mail::queued(ResetPasswordMail::class)->sole()->token;
}

dataset('look-alike addresses', [
    'accent in the domain' => 'victim@exämple.com',
    'accent in the local part' => 'vïctim@example.com',
]);

dataset('case variants', [
    'upper case' => 'VICTIM@EXAMPLE.COM',
    'mixed case' => 'Victim@Example.com',
]);

test('a look-alike address never receives the reset link of an account', function (string $lookAlike) {
    $victim = User::factory()->create(['mail' => 'victim@example.com']);

    $this->post(route('forgot.password.post'), ['mail' => $lookAlike])
        ->assertSessionHas('success');

    expect(queuedResetRecipients())->toBe(['victim@example.com'])
        ->and(PasswordResetToken::pluck('email')->all())->toBe(['victim@example.com']);

    $this->post(route('reset.password.post', queuedResetToken()), [
        'password' => 'N3wPassword!',
        'password_confirmation' => 'N3wPassword!',
    ])->assertRedirect(route('login'));

    expect(Hash::check('N3wPassword!', $victim->fresh()->password))->toBeTrue();
})->with('look-alike addresses');

test('a case variant of the stored address mails the link to the stored address', function (string $variant) {
    $owner = User::factory()->create(['mail' => 'victim@example.com']);

    $this->post(route('forgot.password.post'), ['mail' => $variant])
        ->assertSessionHas('success');

    expect(queuedResetRecipients())->toBe(['victim@example.com']);

    $this->post(route('reset.password.post', queuedResetToken()), [
        'password' => 'N3wPassword!',
        'password_confirmation' => 'N3wPassword!',
    ])->assertRedirect(route('login'));

    expect(Hash::check('N3wPassword!', $owner->fresh()->password))->toBeTrue();
})->with('case variants');

test('the response is the same for known, unknown and look-alike addresses', function () {
    User::factory()->create(['mail' => 'victim@example.com']);
    $addresses = ['victim@example.com', 'nobody@example.com', 'victim@exämple.com', 'VICTIM@EXAMPLE.COM'];

    $json = array_map(function (string $mail): array {
        $response = $this->postJson(route('forgot.password.post'), ['mail' => $mail]);

        return [$response->status(), $response->json()];
    }, $addresses);

    $web = array_map(function (string $mail): array {
        $response = $this->from(route('forgot.password.get'))->post(route('forgot.password.post'), ['mail' => $mail]);

        return [$response->status(), $response->headers->get('Location'), session('success'), session('errors')];
    }, $addresses);

    expect(array_unique($json, SORT_REGULAR))->toHaveCount(1)
        ->and($json[0][0])->toBe(200)
        ->and(array_unique($web, SORT_REGULAR))->toHaveCount(1)
        ->and($web[0][2])->not->toBeNull();
});

test('when two accounts have look-alike addresses, each link reaches and resets only the exact account', function () {
    $victim = User::factory()->create(['mail' => 'victim@example.com']);
    $attacker = User::factory()->create(['mail' => 'victim@exämple.com']);

    $this->post(route('forgot.password.post'), ['mail' => 'victim@exämple.com'])
        ->assertSessionHas('success');

    expect(queuedResetRecipients())->toBe(['victim@exämple.com']);

    $this->post(route('reset.password.post', queuedResetToken()), [
        'password' => 'N3wPassword!',
        'password_confirmation' => 'N3wPassword!',
    ])->assertRedirect(route('login'));

    expect(Hash::check('N3wPassword!', $attacker->fresh()->password))->toBeTrue()
        ->and(Hash::check('password', $victim->fresh()->password))->toBeTrue();
});

test('an address that matches several accounts but none exactly sends no link', function () {
    User::factory()->create(['mail' => 'victim@example.com']);
    User::factory()->create(['mail' => 'victim@exämple.com']);

    $this->post(route('forgot.password.post'), ['mail' => 'VICTIM@EXAMPLE.COM'])
        ->assertSessionHas('success');

    Mail::assertNothingQueued();

    expect(PasswordResetToken::count())->toBe(0);
});

test('a token stored for a look-alike address does not reset the account', function () {
    $victim = User::factory()->create(['mail' => 'victim@example.com']);

    PasswordResetToken::create([
        'email' => 'victim@exämple.com',
        'token' => PasswordResetToken::hashToken('look-alike-token'),
    ]);

    $this->post(route('reset.password.post', 'look-alike-token'), [
        'password' => 'N3wPassword!',
        'password_confirmation' => 'N3wPassword!',
    ])->assertRedirect(route('forgot.password.get'));

    expect(Hash::check('password', $victim->fresh()->password))->toBeTrue();
});

test('a reset token only changes the password of the account it was issued for', function () {
    $owner = User::factory()->create(['mail' => 'owner@example.com']);
    $other = User::factory()->create(['mail' => 'other@example.com']);

    $this->post(route('forgot.password.post'), ['mail' => 'owner@example.com']);

    $this->post(route('reset.password.post', queuedResetToken()), [
        'mail' => 'other@example.com',
        'email' => 'other@example.com',
        'password' => 'N3wPassword!',
        'password_confirmation' => 'N3wPassword!',
    ])->assertRedirect(route('login'));

    expect(Hash::check('N3wPassword!', $owner->fresh()->password))->toBeTrue()
        ->and(Hash::check('password', $other->fresh()->password))->toBeTrue();
});

test('a reset request replaces only the pending link of the requesting account', function () {
    $victim = User::factory()->create(['mail' => 'victim@example.com']);
    User::factory()->create(['mail' => 'victim@exämple.com']);

    $this->post(route('forgot.password.post'), ['mail' => 'victim@example.com']);
    $firstVictimToken = Mail::queued(ResetPasswordMail::class)->last()->token;

    $this->post(route('forgot.password.post'), ['mail' => 'victim@exämple.com']);

    $this->get(route('reset.password.get', $firstVictimToken))->assertOk();

    $this->post(route('forgot.password.post'), ['mail' => 'victim@example.com']);
    $secondVictimToken = Mail::queued(ResetPasswordMail::class)->last()->token;

    $this->get(route('reset.password.get', $firstVictimToken))->assertRedirect(route('forgot.password.get'));
    $this->post(route('reset.password.post', $secondVictimToken), [
        'password' => 'N3wPassword!',
        'password_confirmation' => 'N3wPassword!',
    ])->assertRedirect(route('login'));

    expect(Hash::check('N3wPassword!', $victim->fresh()->password))->toBeTrue();
});

dataset('equal spellings of one address', [[
    ['victim@example.com', 'VICTIM@EXAMPLE.COM', 'Victim@Example.com', 'victim@exämple.com'],
]]);

test('equal spellings of one address get three reset mails per link lifetime and the same answer', function (array $spellings) {
    User::factory()->create(['mail' => 'victim@example.com']);

    $json = array_map(function (string $mail): array {
        $response = $this->postJson(route('forgot.password.post'), ['mail' => $mail]);
        $headers = array_keys($response->headers->all());
        sort($headers);

        return [$response->status(), $response->headers->get('Content-Type'), $headers, $response->json()];
    }, $spellings);

    expect(queuedResetRecipients())->toBe(array_fill(0, 3, 'victim@example.com'))
        ->and(array_unique($json, SORT_REGULAR))->toHaveCount(1)
        ->and($json[0][0])->toBe(200);
})->with('equal spellings of one address');

test('a reset request over the account cap keeps the last link working', function (array $spellings) {
    $victim = User::factory()->create(['mail' => 'victim@example.com']);

    $web = array_map(function (string $mail): array {
        $response = $this->from(route('forgot.password.get'))->post(route('forgot.password.post'), ['mail' => $mail]);

        return [$response->status(), $response->headers->get('Location'), session('success'), session('errors')];
    }, $spellings);

    $tokens = Mail::queued(ResetPasswordMail::class)->map(fn (ResetPasswordMail $mail): string => $mail->token)->values();

    expect(array_unique($web, SORT_REGULAR))->toHaveCount(1)
        ->and($web[0][2])->not->toBeNull()
        ->and($tokens)->toHaveCount(3)
        ->and(PasswordResetToken::count())->toBe(1);

    $this->post(route('reset.password.post', $tokens->last()), [
        'password' => 'N3wPassword!',
        'password_confirmation' => 'N3wPassword!',
    ])->assertRedirect(route('login'));

    expect(Hash::check('N3wPassword!', $victim->fresh()->password))->toBeTrue();
})->with('equal spellings of one address');

test('an account at its reset cap does not limit other accounts or unknown addresses', function () {
    User::factory()->create(['mail' => 'victim@example.com']);
    User::factory()->create(['mail' => 'other@example.com']);

    foreach (range(1, 4) as $attempt) {
        $this->postJson(route('forgot.password.post'), ['mail' => 'victim@example.com'])->assertOk();
    }

    foreach (range(1, 4) as $attempt) {
        $this->postJson(route('forgot.password.post'), ['mail' => 'nobody@example.com'])
            ->assertOk()
            ->assertJsonPath('message', __('We have e-mailed your password reset link!'));
    }

    $this->postJson(route('forgot.password.post'), ['mail' => 'other@example.com'])->assertOk();

    expect(queuedResetRecipients())->toBe([...array_fill(0, 3, 'victim@example.com'), 'other@example.com']);
});

test('the reset cap of an account lasts one link lifetime', function (int $lifetimeMinutes) {
    config(['habbo.password_reset_token_time' => $lifetimeMinutes]);
    User::factory()->create(['mail' => 'victim@example.com']);
    $start = now()->toImmutable();

    foreach (range(1, 3) as $attempt) {
        $this->postJson(route('forgot.password.post'), ['mail' => 'victim@example.com'])->assertOk();
    }

    $this->travelTo($start->addSeconds($lifetimeMinutes * 60 - 1));
    $this->postJson(route('forgot.password.post'), ['mail' => 'victim@example.com'])->assertOk();

    expect(queuedResetRecipients())->toHaveCount(3);

    $this->travelTo($start->addSeconds($lifetimeMinutes * 60 + 1));
    $this->postJson(route('forgot.password.post'), ['mail' => 'victim@example.com'])->assertOk();

    expect(queuedResetRecipients())->toHaveCount(4);
})->with([
    'default lifetime' => [15],
    'longer lifetime' => [30],
]);
