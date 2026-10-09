<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The mirror of tests/Feature/Misc/TestDatabaseIsolationTest: this suite must
 * be on Ada's own database, not the Arcturus one.
 */
test('the ada suite runs against the ada database', function () {
    expect(DB::connection()->getDatabaseName())->toBe(env('DB_ADA_DATABASE', 'testing_ada'));
});

test('the ada database holds the ef schema with atom mapped onto players', function () {
    expect(Schema::hasTable('players'))->toBeTrue('The Ada suite is pointed at an Arcturus database')
        ->and(Schema::hasTable('player_data'))->toBeTrue()
        ->and(Schema::hasTable('roles'))->toBeTrue()
        // Atom's user is Ada's player; it keeps no table of its own.
        ->and(Schema::hasTable('users'))->toBeFalse()
        ->and(Schema::hasColumns('players', ['two_factor_secret', 'referral_code', 'website_remember_token']))->toBeTrue();
});

test('the ada suite resolves the ada driver', function () {
    expect(config('emulator.driver'))->toBe('ada');
});
