#!/usr/bin/env python3
"""CareBP local development database setup/status/refresh. Python 3 stdlib only."""
import argparse
import getpass
import hashlib
import os
from pathlib import Path
import re
import shutil
import subprocess
import sys
import tempfile

ROOT = Path(__file__).resolve().parent.parent
SCHEMA = ROOT / 'database/schema.sql'
SEED = ROOT / 'database/seed.sql'
STAMP_DIR = ROOT / '.carebp'

def stamp_for(env):
    return STAMP_DIR / (env['DB_NAME'] + '.seed.sha256')
TABLES = ('healthcare_locations', 'location_types', 'bp_categories', 'recommendations')


def fail(message):
    raise RuntimeError(message)


def read_env():
    path = ROOT / '.env'
    if not path.exists():
        fail('Missing .env; copy .env.example to .env and enter local DB credentials.')
    result = {}
    for num, line in enumerate(path.read_text().splitlines(), 1):
        line = line.strip()
        if not line or line.startswith('#'):
            continue
        if line.startswith('export '):
            line = line[7:].strip()
        if '=' not in line:
            fail(f'Invalid .env line {num}: expected KEY=VALUE')
        key, val = (x.strip() for x in line.split('=', 1))
        if val and val[0] in ('"', "'") and val[-1] == val[0]:
            val = val[1:-1]
        else:
            val = val.split(' #', 1)[0].strip()
        result[key] = val
    # Match the app's documented precedence: process env overrides .env.
    for key in ('DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'):
        if key in os.environ:
            result[key] = os.environ[key]
    for key in ('DB_NAME', 'DB_HOST', 'DB_USER', 'DB_PASSWORD'):
        if not result.get(key):
            fail(f'Missing {key} in .env/environment')
    result.setdefault('DB_PORT', '3306')
    if result['DB_HOST'] not in ('127.0.0.1', 'localhost', '::1'):
        fail('Refusing non-local DB_HOST. Use a local development MySQL instance.')
    if not re.fullmatch(r'[A-Za-z0-9_]+', result['DB_NAME']):
        fail('Invalid DB_NAME: allowed letters, numbers and underscores only')
    if result['DB_NAME'] != 'carebp' and not result['DB_NAME'].endswith('_dev'):
        fail('Refusing DB_NAME other than carebp or *_dev')
    if not re.fullmatch(r'[0-9]+', result['DB_PORT']) or not 1 <= int(result['DB_PORT']) <= 65535:
        fail('Invalid DB_PORT')
    if any(os.getenv(k, '').lower() in ('production', 'prod', 'staging') for k in ('APP_ENV', 'ENVIRONMENT', 'CAREBP_ENV')):
        fail('Refusing non-development APP_ENV/ENVIRONMENT/CAREBP_ENV')
    return result


def sql_string(s):
    return "'" + s.replace('\\', '\\\\').replace("'", "''") + "'"


def config_file(user, password, host=None, port=None):
    f = tempfile.NamedTemporaryFile(mode='w', prefix='carebp_mysql_', delete=False)
    os.chmod(f.name, 0o600)
    for v in (user, password, host or ''):
        if '\n' in v or '\r' in v or '\x00' in v:
            f.close(); os.unlink(f.name)
            fail('Invalid newline in DB credential')
    # Protect option-file injection in credentials by escaping backslashes and quotes.
    def cfg(s):
        return '"' + s.replace('\\', '\\\\').replace('"', '\\"') + '"'
    f.write('[client]\nuser=' + cfg(user) + '\npassword=' + cfg(password) + '\n')
    if host:
        f.write('host=' + cfg(host) + '\nport=' + str(port) + '\n')
    f.close()
    return Path(f.name)


