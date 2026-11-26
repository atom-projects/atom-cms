<?php

namespace Tests\Fixtures;

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
use App\Emulator\Data\PlayerConstraints;
use App\Emulator\Data\PlayerSchema;
use App\Emulator\Drivers\Arcturus\ArcturusBadgeRepository;
use App\Emulator\Drivers\Arcturus\ArcturusBanRepository;
use App\Emulator\Drivers\Arcturus\ArcturusCurrencyRepository;
use App\Emulator\Drivers\Arcturus\ArcturusFurnitureRepository;
use App\Emulator\Drivers\Arcturus\ArcturusPlayerRepository;
use App\Emulator\Drivers\Arcturus\ArcturusPlayerSettingsRepository;
use App\Emulator\Drivers\Arcturus\ArcturusPlayerStatsRepository;
use App\Emulator\Drivers\Arcturus\ArcturusRankRepository;
use App\Emulator\Drivers\Arcturus\ArcturusRoomRepository;
use App\Services\UnsupportedRcon;

class ThirdEmulatorDriver implements EmulatorDriver
{
    public function key(): string
    {
        return 'third';
    }

    public function label(): string
    {
        return 'Third emulator';
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
        return [];
    }

    public function schemaFeatures(): array
    {
        return [];
    }

    public function playerConstraints(): PlayerConstraints
    {
        return new PlayerConstraints(24, 100, 80, 220);
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
        return app(ThirdEmulatorInstaller::class);
    }

    public function rcon(): Rcon
    {
        return new UnsupportedRcon($this->key());
    }

    public function migrationPaths(): array
    {
        return [];
    }

    public function userRelationManagers(): array
    {
        return [];
    }
}
