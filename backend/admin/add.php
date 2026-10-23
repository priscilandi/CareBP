<?php
/*
 * Attribution: INSERT workflow adapted from Kendra's add.php and index.php.
 * Kendra's files demonstrated inserting healthcare_locations with prepared
 * PDO statements. This version accepts validated form input and includes
 * the required type_id and additional fields from CareBP's schema.
 */
require_once __DIR__ . '/admin_helpers.php';
require_post();
$data = location_input($pdo);
$sql = 'INSERT INTO healthcare_locations
(type_id, name, address, city, state, zip_code, phone, hours, latitude, longitude, offers_bp_screening, website, is_active)
VALUES (:type_id, :name, :address, :city, :state, :zip_code, :phone, :hours, :latitude, :longitude, :offers_bp_screening, :website, :is_active)';
$pdo->prepare($sql)->execute($data);
redirect_with_message('Location added successfully.');
