<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

final class PasswordVerifier
{
    /**
     * Check a password against the player's stored one and keep it current: a
     * hash of another algorithm or cost, as a hotel that changed emulators
     * has, is replaced after a successful check, and so is a plaintext
     * password, which PlusEMU once stored.
     */
    public function verify(User $user, string $password): bool
    {
        $stored = $user->getAttribute('password');

        if (! is_string($stored) || $stored === '') {
            return false;
        }

        $hashed = password_get_info($stored)['algo'] !== null;

        if (! ($hashed ? password_verify($password, $stored) : hash_equals($stored, $password))) {
            return false;
        }

        if (! $hashed || Hash::needsRehash($stored)) {
            // The password cast hashes it with the active emulator's driver.
            $user->forceFill(['password' => $password])->save();
        }

        return true;
    }
}
