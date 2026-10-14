import csv
import os
import mysql.connector

# Locate the CSV relative to this script
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
CSV_FILE = os.path.join(
    SCRIPT_DIR, "..", "data", "zip_codes_ga_sc_ms.csv"
)

# Connect to CAREBP MySQL database
db = mysql.connector.connect(
    host="localhost",
    user="root",
    password=os.environ["CAREBP_DB_PASSWORD"],
    database="carebp"
)

cursor = db.cursor()

# Read ZIP codes from CSV
with open(CSV_FILE, newline="") as file:
    reader = csv.DictReader(file)

    rows = [
        (
            row["zip_code"],
            float(row["latitude"]),
            float(row["longitude"])
        )
        for row in reader
    ]

# Insert new ZIP codes without overwriting existing ones
query = """
INSERT IGNORE INTO zip_codes (zip_code, latitude, longitude)
VALUES (%s, %s, %s)
"""

cursor.executemany(query, rows)
db.commit()

print(f"Processed {len(rows)} ZIP codes.")
print(f"Inserted {cursor.rowcount} new ZIP codes.")

cursor.close()
db.close()