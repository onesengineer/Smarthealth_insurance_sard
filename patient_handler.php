<?php
// Database connection
require_once 'config.php';

// Enable strict mode for MySQLi (optional but recommended for debugging)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Set headers for JSON response
header('Content-Type: application/json');

// Function to sanitize input data
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Process AJAX request for patient registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_patient'])) {
    // Initialize response array
    $response = array(
        'status' => 'error',
        'message' => ''
    );
    
    try {
        // Capture and sanitize patient details from the form
        $username = sanitizeInput($_POST['username']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $first_name = sanitizeInput($_POST['first_name']);
        $last_name = sanitizeInput($_POST['last_name']);
        $email = isset($_POST['email']) ? sanitizeInput($_POST['email']) : '';
        $phone_number = sanitizeInput($_POST['phone_number']);
        $gender = sanitizeInput($_POST['gender']);
        $date_of_birth = sanitizeInput($_POST['date_of_birth']);
        $address = sanitizeInput($_POST['address']);
        $insurance_category = isset($_POST['insurance_category']) ? sanitizeInput($_POST['insurance_category']) : 'basic';
        $emergency_contact_name = isset($_POST['emergency_contact_name']) ? sanitizeInput($_POST['emergency_contact_name']) : '';
        $emergency_contact_phone = isset($_POST['emergency_contact_phone']) ? sanitizeInput($_POST['emergency_contact_phone']) : '';
        $blood_group = isset($_POST['blood_group']) ? sanitizeInput($_POST['blood_group']) : '';
        $allergies = isset($_POST['allergies']) ? sanitizeInput($_POST['allergies']) : '';
        
        // File uploads
        $photo_path = null;
        $fingerprint_data = null;
        
        // Handle photo upload
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $upload_dir = 'uploads/photos/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_name = time() . '_' . basename($_FILES['photo']['name']);
            $target_file = $upload_dir . $file_name;
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($_FILES['photo']['type'], $allowed_types)) {
                throw new Exception("Invalid file type. Only JPG, PNG, and GIF files are allowed.");
            }
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
                $photo_path = $target_file;
            } else {
                throw new Exception("Error uploading photo.");
            }
        }
        
        // Handle fingerprint upload
        if (isset($_FILES['fingerprint']) && $_FILES['fingerprint']['error'] == 0) {
            $upload_dir = 'uploads/fingerprints/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_name = time() . '_' . basename($_FILES['fingerprint']['name']);
            $target_file = $upload_dir . $file_name;
            if (move_uploaded_file($_FILES['fingerprint']['tmp_name'], $target_file)) {
                $fingerprint_data = file_get_contents($target_file);
            } else {
                throw new Exception("Error uploading fingerprint data.");
            }
        }
        
        // Input validation
        if (empty($username) || empty($first_name) || empty($last_name) || empty($phone_number) || empty($gender) || empty($date_of_birth) || empty($address)) {
            throw new Exception("Please fill in all required fields.");
        }
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address.");
        }
        
        // Check if username already exists
        $check_sql = "SELECT user_id FROM users WHERE username = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        if ($check_result->num_rows > 0) {
            throw new Exception("Username already exists. Please choose a different username.");
        }
        
        // Begin transaction
        $conn->begin_transaction();
        
        // Step 1: Insert into the `users` table
        $user_sql = "INSERT INTO users (username, password, first_name, last_name, email, phone_number, user_type, status, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, 'patient', 'active', NOW())";
        $user_stmt = $conn->prepare($user_sql);
        $user_stmt->bind_param("ssssss", $username, $password, $first_name, $last_name, $email, $phone_number);
        $user_stmt->execute();
        $user_id = $user_stmt->insert_id;
        
        // Step 2: Insert into the `patients` table
        $patient_sql = "INSERT INTO patients (user_id, gender, date_of_birth, address, photo_path, fingerprint_data, insurance_category, emergency_contact_name, emergency_contact_phone, blood_group, allergies) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $patient_stmt = $conn->prepare($patient_sql);
        $patient_stmt->bind_param("issssssssss", $user_id, $gender, $date_of_birth, $address, $photo_path, $fingerprint_data, $insurance_category, $emergency_contact_name, $emergency_contact_phone, $blood_group, $allergies);
        $patient_stmt->execute();
        $patient_id = $patient_stmt->insert_id;
        
        // Commit transaction
        $conn->commit();
        
        $response['status'] = 'success';
        $response['message'] = "Patient registered successfully! Patient ID: " . $patient_id;
        $response['patient_id'] = $patient_id;
        
    } catch (Exception $e) {
        if ($conn->errno == 0) {
            $conn->rollback();
        }
        $response['message'] = $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}

$response = array(
    'status' => 'error',
    'message' => 'Invalid request'
);
echo json_encode($response);
?>
