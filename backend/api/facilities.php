<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

function publicLocation(array $row): array
{
    foreach (['location_id', 'type_id', 'offers_bp_screening'] as $key) {
        $row[$key] = (int) $row[$key];
    }
    foreach (['latitude', 'longitude'] as $key) {
        $row[$key] = $row[$key] === null ? null : (float) $row[$key];
    }
    return $row;
}

// Public reads only. All user filters are bound parameters and only active records appear.
runApi(static function (): never {
    requireMethod('GET');
    $columns = 'l.location_id, l.type_id, t.type_name, l.name, l.address, l.city, l.state,
                l.zip_code, l.phone, l.hours, l.website, l.latitude, l.longitude,
                l.offers_bp_screening, l.last_verified';
    $from = ' FROM healthcare_locations l JOIN location_types t ON t.type_id = l.type_id';

    if (isset($_GET['id'])) {
        $id = positiveInt($_GET['id'], 'id');
        $stmt = getDatabase()->prepare('SELECT ' . $columns . $from .
            ' WHERE l.location_id = :id AND l.is_active = 1 AND l.offers_bp_screening = 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new ApiException(404, 'not_found', 'Location not found.');
        }
        jsonResponse(['data' => publicLocation($row)]);
    }

    $zip = queryString('zip', 5);
    if ($zip !== '' && preg_match('/^[0-9]{5}$/D', $zip) !== 1) {
        throw new ApiException(422, 'invalid_zip', 'ZIP code must contain exactly five digits.');
    }
    $q = queryString('q', 100);
    $city = queryString('city', 80);
    $state = queryString('state', 2);
    if ($state !== '') {
        $state = usState($state);
    }
    $typeId = isset($_GET['type_id']) && $_GET['type_id'] !== ''
        ? positiveInt($_GET['type_id'], 'type_id') : null;
    $page = isset($_GET['page']) ? positiveInt($_GET['page'], 'page', 100000) : 1;
    $perPage = isset($_GET['per_page']) ? positiveInt($_GET['per_page'], 'per_page', 50) : 12;

    $where = ['l.is_active = 1', 'l.offers_bp_screening = 1'];
    $params = [];
    if ($zip !== '') {
        $where[] = 'l.zip_code = :zip';
        $params['zip'] = $zip;
    }
    if ($q !== '') {
        $pattern = likePattern($q);
        $where[] = "(l.name LIKE :q_name ESCAPE '!' OR l.city LIKE :q_city ESCAPE '!'
                      OR t.type_name LIKE :q_type ESCAPE '!')";
        $params += ['q_name' => $pattern, 'q_city' => $pattern, 'q_type' => $pattern];
    }
    if ($city !== '') {
        $where[] = "l.city LIKE :city ESCAPE '!'";
        $params['city'] = likePattern($city);
    }
    if ($state !== '') {
        $where[] = 'l.state = :state';
        $params['state'] = $state;
    }
    if ($typeId !== null) {
        $where[] = 'l.type_id = :type_id';
        $params['type_id'] = $typeId;
    }
    $filteredFrom = $from . ' WHERE ' . implode(' AND ', $where);
    $db = getDatabase();
    $count = $db->prepare('SELECT COUNT(*)' . $filteredFrom);
    foreach ($params as $name => $value) {
        $count->bindValue(':' . $name, $value, $name === 'type_id' ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $count->execute();
    $total = (int) $count->fetchColumn();

    $stmt = $db->prepare('SELECT ' . $columns . $filteredFrom .
        ' ORDER BY l.name ASC, l.location_id ASC LIMIT :limit OFFSET :offset');
    foreach ($params as $name => $value) {
        $stmt->bindValue(':' . $name, $value, $name === 'type_id' ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
    $stmt->execute();

    jsonResponse([
        'data' => array_map('publicLocation', $stmt->fetchAll()),
        'meta' => [
            'page' => $page, 'per_page' => $perPage, 'total' => $total,
            'total_pages' => (int) ceil($total / $perPage),
        ],
    ]);
});
