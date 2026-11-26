<?php

namespace App\Emulator\Drivers\Plus;

use App\Emulator\Contracts\BadgeRepository;
use App\Emulator\Data\OwnedBadge;
use App\Models\User;
use App\Services\Badge\BadgeGrantMutex;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class PlusBadgeRepository implements BadgeRepository
{
    public function __construct(private readonly BadgeGrantMutex $mutex = new BadgeGrantMutex) {}

    /** @return HasMany<covariant Model, User> */
    public function relation(User $user): HasMany
    {
        return $user->hasMany(PlusUserBadge::class, 'user_id');
    }

    public function codes(User $user): array
    {
        return DB::table('user_badges')->where('user_id', $user->id)->pluck('badge_id')->all();
    }

    public function grant(User $user, string $badge): void
    {
        $this->mutex->run($badge, function () use ($badge, $user): void {
            DB::table('user_badges')->insertOrIgnore([
                'user_id' => $user->id, 'badge_id' => $badge, 'badge_slot' => 0,
            ]);
        });
    }

    public function revoke(User $user, string $badge): void
    {
        DB::table('user_badges')->where('user_id', $user->id)->where('badge_id', $badge)->delete();
    }

    public function paginate(User $user, int $perPage, string $pageName): LengthAwarePaginator
    {
        return DB::table('user_badges')->where('user_id', $user->id)->orderByDesc('id')
            ->paginate($perPage, ['badge_id', 'badge_slot'], $pageName)
            ->through(fn (object $row) => new OwnedBadge(
                (string) data_get($row, 'badge_id'),
                (int) data_get($row, 'badge_slot'),
            ));
    }
}

final class PlusUserBadge extends Model
{
    protected $table = 'user_badges';

    public $timestamps = false;

    protected $guarded = [];
}
