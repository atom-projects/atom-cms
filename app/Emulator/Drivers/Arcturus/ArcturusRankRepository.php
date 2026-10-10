<?php

namespace App\Emulator\Drivers\Arcturus;

use App\Emulator\Contracts\RankRepository;
use App\Models\Game\Permission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;

class ArcturusRankRepository implements RankRepository
{
    public function model(): string
    {
        return Permission::class;
    }

    public function displayNameColumn(): string
    {
        return 'rank_name';
    }

    public function userKey(): string
    {
        return 'rank';
    }

    public function highestRank(): int
    {
        return (int) Permission::query()->max('id');
    }

    public function optionsBelow(int $rank): array
    {
        return Permission::query()
            ->where('id', '<', $rank)
            ->pluck('rank_name', 'id')
            ->all();
    }

    public function forDisplay(Builder|Relation $query): Builder|Relation
    {
        return $query->select(['id', 'rank_name', 'staff_background']);
    }

    /** @return Collection<int, Permission> */
    public function staffPositions(bool $includeHidden): Collection
    {
        return Permission::query()
            ->select('id', 'rank_name', 'badge', 'staff_color', 'job_description')
            ->when(! $includeHidden, fn (Builder $query) => $query->where('hidden_rank', false))
            ->where('id', '>=', setting('min_staff_rank'))
            ->orderByDesc('id')
            ->with(['users' => function ($query) use ($includeHidden): void {
                $query->select('id', 'username', 'rank', 'motto', 'look', 'hidden_staff', 'online')
                    ->when(! $includeHidden, fn ($query) => $query->where('hidden_staff', false))
                    ->with(['permission' => fn ($query) => $this->forDisplay($query)]);
            }])
            ->get();
    }
}
