<?php

namespace App\Emulator\Drivers\Plus;

use App\Emulator\Contracts\RankRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;

final class PlusRankRepository implements RankRepository
{
    public function model(): string
    {
        return PlusRank::class;
    }

    public function displayNameColumn(): string
    {
        return 'name';
    }

    public function highestRank(): int
    {
        return (int) PlusRank::query()->max('security_level');
    }

    public function optionsBelow(int $rank): array
    {
        return PlusRank::query()->where('security_level', '<', $rank)
            ->orderBy('security_level')
            ->orderByRaw("CASE WHEN security_level = 1 AND slug IN ('user', 'default', 'member') THEN 0 ELSE 1 END")
            ->orderByDesc('weight')->get()
            ->unique('security_level')->pluck('name', 'security_level')->all();
    }

    public function forDisplay(Builder|Relation $query): Builder|Relation
    {
        return $query->select(['id', 'security_level', 'name']);
    }

    public function staffPositions(bool $includeHidden): Collection
    {
        return PlusRank::query()->where('is_staff', true)
            ->when(! $includeHidden, fn (Builder $query) => $query->where('is_hidden', false))
            ->orderByDesc('weight')
            ->with(['users' => fn ($query) => $query
                ->select('website_users.id', 'username', 'native_role_id', 'motto', 'look', 'hidden_staff', 'online')
                ->when(! $includeHidden, fn ($query) => $query->where('hidden_staff', false))])
            ->get();
    }
}
