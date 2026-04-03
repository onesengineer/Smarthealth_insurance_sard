
<?php
// File: ajax/
// This file retrieves all smart cards with patient information

// Include database connection
$host = "localhost";
$db_name = "health_insurance_system";
$username = "root";
$password = "";
$charset = "utf8mb4";

try {
    // Create connection using PDO
    $dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $conn = new PDO($dsn, $username, $password, $options);
    
    // Set timezone for proper timestamp handling
    $conn->query("SET time_zone = '+00:00'");
    
} catch(PDOException $e) {
    // Log error and exit
    error_log("Database Connection Error: " . $e->getMessage());
    die("Database Connection Failed: " . $e->getMessage());
}

try {
    // Query to get smart cards with patient names
    $query = "SELECT sc.card_id, sc.rfid_number, sc.issue_date, sc.expiry_date, 
                     sc.current_balance, sc.card_status, 
                     CONCAT(u.first_name, ' ', u.last_name) AS patient_name
              FROM smart_cards sc
              JOIN patients p ON sc.patient_id = p.patient_id
              JOIN users u ON p.user_id = u.user_id
              ORDER BY sc.card_id DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    $smartCards = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return smart cards as JSON
    header('Content-Type: application/json');
    echo json_encode($smartCards);
    
} catch(PDOException $e) {
    // Return error
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>