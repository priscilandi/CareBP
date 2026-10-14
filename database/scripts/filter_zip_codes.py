import csv
import shapefile

# Input files
ZIP_FILE = "database/data/2026_Gaz_zcta_national.txt"
STATE_FILE = "database/data/cb_2018_us_state_500k/cb_2018_us_state_500k.shp"

# States supported by CAREBP
TARGET_STATES = {"GA", "SC", "MS"}

# Load state boundaries
states = shapefile.Reader(STATE_FILE)

# Select only GA, SC, and MS
selected_states = []

for state in states.shapeRecords():
    if state.record["STUSPS"] in TARGET_STATES:
        selected_states.append(state)

print(f"Loaded {len(selected_states)} states")


# Check whether a coordinate is inside a polygon
def point_in_ring(longitude, latitude, ring):
    inside = False
    previous = ring[-1]

    for current in ring:
        x1, y1 = previous
        x2, y2 = current

        if (y1 > latitude) != (y2 > latitude):
            crossing_x = x1 + (latitude - y1) * (x2 - x1) / (y2 - y1)

            if longitude < crossing_x:
                inside = not inside

        previous = current

    return inside


# Check whether a coordinate is inside a state
def point_in_state(longitude, latitude, state_shape):
    points = state_shape.points
    boundaries = list(state_shape.parts) + [len(points)]

    inside = False

    for start, end in zip(boundaries, boundaries[1:]):
        if point_in_ring(longitude, latitude, points[start:end]):
            inside = not inside

    return inside


# Store matching ZIP codes
matches = []

# Read ZIP coordinates
with open(ZIP_FILE, newline="") as file:
    reader = csv.DictReader(file, delimiter="|")

    for row in reader:
        zip_code = row["GEOID"]
        latitude = float(row["INTPTLAT"])
        longitude = float(row["INTPTLONG"])

        # Check whether ZIP code is in GA, SC, or MS
        for state in selected_states:
            if point_in_state(longitude, latitude, state.shape):
                matches.append((
                    zip_code,
                    state.record["STUSPS"],
                    latitude,
                    longitude
                ))
                break

print(f"Matching ZIP Code Tabulation Areas: {len(matches)}")
print("First five matches:", matches[:5])

# Export matching ZIP codes to CSV
OUTPUT_FILE = "database/data/zip_codes_ga_sc_ms.csv"

with open(OUTPUT_FILE, "w", newline="") as file:
    writer = csv.writer(file)

    # Column headers
    writer.writerow(["zip_code", "state", "latitude", "longitude"])

    # ZIP code records
    writer.writerows(matches)

print(f"Exported {len(matches)} ZIP codes to {OUTPUT_FILE}")