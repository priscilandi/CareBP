# Database management — local development

CareBP uses MySQL 8+ and four documented core tables: `healthcare_locations`, `location_types`, `bp_categories`, and `recommendations`. The application connects via PDO using `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD` from `.env`. Public PHP endpoints are **read-only**; the dedicated runtime MySQL user has `SELECT`, not write permissions. Admin credentials are used separately by local maintenance scripts.

## What the files are for

| File | Purpose | Safe to re-run on populated data? |
|---|---|---|
| `database/schema.sql` | Create tables/indexes in an **empty** DB | **No**. Not a migration. |
| `database/seed.sql` | Replace development locations/types/categories/demo recommendations | **No**. Deletes/reinserts seed data and resets IDs. |
| `database/test_fixture.sql` | Add fictional records for negative tests | Only in an isolated test DB. |
| `database/zip_search.sql` | Example ZIP lookup | Read query as documented in original README. |

**Development only:** The original README documents 39 locations, 9 types, 4 BP categories, and 4 demo recommendations; these are baseline counts, not eternal invariants. Some sample locations have unknown coordinates, hours, contact details, and `last_verified = NULL`. Do not infer or manufacture missing values. The exact schema and seed SQL were not provided with this documentation update; review their actual definitions before applying structural changes or writing production data migrations.

## First-time setup

From the repo root, install MySQL/PHP and create `.env` as explained in [setup](setup.md), then run:

```bash
./scripts/dev-setup.sh
```

By default, Ubuntu/Debian uses `sudo mysql` with local root socket authentication; macOS asks for the MySQL administrator password. Other Linux distributions can use either mode depending on MySQL installation. The script:

1. Rejects non-local MySQL hostnames, unexpected database names, and explicitly production/staging environments.
2. Creates the specified local database if missing.
3. Imports `schema.sql` **only if no tables exist** (it does not migrate populated databases).
4. Creates runtime MySQL users for `localhost` and `127.0.0.1` if absent; grants them only `SELECT` on the local database. **An existing user's password is never automatically changed.**
5. Checks the local `seed.sql` SHA-256 against `.carebp/<database-name>.seed.sha256` and, if changed/missing, asks for explicit confirmation before replacing development data.

The script uses Python 3, MySQL CLI, and `sudo` where applicable. It does not require npm/Composer. **It does not perform a full production-grade schema inspection**, provision a public deployment or make data changes without confirmation.

## After pulling code from Git

```bash
./scripts/db-status.sh
```

The status command prints local table counts and compares the `seed.sql` checksum against `.carebp/<database-name>.seed.sha256`. If the seed changed, run:

```bash
./scripts/refresh-db.sh
```

You must type `RESET carebp` (or `RESET your_name_dev`) to authorize the refresh. The seed import deletes/reinserts the seeded table rows and resets IDs; **local edits to these rows will be lost**. The checksum marker is saved only **after** a successful seed import. Keep `.carebp/` ignored by Git; it records your machine's import history.

Alternatively, `./scripts/dev-setup.sh` also detects seed changes and offers the refresh, so developers may run it after every pull. Starting the PHP server **does not** auto-reset your database. The status check is based on seed file content, not Git history or a comparison of actual database records. If a table is modified manually, the seed checksum cannot detect that drift.

### How frequently do I refresh?

Only after `seed.sql` changes (or when you intentionally want to discard local seed edits). No daily/weekly schedule is necessary. Pure frontend/PHP edits do not require reseeding. Schema changes require reviewed migrations; do **not** solve a schema mismatch by repeatedly importing `schema.sql`.

## Admin connection options

- **Ubuntu/Debian Linux (default socket-root):** `CAREBP_DB_ADMIN_MODE=sudo ./scripts/dev-setup.sh`. This invokes `sudo mysql` with the local MySQL installation's socket-based admin access; if your installation differs, use password mode.
- **macOS Homebrew MySQL:** `./scripts/dev-setup.sh`; prompts for local MySQL root password. A freshly unsecured Homebrew root account may have a blank password; secure it with `mysql_secure_installation`.
- **Other Linux distros or password-based root:** `CAREBP_DB_ADMIN_MODE=password CAREBP_DB_ADMIN_USER=root ./scripts/dev-setup.sh`. The script securely prompts for the password. You may set `CAREBP_DB_ADMIN_PASSWORD` for isolated CI, but avoid leaking secrets in terminal history and shared environments.

Run the scripts as your normal OS user, **not** using `sudo ./scripts/...`. Temporary MySQL credentials are written to mode-0600 files and removed after execution. The privileged admin account is separate from the read-only application account.

### Automation in isolated CI only

Interactive confirmation is intentional. For an ephemeral DB created solely for CI, you can opt in explicitly:

```bash
CAREBP_ALLOW_DEV_DB_RESET=1 ./scripts/dev-setup.sh --yes
```

This flag **must not** be used on shared/staging/production databases or personal datasets. The guard is defensive, not a substitute for correct credentials, least privilege, isolated networking, backups, and deployment policy. The scripts permit the historical local database name `carebp`, so a production database must never be made accessible via the local privileged admin connection.

## Editing seed data safely

1. Confirm the change with the data owner/sponsor; for health guidance, obtain clinical review.
2. Edit `database/seed.sql` while preserving actual schema constraints, reference relationships and any intentional `NULL` unknown fields.
3. If the change alters baseline counts or known ZIP/name results, update `tests/test_seed.py`, `tests/test_api.py`, and relevant frontend expectations. Original tests expect 39 locations, 9 types, 4 categories and 4 demo recommendations.
4. Run `./scripts/refresh-db.sh` on a **disposable local** DB.
5. Run `python3 tests/test_seed.py`, `python3 tests/test_api.py --base-url http://127.0.0.1:8000`, and any impacted browser tests after launching the PHP development server.
6. Commit the revised seed and tests, **not** `.env`, `.carebp/`, SQL dumps with personal data, or actual credentials.

If a developer needs to preserve hand-entered local records, **do not refresh**. Instead create a separate throwaway development DB or design a non-destructive migration/data update.

## Schema changes and production data

`schema.sql` is intended for empty database creation and **must not** be treated as an upgrade script. For existing databases:

- Create a numbered, reviewed migration in an appropriate `database/migrations/` folder (when that system is introduced), with preconditions, backups, rollback/forward plan and tests.
- Use stable external identifiers or unique keys for location upserts, and specify how discontinued records are handled; this requires inspecting the real schema first.
- Never run `seed.sql`, `test_fixture.sql`, or the development scripts against production data.
- Limit database administrators to the environment that needs the operation; keep public APIs read-only until a secure authenticated admin workflow exists.

## Reset and failure cases

- **Unknown marker:** populated DB with no `.carebp/<database-name>.seed.sha256` is **not** assumed safe. Explicit confirmation is required to replace its seed data.
- **Interrupted import:** if MySQL/SQL errors occur after the seed starts, seeded tables may be partially replaced. The checksum marker will not be updated. For a disposable DB, fix the issue and rerun refresh; investigate/restore from backup if any data matters.
- **Existing incomplete schema:** the scripts stop rather than running `schema.sql` over populated tables. Review/migrate manually.
- **Wrong runtime password:** if a `carebp` MySQL user already exists, the script does not rotate its password. Update `.env` or the database account explicitly via an administrator.
- **Checksum matches but rows differ:** the marker cannot detect out-of-band DB edits. Run data tests and inspect/refresh if the DB is disposable.

