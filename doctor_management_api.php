<?php

$db_host = 'localhost';
$db_username = 'root';
$db_password = '';
$db_name = 'health_insurance_system';

// Create connection
$conn = mysqli_connect($db_host, $db_username, $db_password, $db_name);

// Set charset to utf8
if ($conn) {
    mysqli_set_charset($conn, "utf8");
}

// Error reporting (comment out in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Session start (if needed)
if (!session_id()) {
    session_start();
}

// Function to get current logged in user ID (replace with your actual authentication logic)
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit();
}

// Set headers for JSON response
header('Content-Type: application/json');

// Check if the connection was successful
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . mysqli_connect_error()]);
    exit;
}

// Handle different API actions
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

switch ($action) {
    case 'get_doctors':
        getDoctors($conn);
        break;
    case 'get_doctor':
        getDoctor($conn, $_GET['doctor_id']);
        break;
    case 'add_doctor':
        addDoctor($conn);
        break;
    case 'update_doctor':
        updateDoctor($conn);
        break;
    case 'delete_doctor':
        deleteDoctor($conn, $_POST['doctor_id']);
        break;
    case 'get_departments':
        getDepartments($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

// Close the database connection
mysqli_close($conn);

// Function to get all doctors, with optional search filter
function getDoctors($conn) {
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    $query = "
        SELECT d.doctor_id, u.first_name, u.last_name, u.email, u.phone_number, u.username, 
               u.national_id, u.status, d.specialization, d.license_number, d.department, d.consultation_fee
        FROM doctors d
        JOIN users u ON d.user_id = u.user_id
        WHERE 1=1
    ";
    
    // Add search conditions if search term is provided
    if (!empty($search)) {
        $search = mysqli_real_escape_string($conn, $search);
        $query .= " AND (
            u.first_name LIKE '%$search%' OR 
            u.last_name LIKE '%$search%' OR 
            u.email LIKE '%$search%' OR 
            u.phone_number LIKE '%$search%' OR 
            d.specialization LIKE '%$search%' OR 
            d.department LIKE '%$search%' OR 
            d.license_number LIKE '%$search%'
        )";
    }
    
    $query .= " ORDER BY u.first_name, u.last_name";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Error fetching doctors: ' . mysqli_error($conn)]);
        return;
    }
    
    $doctors = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $doctors[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $doctors]);
}

// Function to get a specific doctor by ID
function getDoctor($conn, $doctorId) {
    $doctorId = mysqli_real_escape_string($conn, $doctorId);
    
    $query = "
        SELECT d.doctor_id, u.user_id, u.first_name, u.last_name, u.email, u.phone_number, 
               u.username, u.national_id, u.status, d.specialization, d.license_number, 
               d.department, d.consultation_fee
        FROM doctors d
        JOIN users u ON d.user_id = u.user_id
        WHERE d.doctor_id = '$doctorId'
    ";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Error fetching doctor: ' . mysqli_error($conn)]);
        return;
    }
    
    if (mysqli_num_rows($result) == 0) {
        echo json_encode(['success' => false, 'message' => 'Doctor not found']);
        return;
    }
    
    $doctor = mysqli_fetch_assoc($result);
    echo json_encode(['success' => true, 'data' => $doctor]);
}

