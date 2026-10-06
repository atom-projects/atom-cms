<?php

use App\Enums\CurrencyTypes;
use App\Exceptions\RconConnectionException;
use App\Models\User;
use App\Services\PlusRcon;

function runPlusRconServer(int $connections, Closure $client, array $responses = []): array
{
    $encodedResponses = base64_encode(json_encode($responses, JSON_THROW_ON_ERROR));
    $script = <<<'PHP'
    $responses = json_decode(base64_decode($argv[1]), true, flags: JSON_THROW_ON_ERROR);
    $server = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
    if ($server === false) { fwrite(STDERR, $errorMessage); exit(1); }
    $address = stream_socket_get_name($server, false);
    fwrite(STDOUT, substr($address, strrpos($address, ':') + 1).PHP_EOL);
    fflush(STDOUT);
    $requests = [];
    for ($index = 0; $index < (int) $argv[2]; $index++) {
        $connection = stream_socket_accept($server, 5);
        if ($connection === false) { exit(2); }
        $request = fgets($connection, 4098);
        $requests[] = $request;
        $response = json_encode($responses[$index] ?? ['status' => 0, 'message' => 'OK'], JSON_THROW_ON_ERROR)."\n";
        $middle = intdiv(strlen($response), 2);
        fwrite($connection, substr($response, 0, $middle));
        usleep(10_000);
        fwrite($connection, substr($response, $middle));
        fclose($connection);
    }
    fclose($server);
    fwrite(STDOUT, base64_encode(json_encode($requests, JSON_THROW_ON_ERROR)).PHP_EOL);
    PHP;
    $process = proc_open([PHP_BINARY, '-r', $script, $encodedResponses, (string) $connections], [
        0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
    ], $pipes);
    expect($process)->not->toBeFalse();
    fclose($pipes[0]);

    try {
        $port = (int) trim((string) fgets($pipes[1]));
        expect($port)->toBeGreaterThan(0);
        $client(new PlusRcon('127.0.0.1', $port));
        $requests = json_decode(base64_decode(trim((string) fgets($pipes[1]))), true, flags: JSON_THROW_ON_ERROR);
    } finally {
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);
    }

    expect($exitCode)->toBe(0, $stderr ?: $stdout);

    return $requests;
}

test('plus rcon sends newline framed native commands and reads split acknowledgements', function () {
    $user = new User;
    $user->id = 42;

    $requests = runPlusRconServer(10, function (PlusRcon $rcon) use ($user): void {
        $rcon->giveCurrency($user, CurrencyTypes::Credits, 10);
        $rcon->giveCurrency($user, CurrencyTypes::Duckets, -2);
        $rcon->giveCurrency($user, CurrencyTypes::Diamonds, 3);
        $rcon->giveCurrency($user, CurrencyTypes::Points, 4);
        $rcon->giveBadge($user, 'ADM');
        $rcon->setMotto($user, 'persisted first');
        $rcon->setRank($user, 7);
        $rcon->updateWordFilter();
        $rcon->updateCatalog();
        $rcon->alertUser($user, 'Maintenance soon');
    });

    $decoded = array_map(static fn (string $request): array => json_decode($request, true, flags: JSON_THROW_ON_ERROR), $requests);
    expect($requests)->each->toEndWith("\n")
        ->and($decoded)->toBe([
            ['command' => 'give_user_currency', 'parameters' => ['42', 'credits', '10']],
            ['command' => 'take_user_currency', 'parameters' => ['42', 'duckets', '2']],
            ['command' => 'give_user_currency', 'parameters' => ['42', 'diamonds', '3']],
            ['command' => 'give_user_currency', 'parameters' => ['42', 'gotw', '4']],
            ['command' => 'give_user_badge', 'parameters' => ['42', 'ADM']],
            ['command' => 'reload_user_motto', 'parameters' => ['42']],
            ['command' => 'reload_user_rank', 'parameters' => ['42']],
            ['command' => 'reload_filter', 'parameters' => []],
            ['command' => 'reload_catalog', 'parameters' => []],
            ['command' => 'alert_user', 'parameters' => ['42', 'Maintenance soon']],
        ]);
});

test('plus rcon exposes native rejections and rejects unsupported operations before connecting', function () {
    $user = new User;
    $user->id = 42;

    runPlusRconServer(1, function (PlusRcon $rcon) use ($user): void {
        expect(fn () => $rcon->disconnectUser($user))
            ->toThrow(RconConnectionException::class, 'was rejected: offline');
    }, [['status' => 1, 'message' => 'offline']]);

    $rcon = new PlusRcon('127.0.0.1', 1);
    expect(fn () => $rcon->sendGift($user, 10))->toThrow(RconConnectionException::class, 'does not support sending gifts')
        ->and(fn () => $rcon->forwardUser($user, 10))->toThrow(RconConnectionException::class, 'does not support forwarding users')
        ->and(fn () => $rcon->updateConfig($user, ':shutdown'))->toThrow(RconConnectionException::class, 'does not support executing configuration commands');
});
