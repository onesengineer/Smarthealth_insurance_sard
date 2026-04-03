<?php


// Check if user is logged in and has appropriate permissions
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'doctor'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Include database connection
$host = 'localhost';
$dbname = 'health_insurance_system';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

// DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

// PDO options
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Create PDO instance
    $conn = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // Display error message
    die("Database Connection Failed: " . $e->getMessage());
}
// Get action from request
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Handle different actions
switch ($action) {
    case 'add':
        addTreatment($conn);
        break;
    case 'update':
        updateTreatment($conn);
        break;
    case 'get':
        getTreatment($conn);
        break;
    case 'list':
        listTreatments($conn);
        break;
    case 'delete':
        deleteTreatment($conn);
        break;
    default:
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid action'
        ]);
        break;
}

// Function to add new treatment record
function addTreatment($conn) {
    try {
        // Begin transaction
        $conn->beginTransaction();
        
        // Get treatment data from POST
        $patientId = isset($_POST['patient_id']) ? intval($_POST['patient_id']) : 0;
        $doctorId = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : 0;
        $diagnosis = isset($_POST['diagnosis']) ? trim($_POST['diagnosis']) : '';
        $treatmentNotes = isset($_POST['treatment_notes']) ? trim($_POST['treatment_notes']) : '';
        $followUpDate = isset($_POST['follow_up_date']) && !empty($_POST['follow_up_date']) ? $_POST['follow_up_date'] : null;
        $status = isset($_POST['status']) ? $_POST['status'] : 'open';
        
        // Validate required fields
        if ($patientId <= 0 || $doctorId <= 0 || empty($diagnosis)) {
            throw new Exception('Patient, doctor and diagnosis are required fields');
        }
        
        // Insert treatment record
        $stmt = $conn->prepare("INSERT INTO treatment_records 
                                (patient_id, doctor_id, diagnosis, treatment_notes, follow_up_date, status) 
                                VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$patientId, $doctorId, $diagnosis, $treatmentNotes, $followUpDate, $status]);
        $treatmentId = $conn->lastInsertId();
        
        // Handle services if any
        if (isset($_POST['service_id']) && is_array($_POST['service_id'])) {
            $serviceIds = $_POST['service_id'];
            $quantities = $_POST['quantity'] ?? [];
            $prices = $_POST['price'] ?? [];
            $notes = $_POST['notes'] ?? [];
            
            for ($i = 0; $i < count($serviceIds); $i++) {
                if (!empty($serviceIds[$i]) && isset($quantities[$i]) && isset($prices[$i])) {
                    $serviceId = intval($serviceIds[$i]);
                    $quantity = intval($quantities[$i]);
                    $price = floatval($prices[$i]);
                    $serviceNote = $notes[$i] ?? '';
                    
                    $stmt = $conn->prepare("INSERT INTO treatment_services 
                                           (treatment_id, service_id, quantity, price, notes) 
                                           VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$treatmentId, $serviceId, $quantity, $price, $serviceNote]);
                }
            }
        }
        
        // Handle medications if any
        if (isset($_POST['medication_name']) && is_array($_POST['medication_name'])) {
            $medicationNames = $_POST['medication_name'];
            $dosages = $_POST['dosage'] ?? [];
            $frequencies = $_POST['frequency'] ?? [];
            $durations = $_POST['duration'] ?? [];
            $instructions = $_POST['instructions'] ?? [];
            $medicationPrices = $_POST['medication_price'] ?? [];
            $medicationQuantities = $_POST['medication_quantity'] ?? [];
            
            for ($i = 0; $i < count($medicationNames); $i++) {
                if (!empty($medicationNames[$i]) && isset($dosages[$i]) && isset($medicationPrices[$i])) {
                    $medName = $medicationNames[$i];
                    $dosage = $dosages[$i];
                    $frequency = $frequencies[$i] ?? '';
                    $duration = $durations[$i] ?? '';
                    $instruction = $instructions[$i] ?? '';
                    $medPrice = floatval($medicationPrices[$i]);
                    $medQuantity = isset($medicationQuantities[$i]) ? intval($medicationQuantities[$i]) : 1;
                    
                    $stmt = $conn->prepare("INSERT INTO medication_records 
                                           (treatment_id, medication_name, dosage, frequency, duration, instructions, price, quantity) 
                                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$treatmentId, $medName, $dosage, $frequency, $duration, $instruction, $medPrice, $medQuantity]);
                }
            }
        }
        
        // Record in audit trail
        recordAudit($conn, $_SESSION['user_id'], 'create', 'treatment_records', $treatmentId, null, json_encode($_POST));
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Treatment record created successfully',
            'treatment_id' => $treatmentId
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction in case of error
        $conn->rollBack();
        
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to create treatment record: ' . $e->getMessage()
        ]);
    }
}

