# Other hotel settings

Change these in Housekeeping, under CMS settings. Your theme and emulator decide which settings you see.

| Setting | What it does |
| --- | --- |
| `start_credits`, `start_duckets`, `start_diamonds` | Currency a new user gets at registration. |
| `give_hc_on_register` | Set to `1` to give Habbo Club at registration. |
| `hc_on_register_duration` | Length of that Habbo Club in **seconds**. `2592000` is 30 days. It is not an end date. |
| `cms_color_mode` | Starting color mode: `light` or `dark`. A choice saved in the browser can win. |
| `cms_logo` | URL or path of the hotel logo. |
| `cms_header` | Header image of the Atom theme. |
| `cms_me_backdrop` | Background of the user page in the Atom theme. |

## Avatar images drawn by the hotel

`avatar_imager` is the address the site puts before a figure, as in `https://www.habbo.com/habbo-imaging/avatarimage?figure=`. To draw avatars with your own client, including custom clothing, run the PlusEMU camera service with avatar images turned on. Then:

1. Set `AVATAR_IMAGER_SECRET` in `.env` to the same random secret as the camera service. Use at least 32 characters. The default image address is `APP_URL` followed by `/avatars`. Set `AVATAR_IMAGER_URL` to use a different address.
2. Set `avatar_imager` to `https://<your site>/api/avatar-image?figure=`.

`/api/avatar-image` reads the same parameters as Habbo's imager and redirects to a signed image. It draws only looks that a user wears. Any other figure gets the `start_look` image. The route allows 300 requests per minute per IP address. Apply a rate limit to `/avatars/` at the proxy as well, because browsers can request signed images directly.

## Checked values

Atom refuses to save a value that does not fit its setting:

- `nitro_path` must be a path on this site, such as `/client/nitro`, or a URL on `APP_URL`, `ATOM_FRONTEND_URL` or an origin in `NITRO_ALLOWED_ORIGINS`. Atom also checks it before it opens the client. With another value, the client page returns 503 and no login ticket is sent.
- `housekeeping_url` must be a path on this site, such as `/housekeeping`, or a URL on `APP_URL`.
- Links and images (`discord_invitation_link`, `avatar_imager`, `cms_logo`, `cms_header`, `cms_me_backdrop`, `badges_path`, `group_badge_path`, `room_thumbnail_path`, `furniture_icons_path`, `catalog_icons_path`, `ads_picture_path`) must be a path on this site, an `https://` URL, or a URL on `APP_URL`.
- File and folder paths (`flash_external_texts_file`, `nitro_external_texts_file`, `badge_path_filesystem`, `ads_path_filesystem`) must be absolute and inside an allowed folder. A drive-letter path, such as `C:\Hotel\hotel-files\ExternalTexts.json`, works only when Atom runs on Windows. Atom always allows `/var/www/gamedata`, `/var/www/hotel/hotel-files` and `C:\Hotel\hotel-files`. Add other folders as additional roots in `HOTEL_FILE_ROOTS`, comma separated. Atom does not read, write or delete files through a stored path outside these folders. It writes the refused value to `storage/logs/laravel.log`, and the badge or ads folder stays off until you fix the setting.
- `theme` must be an installed theme. `rcon_ip` must be an IP address or a host name, and `rcon_port` a port from 1 to 65535.
- On and off settings, such as `force_staff_2fa` and `maintenance_enabled`, must be `0` or `1`. Rank settings, such as `min_staff_rank`, must be 1 or higher. `max_accounts_per_ip` must be 0 or more.

Only staff with the housekeeping permission `manage_sensitive_website_settings` can change `nitro_path`, `housekeeping_url`, the file and folder paths, `rcon_ip`, `rcon_port`, `force_staff_2fa` and `min_staff_rank`. New and existing hotels get this permission for the highest rank only. If Atom cannot read the top rank from the emulator during `migrate` or `db:seed`, it does not create the permission and prints a line that says so. Then nobody can change these settings until you add `manage_sensitive_website_settings` with your top rank under Housekeeping > Housekeeping permissions.

Every row in `website_settings` has a comment that explains it. If you edit the table by hand, see [changing settings](README.md#changing-settings).
