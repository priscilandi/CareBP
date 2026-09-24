<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// This table now holds BP-range guidance (NOT submitted facility suggestions).
// Only demo rows rn
runApi(static function (): never {
    requireMethod('GET');
    if (!isset($_GET['category_id'])) {
        throw new ApiException(422, 'invalid_field', 'category_id is required.');
    }
    $categoryId = positiveInt($_GET['category_id'], 'category_id');
    $stmt = getDatabase()->prepare(
        'SELECT r.recommendation_id, r.category_id, c.category_name, r.type_id,
                t.type_name, r.guidance_text, r.care_level
         FROM recommendations r
         JOIN bp_categories c ON c.category_id = r.category_id
         LEFT JOIN location_types t ON t.type_id = r.type_id
         WHERE r.category_id = :category_id AND r.approved = 1
         ORDER BY r.recommendation_id'
    );
    $stmt->execute(['category_id' => $categoryId]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$row) {
        $row['recommendation_id'] = (int) $row['recommendation_id'];
        $row['category_id'] = (int) $row['category_id'];
        $row['type_id'] = $row['type_id'] === null ? null : (int) $row['type_id'];
        $row['is_demo'] = str_starts_with(strtolower(trim($row['guidance_text'])), 'demo guidance');
    }
    unset($row);
    jsonResponse(['data' => $rows]);
});
