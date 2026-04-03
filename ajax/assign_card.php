


<?php
// File: ajax/
// This file assigns a new smart card to a patient

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
    // Check if the RFID number already exists
    $checkQuery = "SELECT card_id FROM smart_cards WHERE rfid_number = :rfid_number";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bindParam(':rfid_number', $_POST['rfid_number']);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        // RFID already exists
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'This RFID number is already assigned to another card']);
        exit;
    }
    
    // Start transaction
    $conn->beginTransaction();
    
    // Insert new smart card
    $query = "INSERT INTO smart_cards (rfid_number, patient_id, issue_date, expiry_date, current_balance, card_status, pin_code) 
              VALUES (:rfid_number, :patient_id, :issue_date, :expiry_date, :initial_balance, 'active', :pin_code)";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':rfid_number', $_POST['rfid_number']);
    $stmt->bindParam(':patient_id', $_POST['patient_id']);
    $stmt->bindParam(':issue_date', $_POST['issue_date']);
    $stmt->bindParam(':expiry_date', $_POST['expiry_date']);
    $stmt->bindParam(':initial_balance', $_POST['initial_balance']);
    
    // Hash the PIN code if provided
    $pinCode = !empty($_POST['pin_code']) ? password_hash($_POST['pin_code'], PASSWORD_DEFAULT) : null;
    $stmt->bindParam(':pin_code', $pinCode);
    
    $stmt->execute();
    $cardId = $conn->lastInsertId();
    
    // If initial balance is greater than 0, create a recharge record
    if ($_POST['initial_balance'] > 0) {
        $rechargeQuery = "INSERT INTO card_recharges (card_id, amount, recharge_date, cashier_id, payment_method, status) 
                         VALUES (:card_id, :amount, NOW(), :cashier_id, 'cash', 'completed')";
        
        $rechargeStmt = $conn->prepare($rechargeQuery);
        $rechargeStmt->bindParam(':card_id', $cardId);
        $rechargeStmt->bindParam(':amount', $_POST['initial_balance']);
        $rechargeStmt->bindParam(':cashier_id', $performed_by);
        $rechargeStmt->execute();
        
        // Also create a transaction record
        $transactionQuery = "INSERT INTO transactions (card_id, transaction_type, amount, transaction_date, 
                            description, performed_by, payment_method, status) 
                            VALUES (:card_id, 'recharge', :amount, NOW(), 'Initial card balance', 
                            :performed_by, 'cash', 'completed')";
        
        $transactionStmt = $conn->prepare($transactionQuery);
        $transactionStmt->bindParam(':card_id', $cardId);
        $transactionStmt->bindParam(':amount', $_POST['initial_balance']);
        $transactionStmt->bindParam(':performed_by', $performed_by);
        $transactionStmt->execute();
    }
    
    // Create audit trail
    $auditQuery = "INSERT INTO audit_trails (user_id, action, entity_type, entity_id, new_values) 
                  VALUES (:user_id, 'create', 'smart_card', :entity_id, :new_values)";
    
    $auditStmt = $conn->prepare($auditQuery);
    $auditStmt->bindParam(':user_id', $performed_by);
    $auditStmt->bindParam(':entity_id', $cardId);
    
    $newValues = json_encode([
        'rfid_number' => $_POST['rfid_number'],
        'patient_id' => $_POST['patient_id'],
        'issue_date' => $_POST['issue_date'],
        'expiry_date' => $_POST['expiry_date'],
        'current_balance' => $_POST['initial_balance']
    ]);
    
    $auditStmt->bindParam(':new_values', $newValues);
    $auditStmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    // Return success
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'card_id' => $cardId]);
    
} catch(PDOException $e) {
    // Rollback transaction on error
    $conn->rollBack();
    
    // Return error
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
