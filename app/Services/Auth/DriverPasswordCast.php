<?php

namespace App\Services\Auth;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/** @implements CastsAttributes<string|null, string|null> */
final class DriverPasswordCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null || config('emulator.driver') !== 'plus') {
            return $value;
        }

        $info = password_get_info((string) $value);
        if ($info['algoName'] === 'argon2id') {
            return $value;
        }

        return app(PasswordHasher::class)->make((string) $value);
    }
}
