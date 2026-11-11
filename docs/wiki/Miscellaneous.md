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

Every row in `website_settings` has a comment that explains it. If you edit the table by hand, see [changing settings](README.md#changing-settings).

## Avatar images

By default the CMS shows avatars with the imager in the `avatar_imager` website setting, which is the official one.

A hotel can draw its own avatar images instead, so custom clothing looks as it does in the hotel. The PlusEMU camera service does this with the client's own renderer. To turn it on:

1. Set `AVATAR_IMAGER_SECRET` in `.env` to the same secret the camera service uses (`AVATAR_IMAGER_SECRET` there too). It must have at least 32 characters.
2. Serve the folder the service writes to at `/avatars/`, and pass a missing image on to the service. The camera service readme has the nginx block. Set `AVATAR_IMAGER_URL` when the images live on another address.
3. Run `php artisan config:cache`.

The CMS signs every figure with the secret, so nobody else can ask your hotel for new images. Images are stored once and stay available while the service is down. With an empty secret the CMS goes back to the `avatar_imager` setting.

Themes build an address with `avatar_url($user, [...])`. The options are `direction`, `head_direction`, `gesture`, `action` (`wav`, `sit`, `lay`, `wlk`, `kiss`, `laugh`), `size` (`s`, `m`, `l`), `headonly`, `dance`, `effect`, `item` and `animated`. `animated` gives a moving image, for example `avatar_url($user, ['dance' => 2, 'animated' => true])`.

Raise the `avatar_imager_version` website setting after you change the art of existing clothing. The hotel then draws every image again.
