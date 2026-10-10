<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for lookups that scanned their table: reset links are found by
 * token, open-position deletes clear applications by rank, and profile pages
 * read a player's placed items and newest guestbook posts.
 *
 * Reset tokens are SHA-256 hashes of random UUIDs, so a repeated token can
 * only be corrupt data. Neither copy is trustworthy and both are removed
 * before the token becomes the key; the player can request a new link.
 */
return new class extends Migration
{
    public function up(): void
    {
        $repeated = DB::table('website_password_resets')->groupBy('token')->havingRaw('COUNT(*) > 1')->pluck('token');
        DB::table('website_password_resets')->whereIn('token', $repeated)->delete();

        Schema::table('website_password_resets', function (Blueprint $table): void {
            $table->primary('token');
        });

        Schema::table('website_staff_applications', function (Blueprint $table): void {
            $table->index('rank_id');
        });

        Schema::table('user_home_items', function (Blueprint $table): void {
            $table->index(['user_id', 'placed']);
        });

        Schema::table('user_home_messages', function (Blueprint $table): void {
            $table->index(['recipient_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        $this->dropIndexKeepingForeignKey('user_home_messages', ['recipient_user_id', 'created_at']);
        $this->dropIndexKeepingForeignKey('user_home_items', ['user_id', 'placed']);
        $this->dropIndexKeepingForeignKey('website_staff_applications', ['rank_id']);

        Schema::table('website_password_resets', function (Blueprint $table): void {
            $table->dropPrimary();
        });
    }

    /**
     * MariaDB drops the index it created for a foreign key once another index
     * leads with the key's column, so that column gets its own index back
     * before this one goes.
     *
     * @param  list<string>  $columns
     */
    private function dropIndexKeepingForeignKey(string $table, array $columns): void
    {
        $column = $columns[0];
        $keyed = collect(Schema::getForeignKeys($table))->contains(fn (array $key): bool => $key['columns'] === [$column]);
        $covered = collect(Schema::getIndexes($table))->contains(fn (array $index): bool => $index['columns'] !== $columns && ($index['columns'][0] ?? null) === $column);

        Schema::table($table, function (Blueprint $blueprint) use ($table, $columns, $column, $keyed, $covered): void {
            if ($keyed && ! $covered) {
                $blueprint->index($column, "{$table}_{$column}_foreign");
            }

            $blueprint->dropIndex($columns);
        });
    }
};
