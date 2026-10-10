<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Atom resolves players by username on every sign-in and counts accounts per
 * address on every registration. Ada indexes neither, and Atom's own users
 * table, which kept usernames unique, is gone, so the guarantee moves onto
 * players. Named apart from EF's ix_ prefix, as in 2026_07_25.
 */
return new class extends Migration
{
    /** Index name => [table, column definition, unique]. */
    private const INDEXES = [
        'atom_players_username_unique' => ['players', '`username`', true],
        'atom_player_website_data_initial_ip_index' => ['player_website_data', '`initial_ip`(45)', false],
        'atom_player_website_data_last_ip_index' => ['player_website_data', '`last_ip`(45)', false],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $name => [$table, $column, $unique]) {
            if (! $this->exists($table, $name)) {
                DB::statement(sprintf('CREATE %sINDEX `%s` ON `%s` (%s)', $unique ? 'UNIQUE ' : '', $name, $table, $column));
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $name => [$table]) {
            if ($this->exists($table, $name)) {
                DB::statement(sprintf('DROP INDEX `%s` ON `%s`', $name, $table));
            }
        }
    }

    private function exists(string $table, string $name): bool
    {
        return DB::table('information_schema.STATISTICS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $name)
            ->exists();
    }
};
