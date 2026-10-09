<?php

namespace App\Emulator\Drivers\Plus;

use Closure;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Works out which players a website_users query can match, so only their
 * projected rows have to be refreshed before it runs.
 *
 * Filters on identity columns (id, username, mail, IP addresses) and on staff
 * rank are translated onto PlusEMU's own tables. The candidates are the players
 * that match there plus the rows whose stale copy still matches here, so a
 * renamed player and the player who took their old name are both refreshed.
 * A query without such a filter returns null and reads the projection as is.
 */
final class PlusPlayerCandidates
{
    /** Upper bound for a translated filter that is not already limited. */
    private const MAX_CANDIDATES = 1000;

    /** website_users columns that mirror a PlusEMU users column one to one. */
    private const NATIVE_COLUMNS = [
        'username' => 'users.username',
        'mail' => 'users.mail',
        'ip_current' => 'users.ip_last',
        'ip_register' => 'users.ip_reg',
    ];

    /**
     * Equality filters match a handful of players at most, so only a pattern
     * search is held to the rows the query will actually return.
     *
     * @return list<int>|null
     */
    public function for(Builder $query): ?array
    {
        $keys = $this->keys($query);
        if ($keys !== null) {
            return $keys;
        }

        $filter = $this->group($query->wheres, $this->table($query));
        if ($filter === null) {
            return null;
        }

        $limit = $query->limit === null || ! $this->searchesPattern($query->wheres) ? self::MAX_CANDIDATES : min(self::MAX_CANDIDATES, $query->limit + (int) $query->offset);
        $native = DB::table('users')->where(fn (Builder $users) => $filter($users, true))->limit($limit)->pluck('users.id');
        $projected = DB::table('website_users')->where(fn (Builder $projection) => $filter($projection, false))->limit($limit)->pluck('website_users.id');

        return array_values($native->merge($projected)->map(fn (mixed $id): int => (int) $id)->unique()->all());
    }

    /**
     * Ids pinned by a top-level key constraint, which bounds the result on its own.
     *
     * @return list<int>|null
     */
    private function keys(Builder $query): ?array
    {
        foreach ($query->wheres as $where) {
            if (($where['boolean'] ?? 'and') !== 'and') {
                return null;
            }
        }

        foreach ($query->wheres as $where) {
            if ($this->column($where, $this->table($query)) !== 'id') {
                continue;
            }
            $values = match ($where['type']) {
                'Basic' => $where['operator'] === '=' ? [$where['value']] : null,
                'In', 'InRaw' => $where['values'],
                default => null,
            };
            $ids = $values === null ? null : $this->integers($values);
            if ($ids !== null) {
                return $ids;
            }
        }

        return null;
    }

    /**
     * Translate a where list into a filter for either table, or null when no
     * part of it narrows the candidates. Untranslatable conditions are dropped
     * from an AND chain, which only widens the candidate set; an OR branch
     * without any translatable condition could match anyone.
     *
     * @param  array<int, array<string, mixed>>  $wheres
     *
     * @return (Closure(Builder, bool): mixed)|null
     */
    private function group(array $wheres, string $from): ?Closure
    {
        $branches = [];
        foreach ($wheres as $where) {
            $boolean = $where['boolean'] ?? 'and';
            if ($boolean !== 'and' && $boolean !== 'or') {
                return null;
            }
            if ($branches === [] || $boolean === 'or') {
                $branches[] = [];
            }
            $condition = $this->condition($where, $from);
            if ($condition !== null) {
                $branches[array_key_last($branches)][] = $condition;
            }
        }

        if ($branches === [] || in_array([], $branches, true)) {
            return null;
        }

        return function (Builder $query, bool $native) use ($branches): void {
            foreach ($branches as $conditions) {
                $query->orWhere(function (Builder $branch) use ($conditions, $native): void {
                    foreach ($conditions as $condition) {
                        $condition($branch, $native);
                    }
                });
            }
        };
    }

    /**
     * @param  array<string, mixed>  $where
     *
     * @return (Closure(Builder, bool): mixed)|null
     */
    private function condition(array $where, string $from): ?Closure
    {
        if ($where['type'] === 'Nested') {
            $nested = $this->group($where['query']->wheres, $from);

            return $nested === null ? null : fn (Builder $query, bool $native) => $query->where(fn (Builder $inner) => $nested($inner, $native));
        }

        $column = $this->column($where, $from);
        if ($column === 'id' && in_array($where['type'], ['In', 'InRaw'], true)) {
            $ids = $this->integers($where['values']);

            return $ids === null ? null : fn (Builder $query, bool $native) => $query->whereIn($native ? 'users.id' : 'website_users.id', $ids);
        }

        if ($where['type'] !== 'Basic' || ! is_scalar($where['value'])) {
            return null;
        }

        $operator = strtolower((string) $where['operator']);
        $value = $where['value'];

        return match (true) {
            $column === 'id' && $operator === '=' && is_numeric($value) => fn (Builder $query, bool $native) => $query->where($native ? 'users.id' : 'website_users.id', (int) $value),
            $column !== null && isset(self::NATIVE_COLUMNS[$column]) && ($operator === '=' || ($operator === 'like' && $column === 'username')) => fn (Builder $query, bool $native) => $query->where($native ? self::NATIVE_COLUMNS[$column] : "website_users.{$column}", $operator, $value),
            $column === 'rank' && is_numeric($value) => $this->rank($operator, (int) $value),
            default => null,
        };
    }

    /** @param  array<int, array<string, mixed>>  $wheres */
    private function searchesPattern(array $wheres): bool
    {
        foreach ($wheres as $where) {
            if ($where['type'] === 'Nested' ? $this->searchesPattern($where['query']->wheres) : strtolower((string) ($where['operator'] ?? '')) === 'like') {
                return true;
            }
        }

        return false;
    }

    /**
     * Staff-rank filters only; a filter that every member satisfies would make
     * the whole player base a candidate. The projected rank is the highest
     * active role's security level, defaulting to 1.
     *
     * @return (Closure(Builder, bool): mixed)|null
     */
    private function rank(string $operator, int $level): ?Closure
    {
        $selective = match ($operator) {
            '>=', '=' => $level >= 2,
            '>' => $level >= 1,
            default => false,
        };
        if (! $selective) {
            return null;
        }

        return fn (Builder $query, bool $native) => $native
            ? $query->whereIn('users.id', PlusPlayerProjection::activeRoles()->select('user_roles.user_id')->where('roles.security_level', $operator, $level))
            : $query->where('website_users.rank', $operator, $level);
    }

    private function table(Builder $query): string
    {
        return is_string($query->from) ? $query->from : '';
    }

    /** @param  array<string, mixed>  $where */
    private function column(array $where, string $from): ?string
    {
        $column = $where['column'] ?? null;
        if (! is_string($column)) {
            return null;
        }
        if (! str_contains($column, '.')) {
            return $column;
        }
        [$table, $name] = explode('.', $column, 2);

        return $table === $from ? $name : null;
    }

    /**
     * @param  iterable<mixed>  $values
     *
     * @return list<int>|null
     */
    private function integers(iterable $values): ?array
    {
        $ids = [];
        foreach ($values as $value) {
            if ($value instanceof Expression || ! is_numeric($value)) {
                return null;
            }
            $ids[] = (int) $value;
        }

        return array_values(array_unique($ids));
    }
}
