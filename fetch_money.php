<?php
// Database connection
$host = "localhost";
$db_name = "health_insurance_system";
$username = "root";
$password = "";
$charset = "utf8mb4";

try {
    $dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $conn = new PDO($dsn, $username, $password, $options);
    $conn->query("SET time_zone = '+00:00'");
    
} catch(PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    die("Database Connection Failed: " . $e->getMessage());
}

// Query to fetch current money value
$query = "SELECT amount FROM money WHERE money_id = 1";  // Assuming only 1 row for simplicity
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result) {
    echo json_encode(['success' => true, 'amount' => $result['amount']]);
} else {
    echo json_encode(['success' => false]);
}
?>
