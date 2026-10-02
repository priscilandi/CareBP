# CareBP — LAMP installation and testing

CareBP is a browser-based blood-pressure education and screening-location directory. Its stack is **Linux + Apache + MySQL 8+ + PHP 8.1+**, with a plain **HTML/CSS/JavaScript frontend**. The frontend makes same-origin requests to six public PHP JSON endpoints. There is no npm build, Composer install, or JavaScript framework required to run the site.

The supplied `database/seed.sql` contains **9 location types, 39 sponsor-supplied screening locations, 4 BP categories, and 4 demonstration recommendations**. All 39 location entries are active and marked as offering a BP screening. However, `last_verified` is `NULL`, and the seed does not independently establish that equipment is available today. Check current availability before directing someone to a site. **The four `Demo guidance` rows are placeholder content, not clinical instructions; the frontend deliberately hides them as medical guidance.**

> **Safety and data handling:** This is an educational prototype, not a diagnosis, an emergency-response service, or a continuously verified facility directory. Do not deploy it publicly until the sponsor confirms current location and medical content. The supplied admin pages and authorization/audit includes are empty placeholders; there is no usable admin login or editing workflow.

> **macOS:** the full Apache setup in Section 5 uses Debian/Ubuntu-only tools (`apt`, `a2ensite`, `systemctl`). macOS-specific commands are called out inline below wherever they differ, and **Section 6 (PHP development server) is the recommended path for local development on a Mac** - it uses the same database and API without needing Apache configuration at all.

## 1. Project layout

```text
CareBP-main/
├── .env.example              database connection template
├── apache/carebp.conf        restricted Apache virtual host
├── backend/
│   ├── api/                  public JSON endpoints
│   ├── config/database.php   PDO MySQL connection + local .env reader
│   ├── includes/functions.php
│   └── router.php            PHP development-server router
├── database/
│   ├── schema.sql            MySQL tables and indexes
│   ├── seed.sql              39 supplied locations and reference data
│   ├── test_fixture.sql      optional negative tests; NOT real locations
│   └── zip_search.sql        sample ZIP query for MySQL console
├── frontend/                 index.html, styles.css, script.js
└── tests/                    seed, HTTP API, and Chromium UI tests
```

## 2. Install the LAMP components

### Debian or Ubuntu

On **Debian or Ubuntu**, install Apache, **MySQL Server 8+**, PHP, and PHP's MySQL PDO extension:

```bash
sudo apt update
sudo apt install apache2 mysql-server php libapache2-mod-php php-mysql
sudo systemctl enable --now mysql apache2
php -v                          # Requires PHP 8.1 or later
php -m | grep -i pdo_mysql      # Must show pdo_mysql
sudo apache2ctl configtest      # Expected: Syntax OK
```

This setup uses MySQL throughout. If using PHP-FPM rather than `libapache2-mod-php`, configure Apache's PHP-FPM handler separately. If the `pdo_mysql` check is empty, database-backed endpoints will fail even if MySQL is running.

### macOS (Homebrew)

```bash
# Install Homebrew first if you don't have it: https://brew.sh
brew install php mysql
brew services start mysql
php -v                          # Requires PHP 8.1 or later
php -m | grep -i pdo_mysql      # Must show pdo_mysql
```

Wiring Homebrew's PHP into Apache on macOS (`mod_php` vs. PHP-FPM, with config paths that vary by Homebrew version and chip architecture) is inconsistent enough across setups that this project hasn't tested it directly — everyone who's run CareBP on a Mac has used either this dev server or a Linux VM (e.g. Multipass) instead. **For local development on macOS, skip Section 5 (Apache) and use Section 6 (PHP development server) instead** — same database, same API, no Apache config needed. If you want full Apache, running the Debian/Ubuntu instructions in Sections 2 and 5 inside a Linux VM (Multipass, UTM, etc.) is the better-tested route than wiring up Apache directly via Homebrew.

## 3. Create and seed a **development** database

From the project root (the directory containing `backend`, `frontend`, and `database`), open the administrative SQL shell:

