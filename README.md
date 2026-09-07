# scrapefeed — cPanel deployment

## What this is
A self-contained PHP + MySQL SaaS: public landing page, user signup/login,
a dashboard to run Shopify/WooCommerce product extractions, CSV/JSON
export with download, and an admin panel for users/settings/queue.
No Node, no Python, no Composer — plain PHP 8+ with the `curl` and `pdo_mysql`
extensions, which every standard cPanel PHP install has enabled by default.

## Folder layout (important — do not flatten this)

```
your-account-root/
├── public_html/        ← upload contents of this folder as your site's web root
├── includes/            ← upload as a SIBLING of public_html, NOT inside it
├── exports/              ← created automatically by the installer; leave outside public_html
└── cron_process_queue.php  ← upload at the account root (same level as includes/)
```

`includes/` holds `config.php` (your DB password) and the extraction engine.
Keeping it outside `public_html` means it is never reachable by a URL, even
if `.htaccess` is ever misconfigured or disabled by the host.

If your hosting only lets you upload into `public_html` and nothing above
it, ask support to enable "outside document root" access, or as a fallback
put `includes/` inside `public_html` and keep the `.htaccess` rule in place
(it blocks `/includes/*` at the web server level) — the folder-outside-root
approach is simply the safer default.

## Steps

1. **Create the database.** In cPanel → MySQL® Databases: create a database
   and a user, add the user to the database with ALL PRIVILEGES. Note the
   three values (usually prefixed with your cPanel username).

2. **Upload the files** into the layout above, via File Manager or FTP.

3. **Set folder permissions.** `exports/` needs to be writable by PHP —
   755 is usually enough on shared hosting; the installer creates it for
   you on first run.

4. **Run the installer.** Visit `https://yourdomain.com/install/` in a
   browser. Enter your DB credentials, then create the first admin
   account. This writes `includes/config.php` and creates all tables —
   you won't need phpMyAdmin for this.

5. **Set up the cron job.** In cPanel → Cron Jobs, add:
   - Common Settings: Every 5 Minutes
   - Command: `php /home/YOURCPANELUSER/cron_process_queue.php`

   This picks up any extraction queued for background processing (jobs
   with no product limit, or a limit over 300 — see `new-extraction.php`).
   Small, bounded jobs run immediately when the user submits the form, so
   the cron job is a safety net for the bigger ones.

6. **Log in as admin** at `/admin/login.php` with the account you just
   created. From there: Settings (site name, free signup credits), Users
   (adjust credits, disable accounts, grant admin), Extractions (see every
   job across all users, requeue failed ones).

## What it can and can't extract

- **Shopify**: any live storefront's public `/products.json` feed — no
  API key needed, works on every store.
- **WooCommerce**: tries the public Store API first (best data), falls
  back to the WordPress REST API, then to parsing the rendered `/shop/`
  pages if neither API is exposed.
- **Not included**: JavaScript-rendered storefronts that need a headless
  browser to produce any HTML at all. Shared cPanel hosting can't run
  Chromium/Playwright, so that tier from the original spec isn't in this
  build. Nearly all Shopify and WooCommerce stores render their catalog
  server-side, so this covers the large majority of real stores.

6. **Delete or rename `public_html/install/` once setup is done.** The
   wizard checks for `includes/config.php` and refuses to re-run once it
   exists, but there's no reason to leave the folder reachable at all —
   remove it after you've confirmed login works.

## Security notes
- Passwords are hashed with `password_hash()` (bcrypt).
- CSRF tokens on every state-changing form.
- Export files are served through `download.php` with an ownership
  check — the files themselves sit outside `public_html` and are never
  directly linkable.
- `config.php` is never committed/shipped with real credentials — the
  installer writes it for you.
