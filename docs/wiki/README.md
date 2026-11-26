# Documentation index

These pages are the guides for Atom CMS. They are kept in this repository and reviewed in pull requests.

## Start here

- [Home](Home.md)
- [1. Install and update Atom CMS](1.-Installing-Atom-CMS.md)
- [10. Headless mode](10.-Headless-mode.md)

## Set up your hotel

- [2. Language and translations](2.-Language-%26-translations.md)
- [3. Theme system](3.-Theme-system.md)
- [8. Nitro, Flash and AIR clients](8.-Nitro-setup.md)
- [9. Shop and PayPal](9.-Shop-setup.md)
- [7. FindRetros](7.-Findretros-setup.md)
- [Other hotel settings](Miscellaneous.md)

## Protect your hotel

- [4. Bot protection](4.-Bot-protection.md)
- [5. VPN and IP restrictions](5.-VPN-Prevention.md)
- [Registration controls](Registration-limiting.md)
- [Account limits](Account-limitation.md)
- [Username filtering](Username-filtering.md)
- [Two-factor authentication](Two%E2%80%90factor-authentication.md)

## Run your hotel

- [6. Maintenance mode](6.-Maintenance-mode.md)
- [Staff applications](Staff-applications.md)
- [Error logging](Error-logging.md)

## Contribute

- [0. Contribution guidelines](0.-Contribution-guidelines.md)
- [Credits](Credits.md)

## Changing settings

Change settings in Housekeeping (`/housekeeping`) whenever you can. Housekeeping refreshes the caches for you.

If you change the database by hand, Atom does not know about it. Refresh the cache yourself with `php artisan tinker`:

| What you changed | Run this |
| --- | --- |
| `website_settings` | `app(\App\Services\SettingsService::class)->refresh();` |
| Website permissions | `app(\App\Services\PermissionsService::class)->refresh();` |
| Housekeeping permissions | `app(\App\Services\HousekeepingPermissionsService::class)->refresh();` |
| Community data | `\App\Support\CommunityCache::forgetAll();` |

For `.env` changes, run `php artisan config:cache` in production or `php artisan config:clear` while you develop. Then reload long-running processes.

## Update these pages

Edit the pages in a pull request against `dev`. The page names match the old GitHub wiki, so a maintainer can publish reviewed pages to the [GitHub wiki](https://github.com/atom-projects/atom-cms/wiki). A pull request does not update the public wiki by itself.
