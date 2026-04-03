<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'health_insurance_system');
define('DB_USER', 'root'); // Change this to your database username
define('DB_PASS', ''); // Change this to your database password

// Other configuration settings
define('SITE_URL', 'http://localhost/health_insurance_system/'); // Change this to your site URL
define('TIMEZONE', 'Africa/Accra'); // Set appropriate timezone

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set(TIMEZONE);

// Session settings
session_start();
// Set the content type to JSON
header('Content-Type: application/json');

// Check if the action parameter is set
if (!isset($_GET['action'])) {
    echo json_encode(['status' => 'error', 'message' => 'Action parameter is required']);
    exit;
}

$action = $_GET['action'];

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    switch ($action) {
        case 'list':
            listServices($pdo);
            break;
        case 'get':
            getService($pdo);
            break;
        case 'add':
            addService($pdo);
            break;
        case 'update':
            updateService($pdo);
            break;
        case 'delete':
            deleteService($pdo);
            break;
        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action parameter']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}

// Function to list services with pagination and filtering
function listServices($pdo) {
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = 10; // Items per page
    $offset = ($page - 1) * $limit;
    
    // Get search and filter parameters
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $category = isset($_GET['category']) ? $_GET['category'] : '';
    
    // Build query conditions
    $conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $conditions[] = "(service_name LIKE ? OR description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($category)) {
        $conditions[] = "service_category = ?";
        $params[] = $category;
    }
    
    $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
    
    // Count total records for pagination
    $countQuery = "SELECT COUNT(*) FROM medical_services $whereClause";
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalRecords = $stmt->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);
    
    // Get records with pagination
    $query = "SELECT * FROM medical_services $whereClause ORDER BY service_name LIMIT $offset, $limit";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'data' => [
            'services' => $services,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'totalRecords' => $totalRecords
        ]
    ]);
}

// Function to get a single service by ID
function getService($pdo) {
    if (!isset($_GET['service_id']) || empty($_GET['service_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Service ID is required']);
        return;
    }
    
    $serviceId = $_GET['service_id'];
    
    $query = "SELECT * FROM medical_services WHERE service_id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$serviceId]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$service) {
        echo json_encode(['status' => 'error', 'message' => 'Service not found']);
        return;
    }
    
    echo json_encode(['status' => 'success', 'data' => $service]);
}

// Function to add a new service
function addService($pdo) {
    // Validate required fields
    $requiredFields = ['service_name', 'service_category', 'base_price'];
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            echo json_encode(['status' => 'error', 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required']);
            return;
        }
    }
    
    // Get form data
    $serviceName = $_POST['service_name'];
    $serviceCategory = $_POST['service_category'];
    $basePrice = floatval($_POST['base_price']);
    $description = isset($_POST['description']) ? $_POST['description'] : '';
    $status = isset($_POST['status']) ? $_POST['status'] : 'active';
    
    // Validate service category
    $validCategories = ['consultation', 'laboratory', 'radiology', 'medication', 'surgery', 'other'];
    if (!in_array($serviceCategory, $validCategories)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid service category']);
        return;
    }
    
    // Check if service name already exists
    $checkQuery = "SELECT service_id FROM medical_services WHERE service_name = ?";
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->execute([$serviceName]);
    if ($checkStmt->rowCount() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'A service with this name already exists']);
        return;
    }
    
    // Insert new service
    $insertQuery = "INSERT INTO medical_services (service_name, service_category, base_price, description, status) 
                    VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($insertQuery);
    $result = $stmt->execute([$serviceName, $serviceCategory, $basePrice, $description, $status]);
    
    if ($result) {
        $serviceId = $pdo->lastInsertId();
        
        // Add to audit trail
        addAuditTrail($pdo, 'create', 'medical_services', $serviceId, null, [
            'service_name' => $serviceName,
            'service_category' => $serviceCategory,
            'base_price' => $basePrice,
            'description' => $description,
            'status' => $status
        ]);
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'Service added successfully',
            'data' => ['service_id' => $serviceId]
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add service']);
    }
}

