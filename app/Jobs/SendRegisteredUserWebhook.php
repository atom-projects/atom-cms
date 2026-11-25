<?php

namespace App\Jobs;

use App\Support\DiscordWebhookUrl;
use App\Support\OutboundHttp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Notifies the configured Discord webhook about a new registration. Dispatched
 * after the response so the Discord round-trip never delays sign-up.
 */
class SendRegisteredUserWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 60, 300];

    public function __construct(
        private readonly string $username,
        private readonly string $ip,
        private readonly string $email,
    ) {}

    public function handle(): void
    {
        $url = setting('discord_webhook_url');

        if (! DiscordWebhookUrl::isValid($url)) {
            Log::error('Discord registration webhook URL is missing or invalid.');

            return;
        }

        try {
            $response = OutboundHttp::request()
                ->asJson()
                ->post($url, [
                    'username' => sprintf('%s Bot', setting('hotel_name')),
                    'content' => sprintf(
                        'User: %s has just registered, with the IP: %s and E-mail: %s',
                        $this->inlineCode($this->username),
                        $this->inlineCode($this->ip),
                        $this->inlineCode($this->email),
                    ),
                    'allowed_mentions' => ['parse' => []],
                ]);
        } catch (Throwable $exception) {
            Log::warning('Discord registration webhook delivery failed.', [
                'exception_class' => $exception::class,
            ]);

            throw new RuntimeException('Discord registration webhook delivery failed.');
        }

        if (! $response->successful()) {
            Log::warning('Discord registration webhook returned an error.', [
                'status' => $response->status(),
            ]);

            throw new RuntimeException('Discord registration webhook delivery failed.');
        }
    }

    /**
     * Discord renders nothing inside a code span, so visitor text cannot format, link or mention.
     * Control characters and line separators become a space so the value stays on one line.
     */
    private function inlineCode(string $value): string
    {
        $value = preg_replace('/(?:[\x00-\x1F\x7F]|\xC2[\x80-\x9F]|\xE2\x80[\xA8\xA9])+/', ' ', str_replace('`', '', $value));

        return '`' . $value . '`';
    }
}
