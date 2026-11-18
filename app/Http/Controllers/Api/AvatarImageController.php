<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Avatar\AvatarImager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AvatarImageController extends Controller
{
    public function __invoke(Request $request, AvatarImager $imager): RedirectResponse
    {
        abort_if(blank(config('habbo.avatar_imager.secret')), 404);

        $figure = $request->query('figure');
        $user = is_string($figure) && $imager->isDrawable($figure)
            ? User::query()->where('look', $figure)->first()
            : null;

        if ($user === null) {
            $figure = (string) (setting('start_look') ?: 'hr-100-61.hd-180-1.ch-210-66.lg-270-110.sh-305-62');
        }

        $gender = $user !== null && strtoupper((string) $user->gender) === 'F' ? 'f' : 'm';

        return redirect()
            ->away($imager->signedUrl((string) $figure, $gender, $request->query()))
            ->header('Cache-Control', 'public, max-age=86400');
    }
}
