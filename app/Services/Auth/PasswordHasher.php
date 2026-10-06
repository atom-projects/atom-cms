<?php

namespace App\Services\Auth;

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\Hash;

final class PasswordHasher
{
    public function make(string $password): string
    {
        return $this->driver()->make($password);
    }

    public function check(string $password, string $hash): bool
    {
        return $this->driver()->check($password, $hash);
    }

    private function driver(): Hasher
    {
        return Hash::driver(config('emulator.driver') === 'plus' ? 'argon2id' : 'bcrypt');
    }
}
