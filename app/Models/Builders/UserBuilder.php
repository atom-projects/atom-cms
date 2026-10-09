<?php

namespace App\Models\Builders;

use App\Emulator\Data\PlayerSchema;
use App\Emulator\Emulator;
use App\Models\User;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Speaks Atom's attribute names on top of the active driver's player schema:
 * filters, sorts, selections and writes on an Atom attribute reach the native
 * column or the table the driver keeps it in, so callers never need to know
 * which emulator they run on.
 *
 * @extends Builder<User>
 */
class UserBuilder extends Builder
{
    /** {@inheritDoc} */
    public function applyScopes()
    {
        $builder = parent::applyScopes();
        $schema = $this->schema();

        if (! $schema->isEmpty()) {
            $this->translate($builder->getQuery(), $schema);
        }

        return $builder;
    }

    /** {@inheritDoc} */
    public function getModels($columns = ['*'])
    {
        $columns = $this->withKey($columns);
        $schema = $this->schema();

        if (! $schema->isEmpty()) {
            if ($this->query->columns === null || $this->query->columns === []) {
                $columns = $this->selection(Arr::wrap($columns), $schema);
            } else {
                $this->query->columns = $this->selection($this->query->columns, $schema);
            }
        }

        return parent::getModels($columns);
    }

    /** {@inheritDoc} */
    public function pluck($column, $key = null)
    {
        $schema = $this->schema();

        if ($schema->isEmpty() || ! is_string($column) || ! $this->isMapped($column, $schema)) {
            return parent::pluck($column, $key);
        }

        $name = (string) $this->attributeName($column);
        $columns = $key === null ? [$column] : [$column, $key];
        $values = $this->toBase()->get($this->selection($columns, $schema))->pluck($name, $key === null ? null : Str::afterLast((string) $key, '.'));

        // As Eloquent's own pluck does, cast values the model casts.
        if (! $this->model->hasCast($name) && ! $this->model->hasGetMutator($name)) {
            return $values;
        }

        return $values->map(fn (mixed $value): mixed => $this->model->newFromBuilder([$name => $value])->getAttribute($name));
    }

