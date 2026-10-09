<?php

namespace App\Emulator\Drivers\Plus;

use App\Emulator\Contracts\PlayerSettingsRepository;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class PlusPlayerSettingsRepository implements PlayerSettingsRepository
{
    /**
     * users_settings.home_room references rooms, and INSERT IGNORE would
     * swallow that failure along with the row, so a configured home room that
     * no longer exists falls back to none.
     */
    public function created(User $user): void
    {
        $homeRoom = (int) $user->home_room;
        if ($homeRoom !== 0 && ! DB::table('rooms')->where('id', $homeRoom)->exists()) {
            $homeRoom = 0;
        }

        DB::table('users_settings')->insertOrIgnore(['user_id' => $user->id, 'home_room' => $homeRoom]);
        DB::table('user_statistics')->insertOrIgnore(['id' => $user->id]);
    }

    public function canChangeName(User $user): bool
    {
        return false;
    }

    public function setCanChangeName(User $user, bool $allowed): void {}
}
