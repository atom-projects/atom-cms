<?php

namespace App\Emulator\Drivers\Plus;

use App\Emulator\Contracts\AllocatesPlayerIdentity;
use App\Emulator\Contracts\PlayerRepository;
use App\Emulator\Contracts\PreparesPlayerQueries;
use App\Emulator\Data\HomeFriend;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class PlusPlayerRepository implements AllocatesPlayerIdentity, PlayerRepository, PreparesPlayerQueries
{
    public function prepareQuery(Builder $query): void
    {
        app(PlusPlayerProjection::class)->synchronize();
        $query->whereIn($query->getModel()->getQualifiedKeyName(), DB::table('users')->select('id'));
    }

    public function allocateIdentity(): array
    {
        return ['id' => max(
            (int) DB::table('users')->max('id'),
            (int) DB::table('website_users')->max('id'),
        ) + 1];
    }

    public function created(User $user): void
    {
        DB::transaction(function () use ($user): void {
            DB::table('users')->insert([
                'id' => $user->id, 'username' => $user->username, 'password' => $user->password,
                'mail' => $user->mail, 'auth_ticket' => '', 'rank' => $this->securityLevel((int) $user->rank),
                'credits' => (int) $user->credits,
                'look' => $user->look, 'gender' => $user->gender ?: 'M', 'motto' => $user->motto,
                'account_created' => now(), 'last_online' => now(), 'online' => false,
                'ip_last' => $user->ip_current, 'ip_reg' => $user->ip_register,
            ]);
            DB::table('user_roles')->insertOrIgnore(['user_id' => $user->id, 'role_id' => $this->roleId((int) $user->rank), 'created_at' => now()]);
        });
    }

    public function updated(User $user): void
    {
        $values = [];
        foreach (['username' => 'username', 'password' => 'password', 'mail' => 'mail', 'look' => 'look', 'motto' => 'motto', 'gender' => 'gender', 'ip_current' => 'ip_last'] as $attribute => $column) {
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
        $native = DB::table('users')->leftJoin('users_settings', 'users_settings.user_id', '=', 'users.id')
            ->whereIn('users.id', $byId->keys())->get([
                'users.id', 'users.username', 'users.password', 'users.mail', 'users.credits', 'users.look', 'users.gender',
                'users.motto', 'users.account_created', 'users.last_online', 'users.online', 'users.ip_last', 'users.ip_reg',
                'users_settings.home_room',
            ])->keyBy('id');
        $currencies = DB::table('user_currencies')->whereIn('user_id', $byId->keys())
            ->whereIn('type', [PlusCurrencyRepository::DUCKETS, PlusCurrencyRepository::GOTW_POINTS])->get(['user_id', 'type', 'amount'])
            ->mapWithKeys(fn (object $row): array => [$row->user_id . ':' . $row->type => (int) $row->amount]);
        $roles = DB::table('user_roles')->join('roles', 'roles.id', '=', 'user_roles.role_id')->whereIn('user_id', $byId->keys())
            ->where(fn (QueryBuilder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', $this->utcNow()))
            ->orderByDesc('roles.security_level')->orderByDesc('roles.weight')->orderBy('roles.id')
            ->get(['user_id', 'role_id', 'roles.security_level'])->unique('user_id')->keyBy('user_id');
        foreach ($byId as $id => $user) {
            if (($row = $native->get($id)) === null) {
                continue;
            }
            $user->setRawAttributes(array_merge($user->getAttributes(), [
                'username' => $row->username, 'password' => $row->password, 'mail' => $row->mail,
                'credits' => (int) $row->credits, 'pixels' => $currencies[$id . ':' . PlusCurrencyRepository::DUCKETS] ?? 0,
                'points' => $currencies[$id . ':' . PlusCurrencyRepository::GOTW_POINTS] ?? 0, 'look' => $row->look ?? '', 'gender' => $row->gender ?? 'M',
                'motto' => $row->motto ?? '', 'account_created' => $this->unix($row->account_created),
                'last_online' => $this->unix($row->last_online), 'online' => (bool) $row->online,
                'ip_current' => $row->ip_last ?? '', 'ip_register' => $row->ip_reg ?? '',
                'home_room' => (int) ($row->home_room ?? 0), 'rank' => (int) ($roles[$id]->security_level ?? 1),
                'native_role_id' => $roles[$id]->role_id ?? null,
            ]), true);
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
        return $this->whereOnline(User::query()->whereKey($this->friendIds($user)))->orderByDesc('last_online')->limit($limit)->get();
    }

    public function friendsForHome(User $user, int $perPage, string $pageName): LengthAwarePaginator
    {
        $page = User::query()->whereKey($this->friendIds($user))->orderBy('username')->paginate($perPage, ['*'], $pageName);

        return $page->through(fn (User $friend) => new HomeFriend($friend));
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

    private function unix(mixed $value): int
    {
        return $value === null ? 0 : Carbon::parse($value)->unix();
    }

    private function utcNow(): string
    {
        return now('UTC')->format('Y-m-d H:i:s.u');
    }
}
