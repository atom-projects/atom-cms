<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\Client\ClientLaunchService;
use App\Support\AuthenticatedUser;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    /**
     * Serve a game client. The route supplies which one via a default for the
     * "client" parameter (client.nitro or client.flash).
     */
    public function __invoke(Request $request, string $client): View
    {
        $user = AuthenticatedUser::from($request);

        $launcher = app(ClientLaunchService::class);
        $ipAddress = $request->ip() ?: 'unknown';

        if ($client === 'flash') {
            return view('client.flash', ['sso' => $launcher->ticket($user, $ipAddress)]);
        }

        $launch = $launcher->nitro($user, $ipAddress);
        abort_if($launch === null, 503, 'The game client is not configured correctly.');

        // Custom theme views may still build the URL from $sso; nitro() has already checked nitro_path.
        return view('client.nitro', ['sso' => $launch['sso'], 'nitroUrl' => $launch['url']]);
    }
}
