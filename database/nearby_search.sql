-- CAREBP Nearby Location Search
-- Returns the closest active BP monitor locations
-- based on latitude and longitude.

SET @search_lat = 33.477273700077;
SET @search_lon = -81.988978077131;

SELECT
    hl.location_id,
    hl.name,
    hl.address,
    hl.city,
    hl.state,
    hl.zip_code,
    hl.phone,
    hl.hours,
    hl.website,
    lt.type_name,
    ROUND(
        ST_Distance_Sphere(
            POINT(@search_lon, @search_lat),
            POINT(hl.longitude, hl.latitude)
        ) / 1609.344,
        2
    ) AS distance_miles
FROM healthcare_locations AS hl
JOIN location_types AS lt
    ON hl.type_id = lt.type_id
WHERE hl.is_active = 1
  AND hl.latitude IS NOT NULL
  AND hl.longitude IS NOT NULL
ORDER BY distance_miles ASC
LIMIT 10;