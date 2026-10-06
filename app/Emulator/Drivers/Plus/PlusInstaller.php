<?php

namespace App\Emulator\Drivers\Plus;

use App\Emulator\Contracts\EmulatorInstaller;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

final class PlusInstaller implements EmulatorInstaller
{
    private const REQUIRED_TABLES = ['users', 'users_settings', 'user_statistics', 'user_badges', 'items', 'furniture', 'catalog_items', 'rooms', 'bans', 'roles', 'user_roles', 'user_access_tokens', 'user_remember_tokens', 'user_sessions'];

    public function prepare(Command $command): bool
    {
        $missing = array_values(array_filter(self::REQUIRED_TABLES, fn (string $table): bool => ! Schema::hasTable($table)));

        if ($missing !== []) {
            error('PlusEMU schema is incomplete. Start the current PlusEMU migration runner first. Missing: ' . implode(', ', $missing));

            return false;
        }

        info('PlusEMU schema found; native tables will not be imported or migrated by Atom.');

        return true;
    }
}