class Client:
    def __init__(self, env):
        self.env = env
        if not shutil.which('mysql'):
            fail('mysql client not found in PATH')
        self.runtime_conf = config_file(env['DB_USER'], env['DB_PASSWORD'], env['DB_HOST'], env['DB_PORT'])
        self.admin_conf = None
        mode = os.getenv('CAREBP_DB_ADMIN_MODE', 'password' if sys.platform == 'darwin' else 'sudo')
        if mode not in ('sudo', 'password'):
            fail('CAREBP_DB_ADMIN_MODE must be sudo or password')
        self.mode = mode
        if mode == 'password':
            user = os.getenv('CAREBP_DB_ADMIN_USER', 'root')
            password = os.getenv('CAREBP_DB_ADMIN_PASSWORD')
            if password is None:
                password = getpass.getpass(f'MySQL admin password for {user}: ')
            self.admin_conf = config_file(user, password, env['DB_HOST'], env['DB_PORT'])
        elif not shutil.which('sudo'):
            fail('sudo is unavailable. Set CAREBP_DB_ADMIN_MODE=password.')

    def close(self):
        for f in (self.runtime_conf, self.admin_conf):
            if f:
                f.unlink(missing_ok=True)

    def run(self, sql=None, *, admin=True, database=None, file=None, capture=True):
        if self.mode == 'sudo' and admin:
            cmd = ['sudo', 'mysql', '--batch', '--skip-column-names']
        else:
            cfg = self.admin_conf if admin else self.runtime_conf
            cmd = ['mysql', '--defaults-extra-file=' + str(cfg), '--batch', '--skip-column-names']
        if database:
            cmd.append(database)
        if file:
            with open(file, 'rb') as handle:
                cp = subprocess.run(cmd, stdin=handle, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        else:
            cp = subprocess.run(cmd, input=(sql or '').encode(), stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        if cp.returncode:
            error = cp.stderr.decode(errors='replace').strip()
            fail(f'MySQL command failed ({cp.returncode}): {error}')
        return cp.stdout.decode(errors='replace').strip()


def digest():
    if not SEED.is_file():
        fail('Missing database/seed.sql')
    return hashlib.sha256(SEED.read_bytes()).hexdigest()


def status(client, env):
    db = env['DB_NAME']
    exists = client.run(f'SHOW DATABASES LIKE {sql_string(db)};') == db
    if not exists:
        print(f'Database {db} does not exist; run ./scripts/dev-setup.sh')
        return False, False
    tables = set(client.run('SHOW TABLES;', database=db).splitlines())
    if tables and not set(TABLES).issubset(tables):
        fail('Database has tables but is missing expected CareBP tables. Do not apply schema.sql automatically; investigate/migrate.')
    if not tables:
        print('Database exists, but schema is empty; run ./scripts/dev-setup.sh')
        return True, False
    current = digest()
    stamp = stamp_for(env)
    prior = stamp.read_text().strip() if stamp.exists() else None
    print('Seed status:', 'current' if prior == current else 'changed' if prior else 'unknown (no import marker)')
    for table in TABLES:
        count = client.run(f'SELECT COUNT(*) FROM `{table}`;', admin=False, database=db)
        print(f'  {table}: {count}')
    if prior != current:
        print('Run ./scripts/refresh-db.sh to replace local seed data (destructive).')
    else:
        print('Note: checksum tracks seed file imports, not manual changes to database rows.')
    return True, True


def confirm(db, force):
    if force:
        if os.getenv('CAREBP_ALLOW_DEV_DB_RESET') != '1':
            fail('--yes requires CAREBP_ALLOW_DEV_DB_RESET=1 (for isolated CI only)')
        print('Non-interactive destructive operation explicitly authorized.')
        return
    print(f'WARNING: importing seed.sql DELETES and replaces existing seeded rows in local database {db}.')
    if not sys.stdin.isatty():
        fail('Interactive confirmation needed. For isolated CI only, use CAREBP_ALLOW_DEV_DB_RESET=1 and --yes.')
    val = input(f'Type RESET {db} to continue: ')
    if val != 'RESET ' + db:
        fail('Cancelled: confirmation did not match')


def initialize(client, env):
    db = env['DB_NAME']
    client.run(f'CREATE DATABASE IF NOT EXISTS `{db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;')
    tables = set(client.run('SHOW TABLES;', database=db).splitlines())
    if tables and not set(TABLES).issubset(tables):
        fail('Existing database has an incomplete/unexpected schema. Manual migration required.')
    if not tables:
        if not SCHEMA.is_file():
            fail('Missing database/schema.sql')
        print('Importing schema into empty database...')
        client.run(database=db, file=SCHEMA)
    for host in ('localhost', '127.0.0.1'):
        user = sql_string(env['DB_USER'])
        h = sql_string(host)
        password = sql_string(env['DB_PASSWORD'])
        client.run(f'CREATE USER IF NOT EXISTS {user}@{h} IDENTIFIED BY {password}; GRANT SELECT ON `{db}`.* TO {user}@{h};')
    # Existing users' passwords are not changed; a stale .env may fail the next connection.
    client.run('SELECT 1;', admin=False, database=db)


def refresh(client, env, force):
    db = env['DB_NAME']
    tables = set(client.run('SHOW TABLES;', database=db).splitlines())
    if not set(TABLES).issubset(tables):
        fail('Required schema tables not found. Run ./scripts/dev-setup.sh first.')
    confirm(db, force)
    print('Importing development seed...')
    client.run(database=db, file=SEED)
    stamp = stamp_for(env)
    stamp.parent.mkdir(mode=0o700, parents=True, exist_ok=True)
    stamp.write_text(digest() + '\n')
    os.chmod(stamp, 0o600)
    print('Seed imported; recorded checksum. Run python3 tests/test_seed.py.')


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('mode', choices=('setup', 'status', 'refresh'))
    parser.add_argument('--yes', action='store_true', help='CI only; requires CAREBP_ALLOW_DEV_DB_RESET=1')
    args = parser.parse_args()
    if args.yes and args.mode == 'status':
        fail('--yes not valid for status')
    env = read_env()
    if not SEED.is_file():
        fail('Expected database/seed.sql from the application repository.')
    client = Client(env)
    try:
        if args.mode == 'setup':
            initialize(client, env)
            exists, ready = status(client, env)
            stamp = stamp_for(env)
            if not stamp.exists() or stamp.read_text().strip() != digest():
                refresh(client, env, args.yes)
            else:
                print('No seed refresh necessary.')
        elif args.mode == 'status':
            status(client, env)
        else:
            exists, ready = status(client, env)
            if not ready:
                fail('Database not initialized; run ./scripts/dev-setup.sh')
            refresh(client, env, args.yes)
    finally:
        client.close()


if __name__ == '__main__':
    try:
        main()
    except (RuntimeError, KeyboardInterrupt, OSError) as exc:
        print(f'ERROR: {exc}', file=sys.stderr)
        sys.exit(1)
