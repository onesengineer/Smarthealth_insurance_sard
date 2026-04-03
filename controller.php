<?php
function connectDB() {
    $host = 'localhost';
    $dbname = 'health_insurance_system';
    $username = 'root';
    $password = '';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

function getAdminDetails($userId) {
    $pdo = connectDB();
    
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.first_name, u.last_name, u.email, u.phone_number, 
               u.status, a.role, a.permissions
        FROM users u
        JOIN administrators a ON u.user_id = a.user_id
        WHERE u.user_id = ? AND u.user_type = 'admin'
    ");
    
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getDashboardStats() {
    $pdo = connectDB();
    $today = date('Y-m-d');
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM patients");
    $totalPatients = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM doctors");
    $totalDoctors = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date = ?");
    $stmt->execute([$today]);
    $appointmentsToday = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) 
        FROM transactions 
        WHERE DATE(transaction_date) = ? 
        AND transaction_type = 'payment'
        AND status = 'completed'
    ");
    $stmt->execute([$today]);
    $revenueToday = $stmt->fetchColumn();
    
    return [
        'total_patients' => $totalPatients,
        'total_doctors' => $totalDoctors,
        'appointments_today' => $appointmentsToday,
        'revenue_today' => $revenueToday
    ];
}

function getRecentPatients($limit = 5) {
    $pdo = connectDB();
    
    $stmt = $pdo->prepare("
        SELECT p.patient_id, u.first_name, u.last_name, u.email, u.status,
               p.photo_path, p.insurance_category
        FROM patients p
        JOIN users u ON p.user_id = u.user_id
        ORDER BY u.created_at DESC
        LIMIT ?
    ");

    // Bind LIMIT as an integer
    $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRecentTransactions($limit = 5) {
    $pdo = connectDB();
    
    $stmt = $pdo->prepare("
        SELECT t.transaction_id, t.amount, t.transaction_date, t.transaction_type,
               CONCAT(u.first_name, ' ', u.last_name) as patient_name
        FROM transactions t
        JOIN smart_cards sc ON t.card_id = sc.card_id
        JOIN patients p ON sc.patient_id = p.patient_id
        JOIN users u ON p.user_id = u.user_id
        ORDER BY t.transaction_date DESC
        LIMIT ?
    ");

    // Bind LIMIT as an integer
    $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


function getUpcomingAppointments($limit = 5) {
    $pdo = connectDB();
    $today = date('Y-m-d');

    $stmt = $pdo->prepare("
        SELECT a.appointment_id, a.appointment_date, a.appointment_time, 
               a.purpose, a.status,
               CONCAT(pu.first_name, ' ', pu.last_name) as patient_name,
               CONCAT(du.first_name, ' ', du.last_name) as doctor_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN users pu ON p.user_id = pu.user_id
        JOIN doctors d ON a.doctor_id = d.doctor_id
        JOIN users du ON d.user_id = du.user_id
        WHERE a.appointment_date >= ?
        AND a.status = 'scheduled'
        ORDER BY a.appointment_date ASC, a.appointment_time ASC
        LIMIT ?
    ");

    // Bind date and LIMIT correctly
    $stmt->bindValue(1, $today, PDO::PARAM_STR);
    $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


function cancelAppointment($appointmentId) {
    $pdo = connectDB();
    
    $stmt = $pdo->prepare("
        UPDATE appointments 
        SET status = 'cancelled' 
        WHERE appointment_id = ?
    ");
    
    return $stmt->execute([$appointmentId]);
}

function getPatientDetails($patientId) {
    $pdo = connectDB();
    
    $stmt = $pdo->prepare("
        SELECT p.*, u.first_name, u.last_name, u.email, u.phone_number, 
               u.national_id, u.status
        FROM patients p
        JOIN users u ON p.user_id = u.user_id
        WHERE p.patient_id = ?
    ");
    
    $stmt->execute([$patientId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getPatientSmartCard($patientId) {
    $pdo = connectDB();
    
    $stmt = $pdo->prepare("
        SELECT * FROM smart_cards 
        WHERE patient_id = ? 
        ORDER BY card_id DESC 
        LIMIT 1
    ");
    
    $stmt->execute([$patientId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getPatientTreatmentHistory($patientId) {
    $pdo = connectDB();
    
    $stmt = $pdo->prepare("
        SELECT t.treatment_id, t.diagnosis, t.treatment_notes, t.treatment_date,
               t.follow_up_date, t.status,
               CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
               (SELECT SUM(ts.price * ts.quantity) 
                FROM treatment_services ts 
                WHERE ts.treatment_id = t.treatment_id) as service_total,
               (SELECT SUM(m.price * m.quantity) 
                FROM medication_records m 
                WHERE m.treatment_id = t.treatment_id) as medication_total
        FROM treatment_records t
        JOIN doctors d ON t.doctor_id = d.doctor_id
        JOIN users u ON d.user_id = u.user_id
        WHERE t.patient_id = ?
        ORDER BY t.treatment_date DESC
    ");
    
    $stmt->execute([$patientId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPatientAppointments($patientId) {
    $pdo = connectDB();
    
    $stmt = $pdo->prepare("
        SELECT a.appointment_id, a.appointment_date, a.appointment_time, 
               a.purpose, a.status, a.notes,
               CONCAT(u.first_name, ' ', u.last_name) as doctor_name
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.doctor_id
        JOIN users u ON d.user_id = u.user_id
        WHERE a.patient_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ");
    
    $stmt->execute([$patientId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPatientTransactions($patientId) {
    $pdo = connectDB();
    
    $stmt = $pdo->prepare("
        SELECT t.transaction_id, t.amount, t.transaction_date, t.transaction_type,
               t.description, t.reference_number, t.payment_method, t.status,
               CONCAT(u.first_name, ' ', u.last_name) as performed_by
        FROM transactions t
        JOIN smart_cards sc ON t.card_id = sc.card_id
        JOIN users u ON t.performed_by = u.user_id
        WHERE sc.patient_id = ?
        ORDER BY t.transaction_date DESC
    ");
    
    $stmt->execute([$patientId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllDepartments() {
    $pdo = connectDB();
    
    $stmt = $pdo->query("
        SELECT d.*, CONCAT(u.first_name, ' ', u.last_name) as head_doctor_name
        FROM departments d
        LEFT JOIN doctors dr ON d.head_doctor_id = dr.doctor_id
        LEFT JOIN users u ON dr.user_id = u.user_id
        ORDER BY d.department_name
    ");
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllDoctors() {
    $pdo = connectDB();
    
    $stmt = $pdo->query("
        SELECT d.*, u.first_name, u.last_name, u.email, u.phone_number, u.status
        FROM doctors d
        JOIN users u ON d.user_id = u.user_id
        ORDER BY u.last_name, u.first_name
    ");
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllMedicalServices() {
    $pdo = connectDB();
    
    $stmt = $pdo->query("
        SELECT * FROM medical_services
        ORDER BY service_category, service_name
    ");
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function addPatient($userData, $patientData) {
    $pdo = connectDB();
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password, first_name, last_name, email, 
                              phone_number, user_type, national_id)
            VALUES (?, ?, ?, ?, ?, ?, 'patient', ?)
        ");
        
        $stmt->execute([
            $userData['username'],
            password_hash($userData['password'], PASSWORD_DEFAULT),
            $userData['first_name'],
            $userData['last_name'],
            $userData['email'],
            $userData['phone_number'],
            $userData['national_id']
        ]);
        
        $userId = $pdo->lastInsertId();
        
        $stmt = $pdo->prepare("
            INSERT INTO patients (user_id, gender, date_of_birth, address, 
                                 photo_path, insurance_category, emergency_contact_name,
                                 emergency_contact_phone, blood_group, allergies)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            $patientData['gender'],
            $patientData['date_of_birth'],
            $patientData['address'],
            $patientData['photo_path'] ?? null,
            $patientData['insurance_category'],
            $patientData['emergency_contact_name'] ?? null,
            $patientData['emergency_contact_phone'] ?? null,
            $patientData['blood_group'] ?? null,
            $patientData['allergies'] ?? null
        ]);
        
        $patientId = $pdo->lastInsertId();
        
        if (isset($patientData['create_card']) && $patientData['create_card']) {
            $rfidNumber = 'RFID-' . time() . '-' . rand(1000, 9999);
            $issueDate = date('Y-m-d');
            $expiryDate = date('Y-m-d', strtotime('+1 year'));
            
            $stmt = $pdo->prepare("
                INSERT INTO smart_cards (rfid_number, patient_id, issue_date, 
                                        expiry_date, current_balance)
                VALUES (?, ?, ?, ?, 0.00)
            ");
            
            $stmt->execute([$rfidNumber, $patientId, $issueDate, $expiryDate]);
        }
        
        $pdo->commit();
        return ['success' => true, 'patient_id' => $patientId];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function updatePatient($patientId, $userData, $patientData) {
    $pdo = connectDB();
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("SELECT user_id FROM patients WHERE patient_id = ?");
        $stmt->execute([$patientId]);
        $userId = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("
            UPDATE users SET 
                first_name = ?,
                last_name = ?,
                email = ?,
                phone_number = ?,
                national_id = ?
            WHERE user_id = ?
        ");
        
        $stmt->execute([
            $userData['first_name'],
            $userData['last_name'],
            $userData['email'],
            $userData['phone_number'],
            $userData['national_id'],
            $userId
        ]);
        
        $stmt = $pdo->prepare("
            UPDATE patients SET 
                gender = ?,
                date_of_birth = ?,
                address = ?,
                insurance_category = ?,
                emergency_contact_name = ?,
                emergency_contact_phone = ?,
                blood_group = ?,
                allergies = ?
            WHERE patient_id = ?
        ");
        
        $stmt->execute([
            $patientData['gender'],
            $patientData['date_of_birth'],
            $patientData['address'],
            $patientData['insurance_category'],
            $patientData['emergency_contact_name'] ?? null,
            $patientData['emergency_contact_phone'] ?? null,
            $patientData['blood_group'] ?? null,
            $patientData['allergies'] ?? null,
            $patientId
        ]);
        
        if (isset($patientData['photo_path']) && !empty($patientData['photo_path'])) {
            $stmt = $pdo->prepare("
                UPDATE patients SET photo_path = ? WHERE patient_id = ?
            ");
            $stmt->execute([$patientData['photo_path'], $patientId]);
        }
        
        $pdo->commit();
        return ['success' => true];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function createAppointment($appointmentData) {
    $pdo = connectDB();
    
    $stmt = $pdo->prepare("
        INSERT INTO appointments (patient_id, doctor_id, appointment_date, 
                                 appointment_time, purpose, notes)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $appointmentData['patient_id'],
        $appointmentData['doctor_id'],
        $appointmentData['appointment_date'],
        $appointmentData['appointment_time'],
        $appointmentData['purpose'],
        $appointmentData['notes'] ?? null
    ]);
    
    if ($result) {
        return ['success' => true, 'appointment_id' => $pdo->lastInsertId()];
    } else {
        return ['success' => false, 'message' => 'Failed to create appointment'];
    }
}

function processCardPayment($cardId, $amount, $description, $performedBy) {
    $pdo = connectDB();
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("SELECT current_balance, card_status FROM smart_cards WHERE card_id = ?");
        $stmt->execute([$cardId]);
        $card = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$card) {
            throw new Exception("Card not found");
        }
        
        if ($card['card_status'] != 'active') {
            throw new Exception("Card is not active");
        }
        
        if ($card['current_balance'] < $amount) {
            throw new Exception("Insufficient balance");
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO transactions (card_id, transaction_type, amount, description, 
                                     performed_by, payment_method, status)
            VALUES (?, 'payment', ?, ?, ?, 'card', 'completed')
        ");
        
        $stmt->execute([$cardId, $amount, $description, $performedBy]);
        
        $stmt = $pdo->prepare("
            UPDATE smart_cards SET current_balance = current_balance - ? WHERE card_id = ?
        ");
        
        $stmt->execute([$amount, $cardId]);
        
        $pdo->commit();
        return ['success' => true, 'transaction_id' => $pdo->lastInsertId()];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function rechargeSmartCard($cardId, $amount, $paymentMethod, $referenceNumber, $cashierId) {
    $pdo = connectDB();
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("SELECT card_status FROM smart_cards WHERE card_id = ?");
        $stmt->execute([$cardId]);
        $cardStatus = $stmt->fetchColumn();
        
        if (!$cardStatus) {
            throw new Exception("Card not found");
        }
        
        if ($cardStatus != 'active') {
            throw new Exception("Card is not active");
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO card_recharges (card_id, amount, cashier_id, payment_method, reference_number)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $cardId, 
            $amount, 
            $cashierId, 
            $paymentMethod, 
            $referenceNumber
        ]);
        
        $stmt = $pdo->prepare("
            INSERT INTO transactions (card_id, transaction_type, amount, description, 
                                     performed_by, reference_number, payment_method)
            VALUES (?, 'recharge', ?, 'Card recharge', ?, ?, ?)
        ");
        
        $stmt->execute([
            $cardId, 
            $amount, 
            $cashierId, 
            $referenceNumber, 
            $paymentMethod
        ]);
        
        $stmt = $pdo->prepare("
            UPDATE smart_cards SET current_balance = current_balance + ? WHERE card_id = ?
        ");
        
        $stmt->execute([$amount, $cardId]);
        
        $pdo->commit();
        return ['success' => true];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function createTreatmentRecord($treatmentData) {
    $pdo = connectDB();
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            INSERT INTO treatment_records (patient_id, doctor_id, diagnosis, 
                                          treatment_notes, follow_up_date)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $treatmentData['patient_id'],
            $treatmentData['doctor_id'],
            $treatmentData['diagnosis'],
            $treatmentData['treatment_notes'] ?? null,
            $treatmentData['follow_up_date'] ?? null
        ]);
        
        $treatmentId = $pdo->lastInsertId();
        
        if (isset($treatmentData['services']) && is_array($treatmentData['services'])) {
            $serviceStmt = $pdo->prepare("
                INSERT INTO treatment_services (treatment_id, service_id, quantity, price, notes)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            foreach ($treatmentData['services'] as $service) {
                $serviceStmt->execute([
                    $treatmentId,
                    $service['service_id'],
                    $service['quantity'],
                    $service['price'],
                    $service['notes'] ?? null
                ]);
            }
        }
        
        if (isset($treatmentData['medications']) && is_array($treatmentData['medications'])) {
            $medStmt = $pdo->prepare("
                INSERT INTO medication_records (treatment_id, medication_name, dosage, 
                                               frequency, duration, instructions, 
                                               price, quantity)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($treatmentData['medications'] as $med) {
                $medStmt->execute([
                    $treatmentId,
                    $med['medication_name'],
                    $med['dosage'],
                    $med['frequency'] ?? null,
                    $med['duration'] ?? null,
                    $med['instructions'] ?? null,
                    $med['price'],
                    $med['quantity']
                ]);
            }
        }
        
        $pdo->commit();
        return ['success' => true, 'treatment_id' => $treatmentId];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function getTotalRevenue($startDate, $endDate) {
    $pdo = connectDB();
    
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) AS total_revenue
        FROM transactions
        WHERE transaction_type = 'payment'
        AND status = 'completed'
        AND transaction_date BETWEEN ? AND ?
    ");
    
    $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    return $stmt->fetchColumn();
}

function getPatientDistribution() {
    $pdo = connectDB();
    
    $stmt = $pdo->query("
        SELECT insurance_category, COUNT(*) as count
        FROM patients
        GROUP BY insurance_category
    ");
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getMonthlyRevenue() {
    $pdo = connectDB();
    $result = [];
    
    for ($i = 11; $i >= 0; $i--) {
        $startDate = date('Y-m-01', strtotime("-$i months"));
        $endDate = date('Y-m-t', strtotime("-$i months"));
        
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) AS revenue
            FROM transactions
            WHERE transaction_type = 'payment'
            AND status = 'completed'
            AND transaction_date BETWEEN ? AND ?
        ");
        
        $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        $monthlyRevenue = $stmt->fetchColumn();
        
        $result[] = [
            'month' => date('M', strtotime($startDate)),
            'revenue' => floatval($monthlyRevenue)
        ];
    }
    
    return $result;
}

function getAppointmentStats($startDate, $endDate) {
    $pdo = connectDB();
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) AS scheduled,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled,
            SUM(CASE WHEN status = 'no_show' THEN 1 ELSE 0 END) AS no_show
        FROM appointments
        WHERE appointment_date BETWEEN ? AND ?
    ");
    
    $stmt->execute([$startDate, $endDate]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function logActivity($userId, $action, $ipAddress, $deviceInfo) {
    $pdo = connectDB();
    
    $stmt = $pdo->prepare("
        INSERT INTO access_logs (user_id, action, ip_address, device_info)
        VALUES (?, ?, ?, ?)
    ");
    
    return $stmt->execute([$userId, $action, $ipAddress, $deviceInfo]);
}

function createAuditTrail($userId, $action, $entityType, $entityId, $oldValues, $newValues) {
    $pdo = connectDB();
    
    $stmt = $pdo->prepare("
        INSERT INTO audit_trails (user_id, action, entity_type, entity_id, old_values, new_values)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    return $stmt->execute([
        $userId,
        $action,
        $entityType,
        $entityId,
        json_encode($oldValues),
        json_encode($newValues)
    ]);
}

function generateReport($reportType, $parameters) {
    $pdo = connectDB();
    $result = [];
    
    validateReportParameters($reportType, $parameters);
    
    try {
        switch ($reportType) {
            case 'financial':
                $startDate = $parameters['start_date'];
                $endDate = $parameters['end_date'];
                
                $totalRevenue = getTotalRevenue($startDate, $endDate);
                
                $stmt = $pdo->prepare("
                    SELECT 
                        ms.service_category, 
                        SUM(ts.price * ts.quantity) as revenue,
                        COUNT(DISTINCT tr.treatment_id) as treatment_count
                    FROM treatment_services ts
                    JOIN medical_services ms ON ts.service_id = ms.service_id
                    JOIN treatment_records tr ON ts.treatment_id = tr.treatment_id
                    WHERE DATE(tr.treatment_date) BETWEEN ? AND ?
                    GROUP BY ms.service_category
                    ORDER BY revenue DESC
                ");
                
                $stmt->execute([$startDate, $endDate]);
                $revenueByCategory = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $stmt = $pdo->prepare("
                    SELECT 
                        payment_method, 
                        COUNT(*) as count, 
                        SUM(amount) as total,
                        AVG(amount) as average
                    FROM transactions
                    WHERE transaction_date BETWEEN ? AND ?
                    GROUP BY payment_method
                    ORDER BY total DESC
                ");
                
                $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                $transactionsByMethod = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $stmt = $pdo->prepare("
                    SELECT 
                        DATE_FORMAT(tr.treatment_date, '%Y-%m') as month,
                        SUM(ts.price * ts.quantity) as monthly_revenue
                    FROM treatment_services ts
                    JOIN treatment_records tr ON ts.treatment_id = tr.treatment_id
                    WHERE DATE(tr.treatment_date) BETWEEN ? AND ?
                    GROUP BY DATE_FORMAT(tr.treatment_date, '%Y-%m')
                    ORDER BY month ASC
                ");
                
                $stmt->execute([$startDate, $endDate]);
                $monthlyRevenue = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $result = [
                    'total_revenue' => $totalRevenue,
                    'revenue_by_category' => $revenueByCategory,
                    'transactions_by_method' => $transactionsByMethod,
                    'monthly_revenue' => $monthlyRevenue,
                    'report_period' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate
                    ]
                ];
                break;
                
            case 'patient':
                $stmt = $pdo->query("
                    SELECT 
                        DATE_FORMAT(u.created_at, '%Y-%m') as month, 
                        COUNT(*) as count,
                        COUNT(*) / (
                            SELECT COUNT(*) FROM users 
                            JOIN patients ON users.user_id = patients.user_id
                        ) * 100 as percentage
                    FROM users u
                    JOIN patients p ON u.user_id = p.user_id
                    GROUP BY DATE_FORMAT(u.created_at, '%Y-%m')
                    ORDER BY month
                ");
                
                $registrationTrend = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $patientDistribution = getPatientDistribution();
                
                $stmt = $pdo->query("
                    SELECT 
                        CASE 
                            WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 18 THEN 'Under 18'
                            WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 18 AND 30 THEN '18-30'
                            WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 31 AND 45 THEN '31-45'
                            WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 46 AND 60 THEN '46-60'
                            ELSE 'Over 60'
                        END as age_group,
                        COUNT(*) as count
                    FROM patients
                    GROUP BY age_group
                    ORDER BY FIELD(age_group, 'Under 18', '18-30', '31-45', '46-60', 'Over 60')
                ");
                
                $patientDemographics = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $result = [
                    'registration_trend' => $registrationTrend,
                    'insurance_distribution' => $patientDistribution,
                    'demographics' => $patientDemographics,
                    'total_patients' => getTotalPatientCount()
                ];
                break;
                
           case 'service':
                // Most used services
                $stmt = $pdo->query("
                    SELECT 
                        ms.service_name, 
                        ms.service_category,
                        COUNT(ts.service_id) as usage_count
                    FROM treatment_services ts
                    JOIN medical_services ms ON ts.service_id = ms.service_id
                    GROUP BY ts.service_id
                    ORDER BY usage_count DESC
                    LIMIT 10
                ");
                
                $mostUsedServices = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Revenue by service
                $stmt = $pdo->query("
                    SELECT 
                        ms.service_name, 
                        ms.service_category,
                        SUM(ts.price * ts.quantity) as revenue,
                        AVG(ts.price) as average_price,
                        COUNT(ts.service_id) as usage_count
                    FROM treatment_services ts
                    JOIN medical_services ms ON ts.service_id = ms.service_id
                    GROUP BY ts.service_id
                    ORDER BY revenue DESC
                    LIMIT 10
                ");
                
                $revenueByService = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Service category breakdown
                $stmt = $pdo->query("
                    SELECT 
                        service_category, 
                        COUNT(*) as service_count
                    FROM medical_services
                    GROUP BY service_category
                    ORDER BY service_count DESC
                ");
                
                $serviceCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $result = [
                    'most_used_services' => $mostUsedServices,
                    'revenue_by_service' => $revenueByService,
                    'service_categories' => $serviceCategories,
                    'total_services' => getTotalServiceCount()
                ];
                break;
                
            case 'doctor':
                // Doctor appointment count
                $stmt = $pdo->query("
                    SELECT 
                        CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                        d.specialization,
                        COUNT(a.appointment_id) as appointment_count,
                        COUNT(a.appointment_id) / (
                            SELECT COUNT(*) FROM appointments
                        ) * 100 as percentage
                    FROM appointments a
                    JOIN doctors d ON a.doctor_id = d.doctor_id
                    JOIN users u ON d.user_id = u.user_id
                    GROUP BY a.doctor_id
                    ORDER BY appointment_count DESC
                ");
                
                $doctorAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Doctor revenue
                $stmt = $pdo->query("
                    SELECT 
                        CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                        d.specialization,
                        SUM(ts.price * ts.quantity) as revenue,
                        COUNT(DISTINCT tr.treatment_id) as treatment_count,
                        SUM(ts.price * ts.quantity) / COUNT(DISTINCT tr.treatment_id) as avg_per_treatment
                    FROM treatment_services ts
                    JOIN treatment_records tr ON ts.treatment_id = tr.treatment_id
                    JOIN doctors d ON tr.doctor_id = d.doctor_id
                    JOIN users u ON d.user_id = u.user_id
                    GROUP BY tr.doctor_id
                    ORDER BY revenue DESC
                ");
                
                $doctorRevenue = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Doctor specialization distribution
                $stmt = $pdo->query("
                    SELECT 
                        specialization, 
                        COUNT(*) as doctor_count
                    FROM doctors
                    GROUP BY specialization
                    ORDER BY doctor_count DESC
                ");
                
                $doctorSpecializations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $result = [
                    'doctor_appointments' => $doctorAppointments,
                    'doctor_revenue' => $doctorRevenue,
                    'specializations' => $doctorSpecializations,
                    'total_doctors' => getTotalDoctorCount()
                ];
                break;
                
     default:
                throw new Exception("Invalid report type: $reportType");
        } // Close switch statement
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Report generation error: " . $e->getMessage());
        throw $e;
    }
} // Close function