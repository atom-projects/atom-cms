# Error logging

## Keep debug off

Set `APP_DEBUG=false` on a public hotel. Never show debug pages to visitors. See [Laravel debug mode](https://laravel.com/docs/13.x/deployment#debug-mode).

## Find the logs

Set `LOG_CHANNEL` in `.env`, then rebuild the config cache.

| Value | Result |
| --- | --- |
| `stack` (default) | Writes to `storage/logs/laravel.log` |
| `daily` | Writes a new file each day in `storage/logs/` and removes old ones |
| `stderr` | Writes to standard error. Good for containers. |

Atom has no `/log-viewer` page. Housekeeping activity logs show what staff did. They are not error logs.

If Laravel cannot write its log, check the PHP and web server logs. Check that the web process can write to `storage/` and `bootstrap/cache/`.

## Share a log safely

Reproduce the error on a private development or test server if you need details. Before you share a log in an issue or chat, remove passwords, tokens, personal data and request data.

See [Laravel logging](https://laravel.com/docs/13.x/logging).
