<?php

namespace App\Emulator\Drivers\Ada;

use App\Emulator\Contracts\RankRepository;
use App\Models\Ada\AdaRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;

class AdaRankRepository implements RankRepository
{
    public function model(): string
    {
        return AdaRole::class;
    }

    public function displayNameColumn(): string
    {
        return 'name';
    }

    public function userKey(): string
    {
        return 'rank';
    }

    public function highestRank(): int
    {
        return (int) AdaRole::query()->max('id');
    }

    public function optionsBelow(int $rank): array
    {
        return AdaRole::query()
            ->where('id', '<', $rank)
            ->pluck('name', 'id')
            ->all();
    }

    public function forDisplay(Builder|Relation $query): Builder|Relation
    {
        return $query->select(['id', 'name']);
    }

    /**
     * Ada's roles table has no hidden_rank column, so $includeHidden can only
     * filter hidden staff members here - every role above min_staff_rank is
     * always listed, unlike Arcturus where a whole rank can be hidden.
     *
     * @return Collection<int, AdaRole>
     */
    public function staffPositions(bool $includeHidden): Collection
    {
        return AdaRole::query()
            ->where('id', '>=', setting('min_staff_rank'))
            ->orderByDesc('id')
            ->with(['users' => fn ($query) => $query
                ->select('players.id', 'username', 'rank', 'motto', 'look', 'hidden_staff', 'online')
                ->when(! $includeHidden, fn ($query) => $query->where('hidden_staff', false))])
            ->get();
    }
}
