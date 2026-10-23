<?php
/*
 * Attribution: DELETE workflow adapted from Kendra's remove.php and index.php.
 * Kendra's files demonstrated deleting a healthcare_locations record with
 * a prepared statement. This version takes a validated location_id from a
 * POST form rather than a hardcoded ID or a GET query parameter.
 */
require_once __DIR__ . '/admin_helpers.php';
require_post();
$id = valid_id($_POST['location_id'] ?? '');
$pdo->prepare('DELETE FROM healthcare_locations WHERE location_id = :id')->execute(['id' => $id]);
redirect_with_message('Location removed.');
