# Local setup — Ubuntu/Debian Linux and macOS

These instructions assume the repository includes `frontend/`, `backend/`, `database/schema.sql`, `database/seed.sql`, `tests/`, `.env.example`, and the accompanying `scripts/`. They follow the application's original README. The PHP development server is the cross-platform default; see [deployment](deployment.md) for Ubuntu/Debian Apache.

## Install dependencies

Choose the commands for the **operating system running the application**. The included `apache/carebp.conf` and Apache installation steps in [deployment](deployment.md) target **Ubuntu/Debian Linux**; macOS users can run the complete site with PHP's built-in server in the PHP server section below without Apache. Commands for other Linux distributions and macOS Apache installations differ and are not covered by the supplied virtual host.

### Ubuntu/Debian Linux

```bash

sudo apt update

sudo apt install mysql-server php-cli php-mysql

sudo systemctl enable --now mysql

```

**Ubuntu/Debian Linux, Apache option only:** Install Apache and the Apache PHP module before following [deployment](deployment.md):

```bash

sudo apt install apache2 libapache2-mod-php

sudo systemctl enable --now apache2

sudo apache2ctl configtest   # Expected: Syntax OK

```

### macOS

Install [Homebrew](https://brew.sh/) first if it is not installed. In macOS Terminal, run:

```bash

brew update

brew install php mysql

brew services start mysql

```

Homebrew's MySQL may start with an unprotected local root account; run `mysql_secure_installation` and set an administrator password before using the database outside an isolated local development machine. If you already have MySQL installed, use its existing installation rather than starting a second server.

**Both Ubuntu/Debian Linux and macOS:** Verify PHP 8.1+, the MySQL PDO extension, and the MySQL client:

```bash

php -v

php -m | grep -i pdo_mysql   # Must show pdo_mysql

mysql --version

```

If `pdo_mysql` is missing on macOS, confirm you are running Homebrew's PHP (`which php` and `php --ini`) rather than an older PHP installation. PHP CLI and Apache can use different configurations. On Ubuntu/Debian, if Apache later reports a missing driver, check its PHP runtime as well. An Apache PHP-FPM setup requires a separately configured handler; [deployment](deployment.md) assumes `libapache2-mod-php` on Ubuntu/Debian.

## Configure the application

Copy the sample environment and edit it. Use a **dedicated disposable local DB** and never commit credentials.

```bash
cp .env.example .env
chmod 600 .env
```

Set these keys in `.env` (replace the password):

```dotenv
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=carebp
DB_USER=carebp
DB_PASSWORD=YOUR_OWN_LOCAL_PASSWORD
```

The scripts accept only a local MySQL host and the database name `carebp` or one ending `_dev`; they refuse production-like environment indicators. Use the MySQL admin account only for setup/seed import. The PHP API runtime user is granted `SELECT` only.

## Initialize or update local database

```bash
./scripts/dev-setup.sh
```

On Ubuntu/Debian this uses `sudo mysql` with local socket root authentication by default. On macOS it prompts for the MySQL `root` password. For a Linux installation without socket-root authentication, set `CAREBP_DB_ADMIN_MODE=password` and optionally `CAREBP_DB_ADMIN_USER=YOUR_ADMIN_USER`. **Do not run the scripts with `sudo`:** the script itself handles the Ubuntu/Debian database administrative call.

The setup command creates the DB and runtime user if missing, imports `schema.sql` only if the database has no tables, and compares the SHA-256 of `seed.sql` against a local marker in `.carebp/`. It prompts before destructive seed replacement; no unattended reset occurs by default. See [database management](database.md) for details. An existing populated database without a marker is treated as unknown and requires explicit confirmation before overwrite.

## Run the website

### PHP development server

This is the shortest way to run the full frontend and PHP API. **It is for development only; do not expose it as a public production server.** From the project root, run the **same command in macOS Terminal or Ubuntu/Debian Linux**:

```bash

php -S 127.0.0.1:8000 -t frontend backend/router.php

```

Leave that terminal running and open **http://127.0.0.1:8000/** in your browser. The `-t frontend` argument serves frontend assets; `backend/router.php` handles the allowed `/api/` requests and blocks direct access to private files. Stop the server with **Ctrl+C**.

In a second terminal, check PHP and database connectivity:

```bash

curl -i http://127.0.0.1:8000/api/health.php

# Expect HTTP 200 and JSON containing "status":"ok","database":"connected"

curl -s 'http://127.0.0.1:8000/api/facilities.php?zip=30904'

```

If the page opens but location searches fail, check `/api/health.php`, MySQL, and `.env` first. If port `8000` is already occupied, choose another local port (for example, `8001`) and use that port for all URLs and tests.

## Validate the installation

### Verification and tests

For either server option, first confirm that `/api/health.php` returns `database: connected`, then search for ZIP `30904` in **Find Care**. The development seed should return Reese Library, Good Shepherd Baptist Church, and The HUB Augusta Locally Grown. Because the seeded coordinates are unknown, a map preview may be unavailable; location results still include address-search links.

### Syntax checks

**macOS and Ubuntu/Debian Linux**, from the project root:

```bash

find backend -name '*.php' -print0 | xargs -0 -n1 php -l

node --check frontend/script.js  # Optional: requires Node.js only for this check

```

### Live MySQL seed test

With `.env` configured and the development seed imported, run the same command on **macOS or Ubuntu/Debian Linux** (requires Python 3 and the `mysql` client):

```bash

python3 tests/test_seed.py

```

This read-only test uses the `mysql` command-line client to check counts, reference integrity, screening flags, and known ZIP/name results. It does not modify data or reimport the seed.

### Live HTTP API test

Run the command matching the server you started (**macOS or Ubuntu/Debian Linux**; the Apache example assumes Ubuntu/Debian):

```bash

# PHP built-in server:

python3 tests/test_api.py --base-url http://127.0.0.1:8000

# Apache:

python3 tests/test_api.py --base-url http://carebp.local

```

The smoke test checks the 39 public locations, ZIP `30904`, types and categories, filters, validation, pagination, demo flags, education routes, and static frontend delivery. With an **isolated throwaway test database only**, you can import `database/test_fixture.sql` after the seed and add `--fixtures` to the API test. That fixture inserts fictional `[TEST]` locations and an unapproved recommendation to confirm excluded content stays private. Never import it into a public database.

To test error handling **without** a connected database or `pdo_mysql` (not database connectivity), use:

```bash

python3 tests/test_api.py --base-url http://127.0.0.1:8000 --expect-db-missing

```

That mode expects HTTP `503` on database-backed routes while the database-independent education endpoint remains available.

### Optional browser tests

**macOS and Ubuntu/Debian Linux:** Use a Python virtual environment to avoid system-managed Python package restrictions (install `python3-venv` first on Ubuntu/Debian if necessary):

```bash

python3 -m venv .venv

source .venv/bin/activate

python3 -m pip install playwright

python3 -m playwright install chromium

python3 tests/test_frontend.py --mock

python3 tests/test_frontend.py --live --base-url http://127.0.0.1:8000

# For Apache, replace the live base URL with http://carebp.local

```

On **Ubuntu/Debian**, `--chromium-path /usr/bin/chromium` may work if you installed system Chromium; on **macOS**, omit that flag to use Playwright's installed browser, or specify the actual path to your own Chromium binary. `--mock` runs the real frontend against simulated API responses without a live server or database; `--live` exercises the actual frontend, PHP endpoints, and seeded MySQL data. Both cover Home, Learn, Find Care, ZIP validation, type/name filters, and suppression of demo recommendations.

For a manual frontend check, open Home, select **Yes**, choose a BP range and timeframe, and confirm that a range explanation appears without showing demo guidance as medical advice. In Learn, open **What do the numbers mean?** In Find Care, search `30904`; choosing **Church** should leave Good Shepherd Baptist Church.

## Next steps

See [troubleshooting](troubleshooting.md), [development guidelines](development.md) and [Apache deployment](deployment.md).
