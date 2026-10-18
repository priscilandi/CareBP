<?php
include 'schema_connection.php';

// Example ID to remove
$id = 1; 

try {
    $sql = "DELETE FROM healthcare_locations WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    
    $stmt->execute([':id' => $id]);
    
    echo "Entry removed successfully!";
} catch (PDOException $e) {
    echo "Error removing entry: " . $e->getMessage();
}
?>