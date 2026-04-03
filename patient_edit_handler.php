<?php
// Database connection settings
$host = 'localhost';
$db = 'health_insurance_system';
$user = 'root';
$pass = '';

try {
    // Create a PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    // Set error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate required fields
if (empty($_POST['patient_id'])) {
    die("Error: Patient ID is required.");
}

    
    $patient_id = $_POST['patient_id'];
    
    // Prepare array of fields to update
    $fields = [];
    $params = [];
    
    // Check each possible field and add to update if present
    if (isset($_POST['name']) && !empty($_POST['name'])) {
        $fields[] = "name = :name";
        $params[':name'] = $_POST['name'];
    }
    
    if (isset($_POST['email']) && !empty($_POST['email'])) {
        $fields[] = "email = :email";
        $params[':email'] = $_POST['email'];
    }
    
    if (isset($_POST['phone']) && !empty($_POST['phone'])) {
        $fields[] = "phone = :phone";
        $params[':phone'] = $_POST['phone'];
    }
    
    if (isset($_POST['address']) && !empty($_POST['address'])) {
        $fields[] = "address = :address";
        $params[':address'] = $_POST['address'];
    }
    
    if (isset($_POST['dob']) && !empty($_POST['dob'])) {
        $fields[] = "date_of_birth = :dob";
        $params[':dob'] = $_POST['dob'];
    }
    
    if (isset($_POST['insurance_id']) && !empty($_POST['insurance_id'])) {
        $fields[] = "insurance_id = :insurance_id";
        $params[':insurance_id'] = $_POST['insurance_id'];
    }
    
    if (isset($_POST['policy_number']) && !empty($_POST['policy_number'])) {
        $fields[] = "policy_number = :policy_number";
        $params[':policy_number'] = $_POST['policy_number'];
    }
    
    // If no fields to update, return error
    if (empty($fields)) {
        die("Error: No fields to update.");
    }
    
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Create UPDATE query
$query = "UPDATE patients SET " . implode(", ", $fields) . " WHERE patient_id = :patient_id";

        $params[':patient_id'] = $patient_id;
        
        // Prepare and execute the query
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        // If there are user table fields to update (like email), update them too
        if (isset($_POST['email']) && !empty($_POST['email'])) {
    $query = "UPDATE patients SET " . implode(", ", $fields) . " WHERE patient_id = :patient_id";

            $userStmt = $pdo->prepare($userQuery);
            $userStmt->bindValue(':email', $_POST['email']);
            $userStmt->bindValue(':patient_id', $patient_id);
            $userStmt->execute();
        }
        
        // Commit the transaction
        $pdo->commit();
        
        // Redirect to patient list or detail page
        header("Location: index.php?id=$patient_id&status=updated");
        exit();
    } catch (PDOException $e) {
        // Roll back the transaction if something failed
        $pdo->rollBack();
        die("Error updating patient: " . $e->getMessage());
    }
} else {
    // Not a POST request
    die("Invalid request method.");
}
?>