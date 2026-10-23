# CareBP admin integration ( IN DEVELOPMENT )

## Configure development environment

In the project-root `.env`, use the existing DB_HOST, DB_PORT, DB_NAME, DB_USER and DB_PASSWORD settings and add:

    ADMIN_PASSWORD=choose-a-long-unique-local-password

Never commit `.env` to Git. Prefer an environment variable over storing the password in `.env`. This is a simple development-only shared password, not suitable for deployment.

Start from the project root:

    php -S 127.0.0.1:8000 -t frontend backend/router.php

Visit http://127.0.0.1:8000/admin/login.php, then go to Facilities.

## Implementation notes

- Facilities supports add/edit/delete, prepared PDO statements, validation, CSRF protection, and HTML escaping. It uses `backend/config/database.php::getDatabase()` and matches `healthcare_locations.location_id` and the required `type_id`.
- Delete permanently removes rows; consider implementing archive (`is_active=0`) instead for production.
- Recommendations/Approvals/Audit Log remain placeholders. The provided schema has `recommendations.approved` but has no explicit audit-log or pending-facility-change tables.
- Development router `/admin/` allowlist updated. Apache file was also updated.
- No complete authentication system: password-based protection is for **local development only**. Do not deploy this publicly without proper user authentication/authorization, rate limits, HTTPS, secure cookie settings and audit trails.
- Existing database data is not modified by installing files. Facility actions in the UI DO CHANGE the DB.

## Code attribution

Comments in `backend/admin/facilities.php`, `add.php`, `edit.php`, and `remove.php` identify the CRUD concepts adapted from Kendra's original `index.php`, `add.php`, `edit.php`, and `remove.php`. These files have been modified to use the CareBP schema and backend. `admin_helpers.php` is new integration code supporting those workflows. The admin dashboard `index.php` is based on the project's existing dashboard, not Kendra's CRUD page.
