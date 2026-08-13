# Catalog FlashUI

The catalog editor is an Atom CMS Filament resource, not a standalone Laravel 9 housekeeping page. That keeps it compatible with the current upstream architecture and makes future upstream merges possible.

## Requirements

- Atom CMS from this fork or a compatible `ObjectRetros/atomcms` checkout
- PHP 8.5+
- Composer 2
- Node.js LTS and npm
- MySQL 8 or MariaDB 10+
- An Arcturus database containing `catalog_pages`, `catalog_items` and `items_base`

## Fresh installation

```bash
git clone https://github.com/Damjann-dev/atomcms-hk-catalogpage.git
cd atomcms-hk-catalogpage
composer setup
```

The standard Atom CMS installer imports the bundled Arcturus database, runs migrations and builds the selected theme. Visit `/housekeeping` after the installer finishes.

## Existing Atom CMS installation

Make a database backup and a Git branch first. Then either use this fork as the main checkout or cherry-pick the FlashUI commit into an up-to-date upstream checkout.

After applying the code:

```bash
composer install
npm ci
npm run build:atom   # use build:dusk when Dusk is active
php artisan migrate --force
php artisan optimize:clear
```

The feature does not replace catalog data and does not ship a hotel database dump. Existing Atom CMS housekeeping permissions are used. A user needs access to housekeeping and the catalog-management permission to edit pages and items.

## What is changed

- `app/Filament/Resources/Hotel/CatalogEditors/Pages/ManageCatalogEditor.php`
  selects the first root page on initial load while preserving Livewire state.
- `resources/views/filament/resources/hotel/catalog-editors/pages/manage-catalog-editor.blade.php`
  provides the FlashUI layout around the existing Filament table and Livewire actions.
- `resources/views/filament/resources/hotel/catalog-editors/pages/partials/catalog-tree.blade.php`
  provides the FlashUI tree rows and drag handles.
- `resources/css/filament/housekeeping/theme.css`
  contains the scoped FlashUI skin.

The feature continues to use Atom CMS's catalog models, policies, reorder service, search service, Filament actions and table actions. This means catalog writes remain subject to the normal application authorization and validation rules.

## Development

```bash
npm run dev:atom
php artisan serve
```

Open `/housekeeping/hotel/catalog-editors` in the local application. The exact housekeeping URL can be changed by the normal Atom CMS configuration.

## Vibe-coded notice

The FlashUI work in this repository was vibe coded with AI assistance. It is shared for experimentation and community use. Inspect the diff, run the test suite and verify permissions, catalog writes and emulator behaviour before deploying it to a live hotel.
