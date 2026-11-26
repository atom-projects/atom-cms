<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    installHotel();
});

test('a user change is logged with its attribute changes', function () {
    $user = User::factory()->create(['motto' => 'before']);

    $user->update(['motto' => 'after']);

    $activity = Activity::query()->where('subject_id', $user->id)->where('event', 'updated')->latest('id')->firstOrFail();

    expect($activity->attribute_changes->get('attributes'))->toMatchArray(['motto' => 'after'])
        ->and($activity->attribute_changes->get('old'))->toMatchArray(['motto' => 'before']);
});

test('the upgrade moves old tracked changes out of properties', function () {
    expect(Schema::hasColumn('activity_log', 'batch_uuid'))->toBeFalse();

    $id = DB::table('activity_log')->insertGetId([
        'description' => 'updated',
        'subject_type' => User::class,
        'subject_id' => '1',
        'properties' => json_encode(['attributes' => ['motto' => 'after'], 'old' => ['motto' => 'before'], 'type' => 'credits']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $migration = require database_path('migrations/2026_11_26_000000_upgrade_activity_log_table_for_activitylog_v5.php');
    $migration->up();

    $row = DB::table('activity_log')->find($id);

    expect(json_decode($row->attribute_changes, true))->toBe(['attributes' => ['motto' => 'after'], 'old' => ['motto' => 'before']])
        ->and(json_decode($row->properties, true))->toBe(['type' => 'credits']);
});
