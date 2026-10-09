<?php

namespace App\Emulator\Drivers\Plus;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use stdClass;

/**
 * Keeps Atom's website_users rows in step with the PlusEMU tables they mirror.
 *
 * Work is proportional to the players a request touches: a user query first
 * refreshes the rows its filters can match, and accounts PlusEMU registered on
 * its own are picked up by id. Refreshing every player is an explicit step
 * (atom:sync-players), never part of a request.
 */
final class PlusPlayerProjection
{
    /** Columns copied from PlusEMU; every other website_users column is Atom's own. */
    private const MIRRORED = [
        'username', 'password', 'mail', 'account_created', 'last_online', 'motto', 'look', 'gender', 'rank',
        'native_role_id', 'credits', 'pixels', 'points', 'online', 'ip_register', 'ip_current', 'home_room',
    ];

    private const CHUNK = 500;

    /** Seconds between checks for accounts PlusEMU registered itself. */
    private const NEW_PLAYER_INTERVAL = 10;

    private const READY_KEY = 'plus_projection_ready';

    private const NEW_PLAYERS_KEY = 'plus_projection_new_players_checked';

    private const CHECKED_UP_TO_KEY = 'plus_projection_checked_up_to';

    /** Both tables exist. Once true it stays true, so it is cached past the request. */
    private static bool $ready = false;

    public function __construct(private readonly PlusPlayerCandidates $candidates) {}

    /** Refresh every player. Returns the number of rows written. */
    public function synchronizeAll(): int
    {
        if (! $this->ready()) {
            return 0;
        }

        $synchronized = 0;
        DB::table('users')->select('id')->chunkById(self::CHUNK, function (Collection $rows) use (&$synchronized): void {
            $synchronized += $this->synchronize($this->ids($rows));
        });

        return $synchronized;
    }

    /**
     * Refresh the given players. Returns the number of rows written.
     *
     * @param  array<int, int>  $ids
     */
    public function synchronize(array $ids): int
    {
        if ($ids === [] || ! $this->ready()) {
            return 0;
        }

        $rows = [];
        foreach (array_chunk($ids, self::CHUNK) as $chunk) {
            foreach ($this->nativeAttributes($chunk) as $id => $attributes) {
                $rows[] = ['id' => $id, ...$attributes, 'online' => (int) $attributes['online']];
            }
        }

        foreach (array_chunk($rows, self::CHUNK) as $chunk) {
            DB::table('website_users')->upsert($chunk, ['id'], self::MIRRORED);
        }

        return count($rows);
    }

    /** Refresh the players a user query can match, just before it runs. */
    public function synchronizeMatching(Builder $query): void
    {
        $ids = $this->candidates->for($query);

        if ($ids !== null) {
            $this->synchronize($ids);
        }
    }

    /**
     * Project accounts PlusEMU created on its own, at most once per interval
     * across requests. PlusEMU ids only grow, so every users id above the last
     * one checked is new; checking costs two index reads. The watermark is the
     * last users id seen rather than the highest projected id, so a CMS
     * registration landing above an unprojected emulator account cannot hide it.
     */
    public function synchronizeNewPlayers(): void
    {
        if (! $this->ready() || ! Cache::add(self::NEW_PLAYERS_KEY, true, self::NEW_PLAYER_INTERVAL)) {
            return;
        }

        $checked = Cache::get(self::CHECKED_UP_TO_KEY);
        $checked = is_numeric($checked) ? (int) $checked : (int) DB::table('website_users')->max('id');
        $latest = (int) DB::table('users')->max('id');
        if ($latest > $checked) {
            DB::table('users')->select('id')->where('id', '>', $checked)->where('id', '<=', $latest)
                ->chunkById(self::CHUNK, fn (Collection $rows) => $this->synchronize($this->ids($rows)));
        }
        Cache::forever(self::CHECKED_UP_TO_KEY, max($checked, $latest));
    }

    /**
     * The current PlusEMU value of every mirrored column, keyed by user id.
     * Players missing from PlusEMU are left out.
     *
     * @param  array<int, int>  $ids
     *
     * @return array<int, array<string, mixed>>
     */
    public function nativeAttributes(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $native = DB::table('users')
            ->leftJoin('users_settings', 'users_settings.user_id', '=', 'users.id')
            ->leftJoin('user_currencies as duckets', fn (JoinClause $join) => $join->on('duckets.user_id', '=', 'users.id')->where('duckets.type', '=', PlusCurrencyRepository::DUCKETS))
            ->leftJoin('user_currencies as gotw_points', fn (JoinClause $join) => $join->on('gotw_points.user_id', '=', 'users.id')->where('gotw_points.type', '=', PlusCurrencyRepository::GOTW_POINTS))
            ->whereIntegerInRaw('users.id', $ids)
            ->get([
                'users.id', 'users.username', 'users.password', 'users.mail', 'users.credits', 'users.look', 'users.gender',
                'users.motto', 'users.account_created', 'users.last_online', 'users.online', 'users.ip_last', 'users.ip_reg',
                'users_settings.home_room', 'duckets.amount as duckets', 'gotw_points.amount as gotw_points',
            ]);
        $roles = self::activeRoles()->whereIntegerInRaw('user_roles.user_id', $ids)
            ->orderByDesc('roles.security_level')->orderByDesc('roles.weight')->orderBy('roles.id')
            ->get(['user_roles.user_id', 'user_roles.role_id', 'roles.security_level'])->unique('user_id')->keyBy('user_id');

        $attributes = [];
        foreach ($native as $row) {
            $role = $roles->get($row->id);
            $attributes[(int) $row->id] = [
                'username' => $row->username, 'password' => $row->password, 'mail' => $row->mail,
                'account_created' => $this->unix($row->account_created), 'last_online' => $this->unix($row->last_online),
                'motto' => $row->motto ?? '', 'look' => $row->look ?? '', 'gender' => $row->gender ?? 'M',
                'rank' => (int) ($role->security_level ?? 1), 'native_role_id' => $role->role_id ?? null,
                'credits' => (int) $row->credits, 'pixels' => (int) ($row->duckets ?? 0), 'points' => (int) ($row->gotw_points ?? 0),
                'online' => (bool) $row->online, 'ip_register' => $row->ip_reg ?? '', 'ip_current' => $row->ip_last ?? '',
                'home_room' => (int) ($row->home_room ?? 0),
            ];
        }

        return $attributes;
    }

    /** Role grants that have not expired, joined to their role. */
    public static function activeRoles(): Builder
    {
        return DB::table('user_roles')->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->where(fn (Builder $query) => $query->whereNull('user_roles.expires_at')->orWhere('user_roles.expires_at', '>', now('UTC')->format('Y-m-d H:i:s.u')));
    }

    /**
     * @param  Collection<int, stdClass>  $rows
     *
     * @return list<int>
     */
    private function ids(Collection $rows): array
    {
        return array_values($rows->map(fn (stdClass $row): int => (int) $row->id)->all());
    }

    private function ready(): bool
    {
        if (self::$ready || Cache::has(self::READY_KEY)) {
            return self::$ready = true;
        }
        if (! Schema::hasTable('website_users') || ! Schema::hasTable('users')) {
            return false;
        }
        Cache::forever(self::READY_KEY, true);

        return self::$ready = true;
    }

    private function unix(mixed $value): int
    {
        return $value === null ? 0 : Carbon::parse($value)->unix();
    }
}
