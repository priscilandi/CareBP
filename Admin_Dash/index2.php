<?php
// 1. DATABASE CONNECTION
$host = 'localhost';
$dbname = 'schema_db'; // Change this to your database name
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Variables to handle state management
$edit_mode = false; 
$edit_id = ''; 
$edit_type_id = '';
$edit_name = '';
$edit_address = '';
$edit_city = '';
$edit_state = '';
$edit_zip_code = '';
$edit_phone = '';
$edit_website = '';
$edit_offers_bp = 0;

// FETCH LOCATION TYPES FOR THE DROPDOWN
$types_stmt = $pdo->query("SELECT * FROM location_types ORDER BY type_name ASC");
$location_types = $types_stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. ADD OR UPDATE ACTIONS
if (isset($_POST['save_location'])) {
    $location_id = $_POST['location_id'];
    $type_id = $_POST['type_id'];
    $name = $_POST['name'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $zip_code = $_POST['zip_code'];
    $phone = $_POST['phone'] ?? null;
    $website = $_POST['website'] ?? null;
    $offers_bp_screening = isset($_POST['offers_bp_screening']) ? 1 : 0;
    
    if (!empty($type_id) && !empty($name) && !empty($address) && !empty($city) && !empty($state) && !empty($zip_code)) {
        if (!empty($location_id)) {
            // Updates existing location entry
            $sql = "UPDATE healthcare_locations 
                    SET type_id = :type_id, name = :name, address = :address, city = :city, state = :state, 
                        zip_code = :zip_code, phone = :phone, website = :website, offers_bp_screening = :offers_bp_screening 
                    WHERE location_id = :location_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':type_id' => $type_id, ':name' => $name, ':address' => $address, ':city' => $city, 
                ':state' => $state, ':zip_code' => $zip_code, ':phone' => $phone, ':website' => $website, 
                ':offers_bp_screening' => $offers_bp_screening, ':location_id' => $location_id
            ]);
        } else {
            // Inserts a brand new location entry
            $sql = "INSERT INTO healthcare_locations (type_id, name, address, city, state, zip_code, phone, website, offers_bp_screening) 
                    VALUES (:type_id, :name, :address, :city, :state, :zip_code, :phone, :website, :offers_bp_screening)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':type_id' => $type_id, ':name' => $name, ':address' => $address, ':city' => $city, 
                ':state' => $state, ':zip_code' => $zip_code, ':phone' => $phone, ':website' => $website, 
                ':offers_bp_screening' => $offers_bp_screening
            ]);
        }
        header("Location: index2.php"); 
        exit;
    }
}

// 3. HANDLE DETECT EDIT BUTTON CLICK (Loads data into fields)
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $stmt = $pdo->prepare("SELECT * FROM healthcare_locations WHERE location_id = :location_id");
    $stmt->execute([':location_id' => $edit_id]);
    $location_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($location_to_edit) {
        $edit_mode = true;
        $edit_type_id = $location_to_edit['type_id'];
        $edit_name = $location_to_edit['name'];
        $edit_address = $location_to_edit['address'];
        $edit_city = $location_to_edit['city'];
        $edit_state = $location_to_edit['state'];
        $edit_zip_code = $location_to_edit['zip_code'];
        $edit_phone = $location_to_edit['phone'];
        $edit_website = $location_to_edit['website'];
        $edit_offers_bp = $location_to_edit['offers_bp_screening'];
    }
}

// 4. HANDLE REMOVE ACTION
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM healthcare_locations WHERE location_id = :location_id");
    $stmt->execute([':location_id' => $id]);
    header("Location: index2.php");
    exit;
}

