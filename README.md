<div align="center">
<img src="https://i.imgur.com/9ePNdJ4.png" alt="Atom CMS"/>

A modern, community-driven website (CMS) for retro hotels, built with Laravel.

[![Discord](https://img.shields.io/badge/Discord-Join%20Server-5865F2?style=flat&logo=discord&logoColor=white)](https://discord.gg/pP6HyZedAj)

[Live demo: Dusk](https://dusk.atomcms.dev) • [Live demo: Atom](https://atom.atomcms.dev) • [Documentation](docs/wiki/README.md)

</div>

>[!NOTE]
>Disclaimer: Educational Use Only
>
> Atom CMS is provided as an educational resource for learning purposes only. The creators and contributors are not responsible for any misuse or unintended consequences arising from its use. By using Atom CMS, you agree to take full responsibility for your actions and ensure compliance with all applicable laws and regulations in your jurisdiction.

## About

Atom CMS runs the website of your hotel: registration, login, news, shop and an admin panel (housekeeping). It has two themes, Atom and Dusk. It works with the Arcturus Morningstar 3.5.5 and Ada emulators.

## Setup

You need PHP 8.5 or newer, Composer 2, Node.js (LTS), and MariaDB or MySQL.

1. Download Atom CMS and run the installer:

   ```bash
   git clone https://github.com/DennisObject/atomcms.git
   cd atomcms
   composer setup
   ```

2. Answer the questions. The installer sets up the database and builds your theme.
3. Point your web server to the `public/` folder. Open `/installation` in your browser and enter your hotel details.

Keep the site private until step 3 is done. The installer never overwrites an existing emulator database.

See the [installation guide](docs/wiki/1.-Installing-Atom-CMS.md) for Windows, Ada, permissions and production settings.

## Update

1. Back up your database, your `.env` file and your uploaded files.
2. Put the site in maintenance mode and get the new code. Keep your `.env` file and the `storage/` folder.

   ```bash
   php artisan down
   ```

3. Install and build:

   ```bash
   composer install --no-dev --prefer-dist --optimize-autoloader
   npm ci
   npm run build:atom
   npm run build:dusk
   php artisan config:clear
   php artisan migrate --force
   php artisan storage:link
   php artisan optimize
   php artisan reload
   ```

4. Bring the site back:

   ```bash
   php artisan up
   ```

Do not run `composer setup` or `migrate:fresh` to update. See [Updating an installed hotel](docs/wiki/1.-Installing-Atom-CMS.md#updating-an-installed-hotel).

## More

- [Documentation](docs/wiki/README.md)
- [Headless mode](docs/wiki/10.-Headless-mode.md) - use Atom as an API for your own website
- [Contributing](docs/wiki/0.-Contribution-guidelines.md)
- [Credits](docs/wiki/Credits.md)
