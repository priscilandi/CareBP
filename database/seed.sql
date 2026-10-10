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
(5, 'Greenblatt Library', '1439 Laney Walker Blvd.', 'Augusta', 'GA', '30912', NULL, NULL, 33.46884694021, -81.987834872002, 1, NULL, 1, NULL),
(5, 'Reese Library', '2500 Walton Way', 'Augusta', 'GA', '30904', NULL, NULL, 33.472, -81.983, 1, NULL, 1, NULL),
(5, 'Sparta-Hancock County Library', '8984 E Broad St', 'Sparta', 'GA', '31087', NULL, NULL, 33.275506477059, -82.968639196759, 1, NULL, 1, NULL),
(5, 'Louisville Library', '306 E. Broad St.', 'Louisville', 'GA', '30434', NULL, NULL, 32.997397460661, -82.406504997465, 1, NULL, 1, NULL),
(5, 'McCollum Library', '405 N. Main Street', 'Wrens', 'GA', '30833', NULL, NULL, 33.211355369619, -82.386202646962, 1, NULL, 1, NULL),
(5, 'Wadley Library', '11 W. College Ave.', 'Wadley', 'GA', '30477', NULL, NULL, 32.868557068179, -82.408366948712, 1, NULL, 1, NULL),
(5, 'Eatonton-Putnam County Library', '309 N. Madison Ave.', 'Eatonton', 'GA', '31024', NULL, NULL, 33.329339331357, -83.391760972268, 1, NULL, 1, NULL),
(5, 'Greene County Library', '610 S. Main St.', 'Greensboro', 'GA', '30642', NULL, NULL, 33.569023409066, -83.183323243679, 1, NULL, 1, NULL),
(5, 'Jasper County Library', '319 E. Greene St.', 'Monticello', 'GA', '31064', NULL, NULL, 33.303585852809, -83.680854586325, 1, NULL, 1, NULL),
(5, 'Monroe-Walton County Library', '217 W. Spring St.', 'Monroe', 'GA', '30655', NULL, NULL, 33.794351591617, -83.715724683535, 1, NULL, 1, NULL),
(5, 'Morgan County Library', '1131 East Ave.', 'Madison', 'GA', '30650', NULL, NULL, 33.594880661541, -83.453097525491, 1, NULL, 1, NULL),
(5, 'O''Kelly Memorial Library', '363 Conyers Rd.', 'Loganville', 'GA', '30052', NULL, NULL, 33.837297825029, -83.90372289369, 1, NULL, 1, NULL),
(5, 'W.H. Stanton Memorial Library', '407 W. Hightower Trail', 'Social Circle', 'GA', '30025', NULL, NULL, 33.659536971443, -83.723735751111, 1, NULL, 1, NULL),
(5, 'Walnut Grove Library', '1000 Walnut Grove Pkwy', 'Walnut Grove', 'GA', '30052', NULL, NULL, 33.746947004247, -83.853451857128, 1, NULL, 1, NULL),
(5, 'Clarkesville-Habersham County Library', '178 East Green St.', 'Clarkesville', 'GA', '30523', NULL, NULL, 34.615568400488, -83.524261508766, 1, NULL, 1, NULL),
(5, 'Cornelia-Habersham County Public Library', '301 N. Main St.', 'Cornelia', 'GA', '30531', NULL, NULL, 34.513761470394, -83.528258239223, 1, NULL, 1, NULL),
(5, 'Rabun County Library', '73 Jo Dotson Circle', 'Clayton', 'GA', '30525', NULL, NULL, 34.88, -83.40, 1, NULL, 1, NULL),
(5, 'Toccoa-Stephens County Library', '53 West Savannah St.', 'Toccoa', 'GA', '30577', NULL, NULL, 34.581647172315, -83.330128609872, 1, NULL, 1, NULL),
(5, 'Cleveland-White County Library', '10 Colonial Dr.', 'Cleveland', 'GA', '30528', NULL, NULL, 34.59833, -83.76472, 1, NULL, 1, NULL),
(5, 'Helen-White County Library', '90 Petes Park Rd.', 'Helen', 'GA', '30545', NULL, NULL, 34.702773139185, -83.73050127933, 1, NULL, 1, NULL),
(5, 'Burke County Library', '130 Highway 24 South', 'Waynesboro', 'GA', '30830', NULL, NULL, 33.085090051379, -82.037467085883, 1, NULL, 1, NULL),
(5, 'Lincoln County Library', '181 N Peachtree St.', 'Lincolnton', 'GA', '30817', NULL, NULL, 33.793901078337, -82.47758657323, 1, NULL, 1, NULL),
(5, 'Warren County Library', '10 Warren St.', 'Warrenton', 'GA', '30828', NULL, NULL, 33.407577153261, -82.661824457324, 1, NULL, 1, NULL),
(5, 'Sardis Public Library', '750 Charles Perry Ave', 'Sardis', 'GA', '30456', NULL, NULL, 32.975015593622, -81.757402455033, 1, NULL, 1, NULL),
(5, 'Midville Library', '149 Trout St.', 'Midville', 'GA', '30441', NULL, NULL, 32.8906, -82.2223, 1, NULL, 1, NULL),
(6, 'Good Shepherd Baptist Church', '1714 Olive Road', 'Augusta', 'GA', '30904', '706-733-0341', NULL, 33.451410281718, -82.00709654841, 1, 'https://www.goodshepherdaugusta.org/', 1, NULL),
(6, 'Trinity CME Church', '2930 Glenn Hills Drive', 'Augusta', 'GA', '30906', NULL, NULL, 33.429874443773, -82.072762952424, 1, NULL, 1, NULL),
(6, 'Antioch Baptist Church', '1454 Florence Street', 'Augusta', 'GA', '30901', '706-724-2809', NULL, 33.465159664985, -81.986083432343, 1, NULL, 1, NULL),
(6, 'Live River Baptist Church', '3404 Jack Kelly Road', 'Augusta', 'GA', '30906', '706-790-3133', NULL, 33.370352428734, -82.016120456138, 1, 'https://liveriverbaptist.org/', 1, NULL),
(6, 'Broadway Baptist Church', '2323 Barton Chapel Road', 'Augusta', 'GA', '30906', '706-790-8185', NULL, 33.444243251412, -82.086130084632, 1, NULL, 1, NULL),
(6, 'Springfield Missionary Baptist Church', '523 Martin Luther King Jr St', 'Thomson', 'GA', '30824', '706-595-5829', NULL, 33.472818700434, -82.503080202565, 1, NULL, 1, NULL),
(7, 'The HUB Augusta Locally Grown', '631 Chafee Ave Suite 102', 'Augusta', 'GA', '30904', '706-701-2834', NULL, 33.477273700077, -81.988978077131, 1, 'https://augustalocallygrown.org/the-hub', 1, NULL),
(7, 'Via Cognitive Health', '105 Lutheran Drive', 'Augusta', 'GA', '30907', '706-738-5039', NULL, 33.51, -82.06, 1, 'https://viacognitivehealth.org/', 1, NULL),
(7, 'MCG Foundation', '720 St. Sebastian Way, Suite 150', 'Augusta', 'GA', '30901', '706-823-5500', 'Mon-Fri 9 AM-4 PM ET', 33.475857243672, -81.981190625312, 1, 'https://www.mcgfoundation.org/', 1, NULL),
(7, 'CSRA RESA Office', '4683 Augusta HWY SE', 'Dearing', 'GA', '30808', NULL, NULL, 33.412637165289, -82.383254575646, 1, NULL, 1, NULL),
(7, 'North Augusta Family YMCA', '406 E Martintown Rd Suite 100', 'North Augusta', 'SC', '29841', '803-278-0882', 'Mon-Fri 5 AM-8 PM; Sat 8 AM-2 PM; Sun 1 PM-5 PM', 33.496528185711, -81.959434199275, 1, 'https://thefamilyy.org/branch/north-augusta', 1, NULL),
(8, 'Burke County Middle School', '356 Southside Drive', 'Waynesboro', 'GA', '30830', '706-554-3532', NULL, 33.083753077314, -81.99252621043, 1, 'https://bcms.burke.k12.ga.us/', 1, NULL),
(8, 'Augusta Prep', '285 Flowing Wells Rd.', 'Augusta', 'GA', '30907', NULL, NULL, 33.500998957619, -82.107509031296, 1, NULL, 1, NULL),
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
-- Sponsor-completed entries without a fixed public BP-monitor location are intentionally not inserted here:
-- Phi Chi Chapter provides mobile/community BP screenings rather than a permanent public monitor location.
-- DLAS BP-monitor equipment is housed internally in animal-research/vivarium facilities and is not a public screening location.
-- The sponsor's Early Headstart in Burke County entry does not identify a specific fixed public BP-monitor location, so it remains pending clarification.

-- The sponsor spreadsheet remains the source of truth for BP-monitor rollout;
-- external sources should only be used to complete missing location details.

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
