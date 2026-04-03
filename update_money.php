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

// Get POST data
$data = json_decode(file_get_contents("php://input"));

if (isset($data->newAmount)) {
    $newAmount = floatval($data->newAmount);

    // Update query
    $updateQuery = "UPDATE money SET amount = :newAmount WHERE money_id = 1"; // Assuming money_id = 1
    $stmt = $conn->prepare($updateQuery);
    $stmt->bindParam(':newAmount', $newAmount);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false]);
}
?>
