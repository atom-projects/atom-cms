# Two-factor authentication

Two-factor authentication (2FA) asks for a code from an app, as well as the password.

## Turn it on for your account

1. Open **User settings > Two factor**.
2. Confirm your password.
3. Scan the code with an authenticator app.
4. Enter a code from the app to finish.
5. Save the recovery codes in a safe place. You need them if you lose the app.

## Require it for staff

In Housekeeping, under CMS settings, set `force_staff_2fa=1`. Every user with a rank of at least `min_staff_rank` must set up 2FA. This also applies to Housekeeping.

## Keep your app key

Laravel uses `APP_KEY` to encrypt the 2FA secrets and recovery codes. Never change `APP_KEY`. See [production configuration](1.-Installing-Atom-CMS.md#production-configuration) and [Laravel Fortify](https://laravel.com/docs/13.x/fortify#two-factor-authentication).

In [headless mode](10.-Headless-mode.md), use the JSON routes: `GET /api/v1/me/two-factor` and `POST /two-factor-challenge`. The full list is in `docs/api/openapi.json`.
