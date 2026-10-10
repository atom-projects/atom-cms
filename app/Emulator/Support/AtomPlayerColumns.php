<?php

namespace App\Emulator\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The per-player columns Atom keeps that no emulator has: two-factor secrets,
 * the remember-me token, staff visibility and team, referral code and website
 * balance. They live on the emulator's own player row, the way Arcturus has
 * always kept them on its users row.
 */
final class AtomPlayerColumns
{
    /**
     * Column => definition, in the order they are added.
     *
     * @return array<string, callable(Blueprint): mixed>
     */
    public static function definitions(): array
    {
        return [
            'two_factor_secret' => fn (Blueprint $table) => $table->text('two_factor_secret')->nullable(),
            'two_factor_recovery_codes' => fn (Blueprint $table) => $table->text('two_factor_recovery_codes')->nullable(),
            'two_factor_confirmed_at' => fn (Blueprint $table) => $table->timestamp('two_factor_confirmed_at')->nullable(),
            'last_login' => fn (Blueprint $table) => $table->unsignedInteger('last_login')->default(0),
            'hidden_staff' => fn (Blueprint $table) => $table->boolean('hidden_staff')->default(false),
            'referral_code' => fn (Blueprint $table) => $table->string('referral_code')->nullable()->unique(),
            'website_balance' => fn (Blueprint $table) => $table->unsignedBigInteger('website_balance')->default(0),
            'team_id' => fn (Blueprint $table) => $table->foreignId('team_id')->nullable()->constrained('website_teams')->nullOnDelete(),
            'website_remember_token' => fn (Blueprint $table) => $table->string('website_remember_token', 100)->nullable(),
        ];
    }

    /** @return list<string> */
    public static function names(): array
    {
        return array_keys(self::definitions());
    }

    /**
     * Add the columns the table does not have yet.
     *
     * @param  list<string>  $except  Columns the emulator already keeps elsewhere.
     */
    public static function add(string $table, array $except = []): void
    {
        $missing = array_filter(
            self::definitions(),
            fn (string $column): bool => ! in_array($column, $except, true) && ! Schema::hasColumn($table, $column),
            ARRAY_FILTER_USE_KEY,
        );

        if ($missing !== []) {
            Schema::table($table, function (Blueprint $blueprint) use ($missing): void {
                foreach ($missing as $define) {
                    $define($blueprint);
                }
            });
        }
    }

    /**
     * Copy the values of the columns both tables have from $from's row with
     * the same id onto $to's.
     *
     * @param  list<string>  $except
     */
    public static function copy(string $from, string $to, array $except = []): void
    {
        $columns = array_filter(
            self::names(),
            fn (string $column): bool => ! in_array($column, $except, true) && Schema::hasColumn($from, $column) && Schema::hasColumn($to, $column),
        );

        if ($columns === []) {
            return;
        }

        $grammar = DB::getQueryGrammar();
        DB::update(sprintf(
            'UPDATE %1$s INNER JOIN %2$s ON %2$s.id = %1$s.id SET %3$s',
            $grammar->wrapTable($to),
            $grammar->wrapTable($from),
            implode(', ', array_map(fn (string $column): string => sprintf('%s = %s', $grammar->wrap("{$to}.{$column}"), $grammar->wrap("{$from}.{$column}")), $columns)),
        ));
    }

    /**
     * Drop the columns again, with the key and index the definitions created.
     *
     * @param  list<string>  $except
     */
    public static function drop(string $table, array $except = []): void
    {
        $present = array_values(array_filter(self::names(), fn (string $column): bool => ! in_array($column, $except, true) && Schema::hasColumn($table, $column)));

        if ($present === []) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($table, $present): void {
            if (in_array('team_id', $present, true)) {
                $blueprint->dropForeign("{$table}_team_id_foreign");
            }
            if (in_array('referral_code', $present, true)) {
                $blueprint->dropUnique("{$table}_referral_code_unique");
            }
            $blueprint->dropColumn($present);
        });
    }
}