```bash
sudo mysql
```

> **macOS note:** Homebrew's MySQL doesn't use Linux's socket-based root authentication, so `sudo mysql` won't work the same way. Instead run `mysql -u root` (no `sudo`; add `-p` if you set a root password).

Run this SQL, replacing the password with your own strong local password:

```sql
CREATE DATABASE carebp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'carebp'@'127.0.0.1' IDENTIFIED BY 'REPLACE_WITH_STRONG_PASSWORD';
CREATE USER 'carebp'@'localhost' IDENTIFIED BY 'REPLACE_WITH_STRONG_PASSWORD';
GRANT SELECT ON carebp.* TO 'carebp'@'127.0.0.1';
GRANT SELECT ON carebp.* TO 'carebp'@'localhost';
EXIT;
```

If the user/database already exists, adapt these statements rather than running `CREATE` twice. The runtime PHP user needs **SELECT only** because the public API is read-only. Run schema and seed imports as a database administrator:

```bash
sudo mysql carebp < database/schema.sql
sudo mysql carebp < database/seed.sql
```

> **macOS:** drop `sudo` — `mysql carebp < database/schema.sql` and `mysql carebp < database/seed.sql` (add `-u root -p` if required, matching however you connected in step 3 above).

**Important: `seed.sql` resets data.** It deletes existing recommendations, locations, categories, and types (in foreign-key-safe order), resets their AUTO_INCREMENT counters, then inserts the supplied development dataset. You may re-run it on a *dedicated development database*, but **never run it against production or independently maintained data**. `schema.sql` is intended for an empty database; it is not a migration.

Verify the import:

```bash
sudo mysql carebp -e 'SELECT COUNT(*) AS locations FROM healthcare_locations; SELECT COUNT(*) AS types FROM location_types; SELECT COUNT(*) AS categories FROM bp_categories; SELECT COUNT(*) AS recommendations FROM recommendations;'
# Expected row counts: 39, 9, 4, 4 (in that order)
sudo mysql carebp < database/zip_search.sql
# ZIP 30904: Reese Library, Good Shepherd Baptist Church, The HUB Augusta Locally Grown
python3 tests/test_seed.py  # Real MySQL read-only checks; requires configured .env
```

(macOS: drop `sudo` from the `mysql` commands above, as noted earlier.)

The developer seed omits mobile-only or nonpublic BP-monitor entries described in its SQL comments. It also leaves unknown coordinates, hours, phone numbers, and last-verification timestamps as `NULL`.

### Optional test-only negative cases

To verify that unpublished or inactive data is excluded, import `database/test_fixture.sql` **only into an isolated throwaway test database** after `seed.sql`. It adds two explicitly fictional `[TEST]` entries (one inactive, one without screening) and an unapproved recommendation. These must **never** appear in the public API. Run `seed.sql` again to erase the test additions in that disposable environment. Never include the test fixture on a real public site.

## 4. Configure PHP's database connection

The server reads a private `.env` file from the **project root**, which must stay outside Apache's document root:

```bash
cp .env.example .env
# Edit .env with your own password. No quotation marks are needed:
```

```dotenv
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=carebp
DB_USER=carebp
DB_PASSWORD=REPLACE_WITH_STRONG_PASSWORD
```

Shell environment variables override `.env`. The repository ignores `.env`; never commit it, move it into `frontend/`, or paste secrets into screenshots. On a typical Apache installation, ensure `www-data` can read `.env` without granting world access:

```bash
sudo chown "$USER":www-data .env
sudo chmod 640 .env
```