// Function to update existing treatment record
function updateTreatment($conn) {
    try {
        // Begin transaction
        $conn->beginTransaction();
        
        // Get treatment data from POST
        $treatmentId = isset($_POST['treatment_id']) ? intval($_POST['treatment_id']) : 0;
        $patientId = isset($_POST['patient_id']) ? intval($_POST['patient_id']) : 0;
        $doctorId = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : 0;
        $diagnosis = isset($_POST['diagnosis']) ? trim($_POST['diagnosis']) : '';
        $treatmentNotes = isset($_POST['treatment_notes']) ? trim($_POST['treatment_notes']) : '';
        $followUpDate = isset($_POST['follow_up_date']) && !empty($_POST['follow_up_date']) ? $_POST['follow_up_date'] : null;
        $status = isset($_POST['status']) ? $_POST['status'] : 'open';
        
        // Validate required fields
        if ($treatmentId <= 0 || $patientId <= 0 || $doctorId <= 0 || empty($diagnosis)) {
            throw new Exception('Treatment ID, patient, doctor and diagnosis are required fields');
        }
        
        // Get old values for audit trail
        $stmt = $conn->prepare("SELECT * FROM treatment_records WHERE treatment_id = ?");
        $stmt->execute([$treatmentId]);
        $oldValues = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$oldValues) {
            throw new Exception('Treatment record not found');
        }
        
        // Update treatment record
        $stmt = $conn->prepare("UPDATE treatment_records 
                               SET patient_id = ?, doctor_id = ?, diagnosis = ?, 
                                   treatment_notes = ?, follow_up_date = ?, status = ? 
                               WHERE treatment_id = ?");
        $stmt->execute([$patientId, $doctorId, $diagnosis, $treatmentNotes, $followUpDate, $status, $treatmentId]);
        
        // Delete all existing services for this treatment
        $stmt = $conn->prepare("DELETE FROM treatment_services WHERE treatment_id = ?");
        $stmt->execute([$treatmentId]);
        
        // Handle services if any
        if (isset($_POST['service_id']) && is_array($_POST['service_id'])) {
            $serviceIds = $_POST['service_id'];
            $quantities = $_POST['quantity'] ?? [];
            $prices = $_POST['price'] ?? [];
            $notes = $_POST['notes'] ?? [];
            
            for ($i = 0; $i < count($serviceIds); $i++) {
                if (!empty($serviceIds[$i]) && isset($quantities[$i]) && isset($prices[$i])) {
                    $serviceId = intval($serviceIds[$i]);
                    $quantity = intval($quantities[$i]);
                    $price = floatval($prices[$i]);
                    $serviceNote = $notes[$i] ?? '';
                    
                    $stmt = $conn->prepare("INSERT INTO treatment_services 
                                           (treatment_id, service_id, quantity, price, notes) 
                                           VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$treatmentId, $serviceId, $quantity, $price, $serviceNote]);
                }
            }
        }
        
        // Delete all existing medications for this treatment
        $stmt = $conn->prepare("DELETE FROM medication_records WHERE treatment_id = ?");
        $stmt->execute([$treatmentId]);
        
        // Handle medications if any
        if (isset($_POST['medication_name']) && is_array($_POST['medication_name'])) {
            $medicationNames = $_POST['medication_name'];
            $dosages = $_POST['dosage'] ?? [];
            $frequencies = $_POST['frequency'] ?? [];
            $durations = $_POST['duration'] ?? [];
            $instructions = $_POST['instructions'] ?? [];
            $medicationPrices = $_POST['medication_price'] ?? [];
            $medicationQuantities = $_POST['medication_quantity'] ?? [];
            
            for ($i = 0; $i < count($medicationNames); $i++) {
                if (!empty($medicationNames[$i]) && isset($dosages[$i]) && isset($medicationPrices[$i])) {
                    $medName = $medicationNames[$i];
                    $dosage = $dosages[$i];
                    $frequency = $frequencies[$i] ?? '';
                    $duration = $durations[$i] ?? '';
                    $instruction = $instructions[$i] ?? '';
                    $medPrice = floatval($medicationPrices[$i]);
                    $medQuantity = isset($medicationQuantities[$i]) ? intval($medicationQuantities[$i]) : 1;
                    
                    $stmt = $conn->prepare("INSERT INTO medication_records 
                                           (treatment_id, medication_name, dosage, frequency, duration, instructions, price, quantity) 
                                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$treatmentId, $medName, $dosage, $frequency, $duration, $instruction, $medPrice, $medQuantity]);
                }
            }
        }
        
        // Record in audit trail
        recordAudit($conn, $_SESSION['user_id'], 'update', 'treatment_records', $treatmentId, json_encode($oldValues), json_encode($_POST));
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Treatment record updated successfully'
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction in case of error
        $conn->rollBack();
        
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to update treatment record: ' . $e->getMessage()
        ]);
    }
}

// Function to get treatment details
function getTreatment($conn) {
    try {
        $treatmentId = isset($_GET['treatment_id']) ? intval($_GET['treatment_id']) : 0;
        
        if ($treatmentId <= 0) {
            throw new Exception('Invalid treatment ID');
        }
        
        // Get treatment record
        $stmt = $conn->prepare("SELECT * FROM treatment_records WHERE treatment_id = ?");
        $stmt->execute([$treatmentId]);
        $treatment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$treatment) {
            throw new Exception('Treatment record not found');
        }
        
        // Get services for this treatment
        $stmt = $conn->prepare("SELECT * FROM treatment_services WHERE treatment_id = ?");
        $stmt->execute([$treatmentId]);
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get medications for this treatment
        $stmt = $conn->prepare("SELECT * FROM medication_records WHERE treatment_id = ?");
        $stmt->execute([$treatmentId]);
        $medications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'treatment' => $treatment,
                'services' => $services,
                'medications' => $medications
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to retrieve treatment details: ' . $e->getMessage()
        ]);
    }
}

