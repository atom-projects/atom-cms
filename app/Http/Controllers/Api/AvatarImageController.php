<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Avatar\AvatarImager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AvatarImageController extends Controller
{
    public function __invoke(Request $request, AvatarImager $imager): RedirectResponse
    {
        abort_unless($imager->enabled(), 404);

        $look = $request->query('figure');
        // The game saves looks with a dot at the end; the renderer draws the same figure without it.
        $figure = is_string($look) ? Str::chopEnd($look, '.') : null;
        $gender = is_string($figure) && $imager->isDrawable($figure)
            ? User::query()->whereLike('look', $look, caseSensitive: true)->value('gender')
            : null;

        if ($gender === null) {
            $figure = (string) setting('start_look');

            if (! $imager->isDrawable($figure)) {
                $figure = 'hr-100-61.hd-180-1.ch-210-66.lg-270-110.sh-305-62';
            }
        }

        return redirect()
            ->away($imager->signedUrl((string) $figure, strtoupper((string) $gender) === 'F' ? 'f' : 'm', $request->query()))
            ->header('Cache-Control', $gender === null ? 'no-store' : 'public, max-age=86400');
    }
}
