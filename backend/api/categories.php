<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// BP ranges from the uploaded bp_categories table, not location types.
runApi(static function (): never {
    requireMethod('GET');
    $rows = getDatabase()->query(
        'SELECT category_id, category_name, display_order
         FROM bp_categories ORDER BY display_order, category_id'
    )->fetchAll();
    foreach ($rows as &$row) {
        $row['category_id'] = (int) $row['category_id'];
        $row['display_order'] = (int) $row['display_order'];
    }
    unset($row);
    jsonResponse(['data' => $rows]);
});
