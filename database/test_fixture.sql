-- NEGATIVE TEST CASES ONLY; use only in a disposable local test database.
-- Run after schema.sql and the 39 real locations in seed.sql.
-- These two fictional records should NEVER appear in public API responses.
INSERT INTO healthcare_locations
(type_id, name, address, city, state, zip_code, phone, hours,
 latitude, longitude, offers_bp_screening, website, is_active, last_verified)
VALUES
((SELECT type_id FROM location_types WHERE type_name='Library'),
 '[TEST] INACTIVE Fictional Library', '10 Imaginary Street', 'Augusta', 'GA', '30904',
 NULL, NULL, NULL, NULL, 1, NULL, 0, NULL),
((SELECT type_id FROM location_types WHERE type_name='Library'),
 '[TEST] NO SCREENING Fictional Library', '20 Imaginary Street', 'Augusta', 'GA', '30904',
 NULL, NULL, NULL, NULL, 0, NULL, 1, NULL);

INSERT INTO recommendations (category_id, type_id, guidance_text, care_level, approved)
VALUES (1, NULL, 'UNAPPROVED TEST ONLY: must never appear in API.', 'test', 0);
