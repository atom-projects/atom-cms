<?php

namespace App\Emulator\Contracts;

use App\Emulator\Models\Rank;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;

interface RankRepository
{
    /** @return class-string<Rank> */
    public function model(): string;

    public function displayNameColumn(): string;

    /** The User attribute that holds the key of the player's rank model. */
    public function userKey(): string;

    /**
     * The highest rank this emulator currently defines.
     *
     * Arcturus ships permissions well past the owner tier, while Ada's base
     * roles stop at Admin. Seeded permission ranks are scaled to this so a
     * stock hotel always has a rank able to reach its own owner-level
     * permissions.
     */
    public function highestRank(): int;

    /** @return array<int|string, string> */
    public function optionsBelow(int $rank): array;

    /** @param Builder<Rank>|Relation<Rank, Rank, Rank> $query
     * @return Builder<Rank>|Relation<Rank, Rank, Rank>
     */
    public function forDisplay(Builder|Relation $query): Builder|Relation;

    /** @return Collection<int, covariant Rank> */
    public function staffPositions(bool $includeHidden): Collection;
}
