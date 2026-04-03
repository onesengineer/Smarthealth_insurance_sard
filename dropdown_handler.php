<?php
// Database configuration
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

// Check if the type parameter is set
if (!isset($_GET['type'])) {
    echo json_encode(['status' => 'error', 'message' => 'Type parameter is required']);
    exit;
}

$type = $_GET['type'];

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    switch ($type) {
        case 'patients':
            getPatients($pdo);
            break;
        case 'doctors':
            getDoctors($pdo);
            break;
        case 'services':
            getServices($pdo);
            break;
        case 'treatments':
            getTreatments($pdo);
            break;
        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid type parameter']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}

// Function to get patients for dropdown
function getPatients($pdo) {
    $stmt = $pdo->prepare("
        SELECT p.patient_id, CONCAT(u.first_name, ' ', u.last_name) AS patient_name
        FROM patients p
        JOIN users u ON p.user_id = u.user_id
        WHERE u.status = 'active'
        ORDER BY u.last_name, u.first_name
    ");
    
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'data' => $patients]);
}

// Function to get doctors for dropdown
function getDoctors($pdo) {
    $stmt = $pdo->prepare("
        SELECT d.doctor_id, CONCAT(u.first_name, ' ', u.last_name) AS doctor_name, d.specialization
        FROM doctors d
        JOIN users u ON d.user_id = u.user_id
        WHERE u.status = 'active'
        ORDER BY u.last_name, u.first_name
    ");
    
    $stmt->execute();
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'data' => $doctors]);
}

// Function to get medical services for dropdown
function getServices($pdo) {
    $stmt = $pdo->prepare("
        SELECT service_id, service_name, service_category, base_price
        FROM medical_services
        WHERE status = 'active'
        ORDER BY service_category, service_name
    ");
    
    $stmt->execute();
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'data' => $services]);
}

// Function to get treatments for dropdown
function getTreatments($pdo) {
    $stmt = $pdo->prepare("
        SELECT t.treatment_id, 
               CONCAT(u.first_name, ' ', u.last_name) AS patient_name, 
               DATE_FORMAT(t.treatment_date, '%Y-%m-%d') AS treatment_date
        FROM treatment_records t
        JOIN patients p ON t.patient_id = p.patient_id
        JOIN users u ON p.user_id = u.user_id
        ORDER BY t.treatment_date DESC
    ");
    
    $stmt->execute();
    $treatments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'data' => $treatments]);
}
?>