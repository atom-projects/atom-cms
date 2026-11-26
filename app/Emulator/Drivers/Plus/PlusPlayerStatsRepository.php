<?php

namespace App\Emulator\Drivers\Plus;

use App\Emulator\Contracts\PlayerStatsRepository;
use App\Emulator\Data\Stat;
use App\Emulator\Support\LeaderboardEntries;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class PlusPlayerStatsRepository implements PlayerStatsRepository
{
    public function supports(Stat $stat): bool
    {
        return true;
    }

    public function topBy(Stat $stat, int $limit, array $excludeUserIds = []): Collection
    {
        $column = match ($stat) {
            Stat::OnlineTime => 'OnlineTime', Stat::RespectsReceived => 'Respect', Stat::AchievementScore => 'AchievementScore',
        };
        $values = DB::table('user_statistics')->whereNotIn('id', $excludeUserIds)->orderByDesc($column)->limit($limit)
            ->pluck($column, 'id')->map(fn ($value): int => (int) $value)->all();

        /** @var array<int, int> $values */
        return LeaderboardEntries::forUsers($values);
    }
}
