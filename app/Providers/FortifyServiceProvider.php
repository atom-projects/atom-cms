<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Requests\ForgotPasswordRequest;
use App\Models\Articles\WebsiteArticle;
use App\Models\Miscellaneous\CameraWeb;
use App\Models\User;
use App\Services\Auth\PasswordVerifier;
use App\Services\Community\CameraService;
use App\Support\FrontendUrls;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\AttemptToAuthenticate;
use Laravel\Fortify\Actions\EnsureLoginIsNotThrottled;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);

        $this->configureRateLimiting();
        $this->configureViews();
        $this->authenticate();
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request): Limit {
            $username = $request->input('username');
            $identity = is_string($username)
                ? 'username:' . Str::transliterate(Str::lower($username))
                : 'invalid-username';

            return Limit::perMinute(5)->by($identity . '|' . $request->ip());
        });
        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)->by($request->session()->get('login.id')));
        RateLimiter::for('two-factor-settings', fn (Request $request) => Limit::perMinute(6)->by(
            ($request->user()?->getAuthIdentifier() ?? 'guest') . '|' . $request->ip(),
        ));
        // Keyed by account only, so new IP addresses do not give a stolen session more guesses.
        // Password fields are not trimmed, so a value of only spaces is still a guess.
        RateLimiter::for('current-password', function (Request $request): Limit {
            if ($request->input('current_password') === null && $request->input('password') === null) {
                return Limit::none();
            }

            return Limit::perMinute(5)->by($request->user()?->getAuthIdentifier() ?? $request->ip());
        });
        // Counts every valid address, with or without an account, so a 429 does not reveal which addresses exist.
        // The window equals the link lifetime, so a blocked address always still holds a live link.
        // The key is only trimmed and lowercased, so look-alike addresses never share a budget.
        RateLimiter::for('password-reset-links', function (Request $request): Limit {
            if (Validator::make($request->only('mail'), (new ForgotPasswordRequest)->rules())->fails()) {
                return Limit::none();
            }

            return Limit::perMinutes(max(1, (int) config('habbo.password_reset_token_time')), 3)
                ->by('mail:' . $request->string('mail')->trim()->lower());
        });
    }

    private function configureViews(): void
    {
        Fortify::loginView(fn () => view('auth.login', $this->authPageData(4, 4)));

        Fortify::registerView(function (Request $request) {
            if (setting('disable_registration') === '1') {
                return redirect(app(FrontendUrls::class)->route('welcome'))->withErrors(['register' => __('Registration is currently disabled.')]);
            }

            return view('auth.register', [
                'referral_code' => $request->route('referral_code'),
                ...$this->authPageData(4, 2),
            ]);
        });

        Fortify::confirmPasswordView(fn () => view('auth.passwords.confirm'));
        Fortify::twoFactorChallengeView(fn () => view('auth.two-factor-challenge'));
    }

    /**
     * Latest articles and camera photos shown alongside the auth forms.
     *
     * @return array{articles: Collection<int, WebsiteArticle>, photos: Collection<int, CameraWeb>}
     */
    private function authPageData(int $articles, int $photos): array
    {
        return [
            'articles' => WebsiteArticle::latest('id')->take($articles)->has('user')->with('user:id,username,look')->get(),
            'photos' => app(CameraService::class)->latestPhotos($photos),
        ];
    }

    private function authenticate(): void
    {
        Fortify::authenticateUsing(function (Request $request): ?User {
            $username = $request->input('username');
            $password = $request->input('password');

            if (! is_string($username) || ! is_string($password)) {
                return null;
            }

            $user = User::query()->where('username', $username)->first();

            return $user !== null && app(PasswordVerifier::class)->verify($user, $password) ? $user : null;
        });

        Fortify::authenticateThrough(function () {
            return array_filter([
                config('fortify.limiters.login') ? null : EnsureLoginIsNotThrottled::class,

                Features::enabled(Features::twoFactorAuthentication()) ? RedirectIfTwoFactorAuthenticatable::class : null,
                AttemptToAuthenticate::class,
                PrepareAuthenticatedSession::class,
            ]);
        });
    }
}
