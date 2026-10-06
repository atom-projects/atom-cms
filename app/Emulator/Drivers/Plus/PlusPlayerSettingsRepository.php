<?php

namespace App\Emulator\Drivers\Plus;

use App\Emulator\Contracts\PlayerSettingsRepository;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class PlusPlayerSettingsRepository implements PlayerSettingsRepository
{
    public function created(User $user): void
    {
        DB::table('users_settings')->insertOrIgnore(['user_id' => $user->id, 'home_room' => (int) $user->home_room]);
        DB::table('user_statistics')->insertOrIgnore(['id' => $user->id]);
    }

    public function canChangeName(User $user): bool
    {
        return false;
    }

    public function setCanChangeName(User $user, bool $allowed): void {}
}
