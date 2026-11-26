<?php

namespace App\Services\Community;

use App\Emulator\Contracts\RankRepository;
use App\Models\Community\Teams\WebsiteTeam;
use App\Support\CommunityCache;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class TeamService
{
    public function __construct(private readonly RankRepository $ranks) {}

    /** @return Collection<int, WebsiteTeam> */
    public function fetchTeams(): Collection
    {
        $cacheEnabled = setting('enable_caching') === '1';

        $resolve = fn (): Collection => WebsiteTeam::select([
            'id',
            'rank_name',
            'badge',
            'staff_color',
            'staff_background',
            'job_description',
        ])
            ->where('hidden_rank', false)
            ->orderByDesc('id')
            ->with(['users' => function ($query) {
                $query->select('id', 'username', 'look', 'motto', $this->ranks->userKey(), 'team_id', 'online')
                    ->with(['permission' => fn ($query) => $this->ranks->forDisplay($query)]);
            }])
            ->get();

        if (! $cacheEnabled) {
            return $resolve();
        }

        return Cache::remember(
            CommunityCache::TEAMS,
            now()->addMinutes((int) setting('cache_timer')),
            $resolve,
        );
    }
}
