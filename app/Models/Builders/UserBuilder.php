<?php

namespace App\Models\Builders;

use App\Emulator\Contracts\PlayerRepository;
use App\Emulator\Contracts\PreparesPlayerQueries;
use App\Models\User;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Gives the active emulator driver one hook per user query instead of one per
 * user. Drivers that mirror emulator state into Atom's users table refresh the
 * whole result set here; drivers that own the table outright do nothing.
 *
 * @extends Builder<User>
 */
class UserBuilder extends Builder
{
    /** {@inheritDoc} */
    public function setModel(Model $model)
    {
        parent::setModel($model);
        $players = app(PlayerRepository::class);
        if ($players instanceof PreparesPlayerQueries) {
            $players->prepareQuery($this);
        }

        return $this;
    }

    /** {@inheritDoc} */
    public function getModels($columns = ['*'])
    {
        $models = parent::getModels($this->withKey($columns));

        if ($models !== []) {
            app(PlayerRepository::class)->hydrateMany($models);
        }

        return $models;
    }

    /**
     * Keep the primary key in the result set.
     *
     * A query that selects a column subset without the key cannot be matched
     * back to the emulator, and would silently serve whatever the mirrored
     * users row happens to hold. The key is hidden from serialisation, so
     * adding it changes nothing a caller can observe.
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