// Function to update an existing service
function updateService($pdo) {
    // Validate required fields
    if (!isset($_POST['service_id']) || empty($_POST['service_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Service ID is required']);
        return;
    }
    
    $requiredFields = ['service_name', 'service_category', 'base_price'];
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            echo json_encode(['status' => 'error', 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required']);
            return;
        }
    }
    
    // Get form data
    $serviceId = $_POST['service_id'];
    $serviceName = $_POST['service_name'];
    $serviceCategory = $_POST['service_category'];
    $basePrice = floatval($_POST['base_price']);
    $description = isset($_POST['description']) ? $_POST['description'] : '';
    $status = isset($_POST['status']) ? $_POST['status'] : 'active';
    
    // Validate service category
    $validCategories = ['consultation', 'laboratory', 'radiology', 'medication', 'surgery', 'other'];
    if (!in_array($serviceCategory, $validCategories)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid service category']);
        return;
    }
    
    // Check if service exists
    $checkQuery = "SELECT * FROM medical_services WHERE service_id = ?";
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->execute([$serviceId]);
    $existingService = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existingService) {
        echo json_encode(['status' => 'error', 'message' => 'Service not found']);
        return;
    }
    
    // Check if another service with the same name exists (excluding the current one)
    $nameCheckQuery = "SELECT service_id FROM medical_services WHERE service_name = ? AND service_id != ?";
    $nameCheckStmt = $pdo->prepare($nameCheckQuery);
    $nameCheckStmt->execute([$serviceName, $serviceId]);
    if ($nameCheckStmt->rowCount() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Another service with this name already exists']);
        return;
    }
    
    // Update service
    $updateQuery = "UPDATE medical_services SET 
                    service_name = ?, 
                    service_category = ?, 
                    base_price = ?, 
                    description = ?, 
                    status = ? 
                    WHERE service_id = ?";
                    
    $stmt = $pdo->prepare($updateQuery);
    $result = $stmt->execute([$serviceName, $serviceCategory, $basePrice, $description, $status, $serviceId]);
    
    if ($result) {
        // Add to audit trail
        addAuditTrail($pdo, 'update', 'medical_services', $serviceId, $existingService, [
            'service_name' => $serviceName,
            'service_category' => $serviceCategory,
            'base_price' => $basePrice,
            'description' => $description,
            'status' => $status
        ]);
        
        echo json_encode(['status' => 'success', 'message' => 'Service updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update service']);
    }
}

// Function to delete a service
function deleteService($pdo) {
    if (!isset($_POST['service_id']) || empty($_POST['service_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Service ID is required']);
        return;
    }
    
    $serviceId = $_POST['service_id'];
    
    // Check if service exists
    $checkQuery = "SELECT * FROM medical_services WHERE service_id = ?";
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->execute([$serviceId]);
    $existingService = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existingService) {
        echo json_encode(['status' => 'error', 'message' => 'Service not found']);
        return;
    }
    
    // Check if service is used in any treatment
    $usageQuery = "SELECT COUNT(*) FROM treatment_services WHERE service_id = ?";
    $usageStmt = $pdo->prepare($usageQuery);
    $usageStmt->execute([$serviceId]);
    $usageCount = $usageStmt->fetchColumn();
    
    if ($usageCount > 0) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'This service cannot be deleted because it is used in ' . $usageCount . ' treatment(s)'
        ]);
        return;
    }
    
    // Delete service
    $deleteQuery = "DELETE FROM medical_services WHERE service_id = ?";
    $stmt = $pdo->prepare($deleteQuery);
    $result = $stmt->execute([$serviceId]);
    
    if ($result) {
        // Add to audit trail
        addAuditTrail($pdo, 'delete', 'medical_services', $serviceId, $existingService, null);
        
        echo json_encode(['status' => 'success', 'message' => 'Service deleted successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete service']);
    }
}

// Helper function to add audit trail
function addAuditTrail($pdo, $action, $entityType, $entityId, $oldValues, $newValues) {
    // Assuming user_id is stored in session
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    
    $query = "INSERT INTO audit_trails (user_id, action, entity_type, entity_id, old_values, new_values) 
              VALUES (?, ?, ?, ?, ?, ?)";
              
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        $userId,
        $action,
        $entityType,
        $entityId,
        $oldValues ? json_encode($oldValues) : null,
        $newValues ? json_encode($newValues) : null
    ]);
}
?>