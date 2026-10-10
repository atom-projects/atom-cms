<?php

namespace App\Emulator\Drivers\Ada;

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
use App\Filament\Resources\User\Users\RelationManagers\AdaBadgesRelationManager;
use App\Services\UnsupportedRcon;

final class AdaDriver implements EmulatorDriver
{
    public function key(): string
    {
        return 'ada';
    }

    public function label(): string
    {
        return 'Ada';
    }

    public function bindings(): array
    {
        return [
            BadgeRepository::class => AdaBadgeRepository::class,
            BanRepository::class => AdaBanRepository::class,
            CurrencyRepository::class => AdaCurrencyRepository::class,
            FurnitureRepository::class => AdaFurnitureRepository::class,
            PlayerRepository::class => AdaPlayerRepository::class,
            PlayerStatsRepository::class => AdaPlayerStatsRepository::class,
            PlayerSettingsRepository::class => AdaPlayerSettingsRepository::class,
            RankRepository::class => AdaRankRepository::class,
            RoomRepository::class => AdaRoomRepository::class,
        ];
    }

    public function features(): array
    {
        // Ada has no command log, no word filter, no per-player name-change
        // grant and no camera storage. Everything else Ada owns is reachable
        // through this driver's repositories and needs no gate.
        return [Feature::RareValues];
    }

    public function schemaFeatures(): array
    {
        return [];
    }

    /**
     * Mirrors Ada's EF column widths: players.username, players.email and
     * player_avatar_data.motto are varchar(50), figure_code is varchar(200).
     */
    public function playerConstraints(): PlayerConstraints
    {
        return new PlayerConstraints(50, 50, 50, 200);
    }

    public function passwordHashing(): string
    {
        return 'bcrypt';
    }

    /**
     * Ada's player row is players (BIGINT ids). The rest of what Atom calls a
     * user Ada normalises into one-to-one tables keyed by player_id, and a
     * player's rank is their highest role.
     */
    public function playerSchema(): PlayerSchema
    {
        $select = fn (string $table, string $column): string => "(SELECT `{$table}`.`{$column}` FROM `{$table}` WHERE `{$table}`.`player_id` = `players`.`id` LIMIT 1)";
        $of = fn (string $table, string $column, string $default): string => 'COALESCE(' . $select($table, $column) . ", {$default})";
        $unix = fn (string $table, string $column): string => 'COALESCE(UNIX_TIMESTAMP(' . $select($table, $column) . '), 0)';

        return new PlayerSchema(
            table: 'players',
            keyType: 'bigInteger',
            columns: ['mail' => 'email', 'account_created' => 'created_at'],
            derived: [
                'look' => $of('player_avatar_data', 'figure_code', "''"),
                'motto' => $of('player_avatar_data', 'motto', "''"),
                'gender' => $of('player_avatar_data', 'gender', "'M'"),
                'credits' => $of('player_data', 'credit_balance', '0'),
                'pixels' => $of('player_data', 'pixel_balance', '0'),
                'points' => $of('player_data', 'gotw_points', '0'),
                'home_room' => $of('player_data', 'home_room_id', '0'),
                'online' => $of('player_data', 'is_online', '0'),
                'last_online' => $unix('player_data', 'last_online'),
                'last_login' => $unix('player_website_data', 'last_login'),
                'ip_register' => $of('player_website_data', 'initial_ip', "''"),
                'ip_current' => $of('player_website_data', 'last_ip', "''"),
                'rank' => 'COALESCE((SELECT MAX(`player_role`.`role_id`) FROM `player_role` WHERE `player_role`.`player_id` = `players`.`id`), 1)',
            ],
            derivedCasts: ['look' => 'string', 'motto' => 'string', 'gender' => 'string', 'ip_register' => 'string', 'ip_current' => 'string', 'online' => 'boolean'],
            timestamps: ['account_created'],
        );
    }

    public function installer(): EmulatorInstaller
    {
        return app(AdaInstaller::class);
    }

    public function rcon(): Rcon
    {
        return new UnsupportedRcon($this->key());
    }

    public function migrationPaths(): array
    {
        return [__DIR__ . '/Migrations'];
    }

    public function userRelationManagers(): array
    {
        return [AdaBadgesRelationManager::class];
    }
}
