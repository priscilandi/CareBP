-- =========================================
-- CareBP Mock Data
-- Development/testing purposes only
-- =========================================


-- Clear existing development data so this file can be rerun safely.
DELETE FROM recommendations;
DELETE FROM healthcare_locations;
DELETE FROM bp_categories;
DELETE FROM location_types;

-- Reset AUTO_INCREMENT IDs so seeded foreign-key IDs remain predictable.
ALTER TABLE recommendations AUTO_INCREMENT = 1;
ALTER TABLE healthcare_locations AUTO_INCREMENT = 1;
ALTER TABLE bp_categories AUTO_INCREMENT = 1;
ALTER TABLE location_types AUTO_INCREMENT = 1;

-- LOCATION TYPES

INSERT INTO location_types (type_name, description)
VALUES
('Hospital', 'Hospital or emergency medical facility'),
('Urgent Care', 'Walk-in urgent care facility'),
('Pharmacy', 'Pharmacy offering healthcare services'),
('Clinic', 'Primary or outpatient healthcare clinic');


-- HEALTHCARE LOCATIONS

INSERT INTO healthcare_locations
(type_id, name, address, city, state, zip_code, phone, hours,
 latitude, longitude, offers_bp_screening, website, is_active, last_verified)
VALUES

(3,
 'CareBP Demo Pharmacy',
 '100 Test Street',
 'Augusta',
 'GA',
 '30904',
 '706-555-0101',
 'Mon-Fri 9 AM-7 PM',
 NULL,
 NULL,
 1,
 'https://example.com',
 1,
 '2026-09-11'),

(4,
 'CareBP Community Clinic',
 '200 Example Avenue',
 'Augusta',
 'GA',
 '30901',
 '706-555-0102',
 'Mon-Fri 8 AM-5 PM',
 NULL,
 NULL,
 1,
 'https://example.com',
 1,
 '2026-09-11'),

(2,
 'CareBP Demo Urgent Care',
 '300 Sample Road',
 'Augusta',
 'GA',
 '30909',
 '706-555-0103',
 'Daily 8 AM-8 PM',
 NULL,
 NULL,
 0,
 'https://example.com',
 1,
 '2026-09-11');


-- BP CATEGORIES

INSERT INTO bp_categories (category_name, display_order)
VALUES
('Normal', 1),
('Elevated', 2),
('Stage 1 Hypertension', 3),
('Stage 2 Hypertension', 4);


-- RECOMMENDATIONS

INSERT INTO recommendations
(category_id, type_id, guidance_text, care_level, approved)
VALUES
(1, NULL, 'Demo guidance for normal blood pressure.', 'Routine', 1),
(2, 3, 'Demo guidance for elevated blood pressure.', 'Routine', 1),
(3, 4, 'Demo guidance for Stage 1 hypertension.', 'Follow-up', 1),
(4, 2, 'Demo guidance for Stage 2 hypertension.', 'Higher priority', 1);