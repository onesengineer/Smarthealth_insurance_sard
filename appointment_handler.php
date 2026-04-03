<?php
// appointment_handler.php
// Handles all AJAX requests for the appointment management system

// Initialize database connection
function dbConnect() {
    $host = 'localhost';
    $dbname = 'health_insurance_system';
    $username = 'root';
    $password = '';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die(json_encode([
            'success' => false,
            'message' => 'Database connection error: ' . $e->getMessage()
        ]));
    }
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Authentication required'
        ]);
        exit;
    }
    return $_SESSION['user_id'];
}

// Main request handler
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$userId = checkAuth();

switch ($action) {
    case 'list':
        listAppointments($userId);
        break;
    case 'view':
        viewAppointment($userId);
        break;
    case 'create':
        createAppointment($userId);
        break;
    case 'update':
        updateAppointment($userId);
        break;
    case 'delete':
        deleteAppointment($userId);
        break;
    case 'getPatients':
        getPatientsList();
        break;
    case 'getDoctors':
        getDoctorsList();
        break;
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action specified'
        ]);
        break;
}

// Get list of appointments with filtering and pagination

// List all appointments with filtering for the current user
function listAppointments($userId) {
    $pdo = dbConnect();
    
    // Get user type to verify access permission
    $stmt = $pdo->prepare("SELECT user_type FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Build base query
    $sql = "
        SELECT 
            a.appointment_id,
            a.appointment_date,
            a.appointment_time,
            a.status,
            CONCAT(u_p.first_name, ' ', u_p.last_name) as patient_name,
            CONCAT(u_d.first_name, ' ', u_d.last_name) as doctor_name,
            d.specialization as doctor_specialization
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN doctors d ON a.doctor_id = d.doctor_id
        JOIN users u_p ON p.user_id = u_p.user_id
        JOIN users u_d ON d.user_id = u_d.user_id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Add search filters if provided
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = "%" . $_GET['search'] . "%";
        $sql .= " AND (u_p.first_name LIKE ? OR u_p.last_name LIKE ? OR 
                       u_d.first_name LIKE ? OR u_d.last_name LIKE ?)";
        $params = array_merge($params, [$search, $search, $search, $search]);
    }
    
    // Add date filter if provided
    if (isset($_GET['date']) && !empty($_GET['date'])) {
        $sql .= " AND a.appointment_date = ?";
        $params[] = $_GET['date'];
    }
    
    // Add access restrictions based on user type
    if ($user['user_type'] == 'patient') {
        $sql .= " AND p.user_id = ?";
        $params[] = $userId;
    } elseif ($user['user_type'] == 'doctor') {
        $sql .= " AND d.user_id = ?";
        $params[] = $userId;
    }
    
    // Add sorting
    $sql .= " ORDER BY a.appointment_date DESC, a.appointment_time ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $appointments
    ]);
}
// View appointment details
function viewAppointment($userId) {
    if (!isset($_GET['id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Appointment ID is required'
        ]);
        return;
    }
    
    $appointmentId = (int)$_GET['id'];
    $pdo = dbConnect();
    
    // Get user type to verify access permission
    $stmt = $pdo->prepare("SELECT user_type FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Build the query with access check if needed
    $sql = "
        SELECT 
            a.*,
            CONCAT(u_p.first_name, ' ', u_p.last_name) as patient_name,
            u_p.phone_number as patient_phone,
            CONCAT(u_d.first_name, ' ', u_d.last_name) as doctor_name,
            d.specialization as doctor_specialization,
            d.department as doctor_department
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN doctors d ON a.doctor_id = d.doctor_id
        JOIN users u_p ON p.user_id = u_p.user_id
        JOIN users u_d ON d.user_id = u_d.user_id
        WHERE a.appointment_id = ?
    ";
    
    $params = [$appointmentId];
    
    // Add access restrictions
    if ($user['user_type'] == 'patient') {
        $sql .= " AND p.user_id = ?";
        $params[] = $userId;
    } elseif ($user['user_type'] == 'doctor') {
        $sql .= " AND d.user_id = ?";
        $params[] = $userId;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$appointment) {
        echo json_encode([
            'success' => false,
            'message' => 'Appointment not found or you do not have permission to view it'
        ]);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $appointment
    ]);
}

// Create new appointment
function createAppointment($userId) {
    $pdo = dbConnect();
    
    // Input validation
    $requiredFields = [
        'patient_id', 
        'doctor_id', 
        'appointment_date', 
        'appointment_time'
    ];
    
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            echo json_encode([
                'success' => false,
                'message' => 'Field "' . $field . '" is required'
            ]);
            return;
        }
    }
    
    try {
        // Prepare data
        $data = [
            'patient_id' => (int)$_POST['patient_id'],
            'doctor_id' => (int)$_POST['doctor_id'],
            'appointment_date' => $_POST['appointment_date'],
            'appointment_time' => $_POST['appointment_time'],
            'purpose' => isset($_POST['purpose']) ? $_POST['purpose'] : null,
            'status' => isset($_POST['status']) ? $_POST['status'] : 'scheduled',
            'notes' => isset($_POST['notes']) ? $_POST['notes'] : null
        ];
        
        // Check for time conflicts
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM appointments 
            WHERE doctor_id = ? 
            AND appointment_date = ? 
            AND appointment_time = ? 
            AND status NOT IN ('cancelled', 'no_show')
        ");
        
        $stmt->execute([
            $data['doctor_id'],
            $data['appointment_date'],
            $data['appointment_time']
        ]);
        
        $conflict = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($conflict['count'] > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Doctor already has an appointment at this time. Please select another time.'
            ]);
            return;
        }
        
        // Insert the appointment
        $sql = "
            INSERT INTO appointments 
            (patient_id, doctor_id, appointment_date, appointment_time, purpose, status, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['patient_id'],
            $data['doctor_id'],
            $data['appointment_date'],
            $data['appointment_time'],
            $data['purpose'],
            $data['status'],
            $data['notes']
        ]);
        
        // Log the action in audit trail
        $appointmentId = $pdo->lastInsertId();
        
        $stmt = $pdo->prepare("
            INSERT INTO audit_trails 
            (user_id, action, entity_type, entity_id, new_values) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            'create',
            'appointment',
            $appointmentId,
            json_encode($data)
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Appointment created successfully',
            'id' => $appointmentId
        ]);
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

