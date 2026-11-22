<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Mail\ResetPasswordMail;
use App\Models\PasswordResetToken;
use App\Models\User;
use App\Support\FrontendUrls;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function __invoke(): View
    {
        return view('auth.passwords.forget');
    }

    public function submitForgetPassword(ForgotPasswordRequest $request): RedirectResponse|JsonResponse
    {
        $user = $this->accountFor($request->string('mail')->value());

        // Do not reveal whether the email exists, to prevent account enumeration.
        if ($user !== null) {
            $token = Str::uuid()->toString();

            PasswordResetToken::where('email', $user->mail)->delete();
            PasswordResetToken::create([
                'email' => $user->mail,
                'token' => PasswordResetToken::hashToken($token),
            ]);

            Mail::to($user->mail)->queue(new ResetPasswordMail($token));
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => __('We have e-mailed your password reset link!')]);
        }

        return back()->with('success', __('We have e-mailed your password reset link!'));
    }

    public function showResetPassword(string $token): View|RedirectResponse
    {
        if (! $this->validToken($token)) {
            return $this->expired();
        }

        return view('auth.passwords.reset', ['token' => $token]);
    }

    public function submitResetPassword(ResetPasswordRequest $request, string $token): RedirectResponse|JsonResponse
    {
        $prt = $this->validToken($token);
        $owner = $prt?->owner();

        if ($prt === null || $owner === null) {
            $prt?->delete();

            if ($request->expectsJson()) {
                return response()->json(['code' => 'invalid_reset_token', 'message' => __('This token has expired!')], 422);
            }

            return $this->expired();
        }

        $owner->changePassword($request->password);
        $prt->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => __('Your password has been successfully reset!')]);
        }

        return redirect(app(FrontendUrls::class)->route('login'))->with('success', __('Your password has been successfully reset!'));
    }

    /**
     * The mail column's collation may ignore case and accents, so the account with
     * the exact address wins, and several loose matches without one find nobody.
     */
    private function accountFor(string $mail): ?User
    {
        $matches = User::where('mail', $mail)->get();

        return $matches->first(fn (User $user): bool => $user->mail === $mail)
            ?? ($matches->containsOneItem() ? $matches->first() : null);
    }

    private function validToken(string $token): ?PasswordResetToken
    {
        $prt = PasswordResetToken::where('token', PasswordResetToken::hashToken($token))->first();

        if ($prt !== null && $prt->hasExpired()) {
            $prt->delete();

            return null;
        }

        return $prt;
    }

    private function expired(): RedirectResponse
    {
        return to_route('forgot.password.get')->withErrors(['message' => __('This token has expired!')]);
    }
}
