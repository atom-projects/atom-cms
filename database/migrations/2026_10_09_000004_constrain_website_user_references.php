<?php

use App\Emulator\Emulator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Every remaining website table that stores a player id now references the
 * emulator's player key with its exact type, and goes with the player the way the
 * rest of a player's website data already does. Referral claims stop blocking
 * a player's deletion. PayPal transactions keep restricting it on purpose:
 * payment records outlive accounts.
 *
 * Rows whose player no longer exists are deleted first. Rolling back drops
 * the indexes MariaDB created for the new keys, except on the idempotency
 * keys, whose unique index already covered user_id.
 */
return new class extends Migration
{
    /** Table => player id column that gains a cascading foreign key. */
    private const REFERENCES = [
        'referrals' => 'referred_user_id',
        'website_api_idempotency_keys' => 'user_id',
        'website_beta_codes' => 'user_id',
    ];

    public function up(): void
    {
        $players = Emulator::playerSchema()->table;

        foreach (self::REFERENCES as $table => $column) {
            DB::table($table)
                ->whereNotNull($column)
                ->whereNotExists(fn ($query) => $query->selectRaw('1')->from($players)->whereColumn("{$players}.id", "{$table}.{$column}"))
                ->delete();
        }

        Schema::table('referrals', function (Blueprint $table): void {
            $table->playerId('referred_user_id')->change();
        });

        Schema::table('website_api_idempotency_keys', function (Blueprint $table): void {
            $table->playerId('user_id')->change();
        });

        Schema::table('website_beta_codes', function (Blueprint $table): void {
            $table->playerId('user_id')->nullable()->change();
        });

        foreach (self::REFERENCES as $table => $column) {
            Schema::table($table, function (Blueprint $blueprint) use ($column): void {
                $blueprint->foreignPlayer($column)->cascadeOnDelete();
            });
        }

        Schema::table('claimed_referral_logs', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->foreignPlayer('user_id')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('claimed_referral_logs', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->foreignPlayer('user_id');
        });

        foreach (self::REFERENCES as $table => $column) {
            Schema::table($table, function (Blueprint $blueprint) use ($table, $column): void {
                $blueprint->dropForeign([$column]);

                if ($table !== 'website_api_idempotency_keys') {
                    $blueprint->dropIndex("{$table}_{$column}_foreign");
                }
            });
        }

        Schema::table('website_beta_codes', function (Blueprint $table): void {
            $table->integer('user_id')->nullable()->change();
        });

        Schema::table('website_api_idempotency_keys', function (Blueprint $table): void {
            $table->unsignedInteger('user_id')->change();
        });

        Schema::table('referrals', function (Blueprint $table): void {
            $table->unsignedBigInteger('referred_user_id')->change();
        });
    }
};
