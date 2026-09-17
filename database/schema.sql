CREATE TABLE location_types (
    type_id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT
);

CREATE TABLE healthcare_locations (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    type_id INT NOT NULL,
    name TEXT NOT NULL,
    address TEXT NOT NULL,
    city TEXT NOT NULL,
    state TEXT NOT NULL,
    zip_code VARCHAR(10) NOT NULL,
    phone TEXT,
    hours TEXT,
    latitude REAL,
    longitude REAL,
    offers_bp_screening BOOLEAN NOT NULL DEFAULT FALSE,
    website TEXT,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    last_verified DATETIME,
    FOREIGN KEY (type_id) REFERENCES location_types(type_id)
);

CREATE INDEX idx_healthcare_locations_zip
ON healthcare_locations(zip_code);

CREATE TABLE bp_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    display_order INT NOT NULL
);

CREATE TABLE recommendations (
    recommendation_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    type_id INT,
    guidance_text TEXT NOT NULL,
    care_level TEXT NOT NULL,
    approved BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (category_id) REFERENCES bp_categories(category_id),
    FOREIGN KEY (type_id) REFERENCES location_types(type_id)
);