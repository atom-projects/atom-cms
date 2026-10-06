<?php

namespace App\Emulator\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

interface PreparesPlayerQueries
{
    /** @param Builder<User> $query */
    public function prepareQuery(Builder $query): void;
}
