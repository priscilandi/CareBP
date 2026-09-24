-- =========================================
-- CareBP Development Seed Data
-- Includes sponsor-provided verified BP-monitor locations
-- =========================================
-- DEVELOPMENT DATA: clears existing entries in these four tables.
-- Do not run on a database containing independently maintained records.

DELETE FROM recommendations;
DELETE FROM healthcare_locations;
DELETE FROM bp_categories;
DELETE FROM location_types;

ALTER TABLE recommendations AUTO_INCREMENT = 1;
ALTER TABLE healthcare_locations AUTO_INCREMENT = 1;
ALTER TABLE bp_categories AUTO_INCREMENT = 1;
ALTER TABLE location_types AUTO_INCREMENT = 1;

-- LOCATION TYPES
INSERT INTO location_types (type_name, description) VALUES
('Hospital', 'Hospital or emergency medical facility'),
('Urgent Care', 'Walk-in urgent care facility'),
('Pharmacy', 'Pharmacy offering healthcare services'),
('Clinic', 'Primary or outpatient healthcare clinic'),
('Library', 'Public or academic library with a BP monitor location'),
('Church', 'Church or faith-based community location with a BP monitor'),
('Community Location', 'Community organization or public location with a BP monitor'),
('School', 'School or educational location with a BP monitor'),
('Salon', 'Salon or personal-care location with a BP monitor');

-- HEALTHCARE LOCATIONS
-- All entries are sponsor-completed locations supplied for development.
-- Unknown phone, hours, coordinates, and website values remain NULL.
-- last_verified remains NULL until separately confirmed and timestamped.
INSERT INTO healthcare_locations
(type_id, name, address, city, state, zip_code, phone, hours,
 latitude, longitude, offers_bp_screening, website, is_active, last_verified)
