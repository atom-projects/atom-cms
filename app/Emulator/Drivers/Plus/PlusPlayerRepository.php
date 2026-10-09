<?php

namespace App\Emulator\Drivers\Plus;

use App\Emulator\Contracts\AllocatesPlayerIdentity;
use App\Emulator\Contracts\CreatesPlayerBeforeUser;
use App\Emulator\Contracts\PlayerRepository;
use App\Emulator\Contracts\PreparesPlayerQueries;
use App\Emulator\Data\HomeFriend;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use stdClass;

final class PlusPlayerRepository implements AllocatesPlayerIdentity, CreatesPlayerBeforeUser, PlayerRepository, PreparesPlayerQueries
{
    public function __construct(private readonly PlusPlayerProjection $projection) {}

    /**
     * website_users rows reference users.id, so a projected row always has a
     * native player behind it; the query only needs its candidates refreshed.
     */
    public function prepareQuery(Builder $query): void
    {
        $this->projection->synchronizeNewPlayers();
        $query->getQuery()->beforeQuery(fn (QueryBuilder $base) => $this->projection->synchronizeMatching($base));
    }

    public function synchronizeAll(): int
    {
        return $this->projection->synchronizeAll();
    }

    public function allocateIdentity(): array
    {
        return ['id' => max(
            (int) DB::table('users')->max('id'),
            (int) DB::table('website_users')->max('id'),
        ) + 1];
    }

    /**
     * The native player is the parent row, so it is written first and Atom's
     * row takes its id.
     */
    public function creating(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $values = [
                'username' => $user->username, 'password' => $user->password,
                'mail' => $user->mail, 'auth_ticket' => '', 'rank' => $this->securityLevel((int) $user->rank),
                'credits' => (int) $user->credits,
                'look' => $user->look, 'gender' => $user->gender ?: 'M', 'motto' => $user->motto,
                'account_created' => now(), 'last_online' => now(), 'online' => false,
                'ip_last' => $user->ip_current, 'ip_reg' => $user->ip_register,
            ];
            if ($user->getKey() === null) {
                $user->setAttribute($user->getKeyName(), (int) DB::table('users')->insertGetId($values));
            } else {
                DB::table('users')->insert(['id' => $user->getKey(), ...$values]);
            }
            DB::table('user_roles')->insertOrIgnore(['user_id' => $user->getKey(), 'role_id' => $this->roleId((int) $user->rank), 'created_at' => now()]);
        });
    }

    public function created(User $user): void {}

    public function updated(User $user): void
    {
        $values = [];
        foreach (['username' => 'username', 'password' => 'password', 'mail' => 'mail', 'credits' => 'credits', 'look' => 'look', 'motto' => 'motto', 'gender' => 'gender', 'ip_current' => 'ip_last'] as $attribute => $column) {
            if ($user->wasChanged($attribute)) {
                $values[$column] = $user->getAttribute($attribute);
            }
        }
        if ($user->wasChanged('password')) {
            $values += [
                'auth_ticket' => null,
                'auth_ticket_expires_at' => null,
                'auth_ticket_exchanged' => false,
                'auth_ticket_session' => null,
            ];
        }
        if ($values !== [] && ! $user->wasChanged('password')) {
            DB::table('users')->where('id', $user->id)->update($values);
        }
        if ($user->wasChanged('password')) {
            DB::transaction(function () use ($user, $values): void {
                DB::table('users')->where('id', $user->id)->lockForUpdate()->firstOrFail();
                DB::table('users')->where('id', $user->id)->update($values + [
                    'credential_generation' => DB::raw('credential_generation + 1'),
                ]);
                foreach (['user_access_tokens', 'user_remember_tokens', 'user_sessions'] as $table) {
                    if (Schema::hasTable($table)) {
                        DB::table($table)->where('user_id', $user->id)->whereNull('revoked_at')->update(['revoked_at' => now('UTC')]);
                    }
                }
            });
        }
        if ($user->wasChanged('rank')) {
            DB::transaction(function () use ($user): void {
                DB::table('user_roles')->where('user_id', $user->id)
                    ->whereIn('role_id', DB::table('roles')->select('id')->where('security_level', '>', 1))
                    ->delete();
                DB::table('user_roles')->insertOrIgnore(['user_id' => $user->id, 'role_id' => $this->roleId((int) $user->rank), 'created_at' => now()]);
                DB::table('users')->where('id', $user->id)->update(['rank' => $this->securityLevel((int) $user->rank)]);
            });
        }
        if ($user->wasChanged('home_room')) {
            DB::table('users_settings')->where('user_id', $user->id)->update(['home_room' => (int) $user->home_room]);
        }
    }

    public function deleted(User $user): void
    {
        DB::table('users')->where('id', $user->id)->delete();
    }

    public function hydrateMany(array $users): void
    {
        $byId = collect($users)->filter(fn (User $user) => $user->getKey() !== null)->keyBy(fn (User $user) => (int) $user->id);
        if ($byId->isEmpty()) {
            return;
        }
        $native = $this->projection->nativeAttributes($byId->keys()->all());
        foreach ($byId as $id => $user) {
            if (isset($native[$id])) {
                $user->setRawAttributes(array_merge($user->getAttributes(), $native[$id]), true);
            }
        }
    }

    public function whereOnline(Builder $query): Builder
    {
        return $query->whereIn($query->getModel()->getQualifiedKeyName(), DB::table('users')->select('id')->where('online', true));
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
        $ids = DB::table('users')->whereIn('id', $this->friendIds($user))->where('online', true)
            ->orderByDesc('last_online')->limit($limit)->pluck('id')->map(fn (mixed $id): int => (int) $id);
        $friends = User::query()->whereKey($ids->all())->get()->keyBy('id');

        return $ids->map(fn (int $id): ?User => $friends->get($id))->filter()->values();
    }

    public function friendsForHome(User $user, int $perPage, string $pageName): LengthAwarePaginator
    {
        $page = DB::table('users')->whereIn('id', $this->friendIds($user))->orderBy('username')->paginate($perPage, ['id'], $pageName);
        $friends = User::query()->whereKey($page->getCollection()->pluck('id')->all())->get()->keyBy('id');

        return $page->through(fn (stdClass $row) => new HomeFriend($friends->get($row->id)));
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
