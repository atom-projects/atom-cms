<?php

namespace App\Emulator\Drivers\Plus;

use App\Emulator\Contracts\CurrencyRepository;
use App\Emulator\Contracts\PlayerRepository;
use App\Emulator\Data\HomeFriend;
use App\Enums\CurrencyTypes;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Atom's User model lives on PlusEMU's users row (see PlusDriver::playerSchema).
 * Attributes PlusEMU normalises into other tables are written here, from the
 * model events of the same save.
 */
final class PlusPlayerRepository implements PlayerRepository
{
    /** Atom currency attributes PlusEMU keeps in user_currencies. */
    private const CURRENCY_ATTRIBUTES = ['pixels' => CurrencyTypes::Duckets, 'points' => CurrencyTypes::Points];

    public function __construct(private readonly CurrencyRepository $currencies) {}

    public function created(User $user): void
    {
        $level = $this->securityLevel((int) $user->rank);
        DB::table('user_roles')->insertOrIgnore(['user_id' => $user->id, 'role_id' => $this->roleId($level), 'created_at' => now()]);
        DB::table('users')->where('id', $user->id)->update(['rank' => $level]);

        foreach (self::CURRENCY_ATTRIBUTES as $attribute => $currency) {
            $this->currencies->give($user, $currency, (int) $user->getAttribute($attribute));
        }
    }

    public function updated(User $user): void
    {
        if ($user->wasChanged('password')) {
            $this->revokeCredentials($user);
        }
        if ($user->wasChanged('rank')) {
            DB::table('user_roles')->where('user_id', $user->id)
                ->whereIn('role_id', DB::table('roles')->select('id')->where('security_level', '>', 1))
                ->delete();
            DB::table('user_roles')->insertOrIgnore(['user_id' => $user->id, 'role_id' => $this->roleId((int) $user->rank), 'created_at' => now()]);
            DB::table('users')->where('id', $user->id)->update(['rank' => $this->securityLevel((int) $user->rank)]);
        }
        if ($user->wasChanged('home_room')) {
            DB::table('users_settings')->where('user_id', $user->id)->update(['home_room' => (int) $user->home_room]);
        }
        foreach (self::CURRENCY_ATTRIBUTES as $attribute => $currency) {
            if ($user->wasChanged($attribute)) {
                $this->currencies->give($user, $currency, (int) $user->getAttribute($attribute) - (int) $user->getOriginal($attribute));
            }
        }
    }

    /** The users row is the player; PlusEMU's foreign keys clear or protect the rest. */
    public function deleting(User $user): void {}

    public function whereOnline(Builder $query): Builder
    {
        return $query->where($query->getModel()->qualifyColumn('online'), true);
    }

    public function issueSso(User $user): string
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
            $updated = DB::table('users')->where('id', $user->id)->update([
                'auth_ticket' => $token, 'auth_ticket_expires_at' => now('UTC')->addSeconds(300)->format('Y-m-d H:i:s.u'),
                'auth_ticket_exchanged' => false, 'auth_ticket_session' => null,
            ]);
            if ($updated === 1) {
                return $token;
            }
        }
        throw new RuntimeException('Failed to issue a PlusEMU SSO ticket.');
    }

    public function onlineFriends(User $user, int $limit): Collection
    {
        return $this->whereOnline(User::query()->whereKey($this->friendIds($user)))->orderByDesc('last_online')->limit($limit)->get();
    }

    public function friendsForHome(User $user, int $perPage, string $pageName): LengthAwarePaginator
    {
        $page = User::query()->whereKey($this->friendIds($user))->orderBy('username')->paginate($perPage, ['*'], $pageName);

        return $page->through(fn (User $friend) => new HomeFriend($friend));
    }

    /**
     * A new password ends every session PlusEMU issued under the old one.
     */
    private function revokeCredentials(User $user): void
    {
        DB::table('users')->where('id', $user->id)->update([
            'credential_generation' => DB::raw('credential_generation + 1'),
            'auth_ticket' => null,
            'auth_ticket_expires_at' => null,
            'auth_ticket_exchanged' => false,
            'auth_ticket_session' => null,
        ]);
        foreach (['user_access_tokens', 'user_remember_tokens', 'user_sessions'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->where('user_id', $user->id)->whereNull('revoked_at')->update(['revoked_at' => now('UTC')]);
            }
        }
    }

    /** @return Collection<int, int> */
    private function friendIds(User $user): Collection
    {
        return DB::table('messenger_friendships')->where('user_one_id', $user->id)->orWhere('user_two_id', $user->id)->get()
            ->map(fn (object $row): int => (int) ($row->user_one_id === $user->id ? $row->user_two_id : $row->user_one_id))->unique();
    }

    private function securityLevel(int $level): int
    {
        return max(1, min(7, $level));
    }

    private function roleId(int $securityLevel): int
    {
        $securityLevel = $this->securityLevel($securityLevel);

        return (int) (DB::table('roles')
            ->where('security_level', $securityLevel)
            ->when($securityLevel === 1, fn (QueryBuilder $query) => $query->orderByRaw("CASE WHEN slug IN ('user', 'default', 'member') THEN 0 ELSE 1 END"))
            ->when($securityLevel > 1, fn (QueryBuilder $query) => $query->orderByDesc('weight'))
            ->orderBy('id')
            ->value('id') ?? throw new RuntimeException("PlusEMU has no role for security level {$securityLevel}."));
    }
}