// 5. FETCH ALL LOCATIONS WITH THEIR JOINED TYPE NAME
$stmt = $pdo->query("SELECT hl.*, lt.type_name 
                     FROM healthcare_locations hl 
                     JOIN location_types lt ON hl.type_id = lt.type_id
                     ORDER BY hl.location_id DESC");
$healthcare_locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Blood Pressure Locations Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background-color: #f4f4f9; }
        .container { max-width: 900px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1); margin: auto; }
        h2 { color: #333; margin-top: 20px; }
        form { margin-bottom: 30px; background: #eef2f7; padding: 15px; border-radius: 6px; }
        .form-group { display: block; margin-bottom: 12px; }
        input[type="text"], input[type="number"], select { display: block; width: 100%; padding: 8px; margin: 6px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .checkbox-group { display: flex; align-items: center; margin: 12px 0; }
        .checkbox-group input { margin-right: 8px; transform: scale(1.2); }
        .btn { border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; color: white; text-decoration: none; font-size: 14px;}
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .btn-primary { background: #007bff; }
        .btn-primary:hover { background: #0069d9; }
        .btn-secondary { background: #6c757d; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #343a40; color: white; }
        .btn-edit { background: #ffc107; color: #212529; padding: 5px 10px; border-radius: 3px; font-size: 13px; text-decoration: none; margin-right: 5px;}
        .btn-edit:hover { background: #e0a800; }
        .btn-delete { background: #dc3545; color: white; padding: 5px 10px; border-radius: 3px; font-size: 13px; text-decoration: none;}
        .btn-delete:hover { background: #c82333; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; color: white; }
        .bg-green { background: #28a745; }
        .bg-red { background: #dc3545; }
    </style>
</head>
<body>

<div class="container">
    <h2><?php echo $edit_mode ? "✏️ Edit Entry (ID: $edit_id)" : "➕ Add New Blood Pressure Location"; ?></h2>
    
    <form action="index2.php" method="POST">
        <input type="hidden" name="location_id" value="<?php echo $edit_id; ?>">

        <label>Location Type:</label>
        <select name="type_id" required>
            <option value="" disabled <?php echo empty($edit_type_id) ? 'selected' : ''; ?>>Select Type...</option>
            <?php foreach ($location_types as $type): ?>
                <option value="<?php echo $type['type_id']; ?>" <?php echo ($edit_type_id == $type['type_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($type['type_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Name:</label>
        <input type="text" name="name" required value="<?php echo htmlspecialchars($edit_name); ?>" placeholder="e.g. Augusta Health Clinic">
        
        <label>Address:</label>
        <input type="text" name="address" required value="<?php echo htmlspecialchars($edit_address); ?>" placeholder="e.g. 123 Health Ave">
       
        <label>City:</label>
        <input type="text" name="city" pattern="[A-Za-z ]+" required value="<?php echo htmlspecialchars($edit_city); ?>" placeholder="e.g. Augusta">
        
        <label for="state">State:</label>
        <select name="state" id="state" required>
          <option value="" disabled <?php echo empty($edit_state) ? 'selected' : ''; ?>>Select a state</option>
          <option value="GA" <?php echo ($edit_state == 'GA') ? 'selected' : ''; ?>>Georgia</option>
          <option value="MS" <?php echo ($edit_state == 'MS') ? 'selected' : ''; ?>>Mississippi</option>
          <option value="NC" <?php echo ($edit_state == 'NC') ? 'selected' : ''; ?>>North Carolina</option>
          <option value="SC" <?php echo ($edit_state == 'SC') ? 'selected' : ''; ?>>South Carolina</option>
        </select>
        
        <label>Zip Code:</label>
        <input type="text" name="zip_code" pattern="[0-9]{5}" required value="<?php echo htmlspecialchars($edit_zip_code); ?>" placeholder="e.g. 30909">

        <label>Phone Number:</label>
        <input type="text" name="phone" pattern="[0-9]{3}-[0-9]{3}-[0-9]{4}" value="<?php echo htmlspecialchars($edit_phone); ?>" placeholder="e.g. 706-555-0199">

        <label>Website URL:</label>
        <input type="text" name="website" value="<?php echo htmlspecialchars($edit_website); ?>" placeholder="e.g. https://clinicwebsite.org">

        <div class="checkbox-group">
            <input type="checkbox" name="offers_bp_screening" id="offers_bp" value="1" <?php echo $edit_offers_bp ? 'checked' : ''; ?>>
            <label for="offers_bp">Offers Blood Pressure Screening</label>
        </div>
        
        <button type="submit" name="save_location" class="btn <?php echo $edit_mode ? 'btn-primary' : 'btn-success'; ?>">
            <?php echo $edit_mode ? 'Update Entry' : 'Add Entry'; ?>
        </button>
        
        <?php if ($edit_mode): ?>
            <a href="index2.php" class="btn btn-secondary">Cancel Edit</a>
        <?php endif; ?> <!-- Added this exact closing block -->
    </form>

     <h2>📋 Existing Database Entries</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Address</th>
            <th>City</th>
            <th>State</th>
            <th>Zip Code</th>
            <th>Phone Number</th>
            <th>Website URL</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($healthcare_locations as $location): ?>
        <tr>
            <td><?php echo $location['location_id']; ?></td>
            <td><?php echo htmlspecialchars($location['name']); ?></td>
            <td><?php echo htmlspecialchars($location['address']); ?></td>
            <td><?php echo htmlspecialchars($location['city']); ?></td>
            <td><?php echo htmlspecialchars($location['state']); ?></td>
            <td><?php echo htmlspecialchars($location['zip_code']); ?></td>
            <td><?php echo htmlspecialchars($location['phone']); ?></td>
            <td><?php echo htmlspecialchars($location['website']); ?></td>
            <td>
                <!-- Click Edit to send data to form above -->
                <a class="btn-edit" href="index2.php?edit_id=<?php echo $location['location_id']; ?>">Edit</a>
                <!-- Click Remove to wipe the row directly -->
                <a class="btn-delete" href="index2.php?delete_id=<?php echo $location['location_id']; ?>" onclick="return confirm('Are you sure you want to permanently delete this location?');">Remove</a>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($healthcare_locations)): ?>
        <tr>
            <td colspan="8" style="text-align: center; color: #888;">No entries found. Table is empty.</td>
         </tr>
        <?php endif; ?>
    </table>
</div>

</body>
</html>