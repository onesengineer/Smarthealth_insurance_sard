<?php
$host = 'localhost';
$db = 'health_insurance_system';
$user = 'root';
$pass = '';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['error' => 'No Patient ID provided']);
    exit;
}

$patientId = intval($_GET['id']);

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch patient details with JOIN
    $stmt = $pdo->prepare("
        SELECT 
            p.patient_id,
            u.user_id,
            u.username,
            u.first_name,
            u.last_name,
            u.email,
            u.phone_number,
            u.user_type,
            u.national_id,
            u.status,
            u.created_at,
            u.updated_at,
            p.gender,
            p.date_of_birth,
            p.address,
            p.photo_path,
            p.insurance_category,
            p.blood_group,
            p.emergency_contact_name,
            p.emergency_contact_phone,
            p.allergies
        FROM patients p
        INNER JOIN users u ON p.user_id = u.user_id
        WHERE p.patient_id = :patient_id AND u.user_type = 'patient'
        LIMIT 1
    ");
    $stmt->execute([':patient_id' => $patientId]);

    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($patient) {
        header('Content-Type: application/json');
        echo json_encode($patient);
    } else {
        echo json_encode(['error' => 'Patient not found']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
}
?>
