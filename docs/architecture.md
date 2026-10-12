# Architecture and public APIs

## What the website does

CareBP provides three main user-facing flows: **Home** (blood-pressure range and timeframe explanation), **Learn** (general educational information), and **Find Care** (search/filter directory of screening locations, including ZIP lookup). It is not a clinical decision tool; demo recommendations are not shown as medical instructions.

## System overview

```text
Browser: frontend/index.html + styles.css + script.js
  ├── GET /api/education.php → educational content (database independent)
  └── GET /api/*.php → PHP 8.1+ JSON endpoints → PDO MySQL → MySQL 8+
Server (development): backend/router.php maps /api/* while serving frontend/
Server (Apache): frontend/ DocumentRoot + restricted backend/api/ alias
Private: .env, backend/config/, backend/includes/, database/
```

There is no npm build or Composer dependency install. Browser requests use same-origin `/api/` URLs; do not load `frontend/index.html` via a `file://` URL. The public endpoints are read-only. The runtime DB account should have only `SELECT`; schema and seed imports require a separate privileged admin login.

## Repository layout

### Existing application directories

Run the commands in this README **from the project root**—the directory containing `frontend/`, `backend/`, `database/`, and `.env.example`—unless a command says otherwise.

```text

CareBP-main/

├── .env.example              Database connection template

├── apache/carebp.conf        Example restricted Apache virtual host

├── backend/

│   ├── api/                  Public PHP JSON endpoints

│   ├── config/database.php   PDO MySQL connection and .env reader

│   ├── includes/functions.php

│   └── router.php            Router for PHP's built-in server

├── database/

│   ├── schema.sql            Tables and indexes

│   ├── seed.sql              Development locations and reference data

│   ├── test_fixture.sql      Optional fictional negative-test records

│   └── zip_search.sql        Example MySQL ZIP lookup

├── frontend/                 index.html, styles.css, script.js

└── tests/                    Database, HTTP API, and browser tests

```

**Keep the project root private.** Only `frontend/` should be the web document root; `/api/` must be routed to the allowed PHP scripts in `backend/api/`. Never expose `.env`, SQL files, internal PHP includes, or unfinished admin files.

## Public endpoints

### API reference

The frontend calls these same-origin, read-only endpoints regardless of whether Apache or PHP's development server is used:

| Endpoint | Purpose |

| --- | --- |

| `GET /api/health.php` | Verify PHP and live database connectivity |

| `GET /api/categories.php` | Four ordered BP categories |

| `GET /api/location-types.php` | Nine location types and public screening counts |

| `GET /api/facilities.php?zip=30904` | Paginated active screening locations; supports `zip`, `q`, `city`, `state`, `type_id`, `page`, `per_page`, and `id` |

| `GET /api/recommendations.php?category_id=1` | Approved rows by category; supplied rows include `is_demo: true` |

| `GET /api/education.php?topic=numbers-mean` | General education article; does not require the database |

Invalid ZIPs return HTTP `422`; unknown public locations return `404`. Missing databases or `pdo_mysql` generally produce `503` for database-backed routes. Other database failures generally return `500` with a generic JSON message; details are logged server-side.

## Design constraints

- Only `frontend/` is public; the Apache alias exposes only intended public `backend/api/` scripts. Never expose `.env`, `.sql` or internal/admin placeholder files.
- Missing geocoordinates stay `NULL`; address-based map links work without a map preview.
- The directory is sponsor-supplied and not continuously verified. `last_verified = NULL` must not be presented as verified data.
- Approved clinical content requires separate review. The seeded recommendations are demonstrations.
- The admin, auth and audit paths currently are placeholders; do not design changes around a working admin panel without implementing authorization and auditability.
