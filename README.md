# CareBP

CareBP is a browser-based blood-pressure education and screening-location directory built using a LAMP-compatible architecture.

The application uses:

- **Frontend:** HTML5, CSS3, JavaScript
- **Backend:** PHP 8.1+ with JSON API endpoints
- **Database:** MySQL 8+
- **Web Server:** Apache 2.4+ (LAMP) or PHP's built-in development server
- **Development Tools:** Python 3 and Git

There is no npm or Composer build step required to run the application.

## Project Status

**CareBP is an educational prototype, not a diagnostic or emergency medical service.**

Important limitations:

- Screening locations in the seed database have not been independently verified (`last_verified` is `NULL`).
- Demonstration recommendations must not be presented as clinical advice.
- Admin, authentication, and audit placeholders do not constitute a functioning management system.
- Sponsor and clinical review are required before public release.

---

## 1. System Requirements

### Required Software

| Component | Requirement |
|---|---|
| Operating System | Ubuntu/Debian Linux or macOS |
| PHP | 8.1 or newer |
| PHP Extensions | PDO, pdo_mysql |
| Database | MySQL 8+ |
| Python | Python 3 |
| Web Server | Apache 2.4+ or PHP development server |
| Version Control | Git |

**Recommended:** Apache with PHP and MySQL for Ubuntu/Debian LAMP environments.

The PHP built-in server is available as an alternative for local development.

See [Setup Documentation](docs/setup.md) for operating-system-specific instructions.

---

## 2. Clone the Repository

Clone the CareBP repository:

```bash
git clone <repository-url>
cd CareBP
```

Replace `<repository-url>` with the actual GitHub repository URL.

All following commands should be run from the project root unless otherwise specified.

---

## 3. Configure Environment Variables


```bash
cp .env.example .env
```

Open `.env` and configure the local MySQL connection credentials according to the provided template.

**Security requirements:**

- Never commit `.env` to Git.
- Do not place production credentials in `.env.example`.
- Do not share database passwords in screenshots or public repositories.
- Use separate database accounts and credentials for development and production.

---

## 4. Set Up the Database

Make sure MySQL is installed and running.

On Ubuntu/Debian:

```bash
sudo systemctl start mysql
sudo systemctl status mysql
```

Run the automated development setup script:

```bash
./scripts/dev-setup.sh
```

The setup script:

1. Configures the local development database using the configured MySQL administrator account.
2. Imports the database schema when needed.
3. Offers to import or refresh development seed data.
4. Prepares the database for local application testing.

**Warning:** Refreshing the development seed can replace existing seeded table data.

### Expanded ZIP code coverage

CareBP supports nearby-location searches using ZIP code coordinates
from Georgia, South Carolina, and Mississippi.

The development seed includes 1,602 ZIP Code Tabulation Areas (ZCTAs).
All ZIP code coordinates are automatically loaded when running `seed.sql`.

The original dataset is stored in `database/data/zip_codes_ga_sc_ms.csv`.
The Python scripts in `database/scripts/` are retained for future
data updates but are not required for normal setup.

**Important:** `seed.sql` resets development data, including ZIP code
coordinates. Only run it against a dedicated development database.
`schema.sql` is intended for an empty database, not as a migration.

### Check Database Status

```bash
./scripts/db-status.sh
```

### Refresh Development Data

```bash
./scripts/refresh-db.sh
```

Run the refresh script only when you intend to replace or reimport development seed data.

For additional information, see [Database Management](docs/database.md).

---

## 5. Launch CareBP

CareBP supports two launch methods.

### Option A: Apache Web Server (LAMP)

**Recommended for Ubuntu/Debian environments configured with Apache.**

This method runs CareBP through Apache rather than starting PHP's built-in development server manually.

#### Step 1: Install Apache and PHP Integration

On Ubuntu/Debian:

```bash
sudo apt update
sudo apt install apache2 php libapache2-mod-php php-mysql
```

Make sure the installed PHP version meets the CareBP requirement of PHP 8.1+.

#### Step 2: Enable Required Apache Modules

```bash
sudo a2enmod rewrite
```

Enable any additional modules required by the supplied virtual host configuration.

#### Step 3: Configure the CareBP Virtual Host

The repository provides an Apache configuration template:

```text
apache/carebp.conf
```

Review this file before enabling it.

Confirm that:

- `DocumentRoot` points to the appropriate CareBP public frontend directory.
- PHP execution is configured correctly.
- API requests are routed to the PHP backend.
- The configured hostname resolves to the local server.
- Sensitive configuration and database files are not publicly accessible.

Refer to [Apache and Deployment](docs/deployment.md) for the correct virtual host configuration and installation procedure.

#### Step 4: Enable the Apache Site

On Ubuntu/Debian, after reviewing and adapting the virtual host configuration:

```bash
sudo cp apache/carebp.conf /etc/apache2/sites-available/carebp.conf
sudo a2ensite carebp.conf
```

If the project uses a custom local hostname, configure hostname resolution according to the deployment documentation.

#### Step 5: Validate and Reload Apache

```bash
sudo apache2ctl configtest
sudo systemctl reload apache2
```

The Apache configuration test should return:

```text
Syntax OK
```

#### Step 6: Open CareBP

Open the hostname defined by the configured Apache virtual host.

For example, if `ServerName` is configured as `carebp.local` and that hostname resolves locally:

```text
http://carebp.local/
```

Verify the health endpoint at:

```text
http://carebp.local/api/health.php
```

The hostname above is an example; use the one configured in your actual Apache environment.

