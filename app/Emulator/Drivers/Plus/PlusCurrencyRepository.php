<?php

namespace App\Emulator\Drivers\Plus;

use App\Emulator\Contracts\CurrencyRepository;
use App\Emulator\Support\LeaderboardEntries;
use App\Enums\CurrencyTypes;
use App\Models\User;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Credits live on users.credits; every activity point type lives in
 * user_currencies (PlusEMU migration 59), where a missing row means 0.
 */
final class PlusCurrencyRepository implements CurrencyRepository
{
    public const DUCKETS = 0;

    public const DIAMONDS = 5;

    public const GOTW_POINTS = 103;

    public function balance(User $user, CurrencyTypes $currency): int
    {
        $type = $this->type($currency);
        if ($type === null) {
            return (int) (DB::table('users')->where('id', $user->id)->value('credits') ?? 0);
        }

        return (int) (DB::table('user_currencies')->where('user_id', $user->id)->where('type', $type)->value('amount') ?? 0);
    }

    public function give(User $user, CurrencyTypes $currency, int $amount): void
    {
        if ($amount === 0) {
            return;
        }
        $type = $this->type($currency);
        if ($type === null) {
            $query = DB::table('users')->where('id', $user->id);
            $amount > 0 ? $query->increment('credits', $amount) : $query->decrement('credits', abs($amount));

            return;
        }

        // Selecting from users keeps a give to a missing player a no-op, like the old column update.
        DB::statement(
            'INSERT INTO user_currencies (user_id, type, amount) SELECT id, ?, ? FROM users WHERE id = ?
                ON DUPLICATE KEY UPDATE amount = user_currencies.amount + VALUES(amount)',
            [$type, $amount, $user->id],
        );
    }

    public function deduct(User $user, CurrencyTypes $currency, int $amount): bool
    {
        if ($amount <= 0) {
            return true;
        }
        $type = $this->type($currency);
        if ($type === null) {
            return DB::table('users')->where('id', $user->id)->where('credits', '>=', $amount)->decrement('credits', $amount) === 1;
        }

        return DB::table('user_currencies')->where('user_id', $user->id)->where('type', $type)
            ->where('amount', '>=', $amount)->decrement('amount', $amount) === 1;
    }

    public function topBy(CurrencyTypes $currency, int $limit, array $excludeUserIds = []): Collection
    {
        $type = $this->type($currency);
        $query = DB::table('users')->whereNotIn('users.id', $excludeUserIds)->limit($limit);
        if ($type === null) {
            $query->orderByDesc('credits')->select(['users.id', 'users.credits as balance']);
        } else {
            $query->leftJoin('user_currencies', fn (JoinClause $join) => $join->on('user_currencies.user_id', '=', 'users.id')->where('user_currencies.type', '=', $type))
                ->select(['users.id', DB::raw('COALESCE(user_currencies.amount, 0) AS balance')])->orderByDesc('balance');
        }
        $balances = $query->pluck('balance', 'id')->map(fn ($value): int => (int) $value)->all();

        /** @var array<int, int> $balances */
        return LeaderboardEntries::forUsers($balances);
    }

    /** The user_currencies type for an activity point currency, or null for credits. */
    private function type(CurrencyTypes $currency): ?int
    {
        return match ($currency) {
            CurrencyTypes::Credits => null, CurrencyTypes::Duckets => self::DUCKETS,
            CurrencyTypes::Diamonds => self::DIAMONDS, CurrencyTypes::Points => self::GOTW_POINTS,
        };
    }
}
