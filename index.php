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

// Variables that will to handle user changes
$edit_mode = false; 
$edit_id = ''; // Creates a blank field
$edit_name = '';
$edit_address = '';
$edit_city = '';
$edit_state = '';
$edit_zip_code = '';

// 2. ADD OR UPDATE ACTIONS
//Checks to see if the save button is clicked => if (isset($_POST['save_location']))
if (isset($_POST['save_location'])) {
   //captures the input written inside each respective form field
   //$id needs a loop to check how many entries there are and update accordingly instead of continous counting
    $id = $_POST['id'];
    $name = $_POST['name'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $zip_code = $_POST['zip_code'];
    
    //error checking to see if name or email is left empty
    //need to add error checking so limited number can be added
    if (!empty($name) && !empty($address) && !empty($city) && !empty($state) && !empty($zip_code)) {
        //
        if (!empty($id)) {
            // Updates existing form, if there is one
            $stmt = $pdo->prepare("UPDATE healthcare_locations SET name = :name, address = :address, city = :city, state = :state, zip_code = :zip_code WHERE id = :id");
            $stmt->execute([':name' => $name, ':address' => $address, ':city' => $city, ':state' => $state, ':zip_code' => $zip_code, ':id' => $id]);
        } else {
            // inserts a new entry if not current ID exists.
            $stmt = $pdo->prepare("INSERT INTO healthcare_locations (name, address, city, state, zip_code) VALUES (:name, :address, :city, :state, :zip_code)");
            //replaces the placeholder with the user's text and passes the query to MySQL database
            $stmt->execute([':name' => $name, ':address' => $address, ':city' => $city, ':state' => $state, ':zip_code' => $zip_code]);
        }
        header("Location: index.php"); //Refreshes the page
        exit;
    }
}

// 3. HANDLE DETECT EDIT BUTTON CLICK (Loads data into fields)
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $stmt = $pdo->prepare("SELECT * FROM healthcare_locations WHERE id = :id");
    $stmt->execute([':id' => $edit_id]);
    $user_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_to_edit) {
        $edit_mode = true;
        $edit_name = $user_to_edit['name'];
        $edit_address = $user_to_edit['address'];
        $edit_city = $user_to_edit['city'];
        $edit_state = $user_to_edit['state'];
        $edit_zip_code = $user_to_edit['zip_code'];
    }
}

// 4. HANDLE REMOVE ACTION
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM healthcare_locations WHERE id = :id");
    $stmt->execute([':id' => $id]);
    header("Location: index.php");
    exit;
}

// 5. FETCH ALL USERS TO DISPLAY
$stmt = $pdo->query("SELECT * FROM healthcare_locations");
$healthcare_locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Database GUI Manager (Full CRUD)</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background-color: #f4f4f9; }
        .container { max-width: 700px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1); margin: auto; }
        h2 { color: #333; margin-top: 20px; }
        form { margin-bottom: 30px; background: #eef2f7; padding: 15px; border-radius: 6px; }
        input[type="text"], input[type="text"], input[type="text"], input[type="text"], input[type="number"] { width: 95%; padding: 8px; margin: 8px 0; border: 1px solid #ccc; border-radius: 4px; }
        .btn { border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; color: white; text-decoration: none; font-size: 14px;}
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .btn-primary { background: #007bff; }
        .btn-primary:hover { background: #0069d9; }
        .btn-secondary { background: #6c757d; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #343a40; color: white; }
        .btn-edit { background: #ffc107; color: #212529; padding: 5px 10px; border-radius: 3px; font-size: 13px; text-decoration: none; margin-right: 5px;}
        .btn-edit:hover { background: #e0a800; }
        .btn-delete { background: #dc3545; color: white; padding: 5px 10px; border-radius: 3px; font-size: 13px; text-decoration: none;}
        .btn-delete:hover { background: #c82333; }
    </style>
</head>
<body>

<div class="container">
    <!-- FORM SWITCHES TITLES AND COLORS AUTOMATICALLY BASED ON MODE -->
    <h2><?php echo $edit_mode ? "✏️ Edit Entry (ID: $edit_id)" : "➕ Add New Entry"; ?></h2>
    
    <form action="index.php" method="POST">
        <!-- Hidden input to track if updating an entry -->
        <input type="hidden" name="id" value="<?php echo $edit_id; ?>">

        <label>Name:</label>
        <input type="text" name="name" required value="<?php echo htmlspecialchars($edit_name); ?>" placeholder="e.g. Augusta Library">
        
        <label>Address:</label>
        <input type="text" name="address" required value="<?php echo htmlspecialchars($edit_address); ?>" placeholder="e.g. 0000 street name">
       
        <label>City:</label>
        <input type="text" name="city" required value="<?php echo htmlspecialchars($edit_city); ?>" placeholder="e.g. Augusta">
        
        <label>State:</label>
        <input type="text" name="state" required value="<?php echo htmlspecialchars($edit_state); ?>" placeholder="e.g. GA">
        
        <label>Zip Code:</label>
        <input type="number" name="zip_code" required value="<?php echo htmlspecialchars($edit_zip_code); ?>" placeholder="e.g. 30909">
        
        <button type="submit" name="save_location" class="btn <?php echo $edit_mode ? 'btn-primary' : 'btn-success'; ?>">
            <?php echo $edit_mode ? 'Update Entry' : 'Add Entry'; ?>
        </button>
        
        <?php if ($edit_mode): ?>
            <a href="index.php" class="btn btn-secondary">Cancel Edit</a>
        <?php endif; ?>
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
            <th>Actions</th>
        </tr>
        <?php foreach ($healthcare_locations as $location): ?>
        <tr>
            <td><?php echo $location['id']; ?></td>
            <td><?php echo htmlspecialchars($location['name']); ?></td>
            <td><?php echo htmlspecialchars($location['address']); ?></td>
            <td><?php echo htmlspecialchars($location['city']); ?></td>
            <td><?php echo htmlspecialchars($location['state']); ?></td>
            <td><?php echo htmlspecialchars($location['zip_code']); ?></td>
            <td>
                <!-- Click Edit to send data to form above -->
                <a class="btn-edit" href="index.php?edit_id=<?php echo $location['id']; ?>">Edit</a>
                <!-- Click Remove to wipe the row directly -->
                <a class="btn-delete" href="index.php?delete_id=<?php echo $location['id']; ?>" onclick="return confirm('Are you sure you want to permanently delete this location?');">Remove</a>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($healthcare_locations)): ?>
        <tr>
            <td colspan="4" style="text-align: center; color: #888;">No entries found. Table is empty.</td>
        </tr>
        <?php endif; ?>
    </table>
</div>

</body>
</html>