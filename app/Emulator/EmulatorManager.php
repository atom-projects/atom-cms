<?php

namespace App\Emulator;

use App\Contracts\Rcon;
use App\Emulator\Contracts\BadgeRepository;
use App\Emulator\Contracts\BanRepository;
use App\Emulator\Contracts\CurrencyRepository;
use App\Emulator\Contracts\EmulatorDriver;
use App\Emulator\Contracts\FurnitureRepository;
use App\Emulator\Contracts\PlayerRepository;
use App\Emulator\Contracts\PlayerSettingsRepository;
use App\Emulator\Contracts\PlayerStatsRepository;
use App\Emulator\Contracts\RankRepository;
use App\Emulator\Contracts\RoomRepository;
use App\Emulator\Data\Feature;
use App\Emulator\Data\SchemaFeature;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

final class EmulatorManager
{
    /** @var list<class-string> */
    public const REPOSITORY_CONTRACTS = [
        BadgeRepository::class,
        BanRepository::class,
        CurrencyRepository::class,
        FurnitureRepository::class,
        PlayerRepository::class,
        PlayerStatsRepository::class,
        PlayerSettingsRepository::class,
        RankRepository::class,
        RoomRepository::class,
    ];

    private ?EmulatorDriver $active = null;

    public function __construct(private readonly Container $container) {}

    public function active(): EmulatorDriver
    {
        if ($this->active !== null) {
            return $this->active;
        }

        return $this->activate($this->driver((string) config('emulator.driver')));
    }

    public function select(string $key): EmulatorDriver
    {
        $driver = $this->driver($key);

        config(['emulator.driver' => $key]);
        $this->activate($driver);

        foreach (self::REPOSITORY_CONTRACTS as $contract) {
            $this->container->forgetInstance($contract);
        }

        $this->container->forgetInstance(Rcon::class);

        return $driver;
    }

    /** Passwords are hashed the way the active emulator expects them. */
    private function activate(EmulatorDriver $driver): EmulatorDriver
    {
        config(['hashing.driver' => $driver->passwordHashing()]);

        return $this->active = $driver;
    }

    /** @return array<string, class-string<EmulatorDriver>> */
    public function drivers(): array
    {
        $drivers = config('emulator.drivers', []);

        return is_array($drivers) ? $drivers : [];
    }

    /** @return array<string, string> */
    public function choices(): array
    {
        $choices = [];

        foreach (array_keys($this->drivers()) as $key) {
            $choices[$key] = $this->driver($key)->label();
        }

        return $choices;
    }

    public function driver(string $key): EmulatorDriver
    {
        $class = $this->drivers()[$key] ?? null;

        if (! is_string($class) || ! is_a($class, EmulatorDriver::class, true)) {
            throw new InvalidArgumentException("Unknown emulator driver [{$key}]");
        }

        $driver = $this->container->make($class);

        if (! $driver instanceof EmulatorDriver || $driver->key() !== $key) {
            throw new InvalidArgumentException("Emulator driver [{$key}] is not configured correctly");
        }

        foreach (self::REPOSITORY_CONTRACTS as $contract) {
            $implementation = $driver->bindings()[$contract] ?? null;

            if (! is_string($implementation) || ! is_a($implementation, $contract, true)) {
                throw new InvalidArgumentException(
                    "Emulator driver [{$key}] has no valid binding for [{$contract}]",
                );
            }
        }

        return $driver;
    }

    public function supports(Feature $feature): bool
    {
        return in_array($feature, $this->active()->features(), true);
    }

    public function supportsSchema(SchemaFeature $feature): bool
    {
        return in_array($feature, $this->active()->schemaFeatures(), true);
    }

    public function repository(string $contract): object
    {
        $implementation = $this->active()->bindings()[$contract] ?? null;

        if (! is_string($implementation) || ! is_a($implementation, $contract, true)) {
            throw new InvalidArgumentException(
                "Emulator driver [{$this->active()->key()}] has no valid binding for [{$contract}]",
            );
        }

        return $this->container->make($implementation);
    }
}
