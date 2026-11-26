<?php

namespace App\Emulator\Drivers\Arcturus;

use App\Contracts\Rcon;
use App\Emulator\Contracts\BadgeRepository;
use App\Emulator\Contracts\BanRepository;
use App\Emulator\Contracts\CurrencyRepository;
use App\Emulator\Contracts\EmulatorDriver;
use App\Emulator\Contracts\EmulatorInstaller;
use App\Emulator\Contracts\FurnitureRepository;
use App\Emulator\Contracts\PlayerRepository;
use App\Emulator\Contracts\PlayerSettingsRepository;
use App\Emulator\Contracts\PlayerStatsRepository;
use App\Emulator\Contracts\RankRepository;
use App\Emulator\Contracts\RoomRepository;
use App\Emulator\Data\Feature;
use App\Emulator\Data\PlayerConstraints;
use App\Emulator\Data\PlayerSchema;
use App\Emulator\Data\SchemaFeature;
use App\Filament\Resources\User\Users\RelationManagers\BadgesRelationManager;
use App\Filament\Resources\User\Users\RelationManagers\ChatLogPrivateRelationManager;
use App\Filament\Resources\User\Users\RelationManagers\ChatLogRelationManager;
use App\Filament\Resources\User\Users\RelationManagers\SettingsRelationManager;
use App\Services\RconService;

final class ArcturusDriver implements EmulatorDriver
{
    public function key(): string
    {
        return 'arcturus';
    }

    public function label(): string
    {
        return 'Arcturus';
    }

    public function bindings(): array
    {
        return [
            BadgeRepository::class => ArcturusBadgeRepository::class,
            BanRepository::class => ArcturusBanRepository::class,
            CurrencyRepository::class => ArcturusCurrencyRepository::class,
            FurnitureRepository::class => ArcturusFurnitureRepository::class,
            PlayerRepository::class => ArcturusPlayerRepository::class,
            PlayerStatsRepository::class => ArcturusPlayerStatsRepository::class,
            PlayerSettingsRepository::class => ArcturusPlayerSettingsRepository::class,
            RankRepository::class => ArcturusRankRepository::class,
            RoomRepository::class => ArcturusRoomRepository::class,
        ];
    }

    public function features(): array
    {
        return Feature::cases();
    }

    public function schemaFeatures(): array
    {
        return SchemaFeature::cases();
    }

    public function playerConstraints(): PlayerConstraints
    {
        return new PlayerConstraints(25, 255, 127, 256);
    }

    public function passwordHashing(): string
    {
        return 'bcrypt';
    }

    public function playerSchema(): PlayerSchema
    {
        return new PlayerSchema;
    }

    public function installer(): EmulatorInstaller
    {
        return app(ArcturusInstaller::class);
    }

    public function rcon(): Rcon
    {
        return app(RconService::class);
    }

    public function migrationPaths(): array
    {
        return [__DIR__ . '/Migrations'];
    }

    public function userRelationManagers(): array
    {
        return [
            SettingsRelationManager::class,
            BadgesRelationManager::class,
            ChatLogRelationManager::class,
            ChatLogPrivateRelationManager::class,
        ];
    }
}
