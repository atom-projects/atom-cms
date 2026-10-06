<?php

namespace App\Emulator\Drivers\Plus;

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
use App\Services\PlusRcon;

final class PlusDriver implements EmulatorDriver
{
    public function key(): string
    {
        return 'plus';
    }

    public function label(): string
    {
        return 'PlusEMU';
    }

    public function bindings(): array
    {
        return [
            BadgeRepository::class => PlusBadgeRepository::class,
            BanRepository::class => PlusBanRepository::class,
            CurrencyRepository::class => PlusCurrencyRepository::class,
            FurnitureRepository::class => PlusFurnitureRepository::class,
            PlayerRepository::class => PlusPlayerRepository::class,
            PlayerSettingsRepository::class => PlusPlayerSettingsRepository::class,
            PlayerStatsRepository::class => PlusPlayerStatsRepository::class,
            RankRepository::class => PlusRankRepository::class,
            RoomRepository::class => PlusRoomRepository::class,
        ];
    }

    public function features(): array
    {
        return [Feature::RareValues];
    }

    public function schemaFeatures(): array
    {
        return [];
    }

    public function playerConstraints(): PlayerConstraints
    {
        return new PlayerConstraints(125, 255, 50, 255);
    }

    public function installer(): EmulatorInstaller
    {
        return app(PlusInstaller::class);
    }

    public function rcon(): Rcon
    {
        return app(PlusRcon::class);
    }

    public function migrationPaths(): array
    {
        return [__DIR__ . '/Migrations'];
    }

    public function userRelationManagers(): array
    {
        return [];
    }
}