// Function to list treatments with pagination and filtering
function listTreatments($conn) {
    try {
        // Get pagination and filter parameters
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        $offset = ($page - 1) * $limit;
        
        // Get filter parameters
        $patientSearch = isset($_GET['search']) ? trim($_GET['search']) : '';
        $treatmentDate = isset($_GET['date']) && !empty($_GET['date']) ? $_GET['date'] : '';
        $status = isset($_GET['status']) && !empty($_GET['status']) ? $_GET['status'] : '';
        
        // Build query with filters
        $query = "SELECT t.*, 
                  CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                  CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
                  p.national_id,
                  (SELECT SUM(ts.price * ts.quantity) FROM treatment_services ts WHERE ts.treatment_id = t.treatment_id) as service_cost,
                  (SELECT SUM(m.price * m.quantity) FROM medication_records m WHERE m.treatment_id = t.treatment_id) as medication_cost
                  FROM treatment_records t
                  JOIN users p ON t.patient_id = p.user_id
                  JOIN users d ON t.doctor_id = d.user_id
                  WHERE 1=1";
        
        $params = [];
        
        // Apply filters
        if (!empty($patientSearch)) {
            $query .= " AND (p.first_name LIKE ? OR p.last_name LIKE ? OR p.national_id LIKE ?)";
            $searchTerm = "%$patientSearch%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($treatmentDate)) {
            $query .= " AND DATE(t.treatment_date) = ?";
            $params[] = $treatmentDate;
        }
        
        if (!empty($status)) {
            $query .= " AND t.status = ?";
            $params[] = $status;
        }
        
        // Get total count for pagination
        $countQuery = str_replace("SELECT t.*, 
                  CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                  CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
                  p.national_id,
                  (SELECT SUM(ts.price * ts.quantity) FROM treatment_services ts WHERE ts.treatment_id = t.treatment_id) as service_cost,
                  (SELECT SUM(m.price * m.quantity) FROM medication_records m WHERE m.treatment_id = t.treatment_id) as medication_cost", 
                  "SELECT COUNT(*)", $query);
        
        $stmt = $conn->prepare($countQuery);
        $stmt->execute($params);
        $totalRecords = $stmt->fetchColumn();
        
        // Add sorting and pagination
        $query .= " ORDER BY t.treatment_date DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        // Execute query
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $treatments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'treatments' => $treatments,
                'pagination' => [
                    'total' => $totalRecords,
                    'per_page' => $limit,
                    'current_page' => $page,
                    'last_page' => ceil($totalRecords / $limit)
                ]
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to retrieve treatments list: ' . $e->getMessage()
        ]);
    }
}

// Function to delete treatment
function deleteTreatment($conn) {
    try {
        // Begin transaction
        $conn->beginTransaction();
        
        $treatmentId = isset($_POST['treatment_id']) ? intval($_POST['treatment_id']) : 0;
        
        if ($treatmentId <= 0) {
            throw new Exception('Invalid treatment ID');
        }
        
        // Get old values for audit trail
        $stmt = $conn->prepare("SELECT * FROM treatment_records WHERE treatment_id = ?");
        $stmt->execute([$treatmentId]);
        $oldValues = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$oldValues) {
            throw new Exception('Treatment record not found');
        }
        
        // Delete associated services
        $stmt = $conn->prepare("DELETE FROM treatment_services WHERE treatment_id = ?");
        $stmt->execute([$treatmentId]);
        
        // Delete associated medications
        $stmt = $conn->prepare("DELETE FROM medication_records WHERE treatment_id = ?");
        $stmt->execute([$treatmentId]);
        
        // Delete treatment record
        $stmt = $conn->prepare("DELETE FROM treatment_records WHERE treatment_id = ?");
        $stmt->execute([$treatmentId]);
        
        // Record in audit trail
        recordAudit($conn, $_SESSION['user_id'], 'delete', 'treatment_records', $treatmentId, json_encode($oldValues), null);
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Treatment record deleted successfully'
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction in case of error
        $conn->rollBack();
        
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to delete treatment record: ' . $e->getMessage()
        ]);
    }
}

// Helper function to record audit trail
function recordAudit($conn, $userId, $action, $entityType, $entityId, $oldValues, $newValues) {
    try {
        $stmt = $conn->prepare("INSERT INTO audit_trails 
                               (user_id, action, entity_type, entity_id, old_values, new_values) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $entityType, $entityId, $oldValues, $newValues]);
    } catch (Exception $e) {
        // Log error but don't stop the main operation
        error_log('Failed to record audit: ' . $e->getMessage());
    }
}
?>