**Note:** Apache must correctly route `/api/` requests to the CareBP backend. The included virtual host configuration should be reviewed before assuming the example URLs will work.

### Option B: PHP Built-in Development Server

This method is intended for lightweight local development and testing.

It does not require Apache virtual host configuration.

From the CareBP project root, run:

```bash
php -S 127.0.0.1:8000 -t frontend backend/router.php
```

Open the frontend:

http://127.0.0.1:8000/

Verify the API health endpoint:

http://127.0.0.1:8000/api/health.php

Keep the terminal running while accessing the application.

Press `Ctrl+C` to stop the development server.

**Important:** PHP's built-in web server is for development and should not be used for public production deployment.

---

## 6. Verify Application Health

CareBP includes an API health-check endpoint:

```text
/api/health.php
```

The endpoint checks whether the PHP API is available and whether the database connection is working.

A successful response resembles:

```json
{
  "application": "CareBP",
  "status": "ok",
  "database": "connected"
}
```

For PHP development-server testing:

```bash
curl http://127.0.0.1:8000/api/health.php
```

For Apache, use the hostname configured in the virtual host:

```bash
curl http://carebp.local/api/health.php
```

If the endpoint fails, verify the PHP configuration, database credentials, MySQL service, and web server routing.

For troubleshooting instructions, see [Troubleshooting](docs/troubleshooting.md).

---

## 7. Running Tests

CareBP includes Python-based tests for backend APIs, frontend behavior, and database seed validation.

Tests are located in:

```text
tests/
```

Test files include:

- `tests/test_api.py`
- `tests/test_frontend.py`
- `tests/test_seed.py`

Use the testing setup and commands documented in the project development documentation.

Ensure the required test dependencies are installed and that the appropriate local services are running before executing integration tests.

See [Development Guidelines](docs/development.md).

---

## 8. Database Management

CareBP uses MySQL to store screening locations and related application data.

Relevant database resources include:

```text
database/
├── schema.sql
├── seed.sql
├── nearby_search.sql
├── zip_search.sql
├── test_fixture.sql
└── scripts/
    └── geocode_locations.py
```

### Database Status

After pulling changes from GitHub, run:

```bash
./scripts/db-status.sh
```

This checks whether the local development database seed is current.

### Seed Refresh

To explicitly reimport development seed data:

```bash
./scripts/refresh-db.sh
```

**Do not use development seed-refresh operations against production data.**

Read [Database Management](docs/database.md) before modifying schemas, importing seeds, or refreshing the database.

---

## 9. Development Workflow

CareBP uses Git for version control.

Development changes should generally be made in a dedicated feature or testing branch rather than directly on `main`.

### Create a Branch from Main

```bash
git switch main
git pull origin main
git switch -c feature-name
```

Commit and push changes:

```bash
git add .
git commit -m "Describe changes"
git push -u origin feature-name
```

### Update an Existing Branch from Main

```bash
git switch feature-name
git fetch origin
git merge origin/main
```

Resolve any merge conflicts before committing additional work or pushing changes.

**Note:** Commit or stash uncommitted changes before pulling, switching branches, or merging when necessary.

---

## 10. Application Security

CareBP may involve sensitive health-related information. Security and privacy must be considered before any public deployment.

Requirements include:

- Keep database credentials and environment configuration private.
- Use prepared SQL statements and validate user input.
- Escape untrusted data rendered in HTML.
- Restrict database and administrative permissions.
- Prevent direct web access to configuration files and private directories.
- Use HTTPS for public deployments.
- Avoid exposing detailed database or PHP errors to visitors.
- Review and secure all administrative and authentication functionality before release.
- Do not store identifiable patient health information without appropriate privacy and security controls.

**Production warning:** The current admin, authentication, and audit placeholders are not a complete management or security system.

The application should not be considered ready for public clinical use without appropriate technical, privacy, security, and clinical reviews.

---

## 11. Documentation

| Document | Description |
|---|---|
| [Architecture and API](docs/architecture.md) | Application architecture, endpoints, features, and data flow |
| [Setup](docs/setup.md) | Cross-platform setup, PHP server, configuration, and dependencies |
| [Database Management](docs/database.md) | Schema, seeds, refresh operations, and production protections |
| [Development Guidelines](docs/development.md) | Frontend, backend, database, and content development practices |
| [Troubleshooting](docs/troubleshooting.md) | MySQL, PHP, Apache, hostname, and testing issues |
| [Apache and Deployment](docs/deployment.md) | Apache virtual host configuration, Ubuntu/Debian deployment, and release restrictions |

---

## 12. Deployment Notes

CareBP can be operated locally using Apache or PHP's built-in development server.

For Apache deployments:

1. Install and configure Apache, PHP, and MySQL.
2. Adapt `apache/carebp.conf` for the target environment.
3. Configure the appropriate virtual host hostname and document root.
4. Verify PHP execution and API routing.
5. Protect environment and database configuration files.
6. Validate database connectivity.
7. Test the frontend and API endpoints.
8. Complete security and clinical reviews before considering public release.

Apache service and virtual host commands in this README apply to Ubuntu/Debian Linux.

macOS development is supported using the PHP development server. Other Linux distributions may require different package and service commands.

For detailed instructions, see [Apache and Deployment](docs/deployment.md).

---

## Disclaimer

CareBP is an educational and demonstration application.

It does not provide medical diagnosis, treatment, emergency response, or professional medical advice.

Screening-location records included in development seed data have not been independently verified.

Information provided through CareBP should not replace consultation with a qualified healthcare professional.