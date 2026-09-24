<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

runApi(static function (): never {
    requireMethod('GET');
    $rows = getDatabase()->query(
        'SELECT t.type_id, t.type_name, t.description,
                COUNT(l.location_id) AS location_count
         FROM location_types t
         LEFT JOIN healthcare_locations l
           ON l.type_id = t.type_id AND l.is_active = 1 AND l.offers_bp_screening = 1
         GROUP BY t.type_id, t.type_name, t.description
         ORDER BY t.type_name'
    )->fetchAll();
    foreach ($rows as &$row) {
        $row['type_id'] = (int) $row['type_id'];
        $row['location_count'] = (int) $row['location_count'];
    }
    unset($row);
    jsonResponse(['data' => $rows]);
});
