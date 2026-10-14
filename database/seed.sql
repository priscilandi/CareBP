-- =========================================
-- CareBP Development Seed Data
-- Includes sponsor-provided verified BP-monitor locations
-- =========================================
-- DEVELOPMENT DATA: clears existing entries in these four tables.
-- Do not run on a database containing independently maintained records.

DELETE FROM recommendations;
DELETE FROM healthcare_locations;
DELETE FROM zip_codes;
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
-- Verified geocoded coordinates are stored here so database rebuilds
-- reproduce the same location data.
-- Locations that could not be matched by the Census Geocoder retain
-- NULL latitude/longitude values.
-- Unknown phone, hours, and website values remain NULL.
-- last_verified remains NULL until separately confirmed and timestamped.
INSERT INTO healthcare_locations
(type_id, name, address, city, state, zip_code, phone, hours,
 latitude, longitude, offers_bp_screening, website, is_active, last_verified)
VALUES
(5, 'Greenblatt Library', '1439 Laney Walker Blvd.', 'Augusta', 'GA', '30912', '706-721-3441', NULL, 33.46884694021, -81.987834872002, 1, 'https://www.augusta.edu/library/greenblatt/', 1, NULL),
(5, 'Reese Library', '2500 Walton Way', 'Augusta', 'GA', '30904', '706-737-1744', NULL, 33.472, -81.983, 1, 'https://www.augusta.edu/library/reese/', 1, NULL),
(5, 'Sparta-Hancock County Library', '8984 E Broad St', 'Sparta', 'GA', '31087', '706-444-5389', 'Mon 10 AM-2 PM; Tue/Wed/Fri 10 AM-5 PM; Thu 10 AM-8 PM; Sat 10 AM-2 PM; Sun Closed', 33.275506477059, -82.968639196759, 1, 'https://www.azalealibraries.org/ha-library', 1, NULL),
(5, 'Louisville Library', '306 E. Broad St.', 'Louisville', 'GA', '30434', '478-625-7079', NULL, 32.997397460661, -82.406504997465, 1, 'https://www.jeffersoncls.org', 1, NULL),
(5, 'McCollum Library', '405 N. Main Street', 'Wrens', 'GA', '30833', '706-547-7567', NULL, 33.211355369619, -82.386202646962, 1, 'https://www.jeffersoncls.org', 1, NULL),
(5, 'Wadley Library', '11 W. College Ave.', 'Wadley', 'GA', '30477', '478-252-5392', 'Mon–Fri 8:30 AM–5:30 PM', 32.868557068179, -82.408366948712, 1, 'https://wadleyga.gov/wadley-library/', 1, NULL),
(5, 'Eatonton-Putnam County Library', '309 N. Madison Ave.', 'Eatonton', 'GA', '31024', '706-485-6768', 'Mon/Tue/Wed/Fri 9–6; Thu 9–8; Sat 10–4; Sun 2–6', 33.329339331357, -83.391760972268, 1, 'https://www.azalealibraries.org/pu-county-library', 1, NULL),
(5, 'Greene County Library', '610 S. Main St.', 'Greensboro', 'GA', '30642', '706-453-7276', 'Mon/Tue/Wed/Fri 9 AM-6 PM; Thu 9 AM-8 PM; Sat 10 AM-4 PM; Sun 2 PM-6 PM', 33.569023409066, -83.183323243679, 1, 'https://www.azalealibraries.org/gr-county-library', 1, NULL),
(5, 'Jasper County Library', '319 E. Greene St.', 'Monticello', 'GA', '31064', '706-468-6292', 'Mon/Tue/Wed/Fri 9 AM-6 PM; Thu 9 AM-8 PM; Sat 10 AM-4 PM; Sun 2 PM-6 PM', 33.303585852809, -83.680854586325, 1, 'https://www.azalealibraries.org/ja-county-library', 1, NULL),
(5, 'Monroe-Walton County Library', '217 W. Spring St.', 'Monroe', 'GA', '30655', '770-267-4630', 'Mon-Thu 9 AM-7 PM; Fri 9 AM-6 PM; Sat 10 AM-4 PM; Sun 2 PM-6 PM', 33.794351591617, -83.715724683535, 1, 'https://www.azalealibraries.org/mr-county-library', 1, NULL),
(5, 'Morgan County Library', '1131 East Ave.', 'Madison', 'GA', '30650', '706-342-1206', 'Mon/Tue/Wed/Fri 9 AM-6 PM; Thu 9 AM-8 PM; Sat 10 AM-4 PM; Sun 2 PM-6 PM', 33.594880661541, -83.453097525491, 1, 'https://www.azalealibraries.org/ma-county-library', 1, NULL),
(5, 'O''Kelly Memorial Library', '363 Conyers Rd.', 'Loganville', 'GA', '30052', '770-466-2895', 'Mon-Thu 9 AM-7 PM; Fri 9 AM-6 PM; Sat 10 AM-4 PM; Sun 2 PM-6 PM', 33.837297825029, -83.90372289369, 1, 'https://www.azalealibraries.org/ok-library', 1, NULL),
(5, 'W.H. Stanton Memorial Library', '407 W. Hightower Trail', 'Social Circle', 'GA', '30025', '770-464-2444', 'Mon/Wed/Fri 9 AM-6 PM; Tue/Thu 9 AM-7 PM; Sat 10 AM-4 PM; Sun 2 PM-6 PM', 33.659536971443, -83.723735751111, 1, 'https://www.azalealibraries.org/sc-library', 1, NULL),
(5, 'Walnut Grove Library', '1000 Walnut Grove Pkwy', 'Walnut Grove', 'GA', '30052', '678-342-9758', 'Mon/Tue/Wed/Fri 9 AM-6 PM; Thu 10 AM-7 PM; Sat 10 AM-2 PM; Sun Closed', 33.746947004247, -83.853451857128, 1, 'https://www.azalealibraries.org/wg-library', 1, NULL),
(5, 'Clarkesville-Habersham County Library', '178 East Green St.', 'Clarkesville', 'GA', '30523', '706-754-4413', 'Mon-Thu 10 AM-6 PM; Fri 10 AM-5 PM; Sat 10 AM-2 PM; Sun Closed', 34.615568400488, -83.524261508766, 1, 'https://clarkesvillelibrary.org/', 1, NULL),
(5, 'Cornelia-Habersham County Public Library', '301 N. Main St.', 'Cornelia', 'GA', '30531', '706-778-2635', 'Mon/Tue 9 AM-6 PM; Wed 9 AM-5 PM; Thu 9 AM-7 PM; Fri 9 AM-5 PM', 34.513761470394, -83.528258239223, 1, 'https://cornelialibrary.org/', 1, NULL),
(5, 'Rabun County Library', '73 Jo Dotson Circle', 'Clayton', 'GA', '30525', '706-782-3731', 'Mon-Thu 9 AM-6 PM; Fri 9 AM-5 PM; Sat 9 AM-1 PM', 34.88, -83.40, 1, 'https://www.rabuncountylibrary.org/', 1, NULL),
(5, 'Toccoa-Stephens County Library', '53 West Savannah St.', 'Toccoa', 'GA', '30577', '706-886-6082', 'Mon-Fri 10 AM-5:30 PM; Sat 10 AM-1 PM', 34.581647172315, -83.330128609872, 1, 'https://toccoastephenslibrary.org/', 1, NULL),
(5, 'Cleveland-White County Library', '10 Colonial Dr.', 'Cleveland', 'GA', '30528', '706-865-5572', 'Mon/Thu 9 AM-6 PM; Tue/Wed/Fri 9 AM-5 PM; Sat 9 AM-12 PM; Sun Closed', 34.59833, -83.76472, 1, 'https://whitecountypubliclibraries.org/', 1, NULL),
(5, 'Helen-White County Library', '90 Petes Park Rd.', 'Helen', 'GA', '30545', '706-878-2438', 'Mon/Wed/Fri 9 AM-5 PM; Tue/Thu 9 AM-6 PM; Sat/Sun Closed', 34.702773139185, -83.73050127933, 1, 'https://whitecountypubliclibraries.org/helen-branch/', 1, NULL),
(5, 'Burke County Library', '130 Highway 24 South', 'Waynesboro', 'GA', '30830', '706-554-3277', 'Mon/Tue/Thu 9 AM-6 PM; Wed/Fri 9 AM-5 PM; Sat 10 AM-2 PM; Sun Closed', 33.085090051379, -82.037467085883, 1, 'https://www.easternregionallibraryga.org/branches', 1, NULL),
(5, 'Lincoln County Library', '181 N Peachtree St.', 'Lincolnton', 'GA', '30817', '706-359-4014', 'Mon-Fri 8 AM-5 PM; Sat 9 AM-12 PM; Sun Closed', 33.793901078337, -82.47758657323, 1, 'https://www.easternregionallibraryga.org/branches', 1, NULL),
(5, 'Warren County Library', '10 Warren St.', 'Warrenton', 'GA', '30828', '706-465-2656', NULL, 33.407577153261, -82.661824457324, 1, 'https://www.easternregionallibraryga.org/branches', 1, NULL),
(5, 'Sardis Public Library', '750 Charles Perry Ave', 'Sardis', 'GA', '30456', '762-225-6482', 'Mon/Wed/Fri 11 AM-5 PM; Tue/Thu/Sat/Sun Closed', 32.975015593622, -81.757402455033, 1, 'https://www.easternregionallibraryga.org/branches', 1, NULL),
(5, 'Midville Library', '149 Trout St.', 'Midville', 'GA', '30441', '762-225-6480', 'Mon/Wed/Fri 11 AM-5 PM; Tue/Thu/Sat/Sun Closed', 32.8906, -82.2223, 1, 'https://www.easternregionallibraryga.org/branches', 1, NULL),
(6, 'Good Shepherd Baptist Church', '1714 Olive Road', 'Augusta', 'GA', '30904', '706-733-0341', NULL, 33.451410281718, -82.00709654841, 1, 'https://www.goodshepherdaugusta.org/', 1, NULL),
(6, 'Trinity CME Church', '2930 Glenn Hills Drive', 'Augusta', 'GA', '30906', NULL, NULL, 33.429874443773, -82.072762952424, 1, NULL, 1, NULL),
(6, 'Antioch Baptist Church', '1454 Florence Street', 'Augusta', 'GA', '30901', '706-724-2809', NULL, 33.465159664985, -81.986083432343, 1, NULL, 1, NULL),
(6, 'Live River Baptist Church', '3404 Jack Kelly Road', 'Augusta', 'GA', '30906', '706-790-3133', NULL, 33.370352428734, -82.016120456138, 1, 'https://liveriverbaptist.org/', 1, NULL),
(6, 'Broadway Baptist Church', '2323 Barton Chapel Road', 'Augusta', 'GA', '30906', '706-790-8185', NULL, 33.444243251412, -82.086130084632, 1, NULL, 1, NULL),
(6, 'Springfield Missionary Baptist Church', '523 Martin Luther King Jr St', 'Thomson', 'GA', '30824', '706-595-5829', NULL, 33.472818700434, -82.503080202565, 1, NULL, 1, NULL),
(7, 'The HUB Augusta Locally Grown', '631 Chafee Ave Suite 102', 'Augusta', 'GA', '30904', '706-701-2834', NULL, 33.477273700077, -81.988978077131, 1, 'https://augustalocallygrown.org/the-hub', 1, NULL),
(7, 'Via Cognitive Health', '105 Lutheran Drive', 'Augusta', 'GA', '30907', '706-738-5039', NULL, 33.51, -82.06, 1, 'https://viacognitivehealth.org/', 1, NULL),
(7, 'MCG Foundation', '720 St. Sebastian Way, Suite 150', 'Augusta', 'GA', '30901', '706-823-5500', 'Mon-Fri 9 AM-4 PM ET', 33.475857243672, -81.981190625312, 1, 'https://www.mcgfoundation.org/', 1, NULL),
(7, 'CSRA RESA Office', '4683 Augusta HWY SE', 'Dearing', 'GA', '30808', '706-556-6225', 'Mon-Fri 8 AM-4 PM', 33.412637165289, -82.383254575646, 1, 'https://www.csraresa.net/', 1, NULL),
(7, 'North Augusta Family YMCA', '406 E Martintown Rd Suite 100', 'North Augusta', 'SC', '29841', '803-278-0882', 'Mon-Fri 5 AM-8 PM; Sat 8 AM-2 PM; Sun 1 PM-5 PM', 33.496528185711, -81.959434199275, 1, 'https://thefamilyy.org/branch/north-augusta', 1, NULL),
(8, 'Burke County Middle School', '356 Southside Drive', 'Waynesboro', 'GA', '30830', '706-554-3532', NULL, 33.083753077314, -81.99252621043, 1, 'https://bcms.burke.k12.ga.us/', 1, NULL),
(8, 'Augusta Prep', '285 Flowing Wells Rd.', 'Augusta', 'GA', '30907', '706-863-1906', NULL, 33.500998957619, -82.107509031296, 1, 'https://www.augustaprep.org/', 1, NULL),
(8, 'Bamberg Middle School', '897 North St.', 'Bamberg', 'SC', '29003', NULL, NULL, 33.301574578872, -81.031597782704, 1, NULL, 1, NULL);