// Update existing appointment
function updateAppointment($userId) {
    if (!isset($_POST['appointment_id']) || empty($_POST['appointment_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Appointment ID is required'
        ]);
        return;
    }
    
    $appointmentId = (int)$_POST['appointment_id'];
    $pdo = dbConnect();
    
    // Input validation
    $requiredFields = [
        'patient_id', 
        'doctor_id', 
        'appointment_date', 
        'appointment_time'
    ];
    
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            echo json_encode([
                'success' => false,
                'message' => 'Field "' . $field . '" is required'
            ]);
            return;
        }
    }
    
    try {
        // Get existing appointment data for audit trail
        $stmt = $pdo->prepare("SELECT * FROM appointments WHERE appointment_id = ?");
        $stmt->execute([$appointmentId]);
        $oldData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$oldData) {
            echo json_encode([
                'success' => false,
                'message' => 'Appointment not found'
            ]);
            return;
        }
        
        // Prepare new data
        $data = [
            'patient_id' => (int)$_POST['patient_id'],
            'doctor_id' => (int)$_POST['doctor_id'],
            'appointment_date' => $_POST['appointment_date'],
            'appointment_time' => $_POST['appointment_time'],
            'purpose' => isset($_POST['purpose']) ? $_POST['purpose'] : null,
            'status' => isset($_POST['status']) ? $_POST['status'] : 'scheduled',
            'notes' => isset($_POST['notes']) ? $_POST['notes'] : null,
            'appointment_id' => $appointmentId
        ];
        
        // Check for time conflicts (excluding this appointment)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM appointments 
            WHERE doctor_id = ? 
            AND appointment_date = ? 
            AND appointment_time = ? 
            AND status NOT IN ('cancelled', 'no_show')
            AND appointment_id != ?
        ");
        
        $stmt->execute([
            $data['doctor_id'],
            $data['appointment_date'],
            $data['appointment_time'],
            $appointmentId
        ]);
        
        $conflict = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($conflict['count'] > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Doctor already has an appointment at this time. Please select another time.'
            ]);
            return;
        }
        
        // Update the appointment
        $sql = "
            UPDATE appointments 
            SET patient_id = ?, 
                doctor_id = ?, 
                appointment_date = ?, 
                appointment_time = ?, 
                purpose = ?, 
                status = ?, 
                notes = ?
            WHERE appointment_id = ?
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['patient_id'],
            $data['doctor_id'],
            $data['appointment_date'],
            $data['appointment_time'],
            $data['purpose'],
            $data['status'],
            $data['notes'],
            $appointmentId
        ]);
        
        // Log the action in audit trail
        $stmt = $pdo->prepare("
            INSERT INTO audit_trails 
            (user_id, action, entity_type, entity_id, old_values, new_values) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            'update',
            'appointment',
            $appointmentId,
            json_encode($oldData),
            json_encode($data)
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Appointment updated successfully'
        ]);
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

// Delete appointment
function deleteAppointment($userId) {
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Appointment ID is required'
        ]);
        return;
    }
    
    $appointmentId = (int)$_POST['id'];
    $pdo = dbConnect();
    
    try {
        // Get appointment data for audit trail
        $stmt = $pdo->prepare("SELECT * FROM appointments WHERE appointment_id = ?");
        $stmt->execute([$appointmentId]);
        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$appointment) {
            echo json_encode([
                'success' => false,
                'message' => 'Appointment not found'
            ]);
            return;
        }
        
        // Delete the appointment
        $stmt = $pdo->prepare("DELETE FROM appointments WHERE appointment_id = ?");
        $stmt->execute([$appointmentId]);
        
        // Log the action in audit trail
        $stmt = $pdo->prepare("
            INSERT INTO audit_trails 
            (user_id, action, entity_type, entity_id, old_values) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            'delete',
            'appointment',
            $appointmentId,
            json_encode($appointment)
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Appointment deleted successfully'
        ]);
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

// Get list of patients for select dropdown
function getPatientsList() {
    $pdo = dbConnect();
    
    try {
        $sql = "
            SELECT 
                p.patient_id,
                CONCAT(u.first_name, ' ', u.last_name) as patient_name
            FROM patients p
            JOIN users u ON p.user_id = u.user_id
            WHERE u.status = 'active'
            ORDER BY u.first_name, u.last_name
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $patients
        ]);
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

// Get list of doctors for select dropdown
function getDoctorsList() {
    $pdo = dbConnect();
    
    try {
        $sql = "
            SELECT 
                d.doctor_id,
                CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                d.specialization,
                d.department
            FROM doctors d
            JOIN users u ON d.user_id = u.user_id
            WHERE u.status = 'active'
            ORDER BY d.department, u.first_name, u.last_name
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $doctors
        ]);
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}
?>