<?php
// Database connection (replace with your connection details)
require_once 'config.php';

if (isset($_GET['patient_id'])) {
    $patient_id = $_GET['patient_id'];

    // Get patient details
    $sql = "SELECT * FROM patients WHERE patient_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $patient = $result->fetch_assoc();

    if ($patient) {
        echo json_encode($patient);
    } else {
        echo json_encode(null); // Patient not found
    }
}
?>
