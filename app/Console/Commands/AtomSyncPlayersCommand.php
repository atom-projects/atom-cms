<?php

namespace App\Console\Commands;

use App\Emulator\Contracts\PlayerRepository;
use App\Emulator\Contracts\PreparesPlayerQueries;
use Illuminate\Console\Command;

class AtomSyncPlayersCommand extends Command
{
    protected $signature = 'atom:sync-players';

    protected $description = 'Refresh every mirrored emulator player. Requests only refresh the players they touch.';

    public function handle(PlayerRepository $players): int
    {
        if (! $players instanceof PreparesPlayerQueries) {
            $this->info('The active emulator driver keeps no player mirror.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Synchronized %d players.', $players->synchronizeAll()));

        return self::SUCCESS;
    }
}
