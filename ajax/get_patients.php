<?php
// File: ajax/
// This file retrieves all registered patients for the dropdown

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
    // Query to get patients with their user information
    $query = "SELECT p.patient_id, u.first_name, u.last_name, u.national_id 
              FROM patients p 
              JOIN users u ON p.user_id = u.user_id 
              WHERE u.status = 'active'
              ORDER BY u.last_name, u.first_name";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return patients as JSON
    header('Content-Type: application/json');
    echo json_encode($patients);
    
} catch(PDOException $e) {
    // Return error
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>