VALUES
(5, 'Greenblatt Library', '1439 Laney Walker Blvd.', 'Augusta', 'GA', '30912', NULL, NULL, NULL, NULL, 1, NULL, 1, NULL),
(5, 'Reese Library', '2500 Walton Way', 'Augusta', 'GA', '30904', NULL, NULL, NULL, NULL, 1, NULL, 1, NULL),
(5, 'Sparta-Hancock County Library', '8984 E Broad St', 'Sparta', 'GA', '31087', NULL, NULL, NULL, NULL, 1, NULL, 1, NULL),
(5, 'Louisville Library', '306 E. Broad St.', 'Louisville', 'GA', '30434', NULL, NULL, NULL, NULL, 1, NULL, 1, NULL),
(5, 'McCollum Library', '405 N. Main Street', 'Wrens', 'GA', '30833', NULL, NULL, NULL, NULL, 1, NULL, 1, NULL),
(5, 'Wadley Library', '11 W. College Ave.', 'Wadley', 'GA', '30477', NULL, NULL, NULL, NULL, 1, NULL, 1, NULL),
(5, 'Eatonton-Putnam County Library', '309 N. Madison Ave.', 'Eatonton', 'GA', '31024', NULL, NULL, NULL, NULL, 1, NULL, 1, NULL),
(5, 'Greene County Library', '610 S. Main St.', 'Greensboro', 'GA', '30642', NULL, NULL, NULL, NULL, 1, NULL, 1, NULL),
(5, 'Jasper County Library', '319 E. Greene St.', 'Monticello', 'GA', '31064', NULL, NULL, NULL, NULL, 1, NULL, 1, NULL),
(5, 'Monroe-Walton County Library', '217 W. Spring St.', 'Monroe', 'GA', '30655', NULL, NULL, NULL, NULL, 1, NULL, 1, NULL),
(5, 'Morgan County Library', '1131 East Ave.', 'Madison', 'GA', '30650', NULL, NULL, NULL, NULL, 1, NULL, 1, NULL),
(5, 'O''Kelly Memorial Library', '363 Conyers Rd.', 'Loganville', 'GA', '30052', NULL, NULL, NULL, NULL, 1, NULL, 1, NULL),
(5, 'W.H. Stanton Memorial Library', '407 W. Hightower Trail', 'Social Circle', 'GA', '30025', NULL, NULL, NULL, NULL, 1, NULL, 1, NULL),
(5, 'Walnut Grove Library', '1000 Walnut Grove Pkwy', 'Walnut Grove', 'GA', '30052', NULL, NULL, NULL, NULL, 1, NULL, 1, NULL),
(5, 'Clarkesville-Habersham County Library', '178 East Green St.', 'Clarkesville', 'GA', '30523', NULL, NULL, NULL, NULL, 1, NULL, 1, NULL),
(5, 'Cornelia-Habersham County Public Library', '301 N. Main St.', 'Cornelia', 'GA', '30531', NULL, NULL, NULL, NULL, 1, NULL, 1, NULL),
(5, 'Rabun County Library', '73 Jo Dotson Circle', 'Clayton', 'GA', '30525', NULL, NULL, NULL, NULL, 1, NULL, 1, NULL),
(5, 'Toccoa-Stephens County Library', '53 West Savannah St.', 'Toccoa', 'GA', '30577', NULL, NULL, NULL, NULL, 1, NULL, 1, NULL),
(5, 'Cleveland-White County Library', '10 Colonial Dr.', 'Cleveland', 'GA', '30528', NULL, NULL, NULL, NULL, 1, NULL, 1, NULL),
(5, 'Helen-White County Library', '90 Petes Park Rd.', 'Helen', 'GA', '30545', NULL, NULL, NULL, NULL, 1, NULL, 1, NULL),
(5, 'Burke County Library', '130 Highway 24 South', 'Waynesboro', 'GA', '30830', NULL, NULL, NULL, NULL, 1, NULL, 1, NULL),
(5, 'Lincoln County Library', '181 N Peachtree St.', 'Lincolnton', 'GA', '30817', NULL, NULL, NULL, NULL, 1, NULL, 1, NULL),
(5, 'Warren County Library', '10 Warren St.', 'Warrenton', 'GA', '30828', NULL, NULL, NULL, NULL, 1, NULL, 1, NULL),
(5, 'Sardis Public Library', '750 Charles Perry Ave', 'Sardis', 'GA', '30456', NULL, NULL, NULL, NULL, 1, NULL, 1, NULL),
(5, 'Midville Library', '149 Trout St.', 'Midville', 'GA', '30441', NULL, NULL, NULL, NULL, 1, NULL, 1, NULL),
(6, 'Good Shepherd Baptist Church', '1714 Olive Road', 'Augusta', 'GA', '30904', '706-733-0341', NULL, NULL, NULL, 1, 'https://www.goodshepherdaugusta.org/', 1, NULL),
(6, 'Trinity CME Church', '2930 Glenn Hills Drive', 'Augusta', 'GA', '30906', NULL, NULL, NULL, NULL, 1, NULL, 1, NULL),
(6, 'Antioch Baptist Church', '1454 Florence Street', 'Augusta', 'GA', '30901', '706-724-2809', NULL, NULL, NULL, 1, NULL, 1, NULL),
(6, 'Live River Baptist Church', '3404 Jack Kelly Road', 'Augusta', 'GA', '30906', '706-790-3133', NULL, NULL, NULL, 1, 'https://liveriverbaptist.org/', 1, NULL),
(6, 'Broadway Baptist Church', '2323 Barton Chapel Road', 'Augusta', 'GA', '30906', '706-790-8185', NULL, NULL, NULL, 1, NULL, 1, NULL),
(6, 'Springfield Missionary Baptist Church', '523 Martin Luther King Jr St', 'Thomson', 'GA', '30824', '706-595-5829', NULL, NULL, NULL, 1, NULL, 1, NULL),
(7, 'The HUB Augusta Locally Grown', '631 Chafee Ave Suite 102', 'Augusta', 'GA', '30904', '706-701-2834', NULL, NULL, NULL, 1, 'https://augustalocallygrown.org/the-hub', 1, NULL),
(7, 'Via Cognitive Health', '105 Lutheran Drive', 'Augusta', 'GA', '30907', '706-738-5039', NULL, NULL, NULL, 1, 'https://viacognitivehealth.org/', 1, NULL),
(7, 'MCG Foundation', '720 St. Sebastian Way, Suite 150', 'Augusta', 'GA', '30901', '706-823-5500', 'Mon-Fri 9 AM-4 PM ET', NULL, NULL, 1, 'https://www.mcgfoundation.org/', 1, NULL),
(7, 'CSRA RESA Office', '4683 Augusta HWY SE', 'Dearing', 'GA', '30808', NULL, NULL, NULL, NULL, 1, NULL, 1, NULL),
(7, 'North Augusta Family YMCA', '406 E Martintown Rd Suite 100', 'North Augusta', 'SC', '29841', '803-278-0882', 'Mon-Fri 5 AM-8 PM; Sat 8 AM-2 PM; Sun 1 PM-5 PM', NULL, NULL, 1, 'https://thefamilyy.org/branch/north-augusta', 1, NULL),
(8, 'Burke County Middle School', '356 Southside Drive', 'Waynesboro', 'GA', '30830', '706-554-3532', NULL, NULL, NULL, 1, 'https://bcms.burke.k12.ga.us/', 1, NULL),
(8, 'Augusta Prep', '285 Flowing Wells Rd.', 'Augusta', 'GA', '30907', NULL, NULL, NULL, NULL, 1, NULL, 1, NULL),
(8, 'Bamberg Middle School', '897 North St.', 'Bamberg', 'SC', '29003', NULL, NULL, NULL, NULL, 1, NULL, 1, NULL);

-- Not inserted: Phi Chi Chapter mobile screenings, DLAS vivarium-only equipment,
-- and the Early Headstart entry awaiting a fixed public location.
-- The sponsor spreadsheet is the source of truth for BP-monitor rollout.

INSERT INTO bp_categories (category_name, display_order) VALUES
('Normal', 1),
('Elevated', 2),
('Stage 1 Hypertension', 3),
('Stage 2 Hypertension', 4);

-- DEMONSTRATION PLACEHOLDERS ONLY. Never use as clinical guidance.
INSERT INTO recommendations
(category_id, type_id, guidance_text, care_level, approved) VALUES
(1, NULL, 'Demo guidance for normal blood pressure.', 'Routine', 1),
(2, 3, 'Demo guidance for elevated blood pressure.', 'Routine', 1),
(3, 4, 'Demo guidance for Stage 1 hypertension.', 'Follow-up', 1),
(4, 2, 'Demo guidance for Stage 2 hypertension.', 'Higher priority', 1);
