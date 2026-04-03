<?php
// File: ajax/
// This file handles card recharge

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
$performed_by = $_SESSION['user_id'];  // Get logged-in user's ID
// $performed_by can be admin, cashier, etc. based on the user session

try {
    // Check if the RFID number exists
    $checkQuery = "SELECT card_id, card_status FROM smart_cards WHERE rfid_number = :rfid_number";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bindParam(':rfid_number', $_POST['rfid_number']);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        // RFID doesn't exist
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Card not found with this RFID number']);
        exit;
    }
    
    $cardData = $checkStmt->fetch(PDO::FETCH_ASSOC);
    $cardId = $cardData['card_id'];
    
    // Check if card is active
    if ($cardData['card_status'] !== 'active') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'This card is not active']);
        exit;
    }
    
    // Get recharge amount
    $amount = floatval($_POST['amount']);
    if ($amount <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Recharge amount must be greater than zero']);
        exit;
    }
    
    // Start transaction
    $conn->beginTransaction();
    
    // Generate receipt number
    $receiptNumber = 'RCH-' . date('YmdHis') . '-' . rand(100, 999);
    
    // Create recharge record
    $rechargeQuery = "INSERT INTO card_recharges (card_id, amount, recharge_date, performed_by, payment_method, reference_number, receipt_number, status) 
                     VALUES (:card_id, :amount, NOW(), :performed_by, :payment_method, :reference_number, :receipt_number, 'completed')";
    
    $rechargeStmt = $conn->prepare($rechargeQuery);
    $rechargeStmt->bindParam(':card_id', $cardId);
    $rechargeStmt->bindParam(':amount', $amount);
    $rechargeStmt->bindParam(':performed_by', $performed_by);  // Logged-in user who is performing the recharge
    $rechargeStmt->bindParam(':payment_method', $_POST['payment_method']);
    $rechargeStmt->bindParam(':reference_number', $_POST['reference_number']);
    $rechargeStmt->bindParam(':receipt_number', $receiptNumber);
    $rechargeStmt->execute();
    
    // Create transaction record
    $transactionQuery = "INSERT INTO transactions (card_id, transaction_type, amount, transaction_date, 
                        description, performed_by, reference_number, payment_method, status) 
                        VALUES (:card_id, 'recharge', :amount, NOW(), 'Card recharge', 
                        :performed_by, :reference_number, :payment_method, 'completed')";
    
    $transactionStmt = $conn->prepare($transactionQuery);
    $transactionStmt->bindParam(':card_id', $cardId);
    $transactionStmt->bindParam(':amount', $amount);
    $transactionStmt->bindParam(':performed_by', $performed_by);  // Same user who performed the recharge
    $transactionStmt->bindParam(':reference_number', $receiptNumber);
    $transactionStmt->bindParam(':payment_method', $_POST['payment_method']);
    $transactionStmt->execute();
    
    // Update card balance
    $updateQuery = "UPDATE smart_cards SET current_balance = current_balance + :amount WHERE card_id = :card_id";
    
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bindParam(':amount', $amount);
    $updateStmt->bindParam(':card_id', $cardId);
    $updateStmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    // Return success
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'receipt_number' => $receiptNumber]);
    
} catch(PDOException $e) {
    // Rollback transaction on error
    $conn->rollBack();
    
    // Return error
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
