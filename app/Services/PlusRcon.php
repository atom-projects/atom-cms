<?php

namespace App\Services;

use App\Contracts\Rcon;
use App\Data\RconResponse;
use App\Enums\CurrencyTypes;
use App\Exceptions\RconConnectionException;
use App\Models\User;
use JsonException;

final readonly class PlusRcon implements Rcon
{
    private const MAX_FRAME_BYTES = 4096;

    /** @var list<string> */
    private const COMMANDS = [
        'alert_user', 'disconnect_user', 'give_user_badge', 'give_user_currency',
        'reload_catalog', 'reload_filter', 'reload_user_motto', 'reload_user_rank', 'take_user_currency',
    ];

    public function __construct(
        private ?string $host = null,
        private ?int $port = null,
    ) {}

    public function isConnected(): bool
    {
        try {
            $socket = $this->connect();
            fclose($socket);

            return true;
        } catch (RconConnectionException) {
            return false;
        }
    }

    public function sendCommand(string $command, ?array $data = null): RconResponse
    {
        if (! in_array($command, self::COMMANDS, true)) {
            throw new RconConnectionException("Plus RCON command '{$command}' is not supported");
        }

        $parameters = $data ?? [];
        foreach ($parameters as $parameter) {
            if (! is_scalar($parameter) || is_bool($parameter)) {
                throw new RconConnectionException("Plus RCON command '{$command}' has an invalid parameter");
            }
        }

        try {
            $payload = json_encode([
                'command' => $command,
                'parameters' => array_map(static fn (mixed $parameter): string => (string) $parameter, array_values($parameters)),
            ], JSON_THROW_ON_ERROR) . "\n";
        } catch (JsonException $exception) {
            throw new RconConnectionException("Unable to encode Plus RCON command '{$command}'", previous: $exception);
        }

        if (strlen($payload) > self::MAX_FRAME_BYTES) {
            throw new RconConnectionException("Plus RCON command '{$command}' exceeds the maximum request size");
        }

        $socket = $this->connect();
        try {
            $this->write($socket, $payload);
            stream_socket_shutdown($socket, STREAM_SHUT_WR);
            $response = fgets($socket, self::MAX_FRAME_BYTES + 2);
            $metadata = stream_get_meta_data($socket);

            if ($metadata['timed_out']) {
                throw new RconConnectionException("Plus RCON command '{$command}' timed out waiting for a response");
            }
            if ($response === false || $response === '') {
                throw new RconConnectionException("Plus RCON command '{$command}' returned an empty response");
            }
            if (! str_ends_with($response, "\n") || strlen($response) > self::MAX_FRAME_BYTES + 1) {
                throw new RconConnectionException("Plus RCON command '{$command}' returned an invalid response frame");
            }
            if (fgetc($socket) !== false) {
                throw new RconConnectionException("Plus RCON command '{$command}' returned more than one response frame");
            }

            return $this->parseResponse($command, $response);
        } finally {
            fclose($socket);
        }
    }

    public function sendGift(User $user, int $itemId, string $message = 'Here is a gift.'): void
    {
        throw new RconConnectionException('Plus RCON does not support sending gifts');
    }

    public function giveCurrency(User $user, CurrencyTypes $currency, int $amount): void
    {
        if ($amount === 0) {
            return;
        }
        if ($amount === PHP_INT_MIN) {
            throw new RconConnectionException('Plus RCON currency amount is out of range');
        }

        $this->dispatch($amount > 0 ? 'give_user_currency' : 'take_user_currency', [
            $user->id,
            match ($currency) {
                CurrencyTypes::Credits => 'credits',
                CurrencyTypes::Duckets => 'duckets',
                CurrencyTypes::Diamonds => 'diamonds',
                CurrencyTypes::Points => 'gotw',
            },
            abs($amount),
        ]);
    }

    public function giveBadge(User $user, string $badge): void
    {
        $this->dispatch('give_user_badge', [$user->id, $badge]);
    }

    public function setMotto(User $user, string $motto): void
    {
        $this->dispatch('reload_user_motto', [$user->id]);
    }

    public function updateWordFilter(): void
    {
        $this->dispatch('reload_filter');
    }

    public function disconnectUser(User $user): void
    {
        $this->dispatch('disconnect_user', [$user->id]);
    }

    public function setRank(User $user, int $rank): void
    {
        $this->dispatch('reload_user_rank', [$user->id]);
    }

    public function updateCatalog(): void
    {
        $this->dispatch('reload_catalog');
    }

    public function alertUser(User $user, string $message): void
    {
        $this->dispatch('alert_user', [$user->id, $message]);
    }

    public function forwardUser(User $user, int $roomId): void
    {
        throw new RconConnectionException('Plus RCON does not support forwarding users');
    }

    public function updateConfig(User $user, string $command): void
    {
        throw new RconConnectionException('Plus RCON does not support executing configuration commands');
    }

    /** @return resource */
    private function connect()
    {
        $host = trim($this->host ?? (string) setting('rcon_ip'));
        $port = $this->port ?? (int) setting('rcon_port');
        if ($host === '' || $port < 1 || $port > 65535) {
            throw new RconConnectionException('RCON host or port is not configured correctly');
        }

        $errorCode = 0;
        $errorMessage = '';
        $timeout = max(0.1, (float) (app()->bound('config') ? config('habbo.rcon.connect_timeout_seconds', 1) : 1));
        $formattedHost = str_contains($host, ':') && ! str_starts_with($host, '[') ? "[{$host}]" : $host;
        $socket = @stream_socket_client("tcp://{$formattedHost}:{$port}", $errorCode, $errorMessage, $timeout, STREAM_CLIENT_CONNECT);
        if ($socket === false) {
            throw new RconConnectionException("Unable to connect to RCON: {$errorMessage} ({$errorCode})");
        }

        $readTimeout = max(0.1, (float) (app()->bound('config') ? config('habbo.rcon.read_timeout_seconds', 2) : 2));
        stream_set_timeout($socket, (int) $readTimeout, (int) (($readTimeout - (int) $readTimeout) * 1_000_000));

        return $socket;
    }

    /** @param resource $socket */
    private function write($socket, string $payload): void
    {
        for ($written = 0, $length = strlen($payload); $written < $length; $written += $bytes) {
            $bytes = fwrite($socket, substr($payload, $written));
            if ($bytes === false || $bytes === 0) {
                throw new RconConnectionException('RCON connection closed before the command was fully written');
            }
        }
    }

    private function parseResponse(string $command, string $response): RconResponse
    {
        try {
            $decoded = json_decode($response, true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RconConnectionException("Plus RCON command '{$command}' returned malformed JSON", previous: $exception);
        }
        if (! is_array($decoded) || ! isset($decoded['status'], $decoded['message']) || ! is_int($decoded['status']) || ! is_string($decoded['message'])) {
            throw new RconConnectionException("Plus RCON command '{$command}' returned an invalid response");
        }

        return new RconResponse($decoded['status'], $decoded['message']);
    }

    /** @param list<int|string> $parameters */
    private function dispatch(string $command, array $parameters = []): void
    {
        $data = [];
        foreach ($parameters as $index => $parameter) {
            $data["parameter_{$index}"] = $parameter;
        }

        $response = $this->sendCommand($command, $data);
        if (! $response->successful()) {
            throw new RconConnectionException("Plus RCON command '{$command}' was rejected: {$response->message}");
        }
    }
}
