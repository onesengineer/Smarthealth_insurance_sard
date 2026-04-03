


<?php
// File: ajax/
// This file disables a smart card

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

// Get current user ID (assuming you have a session variable)
session_start();
$performed_by = $_SESSION['user_id'];

try {
    // Get card ID from POST
    $card_id = $_POST['card_id'];
    
    // Get current card info before updating
    $getCardQuery = "SELECT sc.card_id, sc.rfid_number, sc.card_status, sc.patient_id 
                    FROM smart_cards sc 
                    WHERE sc.card_id = :card_id";
                    
    $getCardStmt = $conn->prepare($getCardQuery);
    $getCardStmt->bindParam(':card_id', $card_id);
    $getCardStmt->execute();
    
    if ($getCardStmt->rowCount() === 0) {
        // Card not found
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Card not found']);
        exit;
    }
    
    $cardData = $getCardStmt->fetch(PDO::FETCH_ASSOC);
    
    // Start transaction
    $conn->beginTransaction();
    
    // Update card status to inactive
    $updateQuery = "UPDATE smart_cards SET card_status = 'inactive' WHERE card_id = :card_id";
    
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bindParam(':card_id', $card_id);
    $updateStmt->execute();
    
    // Create audit trail
    $auditQuery = "INSERT INTO audit_trails (user_id, action, entity_type, entity_id, old_values, new_values) 
                  VALUES (:user_id, 'update', 'smart_card', :entity_id, :old_values, :new_values)";
    
    $auditStmt = $conn->prepare($auditQuery);
    $auditStmt->bindParam(':user_id', $performed_by);
    $auditStmt->bindParam(':entity_id', $card_id);
    
    $oldValues = json_encode([
        'card_status' => $cardData['card_status']
    ]);
    
    $newValues = json_encode([
        'card_status' => 'inactive'
    ]);
    
    $auditStmt->bindParam(':old_values', $oldValues);
    $auditStmt->bindParam(':new_values', $newValues);
    $auditStmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    // Return success
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    
} catch(PDOException $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Return error
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>