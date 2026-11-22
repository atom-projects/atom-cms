<?php

namespace App\Services\Client;

use App\Models\User;
use App\Support\WebsiteSettingRules;

class ClientLaunchService
{
    public function ticket(User $user, string $ipAddress): string
    {
        $user->update(['ip_current' => $ipAddress]);

        return $user->ssoTicket();
    }

    /**
     * Issue a ticket and build the Nitro client URL that carries it. Returns
     * null, without issuing a ticket, when nitro_path points to an origin that
     * is not allowed.
     *
     * @return array{sso: string, url: string}|null
     */
    public function nitro(User $user, string $ipAddress): ?array
    {
        $path = (string) setting('nitro_path', config('habbo.client.nitro_path'));

        if (! WebsiteSettingRules::accepts('nitro_path', $path)) {
            return null;
        }

        $sso = $this->ticket($user, $ipAddress);

        return ['sso' => $sso, 'url' => rtrim(url($path), '/') . '/index.html?sso=' . rawurlencode($sso)];
    }

    /**
     * Build the link an AIR launcher set to this hotel's domain uses to log in with the ticket.
     */
    public function airUrl(string $sso): string
    {
        return 'habbo://hab?' . http_build_query([
            'server' => parse_url((string) config('app.url'), PHP_URL_HOST),
            'token' => $sso,
        ], '', '&', PHP_QUERY_RFC3986);
    }
}
