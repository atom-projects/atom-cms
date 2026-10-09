<?php

namespace App\Emulator\Data;

use App\Casts\UnixDateTime;
use Illuminate\Contracts\Database\Query\Expression as ExpressionContract;
use Illuminate\Database\Query\Expression;
use InvalidArgumentException;

/**
 * Where an emulator keeps the player attributes Atom's User model exposes.
 *
 * Atom's attribute names are Arcturus' users columns. A driver describes its
 * own player table, the type of its key, and how every attribute reaches it:
 * the same column, a column of another name on the player row, or a value the
 * emulator keeps in another table. The User model and its query builder
 * translate reads, filters, sorts and writes accordingly, so no driver keeps
 * a copy of its players in a table of Atom's shape.
 */
final readonly class PlayerSchema
{
    /**
     * @param  string  $table  The emulator's player table; Atom's own per-player columns are added to it.
     * @param  'integer'|'bigInteger'  $keyType  Column type of the player key, for Atom's foreign keys to it.
     * @param  array<string, string>  $columns  Attribute => player-table column of another name.
     * @param  array<string, string>  $derived  Attribute => SQL for a value kept in another table, correlated
     *                                          on the player row and free of bindings. Never written to the
     *                                          player row; the driver's PlayerRepository persists changes.
     * @param  array<string, string>  $derivedCasts  Attribute => cast for a derived value that is not an integer.
     * @param  list<string>  $timestamps  Player-row attributes stored as DATETIME but exposed as unix seconds.
     * @param  list<string>  $hidden  Player-row columns that must never be serialized.
     */
    public function __construct(
        public string $table = 'users',
        public string $keyType = 'integer',
        public array $columns = [],
        public array $derived = [],
        public array $derivedCasts = [],
        public array $timestamps = [],
        public array $hidden = [],
    ) {}

    public function isEmpty(): bool
    {
        return $this->columns === [] && $this->derived === [] && $this->timestamps === [];
    }

    /**
     * Casts for the attributes this schema maps: timestamps to unix seconds,
     * and derived values, which MariaDB returns as strings, to integers unless
     * the driver gives them another type.
     *
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            ...array_fill_keys(array_keys($this->derived), 'integer'),
            ...$this->derivedCasts,
            ...array_fill_keys($this->timestamps, UnixDateTime::class),
        ];
    }

    /**
     * The player-table column an attribute is stored in.
     *
     * @throws InvalidArgumentException When the emulator keeps it in another table.
     */
    public function column(string $attribute): string
    {
        if (isset($this->derived[$attribute])) {
            throw new InvalidArgumentException("{$attribute} is not stored on the player row.");
        }

        return $this->columns[$attribute] ?? $attribute;
    }

    /**
     * A derived attribute's SQL as a query expression, optionally aliased to
     * the attribute for a select list.
     */
    public function derivedExpression(string $attribute, bool $aliased = false): ExpressionContract
    {
        if (! isset($this->derived[$attribute])) {
            throw new InvalidArgumentException("{$attribute} is not a derived player attribute.");
        }

        $sql = $aliased ? "({$this->derived[$attribute]}) as `{$attribute}`" : "({$this->derived[$attribute]})";

        // Drivers author the SQL; nothing in it comes from a request.
        return new Expression($sql); // @phpstan-ignore argument.type
    }

    /**
     * A fetched row with native column names replaced by Atom's.
     *
     * @param  array<string, mixed>  $row
     *
     * @return array<string, mixed>
     */
    public function fromNative(array $row): array
    {
        foreach ($this->columns as $attribute => $column) {
            if (array_key_exists($column, $row)) {
                $row[$attribute] = $row[$column];
                unset($row[$column]);
            }
        }

        return $row;
    }

    /**
     * Values to write to the users row: renamed to native columns, without
     * the attributes the driver keeps elsewhere.
     *
     * @param  array<string, mixed>  $values
     *
     * @return array<string, mixed>
     */
    public function toNative(array $values): array
    {
        $native = [];
        foreach ($values as $attribute => $value) {
            if (! isset($this->derived[$attribute])) {
                $native[$this->columns[$attribute] ?? $attribute] = $value;
            }
        }

        return $native;
    }
}
