<?php

use App\Emulator\Contracts\RankRepository;
use App\Models\Miscellaneous\WebsiteInstallation;
use App\Models\User;
use Database\Seeders\WebsiteMaintenanceTasksSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

it('creates the explicitly named headless administrator through Plus identity rules', function () {
    DB::table('roles')->insert([
        ['id' => 40, 'slug' => 'member', 'name' => 'Member', 'description' => '', 'weight' => 1, 'security_level' => 1, 'badge_code' => '', 'is_staff' => false, 'is_hidden' => false, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 3, 'slug' => 'owner', 'name' => 'Owner', 'description' => '', 'weight' => 999, 'security_level' => 7, 'badge_code' => '', 'is_staff' => true, 'is_hidden' => false, 'created_at' => now(), 'updated_at' => now()],
    ]);
    WebsiteInstallation::query()->delete();
    Cache::flush();
    $previousEmail = getenv('ATOM_ADMIN_EMAIL');
    $previousPassword = getenv('ATOM_ADMIN_PASSWORD');
    putenv('ATOM_ADMIN_EMAIL=plus-operator@example.test');
    putenv('ATOM_ADMIN_PASSWORD=Example-password-42!');

    try {
        $this->artisan('atom:setup', ['--complete' => true, '--admin' => 'PlusOperator', '--no-interaction' => true])
            ->assertSuccessful();
        $admin = User::where('username', 'PlusOperator')->sole();
        expect($admin->rank)->toBe(app(RankRepository::class)->highestRank())
            ->and(Hash::check('Example-password-42!', $admin->password))->toBeTrue()
            ->and(DB::table('users')->where('id', $admin->id)->value('password'))->toStartWith('$argon2id$')
            ->and(DB::table('user_roles')->where('user_id', $admin->id)->value('role_id'))->toBe(3)
            ->and(WebsiteInstallation::where('completed', true)->exists())->toBeTrue();
    } finally {
        putenv($previousEmail === false ? 'ATOM_ADMIN_EMAIL' : 'ATOM_ADMIN_EMAIL=' . $previousEmail);
        putenv($previousPassword === false ? 'ATOM_ADMIN_PASSWORD' : 'ATOM_ADMIN_PASSWORD=' . $previousPassword);
    }
});

it('seeds maintenance tasks through the configured Plus rank repository', function () {
    DB::table('roles')->insert([
        ['id' => 40, 'slug' => 'member', 'name' => 'Member', 'description' => '', 'weight' => 1, 'security_level' => 1, 'badge_code' => '', 'is_staff' => false, 'is_hidden' => false, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 3, 'slug' => 'owner', 'name' => 'Owner', 'description' => '', 'weight' => 999, 'security_level' => 7, 'badge_code' => '', 'is_staff' => true, 'is_hidden' => false, 'created_at' => now(), 'updated_at' => now()],
    ]);

    $this->seed(WebsiteMaintenanceTasksSeeder::class);

    $admin = User::where('username', 'Admin')->sole();
    expect($admin->rank)->toBe(7)
        ->and(DB::table('users')->where('id', $admin->id)->value('password'))->toStartWith('$argon2id$')
        ->and(DB::table('website_maintenance_tasks')->where('user_id', $admin->id)->exists())->toBeTrue();
});

it('returns native Plus staff grouped by their actual active role', function () {
    DB::table('roles')->insert([
        ['id' => 1, 'slug' => 'user', 'name' => 'User', 'description' => '', 'weight' => 1, 'security_level' => 1, 'badge_code' => '', 'is_staff' => false, 'is_hidden' => false, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 7, 'slug' => 'community', 'name' => 'Community Leader', 'description' => '', 'weight' => 700, 'security_level' => 7, 'badge_code' => '', 'is_staff' => true, 'is_hidden' => false, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 9, 'slug' => 'owner', 'name' => 'Owner', 'description' => '', 'weight' => 900, 'security_level' => 7, 'badge_code' => '', 'is_staff' => true, 'is_hidden' => false, 'created_at' => now(), 'updated_at' => now()],
    ]);
    $owner = User::factory()->create();
    DB::table('user_roles')->where('user_id', $owner->id)->delete();
    DB::table('user_roles')->insert(['user_id' => $owner->id, 'role_id' => 9, 'created_at' => now()]);
    Sanctum::actingAs($owner);

    $response = $this->getJson('/api/v1/staff')->assertOk();

    expect($response->json('data.0.name'))->toBe('Owner')
        ->and($response->json('data.0.users.0.username'))->toBe($owner->username)
        ->and($response->json('data.1.users'))->toBe([]);
});
