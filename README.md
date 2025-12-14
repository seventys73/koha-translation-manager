# Koha Translation Manager

Laravel 12 application that lets you ingest Koha PO files, edit translations in a database, and export updated PO files ready to deploy back to a Koha server.
Application can pull/push .po files directily from/to koha instalation path if it lives in the same server as Koha.

## Requirements
- PHP >= 8.2 with ext-zip, ext-pdo, ext-fileinfo enabled
- Composer
- Node.js 18+ and npm (for Vite build)
- MySQL 8+ (or MariaDB 10.6+) database
- Web server pointing to `public/` with PHP-FPM (or `php artisan serve` for a lightweight run)



## Configuration Notes
- **Koha PO directory**: stored in the `settings` table and configurable from Settings. Default is `/usr/share/koha/misc/translator/po`.
- **Target language filter**: stored in settings; used when copying PO files from the Koha path (remote import).
- **Working directories**: raw PO files live in `storage/po`; exported files are written to `storage/exports`; downloadable ZIPs are placed in `storage/app/`.

## How the App Works
- **Authentication & verification**: Dashboard and translation tools require `auth` + `verified`.
- **Settings**: Set the Koha PO root and the target language code on `/settings`. The target language text is used to match filenames during remote import.
- **Dashboard**
  - *Local Import*: upload one or more `.po` files; they are stored under `storage/po` (subfolders preserved).
  - *Remote Import*: copies `.po` files from the configured Koha directory that contain the target language token (e.g. fa-Arab) in the filename.
  - *Sync*: reconcile DB translations with the PO files in `storage/po` (adds new source strings and reapplies saved translations back onto the PO files).
  - *Export ZIP*: writes updated `.po` files to `storage/exports`, and streams a ZIP to download.
  - *Push to Server*: exports then copies the `.po` files from `storage/exports` back into the configured Koha directory.
- **Translation editing**
  - `/translations`: filter by text or file, inline-edit `msgstr`, or open a detail view.
  - Each row is keyed by checksum (`context::msgid`), so edits persist across imports/syncs even if ordering changes.
- **Data model**: `translations` table stores `file_path`, `msgid`, `msgstr`, optional `context`, and a `checksum`. `settings` table stores key/value pairs for Koha path and target language.

** after uploading/pushing .po files, run "sudo koha-translate --update  <language_code>" on koha server.
Example:
```bash
sudo koha-translate --update  fa-Arab
```

## Production Installation
1) Clone and enter the project  
```bash
git clone <repo-url> koha-translation-manager
cd koha-translation-manager
```
2) Copy the environment file and set a key  
```bash
cp .env.example .env
php artisan key:generate
```
3) Configure `.env`  
- `APP_URL=https://your-domain`  
- Database: `DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD`  
- Sessions: keep `SESSION_DRIVER=database` and run `php artisan session:table` (or switch to `file` if you prefer)  
- Queue: set `QUEUE_CONNECTION=database` and run a worker, or change to `sync` if you don’t want a queue worker  
- Mail: set SMTP credentials so users can receive verification emails
4) Install PHP dependencies (optimized, no dev)  
```bash
composer install --no-dev --optimize-autoloader
```
5) Run migrations (includes users, settings, translations, cache, jobs, sessions if you generated it)  
```bash
php artisan migrate --force
```
6) Install and build frontend assets  
```bash
npm ci
npm run build
```
7) Permissions  
Ensure the web server user can write to `storage/` and `bootstrap/cache/`. The app will create `storage/po` and `storage/exports` as needed.
8) Web server  
Point your virtual host/document root to `public/` and reload PHP-FPM. (For a quick run: `php artisan serve --env=production`.)
9) Create your first user  
Visit `/register` in the browser (or create a user manually) and verify the email address—dashboard routes require verified accounts.
