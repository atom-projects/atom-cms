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
use App\Emulator\Data\PlayerSchema;
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

    public function passwordHashing(): string
    {
        return 'argon2id';
    }

    /**
     * PlusEMU keeps Atom's player on its own users row under other names,
     * with DATETIME timestamps, and normalises duckets, GOTW points, the
     * home room and ranks into other tables. A rank is the highest security
     * level among the player's unexpired roles.
     */
    public function playerSchema(): PlayerSchema
    {
        $activeRoles = 'FROM `user_roles` INNER JOIN `roles` ON `roles`.`id` = `user_roles`.`role_id` '
            . 'WHERE `user_roles`.`user_id` = `users`.`id` AND (`user_roles`.`expires_at` IS NULL OR `user_roles`.`expires_at` > UTC_TIMESTAMP(6))';
        $currency = fn (int $type): string => sprintf(
            'COALESCE((SELECT `amount` FROM `user_currencies` WHERE `user_currencies`.`user_id` = `users`.`id` AND `user_currencies`.`type` = %d), 0)',
            $type,
        );

        return new PlayerSchema(
            columns: ['ip_register' => 'ip_reg', 'ip_current' => 'ip_last'],
            derived: [
                'pixels' => $currency(PlusCurrencyRepository::DUCKETS),
                'points' => $currency(PlusCurrencyRepository::GOTW_POINTS),
                'home_room' => 'COALESCE((SELECT `home_room` FROM `users_settings` WHERE `users_settings`.`user_id` = `users`.`id`), 0)',
                'rank' => "COALESCE((SELECT MAX(`roles`.`security_level`) {$activeRoles}), 1)",
                'native_role_id' => "(SELECT `roles`.`id` {$activeRoles} ORDER BY `roles`.`security_level` DESC, `roles`.`weight` DESC, `roles`.`id` LIMIT 1)",
            ],
            timestamps: ['account_created', 'last_online'],
            hidden: ['auth_ticket_expires_at', 'auth_ticket_exchanged', 'auth_ticket_session', 'credential_generation'],
        );
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
