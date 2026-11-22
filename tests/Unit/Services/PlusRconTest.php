<?php

use App\Enums\CurrencyTypes;
use App\Exceptions\RconConnectionException;
use App\Models\User;
use App\Services\PlusRcon;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;

function runPlusRconServer(int $connections, Closure $client, array $responses = [], ?string $secret = null, ?Closure $makeRcon = null): array
{
    $makeRcon ??= static fn (int $port): PlusRcon => new PlusRcon('127.0.0.1', $port, $secret);
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
        $client($makeRcon($port));
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

test('plus rcon sends the configured secret with every request', function () {
    $user = new User;
    $user->id = 42;
    $secret = str_repeat('fake', 8);

    $requests = runPlusRconServer(2, function (PlusRcon $rcon) use ($user): void {
        $rcon->giveBadge($user, 'ADM');
        $rcon->updateCatalog();
    }, secret: $secret);

    expect(array_map(static fn (string $request): array => json_decode($request, true, flags: JSON_THROW_ON_ERROR), $requests))->toBe([
        ['command' => 'give_user_badge', 'parameters' => ['42', 'ADM'], 'secret' => $secret],
        ['command' => 'reload_catalog', 'parameters' => [], 'secret' => $secret],
    ]);
});

test('plus rcon sends no secret when none is configured', function (?string $secret) {
    $requests = runPlusRconServer(1, function (PlusRcon $rcon): void {
        $rcon->updateCatalog();
    }, secret: $secret);

    expect(json_decode($requests[0], true, flags: JSON_THROW_ON_ERROR))->toBe(['command' => 'reload_catalog', 'parameters' => []]);
})->with([null, '']);

test('plus rcon reads the secret from configuration when resolved from the container', function () {
    $secret = str_repeat('fake', 8);
    $previous = Container::getInstance();
    $container = new Container;
    $container->instance('config', new Repository);
    Container::setInstance($container);
    config(['habbo.rcon.secret' => $secret]);

    try {
        $requests = runPlusRconServer(1, function (PlusRcon $rcon): void {
            $rcon->updateCatalog();
        }, makeRcon: static fn (int $port): PlusRcon => app(PlusRcon::class, ['host' => '127.0.0.1', 'port' => $port]));
    } finally {
        Container::setInstance($previous);
    }

    expect(json_decode($requests[0], true, flags: JSON_THROW_ON_ERROR))
        ->toBe(['command' => 'reload_catalog', 'parameters' => [], 'secret' => $secret]);
});

test('plus rcon reports an invalid request with a secret hint that holds for every cause', function (?string $secret) {
    $user = new User;
    $user->id = 42;

    runPlusRconServer(1, function (PlusRcon $rcon) use ($user): void {
        expect(fn () => $rcon->disconnectUser($user))->toThrow(
            RconConnectionException::class,
            "Plus RCON refused command 'disconnect_user' as invalid. If Rcon:Secret is set in the emulator config, check that RCON_SECRET matches it.",
        );
    }, [['status' => 1, 'message' => 'Invalid RCON request.']], $secret);
})->with([null, str_repeat('fake', 8)]);

test('plus rcon hints that an emulator without secret support refuses a configured secret', function () {
    $user = new User;
    $user->id = 42;
    $secret = str_repeat('fake', 8);

    runPlusRconServer(1, function (PlusRcon $rcon) use ($user, $secret): void {
        expect(fn () => $rcon->disconnectUser($user))->toThrow(function (RconConnectionException $exception) use ($secret): void {
            expect($exception->getMessage())
                ->toBe("Plus RCON command 'disconnect_user' was rejected as malformed JSON. The emulator may be older than the shared secret feature. Leave RCON_SECRET empty until the emulator is upgraded.")
                ->not->toContain($secret);
        });
    }, [['status' => 1, 'message' => 'Malformed RCON JSON.']], $secret);
});

test('plus rcon reports malformed json without the secret hint when no secret is configured', function () {
    $user = new User;
    $user->id = 42;

    runPlusRconServer(1, function (PlusRcon $rcon) use ($user): void {
        expect(fn () => $rcon->disconnectUser($user))
            ->toThrow(RconConnectionException::class, "Plus RCON command 'disconnect_user' was rejected: Malformed RCON JSON.");
    }, [['status' => 1, 'message' => 'Malformed RCON JSON.']]);
});
