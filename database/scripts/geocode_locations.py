import json
import time
import urllib.parse
import urllib.request

import mysql.connector


DB_CONFIG = {
    "host": "localhost",
    "user": "root",
    "database": "carebp",
}

CENSUS_URL = "https://geocoding.geo.census.gov/geocoder/geographies/onelineaddress"
GEOCODER_BENCHMARK = "Public_AR_Current"
GEOCODER_VINTAGE = "Current_Current"

def geocode_address(address, city, state, zip_code):
    full_address = f"{address}, {city}, {state} {zip_code}"

    params = urllib.parse.urlencode({
        "address": full_address,
        "benchmark": GEOCODER_BENCHMARK,
        "vintage": GEOCODER_VINTAGE,
        "format": "json",
    })

    request = urllib.request.Request(
        f"{CENSUS_URL}?{params}",
        headers={"User-Agent": "CAREBP academic capstone project"},
    )

    with urllib.request.urlopen(request, timeout=15) as response:
        data = json.loads(response.read().decode("utf-8"))

    matches = data.get("result", {}).get("addressMatches", [])

    if not matches:
        return None

    coordinates = matches[0].get("coordinates", {})

    longitude = coordinates.get("x")
    latitude = coordinates.get("y")

    if latitude is None or longitude is None:
        return None

    return float(latitude), float(longitude)


def main():
    connection = mysql.connector.connect(**DB_CONFIG)
    cursor = connection.cursor(dictionary=True)

    cursor.execute("""
        SELECT
            location_id,
            name,
            address,
            city,
            state,
            zip_code
        FROM healthcare_locations
        WHERE is_active = 1
          AND (latitude IS NULL OR longitude IS NULL)
        ORDER BY location_id
    """)

    locations = cursor.fetchall()

    updated_count = 0
    skipped_count = 0
    error_count = 0

    print(f"Found {len(locations)} locations without coordinates.\n")

    for location in locations:

        full_address = (
            f"{location['address']}, "
            f"{location['city']}, "
            f"{location['state']} "
            f"{location['zip_code']}"
        )

        print(f"Geocoding {location['name']}: {full_address}")

        try:
            coordinates = geocode_address(
                location["address"],
                location["city"],
                location["state"],
                location["zip_code"],
            )

            if coordinates is None:
                skipped_count += 1
                print("  No match found. Skipping.\n")
                continue

            latitude, longitude = coordinates

            print(f"  Found: {latitude}, {longitude}")

            cursor.execute("""
                UPDATE healthcare_locations
                SET
                    latitude = %s,
                    longitude = %s
                WHERE location_id = %s
            """, (
                latitude,
                longitude,
                location["location_id"],
            ))

            connection.commit()
            updated_count += 1
            print("  Database updated.\n")

        except Exception as error:
            error_count += 1
            print(f"  Error: {error}\n")

        time.sleep(0.25)

    cursor.close()
    connection.close()


    print("Geocoding complete.")
    print(f"Updated: {updated_count}")
    print(f"No match: {skipped_count}")
    print(f"Errors: {error_count}")


if __name__ == "__main__":
    main()