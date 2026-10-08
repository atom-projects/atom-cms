<?php

namespace App\Emulator\Drivers\Plus;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class PlusPlayerProjection
{
    public function synchronize(): void
    {
        if (config('emulator.driver') !== 'plus' || ! Schema::hasTable('website_users') || ! Schema::hasTable('users')) {
            return;
        }

        DB::statement(<<<'SQL'
            INSERT INTO website_users
                (id, username, password, mail, account_created, last_online, motto, look, gender, rank, native_role_id, credits, pixels, points, online, ip_register, ip_current, home_room)
            SELECT users.id, users.username, users.password, users.mail,
                COALESCE(UNIX_TIMESTAMP(users.account_created), 0), COALESCE(UNIX_TIMESTAMP(users.last_online), 0),
                COALESCE(users.motto, ''), COALESCE(users.look, ''), COALESCE(users.gender, 'M'),
                COALESCE(active_roles.security_level, 1), active_roles.role_id, users.credits, COALESCE(duckets.amount, 0), COALESCE(gotw_points.amount, 0),
                users.online, COALESCE(users.ip_reg, ''), COALESCE(users.ip_last, ''), COALESCE(users_settings.home_room, 0)
            FROM users
            LEFT JOIN users_settings ON users_settings.user_id = users.id
            LEFT JOIN user_currencies AS duckets ON duckets.user_id = users.id AND duckets.type = 0
            LEFT JOIN user_currencies AS gotw_points ON gotw_points.user_id = users.id AND gotw_points.type = 103
            LEFT JOIN (
                SELECT user_roles.user_id, MAX(roles.security_level) AS security_level,
                    CAST(SUBSTRING_INDEX(GROUP_CONCAT(roles.id ORDER BY roles.security_level DESC, roles.weight DESC, roles.id), ',', 1) AS UNSIGNED) AS role_id
                FROM user_roles
                INNER JOIN roles ON roles.id = user_roles.role_id
                WHERE user_roles.expires_at IS NULL OR user_roles.expires_at > UTC_TIMESTAMP(6)
                GROUP BY user_roles.user_id
            ) AS active_roles ON active_roles.user_id = users.id
            ON DUPLICATE KEY UPDATE
                username = VALUES(username), password = VALUES(password), mail = VALUES(mail),
                account_created = VALUES(account_created), last_online = VALUES(last_online), motto = VALUES(motto),
                look = VALUES(look), gender = VALUES(gender), rank = VALUES(rank), native_role_id = VALUES(native_role_id), credits = VALUES(credits),
                pixels = VALUES(pixels), points = VALUES(points), online = VALUES(online),
                ip_register = VALUES(ip_register), ip_current = VALUES(ip_current), home_room = VALUES(home_room)
            SQL);
    }

    public function import(string $username): ?User
    {
        if (config('emulator.driver') !== 'plus') {
            return null;
        }

        $native = DB::table('users')->leftJoin('users_settings', 'users_settings.user_id', '=', 'users.id')
            ->where('users.username', $username)->first(['users.*', 'users_settings.home_room']);

        if ($native === null) {
            return null;
        }

        $activeRole = DB::table('user_roles')->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('user_id', $native->id)
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now('UTC')->format('Y-m-d H:i:s.u')))
            ->orderByDesc('roles.security_level')->orderByDesc('roles.weight')->orderBy('roles.id')
            ->first(['roles.id', 'roles.security_level']);

        DB::table('website_users')->insertOrIgnore([
            'id' => $native->id, 'username' => $native->username, 'password' => $native->password,
            'mail' => $native->mail, 'account_created' => $this->unix($native->account_created),
            'last_online' => $this->unix($native->last_online), 'motto' => $native->motto ?? '',
            'look' => $native->look ?? '', 'gender' => $native->gender ?? 'M', 'rank' => (int) ($activeRole->security_level ?? 1),
            'native_role_id' => $activeRole->id ?? null,
            'credits' => (int) $native->credits, 'pixels' => $this->currency((int) $native->id, PlusCurrencyRepository::DUCKETS),
            'points' => $this->currency((int) $native->id, PlusCurrencyRepository::GOTW_POINTS), 'online' => (bool) $native->online,
            'ip_register' => $native->ip_reg ?? '', 'ip_current' => $native->ip_last ?? '',
            'home_room' => (int) ($native->home_room ?? 0),
        ]);

        return User::query()->find((int) $native->id);
    }

    private function currency(int $userId, int $type): int
    {
        return (int) (DB::table('user_currencies')->where('user_id', $userId)->where('type', $type)->value('amount') ?? 0);
    }

    private function unix(mixed $value): int
    {
        return $value === null ? 0 : Carbon::parse($value)->unix();
    }
}
