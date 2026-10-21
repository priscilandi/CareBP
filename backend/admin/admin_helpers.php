<?php
/*
 * CareBP integration code (not copied from Kendra's files).
 * Supports the CRUD workflows adapted from Kendra's add.php, edit.php,
 * remove.php, and index.php by adding shared validation, authorization,
 * CSRF verification, and the project's getDatabase() connection.
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_auth.php';
require_admin();
require_once __DIR__ . '/../config/database.php';
$pdo = getDatabase();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
function h($value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function require_post(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed'); }
    if (!hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
        http_response_code(403); exit('Invalid request token');
    }
}
function redirect_with_message(string $message): void {
    $_SESSION['flash'] = $message;
    header('Location: facilities.php');
    exit;
}
function valid_id($value): int {
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) { http_response_code(400); exit('Invalid location ID'); }
    return $id;
}
function location_input(PDO $pdo): array {
    $fields = ['name', 'address', 'city', 'state', 'zip_code'];
    $data = [];
    foreach ($fields as $field) {
        $data[$field] = trim((string)($_POST[$field] ?? ''));
        if ($data[$field] === '') { http_response_code(422); exit('Required field missing: ' . h($field)); }
    }
    if (strlen($data['name']) > 255 || strlen($data['address']) > 500 || strlen($data['city']) > 100 || !preg_match('/^[A-Za-z]{2}$/', $data['state']) || !preg_match('/^\d{5}(?:-\d{4})?$/', $data['zip_code'])) {
        http_response_code(422); exit('Invalid name, address, city, state or ZIP code');
    }
    $data['state'] = strtoupper($data['state']);
    $data['type_id'] = valid_id($_POST['type_id'] ?? '');
    $types = $pdo->prepare('SELECT 1 FROM location_types WHERE type_id = ?');
    $types->execute([$data['type_id']]);
    if (!$types->fetchColumn()) { http_response_code(422); exit('Unknown location type'); }
    foreach (['phone', 'hours', 'website'] as $field) {
        $value = trim((string)($_POST[$field] ?? ''));
        $data[$field] = $value === '' ? null : $value;
    }
    if ($data['website'] !== null && (!filter_var($data['website'], FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($data['website'], PHP_URL_SCHEME)), ['http', 'https'], true))) {
        http_response_code(422); exit('Website must be an HTTP or HTTPS URL');
    }
    foreach (['latitude', 'longitude'] as $field) {
        $raw = trim((string)($_POST[$field] ?? ''));
        if ($raw !== '' && !is_numeric($raw)) { http_response_code(422); exit('Invalid coordinate'); }
        $data[$field] = $raw === '' ? null : (float)$raw;
    }
    if (($data['latitude'] !== null && abs($data['latitude']) > 90) || ($data['longitude'] !== null && abs($data['longitude']) > 180)) {
        http_response_code(422); exit('Coordinates out of range');
    }
    $data['offers_bp_screening'] = isset($_POST['offers_bp_screening']) ? 1 : 0;
    $data['is_active'] = isset($_POST['is_active']) ? 1 : 0;
    return $data;
}
