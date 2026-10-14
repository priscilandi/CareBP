# Troubleshooting

## Common errors

**Platform scope:** Apache service, package, and vhost commands in this README are for **Ubuntu/Debian Linux**; `/etc/hosts` and PHP-CLI instructions are given separately for macOS where needed. The PowerShell instructions apply to **Windows clients**, not to the Ubuntu/Debian Apache server.

| Problem | What to check |

| --- | --- |

| `503 database_unavailable` | Verify MySQL (`sudo systemctl status mysql` on Ubuntu/Debian; `brew services list` on macOS), `.env` credentials, and `pdo_mysql` in the **PHP runtime serving the request**. |

| `500 database_error` | Schema and seed were imported into the configured database, all four tables exist, and the PHP database user has `SELECT` privileges. |

| `carebp-local` does not load from macOS/Linux | Edit `/etc/hosts` **on the browser computer** using `sudo nano /etc/hosts` and map `carebp-local` to `127.0.0.1` (same machine) or Apache’s reachable IP (different machine). Configure Apache `ServerAlias carebp-local`, then check with `curl -i http://carebp-local/api/health.php` (see [deployment](deployment.md)). |

| `carebp-local` does not load from Windows | Find Apache's reachable IP (`hostname -I` on Linux); add `IP_ADDRESS carebp-local` to the **Windows** hosts file using an administrator PowerShell session and Notepad; flush DNS; verify `Test-NetConnection carebp-local -Port 80`. Configure Apache `ServerAlias carebp-local` too (see [deployment](deployment.md)). |

| Frontend opens, but API returns 404 on Apache | The intended virtual host is enabled; `/api/` alias points to the deployed `backend/api/`; `DocumentRoot` points to `frontend/`. |

| Frontend opens, but API returns 404 on PHP's built-in server | Start the server **from the project root** with `-t frontend backend/router.php` rather than serving `frontend/` without the router. |

| Empty or unexpected Find Care results | Check the database in `.env` and the seed counts. ZIP `30904` should have three sample locations. Only reimport `seed.sql` in a disposable development database because it deletes existing records. |

| No map preview | The supplied seed leaves latitude/longitude as `NULL`. Use the location's “View on map” address link. |

| PHP files are displayed/downloaded on Apache | The PHP handler is misconfigured. Disable public access while correcting it. |

| Admin pages or login are missing | `backend/admin/`, `backend/includes/auth.php`, and `backend/includes/audit.php` are placeholders; no login, editing, clinical review, or audit workflow has been implemented. |


## Apache is serving the wrong or outdated directory

Use these steps if Apache is running and `carebp.local` opens, but the page is outdated, Git changes are not appearing, or Apache appears to be serving the wrong copy of CareBP.

### Ubuntu/Debian Apache server

Run these commands **on the Ubuntu/Debian system where Apache is installed**. If Apache is running in WSL, run them inside the WSL Ubuntu terminal.

1. **Confirm Apache's configured CareBP paths.**

   ```bash
   grep -E 'DocumentRoot|Alias' /etc/apache2/sites-enabled/carebp.conf
   ```

   Expected:

   ```text
   DocumentRoot /var/www/carebp/frontend
   Alias /api/ /var/www/carebp/backend/api/
   ```

   If CareBP was installed somewhere other than `/var/www/carebp`, update both paths in `carebp.conf`.

2. **Confirm the CareBP virtual host is active.**

   ```bash
   sudo apache2ctl -S
   ```

   Look for a line similar to:

   ```text
   *:80 carebp.local (/etc/apache2/sites-enabled/carebp.conf:4)
   ```

   If the site is not enabled:

   ```bash
   sudo a2ensite carebp.conf
   sudo apache2ctl configtest
   sudo systemctl reload apache2
   ```

3. **Check the files Apache is actually serving.**

   ```bash
   ls -la /var/www/carebp/frontend/
   ```

   Confirm that `index.html`, `script.js`, and `styles.css` exist and have the expected modification dates.

4. **Locate the Git repository.**

   Do not run Git commands from `/etc/apache2/` or `/var/www/` unless that directory is actually a Git repository.

   If you are unsure where the repository is:

   ```bash
   find ~ -maxdepth 5 -type d -name .git 2>/dev/null
   ```

   Then change into the repository directory, for example:

   ```bash
   cd ~/projects/carebp
   git status -sb
   git log -1 --oneline
   ```

5. **Compare the repository files with the deployed Apache files.**

   From the CareBP Git repository:

   ```bash
   diff -q frontend/index.html /var/www/carebp/frontend/index.html
   diff -q frontend/script.js /var/www/carebp/frontend/script.js
   diff -q frontend/styles.css /var/www/carebp/frontend/styles.css
   ```

   No output means the files are identical. If `diff` reports differences, the Apache deployment is not using the latest repository files.

