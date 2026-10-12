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

## After pulling seed changes

Run `./scripts/db-status.sh`. If seed content differs, use `./scripts/refresh-db.sh` on a disposable local database, then `python3 tests/test_seed.py` and the API tests. If the script rejects an existing schema, use a reviewed migration rather than running `schema.sql` over populated tables.

## Operating-system notes

- **Ubuntu/Debian Linux:** check `systemctl status mysql`, `php -m`, `sudo mysql` and Apache logs if using Apache.
- **macOS:** check `brew services list`, `which php`, `php --ini`, `php -m` and MySQL admin account credentials. The supplied Apache vhost is **not** a macOS vhost.
- **Other Linux distributions:** substitute your distribution's MySQL/PHP package manager and service tooling; the provided PHP development-server command is otherwise the same.
