<?php

namespace App\Emulator\Contracts;

use App\Models\User;

/**
 * For drivers whose native player row is the parent of Atom's user row: the
 * player is written before Atom inserts its own row, which then takes its id.
 */
interface CreatesPlayerBeforeUser
{
    public function creating(User $user): void;
}
