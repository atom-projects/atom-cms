<?php

namespace App\Services\Auth;

use App\Models\User;
use RuntimeException;

final class PasswordVerifier
{
    public function __construct(private readonly PasswordHasher $hasher) {}

    public function verify(User $user, string $password): bool
    {
        $hash = $user->getAttribute('password');

        if (! is_string($hash) || $hash === '') {
            return false;
        }

        try {
            if ($this->hasher->check($password, $hash)) {
                return true;
            }
        } catch (RuntimeException) {
            // PlusEMU historically stored plaintext passwords. They are
            // accepted once and immediately replaced with Argon2id below.
        }

        if (config('emulator.driver') !== 'plus' || str_starts_with($hash, '$argon2id$') || ! hash_equals($hash, $password)) {
            return false;
        }

        $user->forceFill(['password' => $this->hasher->make($password)])->save();

        return true;
    }
}
