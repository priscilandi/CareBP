<?php
/*
 * Attribution: UPDATE workflow adapted from Kendra's edit.php and index.php.
 * Kendra's files provided the prepared UPDATE query and edit-form workflow.
 * This version uses location_id instead of id, validates submitted values,
 * and updates the additional columns defined in CareBP's schema.
 */
require_once __DIR__ . '/admin_helpers.php';
require_post();
$id = valid_id($_POST['location_id'] ?? '');
$data = location_input($pdo);
$data['location_id'] = $id;
$sql = 'UPDATE healthcare_locations SET
 type_id = :type_id, name = :name, address = :address, city = :city,
 state = :state, zip_code = :zip_code, phone = :phone, hours = :hours,
 latitude = :latitude, longitude = :longitude,
 offers_bp_screening = :offers_bp_screening, website = :website, is_active = :is_active
 WHERE location_id = :location_id';
$pdo->prepare($sql)->execute($data);
redirect_with_message('Location updated successfully.');
