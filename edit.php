<?php
include 'schema_connection.php';

// Example data to update
$id = 1; 
$new_address = "3601 lumpkin Rd";

try {
    $sql = "UPDATE healthcare_locations SET address = :address WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    
    $stmt->execute([
        ':address' => $new_address,
        ':id' => $id
    ]);
    
    echo "Entry updated successfully!";
} catch (PDOException $e) {
    echo "Error updating entry: " . $e->getMessage();
}
?>