-- ZIP CODE COORDINATES
-- Used as the search origin for nearby-location searches.
INSERT INTO zip_codes (zip_code, latitude, longitude) VALUES
('29003', 33.3015746, -81.0315978),
('29841', 33.4965282, -81.9594342),
('30025', 33.6595370, -83.7237358),
('30052', 33.7921224, -83.8785874),
('30434', 32.9973975, -82.4065050),
('30456', 32.9750156, -81.7574025),
('30477', 32.8685571, -82.4083669),
('30523', 34.6155684, -83.5242615),
('30531', 34.5137615, -83.5282582),
('30545', 34.7027731, -83.7305013),
('30577', 34.5816472, -83.3301286),
('30642', 33.5690234, -83.1833232),
('30650', 33.5948807, -83.4530975),
('30655', 33.7943516, -83.7157247),
('30808', 33.4126372, -82.3832546),
('30817', 33.7939011, -82.4775866),
('30824', 33.4728187, -82.5030802),
('30828', 33.4075772, -82.6618245),
('30830', 33.0844216, -82.0149966),
('30833', 33.2113554, -82.3862026),
('30901', 33.4705085, -81.9836370),
('30904', 33.4643420, -81.9980373),
('30906', 33.4148234, -82.0583378),
('30907', 33.5009990, -82.1075090),
('30912', 33.4688469, -81.9878349),
('31024', 33.3293393, -83.3917610),
('31064', 33.3035859, -83.6808546),
('31087', 33.2755065, -82.9686392);

