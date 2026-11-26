<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\Client\ClientLaunchService;
use App\Support\AuthenticatedUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AirClientController extends Controller
{
    /**
     * Send the player to the AIR launcher with a fresh login ticket.
     */
    public function __invoke(Request $request, ClientLaunchService $client): RedirectResponse
    {
        abort_unless(config('habbo.client.air_enabled'), 404);

        $sso = $client->ticket(AuthenticatedUser::from($request), $request->ip() ?: 'unknown');

        return redirect()->away($client->airUrl($sso))->header('Cache-Control', 'no-store, private');
    }
}