> **macOS note:** there's no `www-data` group on macOS by default. If you're using the PHP development server (Section 6), it runs as your own user account, so you can skip the `chown`/`chmod` steps above entirely — just make sure the file isn't world-readable (`chmod 600 .env` is fine). If you do set up Apache on macOS, substitute whatever user/group Homebrew's `httpd` runs as (check the `User`/`Group` directives in `httpd.conf` — it's often your own username) wherever this guide says `www-data`.

## 5. Run the full website on Apache

> **macOS note:** the commands in this section (`apt`, `a2ensite`, `a2dissite`, `/etc/apache2/...`, `systemctl`) are Debian/Ubuntu-specific and have no direct equivalent on macOS, and wiring Homebrew's PHP into Apache isn't something this project has tested. The better-tested options on macOS are the PHP development server in **Section 6**, or running Sections 2–5 as written inside a Linux VM (e.g. Multipass) — which is how this project has actually been run on a Mac.

For this example the deployment path is `/var/www/carebp`. Copy the **whole project** so the PHP API can access private configuration; Apache will expose only `frontend/` and the allowed `backend/api/` scripts.

```bash
sudo mkdir -p /var/www/carebp
sudo cp -a . /var/www/carebp/
# Configure /var/www/carebp/.env with the LOCAL server password.
sudo chown root:www-data /var/www/carebp/.env
sudo chmod 640 /var/www/carebp/.env
sudo cp apache/carebp.conf /etc/apache2/sites-available/carebp.conf
sudo a2ensite carebp.conf
sudo apache2ctl configtest       # Expected: Syntax OK
sudo systemctl reload apache2
```

The sample virtual host uses `ServerName carebp.local` and port **80**, for **local development only**. Add `127.0.0.1 carebp.local` to your computer's hosts file (`/etc/hosts` on both Linux and macOS), then open **http://carebp.local/**. If installing elsewhere, update *both* `/var/www/carebp` paths in `apache/carebp.conf`. If Apache's default site intercepts the hostname, disable the default site with `sudo a2dissite 000-default.conf` and reload Apache.

For any publicly accessible installation, configure a real domain, HTTPS/TLS, access logs, and current sponsor/clinical verification. Do not set `DocumentRoot` to the project root; that would risk exposing `.env`, SQL files, and backend internals.

Check backend connectivity and sample location search:

```bash
curl -i http://carebp.local/api/health.php
# HTTP 200: {"application":"CareBP","status":"ok","database":"connected"}
curl -s http://carebp.local/api/categories.php
curl -s 'http://carebp.local/api/facilities.php?zip=30904'
```

## 6. Quicker local alternative: PHP development server

This uses exactly the same MySQL database and API, but skips Apache while developing. It is **not suitable for public hosting**.

> **Recommended for macOS.** This is the simplest, most reliable way to run CareBP locally on a Mac — it avoids the Apache/PHP wiring on bare macOS that this project hasn't tested (see the note in Section 2).

```bash
php -S 127.0.0.1:8000 -t frontend backend/router.php
```

Open **http://127.0.0.1:8000/**. The allowlisted router intentionally prevents direct access to `.env`, SQL files, internal includes, and empty admin placeholders.

## 7. Test backend, database seed, and frontend

The repository includes automated tests with **distinct verification levels**. Only the live database checks verify MySQL; the mock-UI test is not a database test.

**A. Syntax checks** (PHP and Node.js):

```bash
find backend -name '*.php' -print0 | xargs -0 -n1 php -l
node --check frontend/script.js
```

**B. Real MySQL seed checks**: After importing `schema.sql` and `seed.sql` into a dedicated development MySQL database and configuring `.env`, run `python3 tests/test_seed.py`. This test calls the `mysql` command-line client to verify seeded counts, reference integrity, screening flags, and known ZIP/name results. It does not modify database records or rerun the destructive development seed. The MySQL client must be installed and available on PATH (on macOS, `brew install mysql` from Section 2 already puts it there).

**C. Real HTTP + MySQL integration**: Start Apache or the PHP dev server, confirm `/api/health.php` says `database: connected`, then run:

```bash
python3 tests/test_api.py --base-url http://127.0.0.1:8000
# Or with Apache: --base-url http://carebp.local
```

The smoke test checks 39 searchable locations, 30904's three expected facilities, all types and category rows, active/screening filters, validation, pagination, demo flags, education endpoints, and static frontend delivery. If you imported the optional negative fixture into a throwaway database, add `--fixtures` to verify it stays hidden.

To verify **graceful error handling only** on a machine without a database or `pdo_mysql`, use:

```bash
python3 tests/test_api.py --base-url http://127.0.0.1:8000 --expect-db-missing
```

That mode expects HTTP **503** for database-backed endpoints and checks that the independent education endpoint continues to respond. It does **not** test database connectivity.

**D. Browser frontend test** (optional development dependency):

```bash
python3 -m pip install playwright
# If Chromium is not already installed:
python3 -m playwright install chromium
python3 tests/test_frontend.py --mock
python3 tests/test_frontend.py --live --base-url http://127.0.0.1:8000
```

Add `--chromium-path /usr/bin/chromium` if using system Chromium, or point it at Playwright's own install path (e.g. the path printed by `python3 -m playwright install chromium` — this applies the same way on macOS). `--mock` loads the project's real HTML/JavaScript **in memory** and simulates API replies based on three supplied seed locations: it needs no server or database. `--live` loads the actual website, queries PHP/MySQL, and expects the full 39-location seed. Both modes exercise Home, Learn, Find Care, ZIP validation, type/name filters, and suppression of the placeholder demo recommendations.

**Manual check:** Open Home, select **Yes**, choose a BP range and timeframe, and confirm a range explanation appears while demo recommendations are not displayed as advice. In Learn, open “What do the numbers mean?”. In Find Care, search `30904` and confirm the three named locations and map links; selecting **Church** should leave only Good Shepherd Baptist Church. The seed has no coordinates, so a map preview is expected to be unavailable; each result still links to an address search.

## 8. Public API routes

| Endpoint | Purpose |
|---|---|
| `GET /api/health.php` | Check PHP and live DB connectivity |
| `GET /api/categories.php` | Return the four ordered BP categories |
| `GET /api/location-types.php` | Return nine types and public screening-location counts |
| `GET /api/facilities.php?zip=30904` | Paginated active screening locations; supports `zip`, `q`, `city`, `state`, `type_id`, `page`, `per_page`, and `id` |
| `GET /api/recommendations.php?category_id=1` | Approved rows by category; supplied rows are flagged `is_demo: true` |
| `GET /api/education.php?topic=numbers-mean` | General education article independent of the DB |

All public endpoints are read-only. Invalid ZIPs return HTTP **422**; unknown public locations return **404**. Endpoints that need a missing DB or missing `pdo_mysql` return **503**. Other database failures generally return **500** with a generic JSON error; details are logged server-side, not leaked to browsers.

## 9. Troubleshooting and implementation limits

- **503 `database_unavailable`:** Start MySQL (`sudo systemctl start mysql` on Linux, `brew services start mysql` on macOS), check the `.env` credentials/host, and confirm `pdo_mysql` is installed in the **same PHP runtime** you're running the server with.
- **500 `database_error`:** Confirm the schema and seed were imported into `carebp`, all four tables exist, and `carebp` has SELECT privileges.
- **macOS: `a2ensite`/`a2dissite`/`systemctl` not found:** these are Debian/Ubuntu helper scripts with no macOS equivalent. Use the PHP development server (Section 6) for local development, or edit Homebrew's `httpd.conf` directly (with an `Include` line and `brew services restart httpd`) if you need to test under Apache specifically.
- **404 on API routes in Apache:** Confirm the `Alias /api/` entry, PHP handler, and virtual-host activation. Verify `DocumentRoot` remains `frontend/`.
- **Empty or incorrect Find Care:** Reimport only in a dedicated development database. Seeded ZIP `30904` has exactly three locations. Rerunning `seed.sql` deletes all records in the four seeded tables first.
- **The map preview is blank:** This is expected; latitude and longitude are `NULL` in the supplied seed. Use the “View on map” link.
- **PHP source shown/downloaded:** Your Apache PHP handler is not running correctly. Disable public access until fixed.
- **Admin features:** `backend/admin/`, `backend/includes/auth.php`, and `backend/includes/audit.php` are currently placeholders. The supplied Apache config does not make them public. No sign-in, data entry, clinical review workflow, or audit history has been implemented.
