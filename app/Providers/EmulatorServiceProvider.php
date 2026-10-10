<?php

namespace App\Providers;

use App\Contracts\Rcon;
use App\Emulator\Emulator;
use App\Emulator\EmulatorManager;
use App\Services\AfterCommitRcon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Database\Schema\ForeignKeyDefinition;
use Illuminate\Support\ServiceProvider;

/**
 * Binds the emulator contracts to the implementations of the configured driver,
 * so the rest of the CMS depends only on the contracts.
 */
class EmulatorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EmulatorManager::class);

        foreach (EmulatorManager::REPOSITORY_CONTRACTS as $contract) {
            $this->app->singleton(
                $contract,
                fn ($app) => $app->make(EmulatorManager::class)->repository($contract),
            );
        }

        // Wrapped so RCON sends inside a database transaction only fire once it
        // commits - a rolled-back purchase never grants items in the emulator.
        $this->app->singleton(
            Rcon::class,
            fn ($app): Rcon => new AfterCommitRcon($app->make(EmulatorManager::class)->active()->rcon()),
        );
    }

    public function boot(EmulatorManager $manager): void
    {
        foreach ($manager->active()->migrationPaths() as $path) {
            $this->loadMigrationsFrom($path);
        }

        $this->registerPlayerColumns();
    }

    /**
     * Atom's tables reference the active emulator's player row, whose table
     * and key type differ per emulator (signed INT users.id on Arcturus and
     * PlusEMU, signed BIGINT players.id on Ada). Laravel's foreignIdFor()
     * assumes an unsigned BIGINT, so migrations use these instead:
     *
     *     $table->playerId('user_id');
     *     $table->foreignPlayer('user_id')->cascadeOnDelete();
     */
    private function registerPlayerColumns(): void
    {
        Blueprint::macro('playerId', function (string $column): ColumnDefinition {
            /** @var Blueprint $this */
            return Emulator::playerSchema()->keyType === 'bigInteger' ? $this->bigInteger($column) : $this->integer($column);
        });

        Blueprint::macro('foreignPlayer', function (string $column): ForeignKeyDefinition {
            /** @var Blueprint $this */
            return $this->foreign($column)->references('id')->on(Emulator::playerSchema()->table);
        });
    }
}
