#!/usr/bin/env python3
"""Validate the provided MySQL seed's data/foreign keys with a SQLite analogue.

This checks seed consistency only; it does NOT replace an actual MySQL import.
Run from any directory: python3 tests/test_seed.py
"""
from pathlib import Path
import re
import sqlite3
import unittest

ROOT = Path(__file__).resolve().parents[1]
DDL = """
CREATE TABLE location_types (
    type_id INTEGER PRIMARY KEY AUTOINCREMENT,
    type_name TEXT NOT NULL UNIQUE,
    description TEXT
);
CREATE TABLE healthcare_locations (
    location_id INTEGER PRIMARY KEY AUTOINCREMENT,
    type_id INTEGER NOT NULL REFERENCES location_types(type_id),
    name TEXT NOT NULL, address TEXT NOT NULL, city TEXT NOT NULL,
    state TEXT NOT NULL, zip_code TEXT NOT NULL, phone TEXT, hours TEXT,
    latitude REAL, longitude REAL, offers_bp_screening INTEGER NOT NULL,
    website TEXT, is_active INTEGER NOT NULL, last_verified TEXT
);
CREATE TABLE bp_categories (
    category_id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_name TEXT NOT NULL UNIQUE, display_order INTEGER NOT NULL
);
CREATE TABLE recommendations (
    recommendation_id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER NOT NULL REFERENCES bp_categories(category_id),
    type_id INTEGER REFERENCES location_types(type_id),
    guidance_text TEXT NOT NULL, care_level TEXT NOT NULL, approved INTEGER NOT NULL
);
"""


def load_seed(db):
    sql = (ROOT / 'database' / 'seed.sql').read_text(encoding='utf-8')
    # MySQL's AUTO_INCREMENT reset is represented by SQLite's sequence reset.
    sql = re.sub(
        r'ALTER TABLE\s+(\w+)\s+AUTO_INCREMENT\s*=\s*1\s*;',
        lambda match: f"DELETE FROM sqlite_sequence WHERE name='{match.group(1)}';",
        sql,
        flags=re.I,
    )
    db.executescript(sql)


class SeedConsistency(unittest.TestCase):
    def setUp(self):
        self.db = sqlite3.connect(':memory:')
        self.db.execute('PRAGMA foreign_keys = ON')
        self.db.executescript(DDL)

    def tearDown(self):
        self.db.close()

    def one(self, statement):
        return self.db.execute(statement).fetchone()[0]

    def test_seed_counts_foreign_keys_and_locations(self):
        load_seed(self.db)
        self.assertEqual(self.one('SELECT count(*) FROM location_types'), 9)
        self.assertEqual(self.one('SELECT count(*) FROM healthcare_locations'), 39)
        self.assertEqual(self.one('SELECT count(*) FROM bp_categories'), 4)
        self.assertEqual(self.one('SELECT count(*) FROM recommendations'), 4)
        self.assertEqual(self.db.execute('PRAGMA foreign_key_check').fetchall(), [])
        self.assertEqual(self.one("SELECT count(*) FROM healthcare_locations WHERE zip_code = '30904'"), 3)
        self.assertEqual(self.one("SELECT count(*) FROM healthcare_locations WHERE name = 'O''Kelly Memorial Library'"), 1)
        self.assertEqual(self.one('SELECT count(*) FROM healthcare_locations WHERE is_active = 1 AND offers_bp_screening = 1'), 39)
        self.assertEqual(self.one('SELECT count(*) FROM healthcare_locations WHERE length(zip_code) <> 5'), 0)
        self.assertEqual(self.one('SELECT count(*) FROM recommendations WHERE approved = 1 AND guidance_text LIKE \'Demo guidance%\''), 4)
        actual = dict(self.db.execute('SELECT t.type_name, COUNT(h.location_id) FROM location_types t LEFT JOIN healthcare_locations h ON h.type_id=t.type_id GROUP BY t.type_id'))
        for name, count in {'Library': 25, 'Church': 6, 'Community Location': 5, 'School': 3}.items():
            self.assertEqual(actual[name], count)
        self.assertEqual(sum(actual.values()), 39)

    def test_idempotent_development_reset(self):
        load_seed(self.db)
        load_seed(self.db)
        self.assertEqual(self.one('SELECT count(*) FROM healthcare_locations'), 39)
        self.assertEqual(self.one('SELECT max(location_id) FROM healthcare_locations'), 39)
        self.assertEqual(self.one('SELECT max(type_id) FROM location_types'), 9)
        self.assertEqual(self.db.execute('PRAGMA foreign_key_check').fetchall(), [])


if __name__ == '__main__':
    unittest.main(verbosity=2)
