<?php

namespace App\Emulator;

use App\Emulator\Data\Feature;
use App\Emulator\Data\PlayerConstraints;
use App\Emulator\Data\PlayerSchema;
use App\Emulator\Data\SchemaFeature;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\RelationManagers\RelationManagerConfiguration;

/**
 * Answers questions about the configured emulator driver.
 */
class Emulator
{
    public static function driver(): string
    {
        return self::manager()->active()->key();
    }

    public static function supports(Feature $feature): bool
    {
        return self::manager()->supports($feature);
    }

    public static function supportsSchema(SchemaFeature $feature): bool
    {
        return self::manager()->supportsSchema($feature);
    }

    public static function constraints(): PlayerConstraints
    {
        return self::manager()->active()->playerConstraints();
    }

    public static function playerSchema(): PlayerSchema
    {
        return self::manager()->active()->playerSchema();
    }

    /** @return list<class-string<RelationManager>|RelationGroup|RelationManagerConfiguration> */
    public static function userRelationManagers(): array
    {
        return self::manager()->active()->userRelationManagers();
    }

    private static function manager(): EmulatorManager
    {
        return app(EmulatorManager::class);
    }
}