6. **Pull the latest changes and deploy them to Apache.**

   From the CareBP Git repository:

   ```bash
   git switch main
   git pull --ff-only origin main
   ```

   Then copy the updated project into the Apache deployment directory:

   ```bash
   sudo rsync -av \
     --exclude='.git' \
     --exclude='.env' \
     ./ /var/www/carebp/
   ```

   Excluding `.env` prevents the deployed database credentials from being overwritten.

7. **Verify Apache is serving the updated frontend.**

   ```bash
   curl -I -H "Host: carebp.local" http://127.0.0.1/
   ```

   Expected:

   ```text
   HTTP/1.1 200 OK
   ```

   To compare a specific file:

   ```bash
   curl -fsS -H "Host: carebp.local" http://127.0.0.1/script.js | sha256sum
   sha256sum /var/www/carebp/frontend/script.js
   ```

   Matching hashes confirm Apache is serving the deployed `script.js`.

8. **Refresh the browser without using cached files.**

   Open:

   ```text
   http://carebp.local/
   ```

   Then use a hard refresh (`Ctrl+Shift+R` on Windows/Linux or `Cmd+Shift+R` on macOS).

> Apache normally does not need to be restarted for ordinary HTML, CSS, JavaScript, or PHP source changes. The important step is making sure the Git changes are copied into the directory Apache is configured to serve.

### Windows with WSL

If Apache runs inside WSL Ubuntu, the Apache and Git deployment commands above must be run **inside WSL**, not from Windows PowerShell.

Use Windows only to verify what the browser can reach.

1. In WSL, confirm the Apache paths:

   ```bash
   grep -E 'DocumentRoot|Alias' /etc/apache2/sites-enabled/carebp.conf
   sudo apache2ctl -S
   ```

2. In WSL, verify the deployed files:

   ```bash
   ls -la /var/www/carebp/frontend/
   ```

3. From Windows PowerShell, verify that Windows can reach the site:

   ```powershell
   curl.exe -I --noproxy "*" http://carebp.local/
   ```

   If `carebp.local` does not resolve, follow the Windows hosts-file troubleshooting steps in the `carebp-local does not load from Windows` entry above.

4. If the PowerShell request returns `200 OK` but Firefox/Chrome/Edge still shows older content, perform a hard refresh with `Ctrl+Shift+R` and check the browser Developer Tools **Network** tab for cached `index.html`, `script.js`, or `styles.css`.

5. If the Git repository is stored on the Windows filesystem instead of inside WSL, identify its WSL path under `/mnt/c/...` and run the deployment commands from that repository path. For example:

   ```bash
   cd /mnt/c/Users/USERNAME/Documents/CSCI_4712/CareBP
   git status -sb
   git pull --ff-only origin main
   sudo rsync -av \
     --exclude='.git' \
     --exclude='.env' \
     ./ /var/www/carebp/
   ```

### Linux browser/client

If the browser and Apache are on the **same Linux computer**, the Ubuntu/Debian steps above are sufficient.

If the browser is on a different Linux computer, the directory checks and `rsync` commands still run on the Apache server. On the browser computer, only verify that the hostname resolves to the Apache server and refresh the browser cache.

To test from the browser computer:

```bash
curl -I http://carebp.local/
```

If the hostname does not resolve, check `/etc/hosts` on the browser computer as described in the `carebp-local does not load from macOS/Linux` entry above.

### macOS browser/client

The supplied CareBP Apache configuration is for Ubuntu/Debian, so the Apache directory checks above should be run on the **Ubuntu/Debian Apache server**, not on macOS.

If macOS is only being used to open the website:

1. Verify the server responds:

   ```bash
   curl -I http://carebp.local/
   ```

2. If the site opens but looks outdated, use a hard refresh:

   ```text
   Cmd + Shift + R
   ```

3. If `carebp.local` does not resolve, check `/etc/hosts` on the Mac as described in the `carebp-local does not load from macOS/Linux` entry above.

If CareBP is instead being run locally on macOS with PHP's built-in server, this Apache directory troubleshooting section does not apply. Start the PHP server from the CareBP project root so it serves the current Git checkout directly:

```bash
php -S 127.0.0.1:8000 -t frontend backend/router.php
```

## After pulling seed changes

Run `./scripts/db-status.sh`. If seed content differs, use `./scripts/refresh-db.sh` on a disposable local database, then `python3 tests/test_seed.py` and the API tests. If the script rejects an existing schema, use a reviewed migration rather than running `schema.sql` over populated tables.

## Operating-system notes

- **Ubuntu/Debian Linux:** check `systemctl status mysql`, `php -m`, `sudo mysql` and Apache logs if using Apache.
- **macOS:** check `brew services list`, `which php`, `php --ini`, `php -m` and MySQL admin account credentials. The supplied Apache vhost is **not** a macOS vhost.
- **Other Linux distributions:** substitute your distribution's MySQL/PHP package manager and service tooling; the provided PHP development-server command is otherwise the same.
