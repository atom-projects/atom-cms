<?php

namespace App\Emulator\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * For drivers that mirror emulator players into Atom's users table.
 */
interface PreparesPlayerQueries
{
    /** @param Builder<User> $query */
    public function prepareQuery(Builder $query): void;

    /** Refresh every mirrored player. Returns the number of players written. */
    public function synchronizeAll(): int;
}