-- Additional ZIP code coordinates for Georgia, South Carolina,
-- and Mississippi are stored in: database/data/zip_codes_ga_sc_ms.csv

-- The CSV contains 1,602 ZIP Code Tabulation Areas (ZCTAs).
-- These coordinates will be imported separately after running seed.sql.
-- Existing ZIP code coordinates should not be overwritten.

-- BP CATEGORIES

INSERT INTO bp_categories (category_name, display_order)
VALUES
('Normal', 1),
('Elevated', 2),
('Stage 1 Hypertension', 3),
('Stage 2 Hypertension', 4),
('Hypertensive Crisis', 5);

-- RECOMMENDATIONS

INSERT INTO recommendations
(category_id, type_id, guidance_text, care_level, approved)
VALUES
(1, NULL, 'Demo guidance for normal blood pressure.', 'Routine', 1),
(2, 3, 'Demo guidance for elevated blood pressure.', 'Routine', 1),
(3, 4, 'Demo guidance for Stage 1 hypertension.', 'Follow-up', 1),
(4, 2, 'Demo guidance for Stage 2 hypertension.', 'Higher priority', 1),
(5, NULL, 'Demo guidance: If systolic pressure is above 180 or diastolic pressure is above 120, repeat the measurement after one minute. If it remains that high, contact a healthcare professional immediately. Call 911 if symptoms of a hypertensive emergency are present.', 'Urgent', 0);