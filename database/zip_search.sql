-- CareBP ZIP-code location search
-- Development query for testing ZIP-based searches.
-- The backend should use a prepared statement instead of a MySQL session variable.

SET @search_zip = '30904';

SELECT
    hl.location_id,
    hl.name,
    lt.type_name,
    hl.address,
    hl.city,
    hl.state,
    hl.zip_code,
    hl.phone,
    hl.hours,
    hl.website
FROM healthcare_locations AS hl
JOIN location_types AS lt
    ON hl.type_id = lt.type_id
WHERE hl.zip_code = @search_zip
  AND hl.is_active = 1
ORDER BY hl.name;