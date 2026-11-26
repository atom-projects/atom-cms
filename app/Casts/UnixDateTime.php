<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A DATETIME column exposed as unix seconds, the type Atom uses for player
 * timestamps. A missing value reads as 0.
 *
 * @implements CastsAttributes<int, int|string|null>
 */
final class UnixDateTime implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): int
    {
        return $value === null ? 0 : Carbon::parse($value)->unix();
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return (is_numeric($value) ? Carbon::createFromTimestamp((int) $value, config('app.timezone')) : Carbon::parse($value))->format('Y-m-d H:i:s');
    }
}
