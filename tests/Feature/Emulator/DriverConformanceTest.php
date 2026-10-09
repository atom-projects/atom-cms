<?php

use App\Contracts\Rcon;
use App\Emulator\Contracts\EmulatorDriver;
use App\Emulator\Contracts\EmulatorInstaller;
use App\Emulator\Contracts\RankRepository;
use App\Emulator\Data\Feature;
use App\Emulator\Data\SchemaFeature;
use App\Emulator\Emulator;
use App\Emulator\EmulatorManager;
use App\Services\AfterCommitRcon;
use App\Services\UnsupportedRcon;
use Tests\Fixtures\ThirdEmulatorDriver;

dataset('emulator drivers', ['arcturus', 'ada']);

test('registered drivers satisfy the complete driver contract', function (string $key) {
    $manager = app(EmulatorManager::class);
    $driver = $manager->driver($key);

    expect($driver)->toBeInstanceOf(EmulatorDriver::class)
        ->and($driver->key())->toBe($key)
        ->and($driver->label())->not->toBeEmpty()
        ->and(array_keys($driver->bindings()))->toEqualCanonicalizing(EmulatorManager::REPOSITORY_CONTRACTS)
        ->and($driver->installer())->toBeInstanceOf(EmulatorInstaller::class)
        ->and($driver->rcon())->toBeInstanceOf(Rcon::class)
        ->and($driver->features())->each->toBeInstanceOf(Feature::class)
        ->and($driver->schemaFeatures())->each->toBeInstanceOf(SchemaFeature::class)
        ->and($driver->playerConstraints()->usernameLength)->toBeGreaterThan(0)
        ->and($driver->playerConstraints()->emailLength)->toBeGreaterThan(0)
        ->and($driver->playerConstraints()->mottoLength)->toBeGreaterThan(0)
        ->and($driver->playerConstraints()->figureLength)->toBeGreaterThan(0);

    foreach ($driver->bindings() as $contract => $implementation) {
        // Resolving each one proves the class is concrete: a repository that
        // misses a contract method cannot be constructed.
        expect(is_a($implementation, $contract, true))->toBeTrue()
            ->and(app($implementation))->toBeInstanceOf($contract);
    }

    foreach ($driver->migrationPaths() as $path) {
        expect($path)->toBeDirectory();
    }

    foreach ($driver->userRelationManagers() as $relationManager) {
        expect(class_exists($relationManager))->toBeTrue();
    }
})->with('emulator drivers');

test('the registry exposes driver labels and ada owns its migrations', function () {
    $manager = app(EmulatorManager::class);
    $migrationNames = collect($manager->driver('ada')->migrationPaths())
        ->flatMap(fn (string $path) => glob($path . '/*.php') ?: [])
        ->map(fn (string $path) => basename($path));

    expect($manager->choices())->toBe([
        'arcturus' => 'Arcturus',
        'ada' => 'Ada',
        'plus' => 'PlusEMU',
    ])->and($migrationNames)->toContain(
        '2026_07_23_000000_use_ada_roles_for_staff_applications.php',
        '2026_07_24_000000_use_ada_roles_for_shop_articles.php',
        '2026_10_09_000000_map_atom_users_onto_ada_players.php',
    );
});

test('every driver reports player constraints its own schema can store', function (string $key) {
    $constraints = app(EmulatorManager::class)->driver($key)->playerConstraints();

    // Validation is generated from these, so a value wider than the emulator
    // column silently truncates on write.
    expect($constraints->usernameLength)->toBeGreaterThan(2)
        ->and($constraints->emailLength)->toBeGreaterThanOrEqual($constraints->usernameLength)
        ->and($constraints->mottoLength)->toBeGreaterThan(0)
        ->and($constraints->figureLength)->toBeGreaterThan($constraints->mottoLength);
})->with('emulator drivers');

test('drivers that replace a rank table also override the cms foreign keys', function () {
    // Atom's own migrations point website tables at the Arcturus permissions
    // table. A driver with a different rank table has to ship an override, or
    // installing it leaves a foreign key aimed at a table it does not own.
    $constrained = collect(glob(database_path('migrations/*.php')) ?: [])
        ->filter(fn (string $path) => str_contains((string) file_get_contents($path), "on('permissions')"))
        ->map(fn (string $path) => basename($path))
        ->values();

    $overrides = collect(app(EmulatorManager::class)->driver('ada')->migrationPaths())
        ->flatMap(fn (string $path) => glob($path . '/*.php') ?: [])
        ->map(fn (string $path) => (string) file_get_contents($path))
        ->implode("\n");

    expect($constrained)->not->toBeEmpty()
        ->and($overrides)->toContain("on('roles')");

    foreach (['website_staff_applications', 'website_shop_articles'] as $table) {
        expect($overrides)->toContain($table);
    }
});

test('installers recognise an arcturus database', function () {
    // Ada must refuse: without its EF tables there is nothing for Atom to sit
    // on. Arcturus must recognise its own and leave them untouched rather than
    // import the dump over a populated hotel. The mirror of this - an Ada
    // database refusing the Arcturus installer - is in tests/Ada.
    $installers = app(EmulatorManager::class);

    expect($installers->driver('ada')->installer()->prepare(silentCommand()))->toBeFalse()
        ->and($installers->driver('arcturus')->installer()->prepare(silentCommand()))->toBeTrue();
});

test('a third driver is registered without changing shared runtime code', function () {
    config(['emulator.drivers.third' => ThirdEmulatorDriver::class]);

    $driver = app(EmulatorManager::class)->select('third');

    expect($driver)->toBeInstanceOf(ThirdEmulatorDriver::class)
        ->and(Emulator::driver())->toBe('third')
        ->and(Emulator::constraints()->emailLength)->toBe(100)
        ->and(Emulator::supports(Feature::RareValues))->toBeFalse()
        ->and(app(RankRepository::class))->toBeInstanceOf(
            $driver->bindings()[RankRepository::class],
        );
});

test('switching drivers also refreshes the driver owned rcon service', function () {
    $arcturusRcon = app(Rcon::class);

    app(EmulatorManager::class)->select('ada');

    $adaRcon = app(Rcon::class);
    $inner = new ReflectionProperty(AfterCommitRcon::class, 'inner');

    expect($adaRcon)->not->toBe($arcturusRcon)
        ->and($inner->getValue($adaRcon))->toBeInstanceOf(UnsupportedRcon::class);
});

test('an unknown driver fails without replacing the active driver', function () {
    $manager = app(EmulatorManager::class);

    expect(fn () => $manager->select('misspelled'))
        ->toThrow(InvalidArgumentException::class, 'Unknown emulator driver [misspelled]')
        ->and($manager->active()->key())->toBe('arcturus');
});
