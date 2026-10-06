<?php

namespace App\Emulator\Drivers\Plus;

use App\Emulator\Contracts\CurrencyRepository;
use App\Emulator\Support\LeaderboardEntries;
use App\Enums\CurrencyTypes;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class PlusCurrencyRepository implements CurrencyRepository
{
    public function balance(User $user, CurrencyTypes $currency): int
    {
        return (int) (DB::table('users')->where('id', $user->id)->value($this->column($currency)) ?? 0);
    }

    public function give(User $user, CurrencyTypes $currency, int $amount): void
    {
        if ($amount === 0) {
            return;
        }
        $query = DB::table('users')->where('id', $user->id);
        $amount > 0 ? $query->increment($this->column($currency), $amount) : $query->decrement($this->column($currency), abs($amount));
    }

    public function deduct(User $user, CurrencyTypes $currency, int $amount): bool
    {
        if ($amount <= 0) {
            return true;
        }
        $column = $this->column($currency);

        return DB::table('users')->where('id', $user->id)->where($column, '>=', $amount)->decrement($column, $amount) === 1;
    }

    public function topBy(CurrencyTypes $currency, int $limit, array $excludeUserIds = []): Collection
    {
        $column = $this->column($currency);
        $balances = DB::table('users')->whereNotIn('id', $excludeUserIds)->orderByDesc($column)->limit($limit)->pluck($column, 'id')->map(fn ($value): int => (int) $value)->all();

        /** @var array<int, int> $balances */
        return LeaderboardEntries::forUsers($balances);
    }

    private function column(CurrencyTypes $currency): string
    {
        return match ($currency) {
            CurrencyTypes::Credits => 'credits', CurrencyTypes::Duckets => 'activity_points',
            CurrencyTypes::Diamonds => 'vip_points', CurrencyTypes::Points => 'gotw_points',
        };
    }
}
