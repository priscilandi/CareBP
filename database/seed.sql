-- =========================================
-- CareBP Mock Data
-- Development/testing purposes only
-- =========================================

PRAGMA foreign_keys = ON;


-- LOCATION TYPES

INSERT INTO location_types (type_name, description)
VALUES
('Pharmacy', 'Pharmacy offering healthcare services'),
('Community Clinic', 'Community-based healthcare facility'),
('Urgent Care', 'Facility providing urgent medical care'),
('Hospital', 'Hospital providing medical services');


-- HEALTHCARE LOCATIONS

INSERT INTO healthcare_locations
(type_id, name, address, city, state, zip_code, phone, hours,
 latitude, longitude, offers_bp_screening, website, is_active, last_verified)
VALUES

(1,
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

(2,
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

(3,
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
(category_id, guidance_text, care_level, approved)
VALUES
(1, 'Demo guidance for normal blood pressure.', 'Routine', 1),
(2, 'Demo guidance for elevated blood pressure.', 'Routine', 1),
(3, 'Demo guidance for Stage 1 hypertension.', 'Follow-up', 1),
(4, 'Demo guidance for Stage 2 hypertension.', 'Higher priority', 1);