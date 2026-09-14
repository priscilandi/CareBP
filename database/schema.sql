
PRAGMA foreign_keys = ON;

CREATE TABLE location_types (
    type_id INTEGER PRIMARY KEY AUTOINCREMENT,
    type_name TEXT NOT NULL UNIQUE,
    description TEXT
);

CREATE TABLE healthcare_locations (
    location_id INTEGER PRIMARY KEY AUTOINCREMENT,
    type_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    address TEXT NOT NULL,
    city TEXT NOT NULL,
    state TEXT NOT NULL,
    zip_code TEXT NOT NULL,
    phone TEXT,
    hours TEXT,
    latitude REAL,
    longitude REAL,
    offers_bp_screening INTEGER NOT NULL DEFAULT 0,
    website TEXT,
    is_active INTEGER NOT NULL DEFAULT 1,
    last_verified TEXT,
    FOREIGN KEY (type_id) REFERENCES location_types(type_id)
);

CREATE TABLE bp_categories (
    category_id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_name TEXT NOT NULL UNIQUE,
    display_order INTEGER NOT NULL
);

CREATE TABLE recommendations (
    recommendation_id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER NOT NULL,
    guidance_text TEXT NOT NULL,
    care_level TEXT NOT NULL,
    approved INTEGER NOT NULL DEFAULT 0,
    FOREIGN KEY (category_id) REFERENCES bp_categories(category_id)
);