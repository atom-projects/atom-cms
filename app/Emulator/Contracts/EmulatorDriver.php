<?php

namespace App\Emulator\Contracts;

use App\Contracts\Rcon;
use App\Emulator\Data\Feature;
use App\Emulator\Data\PlayerConstraints;
use App\Emulator\Data\PlayerSchema;
use App\Emulator\Data\SchemaFeature;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\RelationManagers\RelationManagerConfiguration;

interface EmulatorDriver
{
    public function key(): string;

    public function label(): string;

    /** @return array<class-string, class-string> */
    public function bindings(): array;

    /** @return list<Feature> */
    public function features(): array;

    /** @return list<SchemaFeature> */
    public function schemaFeatures(): array;

    public function playerConstraints(): PlayerConstraints;

    /** The Laravel hash driver for player passwords, matching the emulator's own login. */
    public function passwordHashing(): string;

    /** Where this emulator keeps the attributes of Atom's User model. */
    public function playerSchema(): PlayerSchema;

    public function installer(): EmulatorInstaller;

    public function rcon(): Rcon;

    /** @return list<string> */
    public function migrationPaths(): array;

    /** @return list<class-string<RelationManager>|RelationGroup|RelationManagerConfiguration> */
    public function userRelationManagers(): array;
}