    /**
     * Atom compares player timestamps as unix seconds. Where the emulator keeps
     * a DATETIME, the compared value becomes one as the condition is added, so
     * the column's index stays usable.
     *
     * {@inheritDoc}
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if (is_string($column) && $this->isTimestamp($column)) {
            [$value, $operator] = $this->query->prepareValueAndOperator($value, $operator, func_num_args() === 2);
            $value = $this->nativeTimestamp($value);
        }

        return parent::where($column, $operator, $value, $boolean);
    }

    /**
     * Timestamps likewise, for a range.
     *
     * @param  Expression|string  $column
     * @param  iterable<mixed>  $values
     */
    public function whereBetween($column, iterable $values, string $boolean = 'and', bool $not = false): static
    {
        if (is_string($column) && $this->isTimestamp($column)) {
            $values = array_map($this->nativeTimestamp(...), is_array($values) ? $values : iterator_to_array($values));
        }

        $this->query->whereBetween($column, $values, $boolean, $not);

        return $this;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function update(array $values)
    {
        $native = $this->schema()->toNative($values);

        // Only attributes the driver keeps in other tables changed; its repositories write those.
        return $native === [] ? 0 : parent::update($native);
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  string|null  $sequence
     */
    public function insertGetId(array $values, $sequence = null): int
    {
        return (int) $this->toBase()->insertGetId($this->schema()->toNative($values), $sequence);
    }

    private function isTimestamp(string $column): bool
    {
        return in_array($this->attributeName($column), $this->schema()->timestamps, true);
    }

    private function nativeTimestamp(mixed $value): mixed
    {
        return is_numeric($value) ? Carbon::createFromTimestamp((int) $value, config('app.timezone'))->format('Y-m-d H:i:s') : $value;
    }

    private function schema(): PlayerSchema
    {
        return Emulator::playerSchema();
    }

    private function isMapped(string $column, PlayerSchema $schema): bool
    {
        $name = $this->attributeName($column);

        return $name !== null && (isset($schema->columns[$name]) || isset($schema->derived[$name]));
    }

    /**
     * The Atom attribute a column reference names, or null when it belongs to
     * another table.
     */
    private function attributeName(string $column): ?string
    {
        if (! str_contains($column, '.')) {
            return $column;
        }

        [$table, $name] = explode('.', $column, 2);

        return $table === $this->model->getTable() ? $name : null;
    }

    /**
     * The native column or expression an attribute reference resolves to.
     */
    private function resolve(mixed $column, PlayerSchema $schema): mixed
    {
        $name = is_string($column) ? $this->attributeName($column) : null;

        return match (true) {
            $name !== null && isset($schema->columns[$name]) => $this->model->qualifyColumn($schema->columns[$name]),
            $name !== null && isset($schema->derived[$name]) => $schema->derivedExpression($name),
            default => $column,
        };
    }

    /**
     * Rewrite a query's filters, sorts and groups from Atom attribute names
     * to the driver's schema. Applying it twice is harmless. Selections are
     * rewritten when models are fetched, after the key has been added.
     */
    private function translate(QueryBuilder $query, PlayerSchema $schema): void
    {
        foreach ($query->wheres as $index => $where) {
            if ($where['type'] === 'Nested') {
                $this->translate($where['query'], $schema);

                continue;
            }

            foreach (['column', 'first', 'second'] as $key) {
                if (isset($where[$key])) {
                    $query->wheres[$index][$key] = $this->resolve($where[$key], $schema);
                }
            }
        }

        foreach ($query->orders ?? [] as $index => $order) {
            if (isset($order['column'])) {
                $query->orders[$index]['column'] = $this->resolve($order['column'], $schema);
            }
        }

        foreach ($query->havings ?? [] as $index => $having) {
            if (isset($having['column'])) {
                $query->havings[$index]['column'] = $this->resolve($having['column'], $schema);
            }
        }

        $query->groups = $query->groups === null ? null : array_map(fn (mixed $group): mixed => $this->resolve($group, $schema), $query->groups);
    }

    /**
     * Select list in native terms. A whole-row selection also selects every
     * derived attribute, after the row so a native column of the same name
     * is overridden; renamed columns are renamed back as rows are hydrated.
     *
     * @param  array<int, mixed>  $columns
     *
     * @return array<int, mixed>
     */
    private function selection(array $columns, PlayerSchema $schema): array
    {
        $table = $this->model->getTable();
        $selected = [];
        $wholeRow = false;

        foreach ($columns as $column) {
            if ($column === '*' || $column === "{$table}.*") {
                $selected[] = "{$table}.*";
                $wholeRow = true;

                continue;
            }

            $name = is_string($column) ? $this->attributeName($column) : null;
            $selected[] = match (true) {
                $name !== null && isset($schema->columns[$name]) => $this->model->qualifyColumn($schema->columns[$name]) . " as {$name}",
                $name !== null && isset($schema->derived[$name]) => $schema->derivedExpression($name, aliased: true),
                default => $column,
            };
        }

        if ($wholeRow) {
            foreach (array_keys($schema->derived) as $name) {
                if (! $this->selectsAlias($selected, $name)) {
                    $selected[] = $schema->derivedExpression($name, aliased: true);
                }
            }
        }

        return $selected;
    }

    /** @param  array<int, mixed>  $columns */
    private function selectsAlias(array $columns, string $name): bool
    {
        foreach ($columns as $column) {
            if ($column instanceof Expression && str_ends_with((string) $column->getValue($this->query->getGrammar()), " as `{$name}`")) {
                return true;
            }
        }

        return false;
    }

    /**
     * Keep the primary key in the result set.
     *
     * A query that selects a column subset without the key yields models that
     * cannot load relations or be saved back. The key is
     * hidden from serialisation, so adding it changes nothing a caller can
     * observe.
     *
     * @param  array<int, Expression|string>|Expression|string  $columns
     *
     * @return array<int, Expression|string>|Expression|string
     */
    private function withKey($columns)
    {
        $selected = $this->query->columns;
        $requested = Arr::wrap($columns);

        if ($this->selects($selected ?: $requested, $this->model->getKeyName())) {
            return $columns;
        }

        if ($selected !== null && $selected !== []) {
            $this->query->addSelect($this->model->qualifyColumn($this->model->getKeyName()));

            return $columns;
        }

        return [...$requested, $this->model->qualifyColumn($this->model->getKeyName())];
    }

    /**
     * Whether a column list already covers the given column, allowing for
     * table-qualified names and wildcards. Raw expressions are opaque, so
     * they are assumed to cover it rather than risk a duplicate select.
     *
     * @param  array<int, mixed>  $columns
     */
    private function selects(array $columns, string $column): bool
    {
        foreach ($columns as $selected) {
            if ($selected instanceof Expression) {
                return true;
            }

            if (! is_string($selected)) {
                continue;
            }

            $name = Str::afterLast($selected, '.');

            if ($name === '*' || $name === $column) {
                return true;
            }
        }

        return $columns === [];
    }
}
