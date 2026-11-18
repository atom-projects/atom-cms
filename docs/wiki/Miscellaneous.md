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

Every row in `website_settings` has a comment that explains it. If you edit the table by hand, see [changing settings](README.md#changing-settings).
