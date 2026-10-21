# Development and change guidelines

## Before editing

1. Identify the affected layer: `frontend/` markup/styles/JS, `backend/api/` routes, `backend/config/` DB access, or `database/` schema/data.
2. Check consumers and response shapes before changing JSON fields. Keep HTTP status codes and filters documented in [architecture](architecture.md).
3. Do not expose `backend/`, `.env`, raw `.sql` or unfinished admin/auth/audit placeholders through the web server.
4. Do not invent healthcare location coordinates, availability, verification dates or medical advice. Missing values should stay `NULL`; confirm updates with the sponsor and review clinical content before publication.
5. Keep seed demo recommendations clearly marked and not rendered as approved medical guidance.
6. Update the relevant docs and tests with behavior changes.

## Frontend changes

- Make edits in `frontend/index.html`, `styles.css` or `script.js`; keep same-origin `/api/` requests.
- Exercise Home range/timeframe flow, Learn article navigation, ZIP and type/name filters in Find Care.
- Validate JavaScript with `node --check frontend/script.js` when Node is installed; Node is not required to run the site.
- Run `python3 tests/test_frontend.py --mock` after installing Playwright per [setup](setup.md); use `--live` for end-to-end tests against the running site.

## PHP/API changes

- Keep SQL parameterized and public endpoints read-only. Validate query params and preserve stable JSON response/error formats.
- Run `find backend -name '*.php' -print0 | xargs -0 -n1 php -l` and `python3 tests/test_api.py --base-url http://127.0.0.1:8000` while the dev server is running.
- Test both successful and invalid requests; the database-independent education API should still work when MySQL is unavailable.

## Database edits

- Review [database management](database.md) before changing `schema.sql`, `seed.sql`, or using MySQL admin credentials.
- Add/change screening records in the source seed only if this is the repository's designated development dataset. Keep relationships and reference identifiers consistent. **Avoid manually changing the local database without tracking the change in source files.**
- After seed changes, use the explicit refresh command and update any fixed-count tests. The original documented baseline is 39 locations, 9 types, 4 BP categories, 4 demo recommendations; those numbers will legitimately change.
- Do not use seed imports to upgrade an existing shared/production DB. Use reviewed, ordered, reversible (where possible) migrations and a separate data publication process.
- Consider stable natural IDs plus unique indexes and idempotent data upserts for non-destructive updates, but only after verifying actual schema/index definitions.

## Suggested pull-request checks

- Document what changed and any compatibility impact.
- Run relevant syntax, seed, API and browser checks; attach failing command/output if tests cannot run.
- Clearly flag data validation or clinical review needed before deployment.
- If schema changed, include a migration and upgrade/rollback instructions; do not treat `schema.sql` as a migration.
