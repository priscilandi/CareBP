<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

runApi(static function (): never {
    requireMethod('GET');
    getDatabase()->query('SELECT 1');
    jsonResponse(['application' => 'CareBP', 'status' => 'ok', 'database' => 'connected']);
});
