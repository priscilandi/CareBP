#!/usr/bin/env python3
"""Read-only checks against the real MySQL CareBP development database.

Run after `database/schema.sql` and `database/seed.sql` are imported and .env
is configured. Requires the MySQL command-line client (mysql) on PATH.
Never resets or writes to a database.
"""
from pathlib import Path
import os
import shutil
import subprocess
import sys
import unittest

ROOT = Path(__file__).resolve().parents[1]
KEYS = ('DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD')


def settings():
    values = {'DB_HOST': '127.0.0.1', 'DB_PORT': '3306', 'DB_NAME': 'carebp', 'DB_USER': 'carebp', 'DB_PASSWORD': ''}
    env_file = ROOT / '.env'
    if env_file.is_file():
        for raw in env_file.read_text(encoding='utf-8').splitlines():
            line = raw.strip()
            if not line or line.startswith('#') or '=' not in line:
                continue
            key, value = line.split('=', 1)
            key, value = key.strip(), value.strip()
            if key in KEYS:
                if len(value) >= 2 and value[0] == value[-1] and value[0] in ('"', "'"):
                    value = value[1:-1]
                values[key] = value
    for key in KEYS:
        values[key] = os.getenv(key, values[key])
    return values


def query(sql):
    if not shutil.which('mysql'):
        raise RuntimeError('mysql client not found on PATH. Install MySQL 8+ before running live seed tests.')
    cfg = settings()
    env = os.environ.copy()
    env['MYSQL_PWD'] = cfg['DB_PASSWORD']
    cmd = [
        'mysql', '--protocol=TCP', '--host=' + cfg['DB_HOST'], '--port=' + cfg['DB_PORT'],
        '--user=' + cfg['DB_USER'], '--database=' + cfg['DB_NAME'],
        '--batch', '--raw', '--skip-column-names', '--execute=' + sql,
    ]
    result = subprocess.run(cmd, env=env, text=True, stdout=subprocess.PIPE,
                            stderr=subprocess.PIPE, timeout=20, check=False)
    if result.returncode:
        raise RuntimeError('MySQL query failed: ' + result.stderr.strip())
    return [row.split('\t') for row in result.stdout.strip().splitlines() if row]


class LiveMySQLSeedTests(unittest.TestCase):
    def test_seed_counts(self):
        row = query('''SELECT
          (SELECT COUNT(*) FROM location_types),
          (SELECT COUNT(*) FROM healthcare_locations),
          (SELECT COUNT(*) FROM bp_categories),
          (SELECT COUNT(*) FROM recommendations),
          (SELECT COUNT(*) FROM healthcare_locations WHERE zip_code = '30904'),
          (SELECT COUNT(*) FROM healthcare_locations WHERE is_active = 1 AND offers_bp_screening = 1),
          (SELECT COUNT(*) FROM healthcare_locations WHERE name = 'O''Kelly Memorial Library'),
          (SELECT COUNT(*) FROM recommendations WHERE approved = 1 AND guidance_text LIKE 'Demo guidance%')''')[0]
        self.assertEqual(tuple(map(int, row)), (9, 39, 4, 4, 3, 39, 1, 4))

    def test_type_breakdown_and_foreign_keys(self):
        types = {name: int(count) for name, count in query('''SELECT t.type_name, COUNT(h.location_id)
            FROM location_types t LEFT JOIN healthcare_locations h ON h.type_id = t.type_id
            GROUP BY t.type_id, t.type_name''')}
        self.assertEqual(len(types), 9)
        for name, count in {'Library': 25, 'Church': 6, 'Community Location': 5, 'School': 3}.items():
            self.assertEqual(types[name], count)
        self.assertEqual(sum(types.values()), 39)
        row = query('''SELECT
            (SELECT COUNT(*) FROM healthcare_locations h LEFT JOIN location_types t ON h.type_id=t.type_id WHERE t.type_id IS NULL),
            (SELECT COUNT(*) FROM recommendations r LEFT JOIN bp_categories b ON r.category_id=b.category_id WHERE b.category_id IS NULL),
            (SELECT COUNT(*) FROM recommendations r LEFT JOIN location_types t ON r.type_id=t.type_id WHERE r.type_id IS NOT NULL AND t.type_id IS NULL),
            (SELECT COUNT(*) FROM healthcare_locations WHERE CHAR_LENGTH(zip_code) <> 5)''')[0]
        self.assertEqual(tuple(map(int, row)), (0, 0, 0, 0))


if __name__ == '__main__':
    unittest.main(verbosity=2)
