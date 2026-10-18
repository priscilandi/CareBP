<?php
include 'schema_connection.php';

// Example data to add
$name = "Greenblatt";
$address = "1439 Laney Walker Blvd";
$city = "Augusta";
$state = "GA"
$zip_code = "30912"

try {
    $sql = "INSERT INTO healthcare_locations (name, address, city, state, zip_code) VALUES (:name, :address, :city, :state, :zip_code)";
    $stmt = $pdo->prepare($sql);
    
    // Bind parameters and execute
    $stmt->execute([
        ':name' => $name,
        ':address' => $address,
        ':city' => $city,
        ':state' => $state,
        ':zip_code' => $zip_code
    ]);
    
    echo "Entry added successfully!";
} catch (PDOException $e) {
    echo "Error adding entry: " . $e->getMessage();
}
?>