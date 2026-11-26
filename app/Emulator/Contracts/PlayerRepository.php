<?php

namespace App\Emulator\Contracts;

use App\Emulator\Data\HomeFriend;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Writes the parts of a player the emulator keeps beside its player row.
 *
 * Atom's User model lives on each emulator's own player table (see the
 * driver's PlayerSchema). Attributes the emulator keeps in other tables are
 * read through the schema and written here, from the model's events, inside
 * the same transaction as the player row.
 */
interface PlayerRepository
{
    public function created(User $user): void;

    public function updated(User $user): void;

    /**
     * Called inside the delete's transaction before the player row goes, for
     * emulator rows that restrict deleting it rather than cascading.
     */
    public function deleting(User $user): void;

    /**
     * Constrain a user query to the players the emulator considers online.
     *
     * Drivers that do not own the users.online column must express this
     * against their own tables; the mirrored column is not authoritative.
     *
     * @param  Builder<User>  $query
     *
     * @return Builder<User>
     */
    public function whereOnline(Builder $query): Builder;

    public function issueSso(User $user): string;

    /** @return Collection<int, User> */
    public function onlineFriends(User $user, int $limit): Collection;

    /** @return LengthAwarePaginator<int, HomeFriend> */
    public function friendsForHome(User $user, int $perPage, string $pageName): LengthAwarePaginator;
}