// Function to add a new doctor
function addDoctor($conn) {
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // First, create a user entry
        $firstName = mysqli_real_escape_string($conn, $_POST['firstName']);
        $lastName = mysqli_real_escape_string($conn, $_POST['lastName']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phoneNumber = mysqli_real_escape_string($conn, $_POST['phoneNumber']);
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password
        $nationalId = mysqli_real_escape_string($conn, $_POST['nationalId']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        
        // Check if username already exists
        $checkQuery = "SELECT user_id FROM users WHERE username = '$username'";
        $checkResult = mysqli_query($conn, $checkQuery);
        
        if (mysqli_num_rows($checkResult) > 0) {
            echo json_encode(['success' => false, 'message' => 'Username already exists']);
            return;
        }
        
        $userQuery = "
            INSERT INTO users (username, password, first_name, last_name, email, phone_number, user_type, national_id, status)
            VALUES ('$username', '$password', '$firstName', '$lastName', '$email', '$phoneNumber', 'doctor', '$nationalId', '$status')
        ";
        
        $userResult = mysqli_query($conn, $userQuery);
        
        if (!$userResult) {
            throw new Exception('Error creating user: ' . mysqli_error($conn));
        }
        
        $userId = mysqli_insert_id($conn);
        
        // Then, create doctor entry
        $specialization = mysqli_real_escape_string($conn, $_POST['specialization']);
        $licenseNumber = mysqli_real_escape_string($conn, $_POST['licenseNumber']);
        $department = mysqli_real_escape_string($conn, $_POST['department']);
        $consultationFee = floatval($_POST['consultationFee']);
        
        $doctorQuery = "
            INSERT INTO doctors (user_id, specialization, license_number, department, consultation_fee)
            VALUES ('$userId', '$specialization', '$licenseNumber', '$department', '$consultationFee')
        ";
        
        $doctorResult = mysqli_query($conn, $doctorQuery);
        
        if (!$doctorResult) {
            throw new Exception('Error creating doctor: ' . mysqli_error($conn));
        }
        
        // Commit the transaction
        mysqli_commit($conn);
        echo json_encode(['success' => true, 'message' => 'Doctor added successfully']);
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Function to update an existing doctor
function updateDoctor($conn) {
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        $doctorId = mysqli_real_escape_string($conn, $_POST['doctorId']);
        
        // First, get the user_id for this doctor
        $getUserIdQuery = "SELECT user_id FROM doctors WHERE doctor_id = '$doctorId'";
        $userIdResult = mysqli_query($conn, $getUserIdQuery);
        
        if (!$userIdResult || mysqli_num_rows($userIdResult) == 0) {
            throw new Exception('Doctor not found');
        }
        
        $userData = mysqli_fetch_assoc($userIdResult);
        $userId = $userData['user_id'];
        
        // Update user information
        $firstName = mysqli_real_escape_string($conn, $_POST['firstName']);
        $lastName = mysqli_real_escape_string($conn, $_POST['lastName']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phoneNumber = mysqli_real_escape_string($conn, $_POST['phoneNumber']);
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $nationalId = mysqli_real_escape_string($conn, $_POST['nationalId']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        
        // Check if username exists and belongs to another user
        $checkQuery = "SELECT user_id FROM users WHERE username = '$username' AND user_id != '$userId'";
        $checkResult = mysqli_query($conn, $checkQuery);
        
        if (mysqli_num_rows($checkResult) > 0) {
            echo json_encode(['success' => false, 'message' => 'Username already exists']);
            return;
        }
        
        $userQuery = "
            UPDATE users SET 
                first_name = '$firstName',
                last_name = '$lastName',
                email = '$email',
                phone_number = '$phoneNumber',
                username = '$username',
                national_id = '$nationalId',
                status = '$status'
        ";
        
        // Only update password if provided
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $userQuery .= ", password = '$password'";
        }
        
        $userQuery .= " WHERE user_id = '$userId'";
        
        $userResult = mysqli_query($conn, $userQuery);
        
        if (!$userResult) {
            throw new Exception('Error updating user: ' . mysqli_error($conn));
        }
        
        // Update doctor information
        $specialization = mysqli_real_escape_string($conn, $_POST['specialization']);
        $licenseNumber = mysqli_real_escape_string($conn, $_POST['licenseNumber']);
        $department = mysqli_real_escape_string($conn, $_POST['department']);
        $consultationFee = floatval($_POST['consultationFee']);
        
        $doctorQuery = "
            UPDATE doctors SET 
                specialization = '$specialization',
                license_number = '$licenseNumber',
                department = '$department',
                consultation_fee = '$consultationFee'
            WHERE doctor_id = '$doctorId'
        ";
        
        $doctorResult = mysqli_query($conn, $doctorQuery);
        
        if (!$doctorResult) {
            throw new Exception('Error updating doctor: ' . mysqli_error($conn));
        }
        
        // Commit the transaction
        mysqli_commit($conn);
        echo json_encode(['success' => true, 'message' => 'Doctor updated successfully']);
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Function to delete a doctor
function deleteDoctor($conn, $doctorId) {
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        $doctorId = mysqli_real_escape_string($conn, $doctorId);
        
        // First, get the user_id for this doctor
        $getUserIdQuery = "SELECT user_id FROM doctors WHERE doctor_id = '$doctorId'";
        $userIdResult = mysqli_query($conn, $getUserIdQuery);
        
        if (!$userIdResult || mysqli_num_rows($userIdResult) == 0) {
            throw new Exception('Doctor not found');
        }
        
        $userData = mysqli_fetch_assoc($userIdResult);
        $userId = $userData['user_id'];
        
     // Check if doctor has appointments
        $checkAppointmentsQuery = "SELECT appointment_id FROM appointments WHERE doctor_id = '$doctorId' LIMIT 1";
        $appointmentsResult = mysqli_query($conn, $checkAppointmentsQuery);
        
        if (mysqli_num_rows($appointmentsResult) > 0) {
            throw new Exception('Cannot delete doctor who has appointments. Please reassign or cancel the appointments first.');
        }
        
        // Check if doctor has treatment records
        $checkTreatmentsQuery = "SELECT treatment_id FROM treatment_records WHERE doctor_id = '$doctorId' LIMIT 1";
        $treatmentsResult = mysqli_query($conn, $checkTreatmentsQuery);
        
        if (mysqli_num_rows($treatmentsResult) > 0) {
            throw new Exception('Cannot delete doctor who has treatment records. Please reassign the treatments first.');
        }
        
        // Delete the doctor entry
        $deleteDoctorQuery = "DELETE FROM doctors WHERE doctor_id = '$doctorId'";
        $deleteDoctorResult = mysqli_query($conn, $deleteDoctorQuery);
        
        if (!$deleteDoctorResult) {
            throw new Exception('Error deleting doctor: ' . mysqli_error($conn));
        }
        
        // Now delete the user entry
        $deleteUserQuery = "DELETE FROM users WHERE user_id = '$userId'";
        $deleteUserResult = mysqli_query($conn, $deleteUserQuery);
        
        if (!$deleteUserResult) {
            throw new Exception('Error deleting user: ' . mysqli_error($conn));
        }
        
        // Log the deletion in audit trails
        $adminId = 1; // Replace with actual admin ID from session
        $auditQuery = "
            INSERT INTO audit_trails (user_id, action, entity_type, entity_id, old_values, new_values)
            VALUES ('$adminId', 'delete', 'doctor', '$doctorId', 'Doctor ID: $doctorId, User ID: $userId', NULL)
        ";
        
        mysqli_query($conn, $auditQuery);
        
        // Commit the transaction
        mysqli_commit($conn);
        echo json_encode(['success' => true, 'message' => 'Doctor deleted successfully']);
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Function to get all departments
function getDepartments($conn) {
    $query = "SELECT department_id, department_name FROM departments ORDER BY department_name";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Error fetching departments: ' . mysqli_error($conn)]);
        return;
    }
    
    $departments = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $departments[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $departments]);
}