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
// Check if patient_id is provided via POST
if (isset($_POST['patient_id']) && !empty($_POST['patient_id'])) {
    $patient_id = $_POST['patient_id'];
    
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // First, check if users table has user_id or patient_id column
        $stmt = $pdo->query("SHOW COLUMNS FROM users");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Delete from users table
        if (in_array('patient_id', $columns)) {
            $query1 = "DELETE FROM users WHERE patient_id = :id";
        } elseif (in_array('user_id', $columns)) {
            $query1 = "DELETE FROM users WHERE user_id = :id";
        } else {
            throw new PDOException("Could not find appropriate ID column in users table");
        }
        
        $stmt1 = $pdo->prepare($query1);
        $stmt1->bindValue(':id', $patient_id, PDO::PARAM_INT);
        $stmt1->execute();
        
        // Delete from patients table - make sure we're using the right column name
        $stmt = $pdo->query("SHOW COLUMNS FROM patients");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (in_array('patient_id', $columns)) {
            $query2 = "DELETE FROM patients WHERE patient_id = :id";
        } elseif (in_array('id', $columns)) {
            $query2 = "DELETE FROM patients WHERE id = :id";
        } else {
            throw new PDOException("Could not find appropriate ID column in patients table");
        }
        
        $stmt2 = $pdo->prepare($query2);
        $stmt2->bindValue(':id', $patient_id, PDO::PARAM_INT);
        $stmt2->execute();
        
        // Commit the transaction
        $pdo->commit();
        
        echo "Patient deleted successfully.";
    } catch (PDOException $e) {
        // Roll back the transaction if something failed
        $pdo->rollBack();
        echo "Failed to delete patient: " . $e->getMessage();
    }
} else {
    echo "Patient ID is required.";
}
?>