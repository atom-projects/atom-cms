<?php

namespace App\Http\Middleware;

use App\Models\Miscellaneous\WebsiteInstallation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets an installer action through only for the session that entered the
 * current installation key. Restarting rotates the key, which ends every
 * earlier acceptance.
 */
class EnsureInstallationKeyAccepted
{
    private const SESSION_KEY = 'installation_key_hash';

    public function handle(Request $request, Closure $next): Response
    {
        if (! self::isAccepted($request)) {
            return to_route('installation.index');
        }

        return $next($request);
    }

    public static function accept(Request $request, string $installationKey): void
    {
        $request->session()->regenerate();
        $request->session()->put(self::SESSION_KEY, hash('sha256', $installationKey));
    }

    public static function isAccepted(Request $request): bool
    {
        $installationKey = WebsiteInstallation::query()->oldest('id')->value('installation_key');
        $acceptedKey = $request->session()->get(self::SESSION_KEY);

        return is_string($installationKey)
            && is_string($acceptedKey)
            && hash_equals(hash('sha256', $installationKey), $acceptedKey);
    }
}
