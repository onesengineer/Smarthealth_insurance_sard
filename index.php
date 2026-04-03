<?php
// Start session
session_start();

// Include database connection and controller
require_once 'controller.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit();
}

// Fetch admin details
$admin = getAdminDetails($_SESSION['user_id']);

// Fetch stats for dashboard
$stats = getDashboardStats();

// Fetch recent patients
$recentPatients = getRecentPatients();

// Fetch recent transactions
$recentTransactions = getRecentTransactions();

// Fetch upcoming appointments
$upcomingAppointments = getUpcomingAppointments();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Insurance System - Admin Dashboard</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
       <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.0/css/bootstrap.min.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #34495e;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            background-color: var(--primary-color);
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            transition: all 0.3s;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 20px 15px;
            background-color: rgba(0, 0, 0, 0.1);
        }
        
        .sidebar-header h3 {
            color: white;
            margin: 0;
            font-size: 1.5rem;
        }
        
        .sidebar-logo {
            max-width: 40px;
            margin-right: 10px;
        }
        
        .sidebar ul.components {
            padding: 20px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar ul li a {
            padding: 12px 20px;
            font-size: 1.1em;
            display: block;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
        }
        
        .sidebar ul li a:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar ul li a i {
            margin-right: 10px;
        }
        
        .sidebar ul li.active > a {
            color: #fff;
            background: var(--secondary-color);
        }
        
        .content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        .navbar {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 10px 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
            margin-bottom: 20px;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card {
            background: linear-gradient(45deg, #3498db, #2c3e50);
            color: white;
        }
        
        .stat-card.card-1 {
            background: linear-gradient(45deg, #3498db, #2980b9);
        }
        
        .stat-card.card-2 {
            background: linear-gradient(45deg, #2ecc71, #27ae60);
        }
        
        .stat-card.card-3 {
            background: linear-gradient(45deg, #f39c12, #e67e22);
        }
        
        .stat-card.card-4 {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
        }
        
        .stat-card .card-body {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .stat-card .stat-icon {
            font-size: 3rem;
            opacity: 0.7;
        }
        
        .stat-card .stat-info h2 {
            font-size: 1.8rem;
            margin-bottom: 5px;
        }
        
        .stat-card .stat-info p {
            margin: 0;
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .table-card .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px 10px 0 0;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        table.table thead {
            background-color: rgba(44, 62, 80, 0.1);
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.1);
        }
        
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .badge-status {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 30px;
        }
        
        .status-scheduled {
            background-color: #3498db;
            color: white;
        }
        
        .status-completed {
            background-color: #2ecc71;
            color: white;
        }
        
        .status-cancelled {
            background-color: #e74c3c;
            color: white;
        }
        
        .status-active {
            background-color: #2ecc71;
            color: white;
        }
        
        .status-inactive {
            background-color: #7f8c8d;
            color: white;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-info img {
            margin-right: 10px;
        }
        
        .btn-view-all {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 5px 15px;
            border-radius: 5px;
            float: right;
            font-size: 0.8rem;
        }
        
        .btn-view-all:hover {
            background-color: var(--dark-color);
            color: white;
        }
        
        .chart-container {
            position: relative;
            height: 350px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 80px;
            }
            
            .sidebar .sidebar-header h3,
            .sidebar ul li a span {
                display: none;
            }
            
            .sidebar ul li a i {
                margin-right: 0;
                font-size: 1.5em;
            }
            
            .content {
                margin-left: 80px;
            }
        }
        /* Modal styles */
.modal {
    display: none;
    position: fixed;
   
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    overflow-y: auto;
}

.modal-content {
    background-color: #f8f9fa;
    margin: 2% auto;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    width: 80%;
    max-width: 1000px;
    animation: modalFade 0.3s ease-in-out;
}

        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        .modal-header {
            background-color: #007bff;
            color: white;
        }
        .search-box {
            margin-bottom: 20px;
        }
        .doctor-card {
            margin-bottom: 15px;
        }
        .action-btns button {
            margin-right: 5px;
        }
  /* Responsive adjustments */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .main-content {
                margin-left: 0;
            }
        }
@keyframes modalFade {
    from {opacity: 0; transform: translateY(-20px);}
    to {opacity: 1; transform: translateY(0);}
}

.close-btn {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    transition: color 0.2s;
}

.close-btn:hover {
    color: #333;
}

/* Tab styles */
.tabs {
    display: flex;
    border-bottom: 1px solid #ddd;
    margin-bottom: 20px;
}

.tab-button {
    background-color: #f1f1f1;
    border: none;
    outline: none;
    cursor: pointer;
    padding: 12px 20px;
    margin-right: 5px;
    border-radius: 5px 5px 0 0;
    transition: all 0.3s;
}

.tab-button:hover {
    background-color: #ddd;
}

.tab-button.active {
    background-color: #4e73df;
    color: white;
}

/* Form styles */
form {
    margin-top: 15px;
}

.form-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
}

.form-group {
    flex: 0 0 48%;
}

.form-group.full-width {
    flex: 0 0 100%;
}

.form-group.search-button {
    display: flex;
    align-items: flex-end;
}

label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
}

input, select, textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background-color: #fff;
    font-size: 14px;
    transition: border-color 0.3s;
}

input:focus, select:focus, textarea:focus {
    border-color: #4e73df;
    outline: none;
    box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.1);
}

textarea {
    min-height: 80px;
    resize: vertical;
}

/* Button styles */
button {
    background-color: #4e73df;
    color: white;
    border: none;
    padding: 10px 15px;
    margin-right: 10px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    transition: background-color 0.3s;
}

button:hover {
    background-color: #2e59d9;
}

button[type="reset"] {
    background-color: #858796;
}

button[type="reset"]:hover {
    background-color: #717384;
}

#deletePatientBtn {
    background-color: #e74a3b;
}

#deletePatientBtn:hover {
    background-color: #c72a1c;
}

.form-actions {
    margin-top: 20px;
    text-align: right;
}

/* Feedback message */
.feedback-message {
    margin: 10px 0;
    padding: 10px;
    border-radius: 4px;
    display: none;
}

.feedback-message.success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
    display: block;
}

.feedback-message.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
    display: block;
}
   .modal-backdrop {
            opacity: 0.5 !important;
        }
        
        .appointment-details {
            border-left: 4px solid #007bff;
            padding-left: 15px;
            margin-bottom: 15px;
        }
        
        .appointment-status {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-scheduled {
            background-color: #ffc107;
            color: #000;
        }
        
        .status-completed {
            background-color: #28a745;
            color: #fff;
        }
        
        .status-cancelled {
            background-color: #dc3545;
            color: #fff;
        }
        
        .status-no_show {
            background-color: #6c757d;
            color: #fff;
        }


    </style>
</head>
<body>

    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-header d-flex align-items-center">
            <img src="logo.avif" alt="Logo" class="sidebar-logo">
            <h3>Health Insurance</h3>
        </div>

        <ul class="list-unstyled components">
            <li class="active">
                <a href="admin.php">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
         <li>
     <a href="#" id="patientsMenuLink">
            <i class="fas fa-users"></i>
            <span>Patients</span>
        </a>
            <li>
               <li class="nav-item">
                <a href="#" id="doctorsMenuLink">
                    <i class="fas fa-user-md"></i>
                    <span>Doctors</span>
                </a>
            </li>
            <li>
                <a href="#" id="appointmentsButton">
                    <i class="fas fa-calendar-check"></i>
                    <span>Appointments</span>
                </a>
            </li>
            <li>
                <a href="#" id="medicalservicesMenuLink">
                    <i class="fas fa-procedures"></i>
                    <span>Medical Services</span>
                </a>
            </li>
          <li>
    <a href="#" id="transactionMenuLink">
        <i class="fas fa-credit-card"></i>
        <span>Transactions</span>
    </a>
</li>
            <li>
                <a href="#" id="smartcardMenuLink">
                    <i class="fas fa-id-card"></i>
                    <span>Smart Cards</span>
                </a>
            </li>
           <li>
    <a href="#" id="reportButton">
        <i class="fas fa-chart-bar"></i>
        <span>Reports</span>
    </a>
</li>

          <li>
    <a href="#" id="settingmodel">
        <i class="fas fa-cog"></i>
        <span>Settings</span>
    </a>
</li>

            <li>
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Page Content -->
    <div class="content">
        <!-- Top Navbar -->
        <nav class="navbar">
            <div class="container-fluid">
                <button type="button" id="sidebarCollapse" class="btn btn-light">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="d-flex align-items-center">
                    <div class="dropdown">
                        <a class="dropdown-toggle text-decoration-none text-dark" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="me-2"><?php echo $admin['first_name'] . ' ' . $admin['last_name']; ?></span>
                            <img src="img.jpg" alt="Admin" class="avatar">
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i> Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Welcome Message -->
         <div class="row">
            <div class="col-md-12">
                <h1 class="mb-4">Dashboard</h1>
            
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="card stat-card card-1">
                    <div class="card-body">
                        <div class="stat-info">
                            <h2><?php echo $stats['total_patients']; ?></h2>
                            <p>Total Patients</p>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card card-2">
                    <div class="card-body">
                        <div class="stat-info">
                            <h2><?php echo $stats['total_doctors']; ?></h2>
                            <p>Doctors</p>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-user-md"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card card-3">
                    <div class="card-body">
                        <div class="stat-info">
                            <h2><?php echo $stats['appointments_today']; ?></h2>
                            <p>Today's Appointments</p>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card card-4">
                    <div class="card-body">
                        <div class="stat-info">
                            <h2>$<?php echo number_format($stats['revenue_today'], 2); ?></h2>
                            <p>Today's Revenue</p>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Monthly Revenue</h5>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                This Year
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#">This Year</a></li>
                                <li><a class="dropdown-item" href="#">Last Year</a></li>
                                <li><a class="dropdown-item" href="#">Last 6 Months</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Patient Distribution</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="patientPieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

       
  <div id="patientModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" id="closeModal">&times;</span>
        
        <!-- Tabs to Switch Between Register and View/Edit -->
        <div class="tabs">
            <button class="tab-button active" id="registerTabBtn">Register Patient</button>
            <button class="tab-button" id="viewEditTabBtn">View/Edit Patient</button>
             <button class="tab-button" id="viewAllTabBtn">View All Patients</button>
        </div>
        
        <!-- Feedback message div -->
        <div id="feedbackMessage" class="feedback-message"></div>
        
        <!-- Register Patient Form (First Section) -->
        <div id="registerTab" class="tab-content">
            <h2>Register Patient</h2>
            <form id="registerPatientForm" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email">
                    </div>
                    <div class="form-group">
                        <label for="phone_number">Phone Number</label>
                        <input type="text" id="phone_number" name="phone_number" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender" required>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" required>
                    </div>
                </div>
                
                <div class="form-group full-width">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="photo">Photo</label>
                        <input type="file" id="photo" name="photo">
                    </div>
                    <div class="form-group">
                        <label for="fingerprint">Fingerprint Data</label>
                        <input type="file" id="fingerprint" name="fingerprint">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="insurance_category">Insurance Category</label>
                        <select id="insurance_category" name="insurance_category">
                            <option value="basic">Basic</option>
                            <option value="standard">Standard</option>
                            <option value="premium">Premium</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="blood_group">Blood Group</label>
                        <input type="text" id="blood_group" name="blood_group">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="emergency_contact_name">Emergency Contact Name</label>
                        <input type="text" id="emergency_contact_name" name="emergency_contact_name">
                    </div>
                    <div class="form-group">
                        <label for="emergency_contact_phone">Emergency Contact Phone</label>
                        <input type="text" id="emergency_contact_phone" name="emergency_contact_phone">
                    </div>
                </div>
                
                <div class="form-group full-width">
                    <label for="allergies">Allergies</label>
                    <textarea id="allergies" name="allergies"></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" id="registerPatientBtn">Register</button>
                    <button type="reset">Clear Form</button>
                </div>
            </form>
        </div>
        
        <!-- View/Edit/Delete Patient Form (Second Section) -->
        <div id="viewEditTab" class="tab-content" style="display: none;">
            <h2>View/Edit Patient</h2>
            <form id="viewEditPatientForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="patient_id">Patient ID</label>
                        <input type="number" id="patient_id" name="patient_id" required>
                    </div>
                    <div class="form-group search-button">
                        <button type="button" id="fetchPatientBtn">Fetch Patient Details</button>
                    </div>
                </div>
                
                <!-- Patient details fields for view and edit -->
                <div id="patientDetails" style="display: none;">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_first_name">First Name</label>
                            <input type="text" id="edit_first_name" name="first_name">
                        </div>
                        <div class="form-group">
                            <label for="edit_last_name">Last Name</label>
                            <input type="text" id="edit_last_name" name="last_name">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_email">Email</label>
                            <input type="email" id="edit_email" name="email">
                        </div>
                        <div class="form-group">
                            <label for="edit_phone_number">Phone Number</label>
                            <input type="text" id="edit_phone_number" name="phone_number">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_gender">Gender</label>
                            <select id="edit_gender" name="gender">
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_date_of_birth">Date of Birth</label>
                            <input type="date" id="edit_date_of_birth" name="date_of_birth">
                        </div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="edit_address">Address</label>
                        <textarea id="edit_address" name="address"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_insurance_category">Insurance Category</label>
                            <select id="edit_insurance_category" name="insurance_category">
                                <option value="basic">Basic</option>
                                <option value="standard">Standard</option>
                                <option value="premium">Premium</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_blood_group">Blood Group</label>
                            <input type="text" id="edit_blood_group" name="blood_group">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_emergency_contact_name">Emergency Contact Name</label>
                            <input type="text" id="edit_emergency_contact_name" name="emergency_contact_name">
                        </div>
                        <div class="form-group">
                            <label for="edit_emergency_contact_phone">Emergency Contact Phone</label>
                            <input type="text" id="edit_emergency_contact_phone" name="emergency_contact_phone">
                        </div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="edit_allergies">Allergies</label>
                        <textarea id="edit_allergies" name="allergies"></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" id="updatePatientBtn">Update</button>
                        <button type="button" id="deletePatientBtn">Delete Patient</button>
                    </div>
                </div>
            </form>
        </div>
           <div id="viewAllTab" class="tab-content" style="display: none;">
            <h2>All Registered Patients</h2>
            <div class="table-container">
                <table id="patientsTable">
                    <thead>
                        <tr>
                            <th>Patient ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Gender</th>
                            <th>DOB</th>
                            <th>Insurance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Patient rows will be populated here via JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


  <!-- Doctor Management Modal -->
<div class="modal fade" id="doctorManagementModal" tabindex="-1" role="dialog" aria-labelledby="doctorManagementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="doctorManagementModalLabel"><i class="fas fa-user-md"></i> Doctor Management</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <!-- Tabbed interface for Doctor Management -->
                <ul class="nav nav-tabs" id="doctorManagementTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="doctors-list-tab" data-toggle="tab" href="#doctorsListTab" role="tab" aria-controls="doctorsListTab" aria-selected="true">
                            <i class="fas fa-list"></i> View Doctors
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="add-doctor-tab" data-toggle="tab" href="#addDoctorTab" role="tab" aria-controls="addDoctorTab" aria-selected="false">
                            <i class="fas fa-user-plus"></i> Register New Doctor
                        </a>
                    </li>
                </ul>
                
                <div class="tab-content p-3" id="doctorManagementTabsContent">
                    <!-- View Doctors Tab -->
                    <div class="tab-pane fade show active" id="doctorsListTab" role="tabpanel" aria-labelledby="doctors-list-tab">
                        <!-- Search Box -->
                        <div class="search-box mb-3">
                            <div class="input-group">
                                <input type="text" id="searchDoctor" class="form-control" placeholder="Search by name, specialization, department...">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" id="searchButton">
                                        <i class="fas fa-search"></i> Search
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Doctors List Table -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Specialization</th>
                                        <th>Department</th>
                                        <th>Contact</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="doctorsList">
                                    <!-- Doctors will be loaded dynamically here -->
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="pagination-container text-center mt-3">
                            <ul class="pagination justify-content-center" id="doctorsPagination">
                                <!-- Pagination links generated dynamically -->
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Register New Doctor Tab -->
                    <div class="tab-pane fade" id="addDoctorTab" role="tabpanel" aria-labelledby="add-doctor-tab">
                        <form id="addDoctorForm">
                            <input type="hidden" id="doctorId" name="doctorId" value="">
                            
                            <!-- Personal Information -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="firstName">First Name</label>
                                        <input type="text" class="form-control" id="firstName" name="firstName" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="lastName">Last Name</label>
                                        <input type="text" class="form-control" id="lastName" name="lastName" required>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Contact Information -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input type="email" class="form-control" id="email" name="email">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phoneNumber">Phone Number</label>
                                        <input type="text" class="form-control" id="phoneNumber" name="phoneNumber" required>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Account Information -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="username">Username</label>
                                        <input type="text" class="form-control" id="username" name="username" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="password">Password</label>
                                        <input type="password" class="form-control" id="password" name="password">
                                        <small class="form-text text-muted">Leave blank if not changing password.</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Professional Information -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="nationalId">National ID</label>
                                        <input type="text" class="form-control" id="nationalId" name="nationalId">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="licenseNumber">License Number</label>
                                        <input type="text" class="form-control" id="licenseNumber" name="licenseNumber" required>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Professional Details -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="specialization">Specialization</label>
                                        <input type="text" class="form-control" id="specialization" name="specialization" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="department">Department</label>
                                        <select class="form-control" id="department" name="department" required>
                                            <!-- Departments will be loaded dynamically -->
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Financial and Status -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="consultationFee">Consultation Fee</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">$</span>
                                            </div>
                                            <input type="number" class="form-control" id="consultationFee" name="consultationFee" step="0.01" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="status">Status</label>
                                        <select class="form-control" id="status" name="status">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                            <option value="suspended">Suspended</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-center mt-4">
                                <button type="reset" class="btn btn-secondary">Reset</button>
                                <button type="button" class="btn btn-success" id="saveDoctorBtn">Save Doctor</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" role="dialog" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteConfirmModalLabel"><i class="fas fa-exclamation-triangle"></i> Delete Confirmation</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this doctor? This action cannot be undone.</p>
                <p><strong>Doctor:</strong> <span id="doctorToDelete"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

   <div class="modal fade" id="appointmentsModal" tabindex="-1" role="dialog" aria-labelledby="appointmentsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="appointmentsModalLabel">
          <i class="fas fa-calendar-check mr-2"></i>Appointments Management
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <!-- Main Content -->
        <div class="container-fluid p-0">
          <div class="row">
            <div class="col-md-12">
              <div class="card border-0">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                  <h5 class="mb-0">Appointments</h5>
                  <div>
                    <button class="btn btn-primary btn-sm" id="newAppointmentBtn">
                      <i class="fas fa-plus mr-1"></i>New Appointment
                    </button>
                  </div>
                </div>
                <div class="card-body">
                  <div class="mb-3">
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                      </div>
                      <input type="text" class="form-control" id="searchAppointment" placeholder="Search by patient name, doctor, date...">
                      <div class="input-group-append">
                        <select class="form-control" id="statusFilter">
                          <option value="">All Statuses</option>
                          <option value="scheduled">Scheduled</option>
                          <option value="completed">Completed</option>
                          <option value="cancelled">Cancelled</option>
                          <option value="no_show">No Show</option>
                        </select>
                      </div>
                      <div class="input-group-append">
                        <input type="date" class="form-control" id="dateFilter">
                      </div>
                    </div>
                  </div>
                  <div class="table-responsive">
                    <table class="table table-striped table-hover" id="appointmentsTable">
                      <thead>
                        <tr>
                          <th>ID</th>
                          <th>Patient</th>
                          <th>Doctor</th>
                          <th>Date</th>
                          <th>Time</th>
                          <th>Purpose</th>
                          <th>Status</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <!-- Table content will be loaded here via AJAX -->
                      </tbody>
                    </table>
                  </div>
                  <div id="pagination" class="d-flex justify-content-center mt-3">
                    <!-- Pagination will be added here -->
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<!-- Add/Edit Appointment Modal -->
<div class="modal fade" id="appointmentFormModal" tabindex="-1" role="dialog" aria-labelledby="appointmentFormModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="appointmentFormModalLabel">
                    <i class="fas fa-calendar-plus mr-2"></i><span id="modalAction">New</span> Appointment
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="appointmentForm">
                    <input type="hidden" id="appointmentId" name="appointment_id" value="">
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="patientSelect">Patient</label>
                            <select class="form-control" id="patientSelect" name="patient_id" required>
                                <option value="">Select Patient</option>
                                <!-- Options will be loaded via AJAX -->
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="doctorSelect">Doctor</label>
                            <select class="form-control" id="doctorSelect" name="doctor_id" required>
                                <option value="">Select Doctor</option>
                                <!-- Options will be loaded via AJAX -->
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="appointmentDate">Appointment Date</label>
                            <input type="date" class="form-control" id="appointmentDate" name="appointment_date" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="appointmentTime">Appointment Time</label>
                            <input type="time" class="form-control" id="appointmentTime" name="appointment_time" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="appointmentPurpose">Purpose</label>
                        <input type="text" class="form-control" id="appointmentPurpose" name="purpose" placeholder="Brief description of appointment purpose">
                    </div>
                    
                    <div class="form-group">
                        <label for="appointmentStatus">Status</label>
                        <select class="form-control" id="appointmentStatus" name="status">
                            <option value="scheduled">Scheduled</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="no_show">No Show</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="appointmentNotes">Notes</label>
                        <textarea class="form-control" id="appointmentNotes" name="notes" rows="3" placeholder="Additional notes or instructions"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveAppointmentBtn">
                    <i class="fas fa-save mr-1"></i>Save Appointment
                </button>
            </div>
        </div>
    </div>
</div>

<!-- View Appointment Modal -->
<div class="modal fade" id="viewAppointmentModal" tabindex="-1" role="dialog" aria-labelledby="viewAppointmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="viewAppointmentModalLabel">
                    <i class="fas fa-calendar-day mr-2"></i>Appointment Details
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="appointmentDetailsContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="editAppointmentBtn">
                    <i class="fas fa-edit mr-1"></i>Edit
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" role="dialog" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteConfirmModalLabel">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Confirm Delete
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this appointment? This action cannot be undone.</p>
                <input type="hidden" id="deleteAppointmentId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash mr-1"></i>Delete
                </button>
            </div>
        </div>
    </div>
</div>
<div id="transactionsModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6); z-index: 1000; backdrop-filter: blur(6px); animation: fadeIn 0.3s ease;">
    <div style="position: relative; background: #ffffff; width: 85%; max-width: 850px; margin: 5% auto; padding: 35px; border-radius: 16px; box-shadow: 0 8px 40px rgba(0, 0, 0, 0.25); animation: slideDown 0.4s ease; border: none; overflow: hidden;">
        <span id="closeModal" style="position: absolute; top: 20px; right: 25px; color: #666; font-size: 30px; font-weight: bold; cursor: pointer; transition: color 0.3s;">
            &times;
        </span>
        <h2 style="margin-top: 0; margin-bottom: 25px; color: #222; font-size: 26px; font-weight: 700; border-bottom: 1px solid #eee; padding-bottom: 12px;">💳 Transactions</h2>
        <div id="transactionsTable" style="width: 100%; margin-top: 20px; max-height: 70vh; overflow-y: auto; scrollbar-width: thin; scrollbar-color: #ccc #f5f5f5;">
            <!-- Data will be loaded here via AJAX -->
        </div>
    </div>
</div>
<div id="reportsModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.6); z-index: 1000; backdrop-filter: blur(4px); animation: fadeIn 0.3s ease;">
    <div style="position: relative; background: #fff; width: 80%; max-width: 900px; margin: 5% auto; padding: 30px; border-radius: 12px; box-shadow: 0 5px 30px rgba(0, 0, 0, 0.2); animation: slideDown 0.4s ease; border: 1px solid #eaeaea;">
        <span id="closeReportModal" style="position: absolute; top: 15px; right: 20px; color: #888; font-size: 28px; font-weight: bold; cursor: pointer; transition: color 0.2s;">&times;</span>
        <h2 style="margin-top: 0; margin-bottom: 20px; color: #333; font-size: 24px; font-weight: 600; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;">Reports</h2>
        <div id="reportsTable" style="width: 100%; margin-top: 15px; max-height: 70vh; overflow-y: auto;">
            <!-- Reports will be loaded here -->
        </div>
    </div>
</div>
<div class="modal fade" id="medicalServicesModal" tabindex="-1" role="dialog" aria-labelledby="medicalServicesModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document" style="max-width: 80%;">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="medicalServicesModalLabel">Medical Services Management</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <!-- Tab navigation -->
        <ul class="nav nav-tabs" id="medicalServicesTabs" role="tablist">
          <li class="nav-item">
            <a class="nav-link active" id="services-tab" data-toggle="tab" href="#services" role="tab" aria-controls="services" aria-selected="true">Services</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" id="treatments-tab" data-toggle="tab" href="#treatments" role="tab" aria-controls="treatments" aria-selected="false">Treatment Records</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" id="medications-tab" data-toggle="tab" href="#medications" role="tab" aria-controls="medications" aria-selected="false">Medications</a>
          </li>
        </ul>
        
        <!-- Tab content -->
        <div class="tab-content mt-3" id="medicalServicesTabContent">
          <!-- Services Tab -->
          <div class="tab-pane fade show active" id="services" role="tabpanel" aria-labelledby="services-tab">
            <div class="d-flex justify-content-between mb-3">
              <h5>Medical Services List</h5>
              <button type="button" class="btn btn-success btn-sm" id="addServiceBtn">
                <i class="fas fa-plus"></i> Add New Service
              </button>
            </div>
            
            <!-- Search and filter -->
            <div class="form-row mb-3">
              <div class="col-md-6">
                <input type="text" class="form-control" id="serviceSearchInput" placeholder="Search services...">
              </div>
              <div class="col-md-4">
                <select class="form-control" id="serviceCategoryFilter">
                  <option value="">All Categories</option>
                  <option value="consultation">Consultation</option>
                  <option value="laboratory">Laboratory</option>
                  <option value="radiology">Radiology</option>
                  <option value="medication">Medication</option>
                  <option value="surgery">Surgery</option>
                  <option value="other">Other</option>
                </select>
              </div>
              <div class="col-md-2">
                <button type="button" class="btn btn-primary btn-block" id="serviceFilterBtn">Filter</button>
              </div>
            </div>
            
            <!-- Services table -->
            <div class="table-responsive">
              <table class="table table-striped table-hover" id="servicesTable">
                <thead class="thead-dark">
                  <tr>
                    <th>ID</th>
                    <th>Service Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="servicesTableBody">
                  <!-- Data will be populated by AJAX -->
                </tbody>
              </table>
            </div>
            
            <!-- Pagination -->
            <nav aria-label="Services pagination">
              <ul class="pagination justify-content-center" id="servicesPagination">
                <!-- Pagination links by JS -->
              </ul>
            </nav>
          </div>
          
          <!-- Treatment Records Tab -->
          <div class="tab-pane fade" id="treatments" role="tabpanel" aria-labelledby="treatments-tab">
            <div class="d-flex justify-content-between mb-3">
              <h5>Treatment Records</h5>
              <button type="button" class="btn btn-success btn-sm" id="addTreatmentBtn">
                <i class="fas fa-plus"></i> New Treatment Record
              </button>
            </div>
            
            <!-- Search and filter for treatments -->
            <div class="form-row mb-3">
              <div class="col-md-4">
                <input type="text" class="form-control" id="patientSearchInput" placeholder="Search by patient name/ID...">
              </div>
              <div class="col-md-3">
                <input type="date" class="form-control" id="treatmentDateFilter">
              </div>
              <div class="col-md-3">
                <select class="form-control" id="treatmentStatusFilter">
                  <option value="">All Statuses</option>
                  <option value="open">Open</option>
                  <option value="closed">Closed</option>
                  <option value="follow_up">Follow Up</option>
                </select>
              </div>
              <div class="col-md-2">
                <button type="button" class="btn btn-primary btn-block" id="treatmentFilterBtn">Filter</button>
              </div>
            </div>
            
            <!-- Treatments table -->
            <div class="table-responsive">
              <table class="table table-striped table-hover" id="treatmentsTable">
                <thead class="thead-dark">
                  <tr>
                    <th>ID</th>
                    <th>Patient</th>
                    <th>Doctor</th>
                    <th>Diagnosis</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="treatmentsTableBody">
                  <!-- Data will be populated by AJAX -->
                </tbody>
              </table>
            </div>
            
            <!-- Pagination -->
            <nav aria-label="Treatments pagination">
              <ul class="pagination justify-content-center" id="treatmentsPagination">
                <!-- Pagination links by JS -->
              </ul>
            </nav>
          </div>
          
          <!-- Medications Tab -->
          <div class="tab-pane fade" id="medications" role="tabpanel" aria-labelledby="medications-tab">
            <div class="d-flex justify-content-between mb-3">
              <h5>Medication Records</h5>
              <button type="button" class="btn btn-success btn-sm" id="addMedicationBtn">
                <i class="fas fa-plus"></i> Add Medication
              </button>
            </div>
            
            <!-- Search and filter for medications -->
            <div class="form-row mb-3">
              <div class="col-md-6">
                <input type="text" class="form-control" id="medicationSearchInput" placeholder="Search medications...">
              </div>
              <div class="col-md-4">
                <select class="form-control" id="dispensedFilter">
                  <option value="">All Status</option>
                  <option value="1">Dispensed</option>
                  <option value="0">Not Dispensed</option>
                </select>
              </div>
              <div class="col-md-2">
                <button type="button" class="btn btn-primary btn-block" id="medicationFilterBtn">Filter</button>
              </div>
            </div>
            
            <!-- Medications table -->
            <div class="table-responsive">
              <table class="table table-striped table-hover" id="medicationsTable">
                <thead class="thead-dark">
                  <tr>
                    <th>ID</th>
                    <th>Treatment ID</th>
                    <th>Medication</th>
                    <th>Dosage</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Dispensed</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="medicationsTableBody">
                  <!-- Data will be populated by AJAX -->
                </tbody>
              </table>
            </div>
            
            <!-- Pagination -->
            <nav aria-label="Medications pagination">
              <ul class="pagination justify-content-center" id="medicationsPagination">
                <!-- Pagination links by JS -->
              </ul>
            </nav>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Service Form Modal -->
<div class="modal fade" id="serviceFormModal" tabindex="-1" role="dialog" aria-labelledby="serviceFormModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title" id="serviceFormModalLabel">Add/Edit Medical Service</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="serviceForm">
        <div class="modal-body">
          <input type="hidden" id="serviceId" name="service_id">
          <div class="form-group">
            <label for="serviceName">Service Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="serviceName" name="service_name" required>
          </div>
          <div class="form-group">
            <label for="serviceCategory">Category <span class="text-danger">*</span></label>
            <select class="form-control" id="serviceCategory" name="service_category" required>
              <option value="">Select Category</option>
              <option value="consultation">Consultation</option>
              <option value="laboratory">Laboratory</option>
              <option value="radiology">Radiology</option>
              <option value="medication">Medication</option>
              <option value="surgery">Surgery</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="form-group">
            <label for="basePrice">Base Price <span class="text-danger">*</span></label>
            <input type="number" step="0.01" class="form-control" id="basePrice" name="base_price" required>
          </div>
          <div class="form-group">
            <label for="serviceDescription">Description</label>
            <textarea class="form-control" id="serviceDescription" name="description" rows="3"></textarea>
          </div>
          <div class="form-group">
            <label for="serviceStatus">Status</label>
            <select class="form-control" id="serviceStatus" name="status">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Treatment Record Form Modal -->
<div class="modal fade" id="treatmentFormModal" tabindex="-1" role="dialog" aria-labelledby="treatmentFormModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title" id="treatmentFormModalLabel">Add/Edit Treatment Record</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="treatmentForm">
        <div class="modal-body">
          <input type="hidden" id="treatmentId" name="treatment_id">
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="patientSelect">Patient <span class="text-danger">*</span></label>
              <select class="form-control" id="patientSelect" name="patient_id" required>
                <option value="">Select Patient</option>
                <!-- Options will be populated via AJAX -->
              </select>
            </div>
            <div class="form-group col-md-6">
              <label for="doctorSelect">Doctor <span class="text-danger">*</span></label>
              <select class="form-control" id="doctorSelect" name="doctor_id" required>
                <option value="">Select Doctor</option>
                <!-- Options will be populated via AJAX -->
              </select>
            </div>
          </div>
          <div class="form-group">
            <label for="diagnosisInput">Diagnosis <span class="text-danger">*</span></label>
            <textarea class="form-control" id="diagnosisInput" name="diagnosis" rows="2" required></textarea>
          </div>
          <div class="form-group">
            <label for="treatmentNotesInput">Treatment Notes</label>
            <textarea class="form-control" id="treatmentNotesInput" name="treatment_notes" rows="3"></textarea>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="followUpDateInput">Follow-up Date</label>
              <input type="date" class="form-control" id="followUpDateInput" name="follow_up_date">
            </div>
            <div class="form-group col-md-6">
              <label for="treatmentStatusSelect">Status</label>
              <select class="form-control" id="treatmentStatusSelect" name="status">
                <option value="open">Open</option>
                <option value="closed">Closed</option>
                <option value="follow_up">Follow Up</option>
              </select>
            </div>
          </div>
          
          <hr>
          <h6>Associated Services</h6>
          <div class="table-responsive">
            <table class="table table-sm" id="treatmentServicesTable">
              <thead class="thead-light">
                <tr>
                  <th>Service</th>
                  <th>Quantity</th>
                  <th>Price</th>
                  <th>Notes</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody id="treatmentServicesTableBody">
                <tr id="serviceRow0" class="service-row">
                  <td>
                    <select class="form-control service-select" name="service_id[]" required>
                      <option value="">Select Service</option>
                      <!-- Options will be populated via AJAX -->
                    </select>
                  </td>
                  <td>
                    <input type="number" class="form-control service-quantity" name="quantity[]" value="1" min="1">
                  </td>
                  <td>
                    <input type="number" step="0.01" class="form-control service-price" name="price[]" required>
                  </td>
                  <td>
                    <input type="text" class="form-control service-notes" name="notes[]">
                  </td>
                  <td>
                    <button type="button" class="btn btn-danger btn-sm remove-service">
                      <i class="fas fa-times"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
              <tfoot>
                <tr>
                  <td colspan="5">
                    <button type="button" class="btn btn-info btn-sm" id="addServiceRow">
                      <i class="fas fa-plus"></i> Add Service
                    </button>
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
          
          <hr>
          <h6>Medications</h6>
          <div class="table-responsive">
            <table class="table table-sm" id="medicationsTableForm">
              <thead class="thead-light">
                <tr>
                  <th>Medication</th>
                  <th>Dosage</th>
                  <th>Frequency</th>
                  <th>Duration</th>
                  <th>Price</th>
                  <th>Qty</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody id="medicationsTableBodyForm">
                <tr id="medicationRow0" class="medication-row">
                  <td>
                    <input type="text" class="form-control medication-name" name="medication_name[]" required>
                  </td>
                  <td>
                    <input type="text" class="form-control medication-dosage" name="dosage[]" required>
                  </td>
                  <td>
                    <input type="text" class="form-control medication-frequency" name="frequency[]" placeholder="e.g. 3x daily">
                  </td>
                  <td>
                    <input type="text" class="form-control medication-duration" name="duration[]" placeholder="e.g. 7 days">
                  </td>
                  <td>
                    <input type="number" step="0.01" class="form-control medication-price" name="medication_price[]" required>
                  </td>
                  <td>
                    <input type="number" class="form-control medication-quantity" name="medication_quantity[]" value="1" min="1">
                  </td>
                  <td>
                    <button type="button" class="btn btn-danger btn-sm remove-medication">
                      <i class="fas fa-times"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
              <tfoot>
                <tr>
                  <td colspan="7">
                    <button type="button" class="btn btn-info btn-sm" id="addMedicationRow">
                      <i class="fas fa-plus"></i> Add Medication
                    </button>
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Treatment Record</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Medication Form Modal -->
<div class="modal fade" id="medicationFormModal" tabindex="-1" role="dialog" aria-labelledby="medicationFormModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title" id="medicationFormModalLabel">Add/Edit Medication</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="medicationForm">
        <div class="modal-body">
          <input type="hidden" id="medicationId" name="medication_id">
          <div class="form-group">
            <label for="treatmentIdSelect">Treatment <span class="text-danger">*</span></label>
            <select class="form-control" id="treatmentIdSelect" name="treatment_id" required>
              <option value="">Select Treatment</option>
              <!-- Options will be populated via AJAX -->
            </select>
          </div>
          <div class="form-group">
            <label for="medicationName">Medication Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="medicationName" name="medication_name" required>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="medicationDosage">Dosage <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="medicationDosage" name="dosage" required>
            </div>
            <div class="form-group col-md-6">
              <label for="medicationFrequency">Frequency</label>
              <input type="text" class="form-control" id="medicationFrequency" name="frequency" placeholder="e.g. 3x daily">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="medicationDuration">Duration</label>
              <input type="text" class="form-control" id="medicationDuration" name="duration" placeholder="e.g. 7 days">
            </div>
            <div class="form-group col-md-6">
              <label for="medicationQuantity">Quantity <span class="text-danger">*</span></label>
              <input type="number" class="form-control" id="medicationQuantity" name="quantity" value="1" min="1" required>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="medicationPrice">Price <span class="text-danger">*</span></label>
              <input type="number" step="0.01" class="form-control" id="medicationPrice" name="price" required>
            </div>
            <div class="form-group col-md-6">
              <label for="medicationDispensed">Dispensed</label>
              <select class="form-control" id="medicationDispensed" name="dispensed">
                <option value="0">No</option>
                <option value="1">Yes</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label for="medicationInstructions">Instructions</label>
            <textarea class="form-control" id="medicationInstructions" name="instructions" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- HTML for the Smart Card Modal -->
<div class="modal fade" id="smartCardModal" tabindex="-1" role="dialog" aria-labelledby="smartCardModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="smartCardModalLabel">Smart Card Management</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <!-- Tabs for different smart card operations -->
        <ul class="nav nav-tabs" id="smartCardTabs" role="tablist">
          <li class="nav-item">
            <a class="nav-link active" id="assign-tab" data-toggle="tab" href="#assign" role="tab" aria-controls="assign" aria-selected="true">Assign New Card</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" id="view-tab" data-toggle="tab" href="#view" role="tab" aria-controls="view" aria-selected="false">View Assigned Cards</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" id="recharge-tab" data-toggle="tab" href="#recharge" role="tab" aria-controls="recharge" aria-selected="false">Recharge Card</a>
          </li>
        </ul>
        
        <!-- Tab content -->
        <div class="tab-content mt-3" id="smartCardTabContent">
          <!-- Assign New Card Tab -->
          <div class="tab-pane fade show active" id="assign" role="tabpanel" aria-labelledby="assign-tab">
            <form id="assignCardForm">
              <div class="form-group">
                <label for="patientSelectt">Select Patient</label>
                <select class="form-control" id="patientSelectt" required>
                  <option value="">-- Select Patient --</option>
                  <!-- Will be populated via AJAX -->
                </select>
              </div>
              <div class="form-group">
                <label for="rfidNumber">RFID Number</label>
                <input type="text" class="form-control" id="rfidNumber" placeholder="Enter RFID Number" required>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="issueDate">Issue Date</label>
                    <input type="date" class="form-control" id="issueDate" value="" required>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="expiryDate">Expiry Date</label>
                    <input type="date" class="form-control" id="expiryDate" required>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="initialBalance">Initial Balance</label>
                    <input type="number" step="0.01" class="form-control" id="initialBalance" value="0.00">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="pinCode">PIN Code</label>
                    <input type="password" class="form-control" id="pinCode" placeholder="Enter PIN Code">
                  </div>
                </div>
              </div>
              <button type="submit" class="btn btn-primary">Assign Card</button>
            </form>
          </div>
          
          <!-- View Assigned Cards Tab -->
          <div class="tab-pane fade" id="view" role="tabpanel" aria-labelledby="view-tab">
            <div class="form-group">
              <label for="searchPatient">Search Patient</label>
              <input type="text" class="form-control" id="searchPatient" placeholder="Search by name or ID">
            </div>
            <div class="table-responsive">
              <table class="table table-bordered table-hover" id="smartCardTable">
                <thead>
                  <tr>
                    <th>Card ID</th>
                    <th>RFID Number</th>
                    <th>Patient Name</th>
                    <th>Issue Date</th>
                    <th>Expiry Date</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <!-- Will be populated via AJAX -->
                </tbody>
              </table>
            </div>
          </div>
          
          <!-- Recharge Card Tab -->
          <div class="tab-pane fade" id="recharge" role="tabpanel" aria-labelledby="recharge-tab">
            <form id="rechargeCardForm">
              <div class="form-group">
                <label for="rechargeRfid">Card RFID Number</label>
                <input type="text" class="form-control" id="rechargeRfid" placeholder="Enter RFID Number" required>
                <small class="form-text text-muted">Scan the card or enter the RFID number manually</small>
              </div>
              <div class="card mb-3">
                <div class="card-body" id="cardDetails">
                  <p class="card-text text-center text-muted">Card details will appear here</p>
                </div>
              </div>
              <div class="form-group">
                <label for="rechargeAmount">Recharge Amount</label>
                <input type="number" step="0.01" class="form-control" id="rechargeAmount" placeholder="Enter amount" required>
              </div>
              <div class="form-group">
                <label for="paymentMethod">Payment Method</label>
                <select class="form-control" id="paymentMethod" required>
                  <option value="cash">Cash</option>
                  <option value="mobile_money">Mobile Money</option>
                  <option value="bank_transfer">Bank Transfer</option>
                </select>
              </div>
              <div class="form-group">
                <label for="referenceNumber">Reference Number</label>
                <input type="text" class="form-control" id="referenceNumber" placeholder="Enter reference number (for mobile money/bank transfer)">
              </div>
              <button type="submit" class="btn btn-success">Recharge Card</button>
            </form>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<style>
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideDown {
    from { transform: translateY(-60px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

#closeModal:hover {
    color: #000;
}

#transactionsModal::-webkit-scrollbar {
    width: 8px;
}

#transactionsModal::-webkit-scrollbar-thumb {
    background-color: #ccc;
    border-radius: 4px;
}

#transactionsModal::-webkit-scrollbar-track {
    background: #f5f5f5;
}
<!-- Popup Modal (Hidden by default) -->
<div id="moneyModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.4); pointer-events: auto;">
    <div class="modal-content" style="background-color: #fff; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 300px; z-index: 1001; position: relative;">
        <span class="close" style="color: #aaa; float: right; font-size: 28px; font-weight: bold;">&times;</span>
        <h2>Update Money</h2>
        <form id="updateMoneyForm">
            <label for="currentAmount">Current Amount:</label>
            <input type="text" id="currentAmount" disabled style="pointer-events: none;"><br>

            <label for="newAmount">New Amount:</label>
            <input type="number" id="newAmount" required style="pointer-events: auto;"><br><br>

            <button type="submit" style="pointer-events: auto;">Update</button>
        </form>
    </div>
</div>

</style>
        <!-- Footer -->
        <footer class="mt-5">
            <div class="text-center">
                <p>&copy; <?php echo date('Y'); ?> Health Insurance System. All rights reserved.</p>
            </div>
        </footer>
    </div>

   


    <!-- Bootstrap JS Bundle with Popper -->
  <!-- Bootstrap 5 Bundle (with Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- jQuery (required for Bootstrap 4 and older versions, not needed for Bootstrap 5) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

<!-- Popper.js (needed for Bootstrap 4 and below, Bootstrap 5 includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>

<!-- Bootstrap 4.6 (optional, only if you are using Bootstrap 4) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Chart.js (if needed for older versions) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>

<!-- Bootstrap JS -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>


    <!-- Custom JavaScript -->
    <script>
        // Toggle sidebar
        document.getElementById('sidebarCollapse').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.content').classList.toggle('active');
        });
        
        // Set appointment ID for delete modal
        const deleteModal = document.getElementById('deleteModal');
        if (deleteModal) {
            deleteModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const appointmentId = button.getAttribute('data-id');
                document.getElementById('appointmentIdToDelete').value = appointmentId;
            });
        }
        
        // Revenue Chart
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Revenue',
                    data: [12500, 15000, 17800, 16300, 21500, 28700, 32100, 35400, 33200, 36500, 39000, 42000],
                    backgroundColor: 'rgba(52, 152, 219, 0.2)',
                    borderColor: '#3498db',
                    borderWidth: 3,
                    tension: 0.4,
                    pointBackgroundColor: '#3498db',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '$' + context.raw.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
        
        // Patient Pie Chart
        const pieCtx = document.getElementById('patientPieChart').getContext('2d');
        const patientPieChart = new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: ['Basic', 'Standard', 'Premium'],
                datasets: [{
                    data: [30, 50, 20],
                    backgroundColor: ['#3498db', '#2ecc71', '#e74c3c'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                cutout: '70%'
            }
        });
    </script>

<script >
    // Tab switching functionality
document.getElementById("registerTabBtn").onclick = function () {
    document.getElementById("registerTab").style.display = "block";
    document.getElementById("viewEditTab").style.display = "none";
    document.getElementById("registerTabBtn").classList.add("active");
    document.getElementById("viewEditTabBtn").classList.remove("active");
};

document.getElementById("viewEditTabBtn").onclick = function () {
    document.getElementById("registerTab").style.display = "none";
    document.getElementById("viewEditTab").style.display = "block";
    document.getElementById("viewEditTabBtn").classList.add("active");
    document.getElementById("registerTabBtn").classList.remove("active");
};

// Function to fetch patient details for editing
function fetchPatientDetails() {
    var patientId = document.getElementById("patient_id").value;
    if (!patientId) {
        alert("Please enter Patient ID.");
        return;
    }

    // AJAX call to get patient details by ID
    var xhr = new XMLHttpRequest();
    xhr.open("GET", "get_patient_details.php?patient_id=" + patientId, true);
    xhr.onload = function () {
        if (xhr.status == 200) {
            var patient = JSON.parse(xhr.responseText);
            if (patient) {
                document.getElementById("edit_first_name").value = patient.first_name;
                document.getElementById("edit_last_name").value = patient.last_name;
                document.getElementById("edit_email").value = patient.email;
                document.getElementById("edit_phone_number").value = patient.phone_number;
                document.getElementById("edit_gender").value = patient.gender;
                document.getElementById("edit_date_of_birth").value = patient.date_of_birth;
                document.getElementById("edit_address").value = patient.address;
                document.getElementById("edit_insurance_category").value = patient.insurance_category;
                document.getElementById("edit_emergency_contact_name").value = patient.emergency_contact_name;
                document.getElementById("edit_emergency_contact_phone").value = patient.emergency_contact_phone;
                document.getElementById("edit_blood_group").value = patient.blood_group;
                document.getElementById("edit_allergies").value = patient.allergies;
                document.getElementById("patientDetails").style.display = "block";
            } else {
                alert("Patient not found.");
            }
        }
    };
    xhr.send();
}

// Function to delete a patient
function deletePatient() {
    var patientId = document.getElementById("patient_id").value;
    if (!patientId) {
        alert("Please enter Patient ID.");
        return;
    }

    // AJAX call to delete patient
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "delete_patient.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onload = function () {
        if (xhr.status == 200) {
            alert("Patient deleted successfully.");
            // Reset the form or hide the details
            document.getElementById("viewEditPatientForm").reset();
            document.getElementById("patientDetails").style.display = "none";
        }
    };
    xhr.send("patient_id=" + patientId);
}


</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Patient Menu Link Event Listener
    document.getElementById('patientsMenuLink').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('patientModal').style.display = 'block';
        // Ensure register tab is active by default
        activateTab('registerTabBtn', 'registerTab');
    });
    
    // Close Modal
    document.getElementById('closeModal').addEventListener('click', function() {
        document.getElementById('patientModal').style.display = 'none';
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target == document.getElementById('patientModal')) {
            document.getElementById('patientModal').style.display = 'none';
        }
    });
    
    // Tab switching functionality
    document.getElementById('registerTabBtn').addEventListener('click', function() {
        activateTab('registerTabBtn', 'registerTab');
    });
    
    document.getElementById('viewEditTabBtn').addEventListener('click', function() {
        activateTab('viewEditTabBtn', 'viewEditTab');
    });
    
    // Register Patient Button Click
    document.getElementById('registerPatientBtn').addEventListener('click', function() {
        registerPatient();
    });
    
    // Fetch Patient Button Click
    document.getElementById('fetchPatientBtn').addEventListener('click', function() {
        fetchPatientDetails();
    });
    
    // Update Patient Button Click
    document.getElementById('updatePatientBtn').addEventListener('click', function() {
        updatePatient();
    });
    
    // Delete Patient Button Click
    document.getElementById('deletePatientBtn').addEventListener('click', function() {
        deletePatient();
    });
    
    // Function to activate a tab
    function activateTab(tabBtnId, tabContentId) {
        // Reset all tabs
        var tabButtons = document.getElementsByClassName('tab-button');
        for (var i = 0; i < tabButtons.length; i++) {
            tabButtons[i].classList.remove('active');
        }
        
        var tabContents = document.getElementsByClassName('tab-content');
        for (var i = 0; i < tabContents.length; i++) {
            tabContents[i].style.display = 'none';
        }
        
        // Activate selected tab
        document.getElementById(tabBtnId).classList.add('active');
        document.getElementById(tabContentId).style.display = 'block';
        
        // Clear feedback message
        clearFeedback();
    }
    
    // Register Patient Function
    function registerPatient() {
        // Get form data
        var form = document.getElementById('registerPatientForm');
        var formData = new FormData(form);
        formData.append('register_patient', 'true');
        
        // AJAX request to register patient
        fetch('patient_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            if (data.includes("successfully")) {
                showFeedback(data, 'success');
                form.reset();
            } else {
                showFeedback(data, 'error');
            }
        })
        .catch(error => {
            showFeedback('An error occurred: ' + error, 'error');
        });
    }
    
    // Fetch Patient Details Function
    function fetchPatientDetails() {
        var patientId = document.getElementById('patient_id').value;
        
        if (!patientId) {
            showFeedback('Please enter a Patient ID', 'error');
            return;
        }
        
        // AJAX request to fetch patient details
        fetch('patient_fetch.php?id=' + patientId)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                showFeedback(data.error, 'error');
                document.getElementById('patientDetails').style.display = 'none';
            } else {
                // Populate fields with patient data
                document.getElementById('edit_first_name').value = data.first_name;
                document.getElementById('edit_last_name').value = data.last_name;
                document.getElementById('edit_email').value = data.email;
                document.getElementById('edit_phone_number').value = data.phone_number;
                document.getElementById('edit_gender').value = data.gender;
                document.getElementById('edit_date_of_birth').value = data.date_of_birth;
                document.getElementById('edit_address').value = data.address;
                document.getElementById('edit_insurance_category').value = data.insurance_category;
                document.getElementById('edit_emergency_contact_name').value = data.emergency_contact_name;
                document.getElementById('edit_emergency_contact_phone').value = data.emergency_contact_phone;
                document.getElementById('edit_blood_group').value = data.blood_group;
                document.getElementById('edit_allergies').value = data.allergies;
                
                // Show patient details section
                document.getElementById('patientDetails').style.display = 'block';
                clearFeedback();
            }
        })
        .catch(error => {
            showFeedback('An error occurred: ' + error, 'error');
        });
    }
    
    // Update Patient Function
   function updatePatient() {
     var patientId = document.getElementById('patient_id').value;
     console.log("Patient ID: ", patientId); // Debugging line
     var form = document.getElementById('viewEditPatientForm');
     var formData = new FormData(form);
     formData.append('edit_patient', 'true');
     formData.append('patient_id', patientId);
     
     // AJAX request to update patient
     fetch('patient_edit_handler.php', {
         method: 'POST',
         body: formData
     })
     .then(response => response.text())
     .then(data => {
         if (data.includes("successfully")) {
             showFeedback(data, 'success');
         } else {
             showFeedback(data, 'error');
         }
     })
     .catch(error => {
         showFeedback('An error occurred: ' + error, 'error');
     });
 }

    // Delete Patient Function
    function deletePatient() {
        var patientId = document.getElementById('patient_id').value;
        
        if (confirm('Are you sure you want to delete this patient? This action cannot be undone.')) {
            // AJAX request to delete patient
            fetch('patient_delete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'patient_id=' + patientId + '&delete_patient=true'
            })
            .then(response => response.text())
            .then(data => {
                if (data.includes("successfully")) {
                    showFeedback(data, 'success');
                    document.getElementById('patientDetails').style.display = 'none';
                    document.getElementById('patient_id').value = '';
                } else {
                    showFeedback(data, 'error');
                }
            })
            .catch(error => {
                showFeedback('An error occurred: ' + error, 'error');
            });
        }
    }
    
    // Show feedback message
    function showFeedback(message, type) {
        var feedbackDiv = document.getElementById('feedbackMessage');
        feedbackDiv.textContent = message;
        feedbackDiv.className = 'feedback-message ' + type;
        
        // Auto-hide feedback after 5 seconds
        setTimeout(function() {
            clearFeedback();
        }, 5000);
    }
    
    // Clear feedback message
    function clearFeedback() {
        var feedbackDiv = document.getElementById('feedbackMessage');
        feedbackDiv.textContent = '';
        feedbackDiv.className = 'feedback-message';
    }
});
document.getElementById('registerTabBtn').addEventListener('click', () => {
    showTab('registerTab');
});
document.getElementById('viewEditTabBtn').addEventListener('click', () => {
    showTab('viewEditTab');
});
document.getElementById('viewAllTabBtn').addEventListener('click', () => {
    showTab('viewAllTab');
    fetchAllPatients(); // Load data when switching to View All tab
});

function showTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(tab => tab.style.display = 'none');
    document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
    document.getElementById(tabId).style.display = 'block';
    document.getElementById(tabId + 'Btn').classList.add('active');
}

// Example fetchAllPatients function (you'll need to hook this up to your backend)
function fetchAllPatients() {
    fetch('fetch_all_patients.php')
        .then(response => response.json())
        .then(data => {
            const tableBody = document.querySelector('#patientsTable tbody');
            tableBody.innerHTML = '';
            data.forEach(patient => {
                const row = `<tr>
                    <td>${patient.patient_id}</td>
                    <td>${patient.username}</td>
                    <td>${patient.first_name} ${patient.last_name}</td>
                    <td>${patient.email}</td>
                    <td>${patient.phone_number}</td>
                    <td>${patient.gender}</td>
                    <td>${patient.date_of_birth}</td>
                    <td>${patient.insurance_category}</td>
                    <td><button onclick="openEdit(${patient.patient_id})">Edit</button></td>
                </tr>`;
                tableBody.insertAdjacentHTML('beforeend', row);
            });
        });
}

function openEdit(patientId) {
    showTab('viewEditTab');
    document.getElementById('patient_id').value = patientId;
    document.getElementById('fetchPatientBtn').click();
}

</script>
   <script>
   $(document).ready(function() {
    // When Doctors menu link is clicked
    $("#doctorsMenuLink").click(function(e) {
        e.preventDefault();
        
        // Show the doctor management modal
        $("#doctorManagementModal").modal('show');
        
        // Load the doctors list
        loadDoctors();
        
        // Load departments for the dropdown
        loadDepartments();
    });

    // Handle search button click
    $('#searchButton').click(function() {
        loadDoctors($('#searchDoctor').val());
    });

    // Handle search on enter key
    $('#searchDoctor').keypress(function(e) {
        if (e.which === 13) {
            loadDoctors($('#searchDoctor').val());
        }
    });

    // Handle Save Doctor button click
    $('#saveDoctorBtn').click(function() {
        saveDoctor();
    });

    // Handle Confirm Delete button click
    $('#confirmDeleteBtn').click(function() {
        const doctorId = $(this).data('doctorId');
        deleteDoctor(doctorId);
    });

    // Handle tab changes
    $('#add-doctor-tab').on('click', function() {
        resetDoctorForm();
    });
    
    // Reset form when modal is closed
    $('#doctorManagementModal').on('hidden.bs.modal', function() {
        // Switch back to the doctors list tab for next time the modal opens
        $('#doctors-list-tab').tab('show');
    });
});

// Function to load doctors list
function loadDoctors(searchTerm = '') {
    // Show loading indicator
    $('#doctorsList').html('<tr><td colspan="6" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading doctors...</td></tr>');
    
    $.ajax({
        url: 'doctor_management_api.php',
        type: 'GET',
        data: { action: 'get_doctors', search: searchTerm },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                displayDoctors(response.data);
            } else {
                showAlert('Error loading doctors: ' + response.message, 'danger');
                $('#doctorsList').html('<tr><td colspan="6" class="text-center">Error loading doctors</td></tr>');
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error: " + status + " - " + error);
            showAlert('Server error while loading doctors', 'danger');
            $('#doctorsList').html('<tr><td colspan="6" class="text-center">Server error</td></tr>');
        }
    });
}

// Function to load departments
function loadDepartments() {
    $.ajax({
        url: 'doctor_management_api.php',
        type: 'GET',
        data: { action: 'get_departments' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#department').empty();
                
                // Add a default empty option
                $('#department').append(
                    $('<option></option>').val('').text('-- Select Department --')
                );
                
                $.each(response.data, function(index, department) {
                    $('#department').append(
                        $('<option></option>').val(department.department_name).text(department.department_name)
                    );
                });
            } else {
                showAlert('Error loading departments: ' + response.message, 'danger');
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error: " + status + " - " + error);
            showAlert('Server error while loading departments', 'danger');
        }
    });
}

// Function to display doctors in the table
function displayDoctors(doctors) {
    const doctorsList = $('#doctorsList');
    doctorsList.empty();

    if (doctors.length === 0) {
        doctorsList.html('<tr><td colspan="6" class="text-center">No doctors found</td></tr>');
        return;
    }

    $.each(doctors, function(index, doctor) {
        const doctorRow = $(`
            <tr>
                <td>Dr. ${doctor.first_name} ${doctor.last_name}</td>
                <td>${doctor.specialization || 'N/A'}</td>
                <td>${doctor.department || 'N/A'}</td>
                <td>
                    ${doctor.phone_number || 'N/A'}<br>
                    <small>${doctor.email || 'N/A'}</small>
                </td>
                <td>
                    <span class="badge badge-${doctor.status === 'active' ? 'success' : (doctor.status === 'inactive' ? 'warning' : 'danger')}">
                        ${doctor.status || 'unknown'}
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-info edit-doctor mr-1" data-id="${doctor.doctor_id}" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-doctor" data-id="${doctor.doctor_id}" 
                            data-name="Dr. ${doctor.first_name} ${doctor.last_name}" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `);

        doctorsList.append(doctorRow);
    });

    // Attach click event to edit buttons
    $('.edit-doctor').click(function() {
        const doctorId = $(this).data('id');
        editDoctor(doctorId);
    });

    // Attach click event to delete buttons
    $('.delete-doctor').click(function() {
        const doctorId = $(this).data('id');
        const doctorName = $(this).data('name');
        
        $('#doctorToDelete').text(doctorName);
        $('#confirmDeleteBtn').data('doctorId', doctorId);
        $('#deleteConfirmModal').modal('show');
    });
}

// Function to save doctor (add or update)
function saveDoctor() {
    // Validate form
    if (!validateDoctorForm()) {
        return;
    }
    
    const formData = $('#addDoctorForm').serializeArray();
    const doctorId = $('#doctorId').val();
    
    // Add action parameter
    formData.push({
        name: 'action',
        value: doctorId ? 'update_doctor' : 'add_doctor'
    });

    // Show loading state
    $('#saveDoctorBtn').html('<i class="fas fa-spinner fa-spin"></i> Saving...').attr('disabled', true);

    $.ajax({
        url: 'doctor_management_api.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            // Reset button state
            $('#saveDoctorBtn').html('Save Doctor').attr('disabled', false);
            
            if (response.success) {
                showAlert(response.message, 'success');
                
                // Switch to the doctors list tab and refresh the list
                $('#doctors-list-tab').tab('show');
                loadDoctors();
                
                // Reset the form
                resetDoctorForm();
            } else {
                showAlert('Error: ' + response.message, 'danger');
            }
        },
        error: function(xhr, status, error) {
            // Reset button state
            $('#saveDoctorBtn').html('Save Doctor').attr('disabled', false);
            
            console.error("AJAX Error: " + status + " - " + error);
            showAlert('Server error while saving doctor information', 'danger');
        }
    });
}

// Function to validate doctor form
function validateDoctorForm() {
    // Clear previous validation errors
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').remove();
    
    let isValid = true;
    
    // Required fields
    const requiredFields = ['firstName', 'lastName', 'phoneNumber', 'username', 'licenseNumber', 'specialization', 'department', 'consultationFee'];
    
    requiredFields.forEach(function(field) {
        const input = $('#' + field);
        if (!input.val().trim()) {
            isValid = false;
            input.addClass('is-invalid');
            input.after(`<div class="invalid-feedback">This field is required</div>`);
        }
    });
    
    // Email validation
    const emailInput = $('#email');
    if (emailInput.val().trim()) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(emailInput.val().trim())) {
            isValid = false;
            emailInput.addClass('is-invalid');
            emailInput.after(`<div class="invalid-feedback">Please enter a valid email address</div>`);
        }
    }
    
    // Password validation - required for new doctors
    const passwordInput = $('#password');
    const doctorId = $('#doctorId').val();
    if (!doctorId && !passwordInput.val().trim()) {
        isValid = false;
        passwordInput.addClass('is-invalid');
        passwordInput.after(`<div class="invalid-feedback">Password is required for new doctors</div>`);
    }
    
    // Phone number validation
    const phoneInput = $('#phoneNumber');
    const phoneRegex = /^\+?[0-9\s\-()]{8,20}$/;
    if (phoneInput.val().trim() && !phoneRegex.test(phoneInput.val().trim())) {
        isValid = false;
        phoneInput.addClass('is-invalid');
        phoneInput.after(`<div class="invalid-feedback">Please enter a valid phone number</div>`);
    }
    
    // Consultation fee validation
    const feeInput = $('#consultationFee');
    if (feeInput.val().trim() && (isNaN(feeInput.val()) || parseFloat(feeInput.val()) < 0)) {
        isValid = false;
        feeInput.addClass('is-invalid');
        feeInput.after(`<div class="invalid-feedback">Please enter a valid consultation fee</div>`);
    }
    
    return isValid;
}

// Function to edit a doctor
function editDoctor(doctorId) {
    // Switch to the add doctor tab
    $('#add-doctor-tab').tab('show');
    
    // Show loading state
    $('#addDoctorForm').html('<div class="text-center p-5"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Loading doctor information...</p></div>');
    
    $.ajax({
        url: 'doctor_management_api.php',
        type: 'GET',
        data: { action: 'get_doctor', doctor_id: doctorId },
        dataType: 'json',
        success: function(response) {
            // Reload the form if it was replaced with a loading indicator
            if ($('#addDoctorForm').find('input').length === 0) {
                location.reload();
                return;
            }
            
            if (response.success) {
                const doctor = response.data;
                
                // Populate the form with doctor data
                $('#doctorId').val(doctor.doctor_id);
                $('#firstName').val(doctor.first_name);
                $('#lastName').val(doctor.last_name);
                $('#email').val(doctor.email);
                $('#phoneNumber').val(doctor.phone_number);
                $('#username').val(doctor.username);
                $('#nationalId').val(doctor.national_id);
                $('#licenseNumber').val(doctor.license_number);
                $('#specialization').val(doctor.specialization);
                $('#department').val(doctor.department);
                $('#consultationFee').val(doctor.consultation_fee);
                $('#status').val(doctor.status);
                
                // Clear password field
                $('#password').val('');
            } else {
                showAlert('Error loading doctor information: ' + response.message, 'danger');
                $('#doctors-list-tab').tab('show');
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error: " + status + " - " + error);
            showAlert('Server error while loading doctor information', 'danger');
            $('#doctors-list-tab').tab('show');
        }
    });
}

// Function to delete a doctor
function deleteDoctor(doctorId) {
    // Show loading state
    $('#confirmDeleteBtn').html('<i class="fas fa-spinner fa-spin"></i> Deleting...').attr('disabled', true);
    
    $.ajax({
        url: 'doctor_management_api.php',
        type: 'POST',
        data: { action: 'delete_doctor', doctor_id: doctorId },
        dataType: 'json',
        success: function(response) {
            $('#deleteConfirmModal').modal('hide');
            
            if (response.success) {
                showAlert(response.message, 'success');
                loadDoctors();
            } else {
                showAlert('Error: ' + response.message, 'danger');
            }
            
            // Reset button state (after a delay to allow modal to close)
            setTimeout(function() {
                $('#confirmDeleteBtn').html('Delete').attr('disabled', false);
            }, 500);
        },
        error: function(xhr, status, error) {
            $('#deleteConfirmModal').modal('hide');
            console.error("AJAX Error: " + status + " - " + error);
            showAlert('Server error while deleting doctor', 'danger');
            
            // Reset button state (after a delay to allow modal to close)
            setTimeout(function() {
                $('#confirmDeleteBtn').html('Delete').attr('disabled', false);
            }, 500);
        }
    });
}

// Function to reset the doctor form
function resetDoctorForm() {
    $('#addDoctorForm')[0].reset();
    $('#doctorId').val('');
    
    // Clear validation errors
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').remove();
}

// Helper function to show alerts
function showAlert(message, type) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;
    
    // Remove any existing alerts
    $('.alert-dismissible').remove();
    
    // Prepend the new alert to the tab content
    $('#doctorManagementTabsContent').prepend(alertHtml);
    
    // Auto dismiss after 5 seconds
    setTimeout(function() {
        $('.alert-dismissible').fadeOut('slow', function() {
            $(this).remove();
        });
    }, 5000);
}
    </script>
    <script>
$(document).ready(function() {
  // Variables
  let currentPage = 1;
  const itemsPerPage = 10;
  
  // Event listener for Appointments button
  $('#appointmentsButton').on('click', function() {
    $('#appointmentsModal').modal('show');
    // Load appointments immediately when modal opens
    loadAppointments();
    loadPatients();
    loadDoctors();
  });
  
  // Event Listeners
  $('#newAppointmentBtn').on('click', function() {
    $('#modalAction').text('New');
    $('#appointmentForm')[0].reset();
    $('#appointmentId').val('');
    $('#appointmentFormModal').modal('show');
  });
  
  $('#editAppointmentBtn').on('click', function() {
    const appointmentId = $('#appointmentDetailsContent').data('appointment-id');
    loadAppointmentForEdit(appointmentId);
  });
  
  $('#saveAppointmentBtn').on('click', function() {
    saveAppointment();
  });
  
  $('#confirmDeleteBtn').on('click', function() {
    const appointmentId = $('#deleteAppointmentId').val();
    deleteAppointment(appointmentId);
  });
  
  $('#searchAppointment, #statusFilter, #dateFilter').on('input change', function() {
    currentPage = 1;
    loadAppointments();
  });
  
  // Functions
  function loadAppointments() {
    const search = $('#searchAppointment').val();
    const status = $('#statusFilter').val();
    const date = $('#dateFilter').val();
    
    // Show loading indicator
    $('#appointmentsTable tbody').html('<tr><td colspan="8" class="text-center"><i class="fas fa-spinner fa-spin mr-2"></i>Loading appointments...</td></tr>');
    
    $.ajax({
      url: 'appointment_handler.php',
      type: 'GET',
      data: {
        action: 'list',
        page: currentPage,
        limit: itemsPerPage,
        search: search,
        status: status,
        date: date
      },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          displayAppointments(response.data, response.total);
        } else {
          $('#appointmentsTable tbody').html('<tr><td colspan="8" class="text-center text-danger">Error: ' + response.message + '</td></tr>');
          $('#pagination').empty();
        }
      },
      error: function(xhr, status, error) {
        console.error("AJAX Error: ", status, error);
        $('#appointmentsTable tbody').html('<tr><td colspan="8" class="text-center text-danger">Failed to load appointments. Please try again.</td></tr>');
        $('#pagination').empty();
      }
    });
  }
  
  function displayAppointments(appointments, totalCount) {
    const tbody = $('#appointmentsTable tbody');
    tbody.empty();
    
    if (appointments.length === 0) {
      tbody.html('<tr><td colspan="8" class="text-center">No appointments found</td></tr>');
      $('#pagination').empty();
      return;
    }
    
    appointments.forEach(function(app) {
      const statusClass = 'status-' + app.status;
      const row = `
        <tr>
          <td>${app.appointment_id}</td>
          <td>${app.patient_name}</td>
          <td>${app.doctor_name}</td>
          <td>${app.appointment_date}</td>
          <td>${app.appointment_time}</td>
          <td>${app.purpose || '-'}</td>
          <td>
            <span class="appointment-status ${statusClass}">
              ${capitalizeFirstLetter(app.status)}
            </span>
          </td>
          <td>
            <button class="btn btn-info btn-sm view-btn" data-id="${app.appointment_id}">
              <i class="fas fa-eye"></i>
            </button>
            <button class="btn btn-primary btn-sm edit-btn" data-id="${app.appointment_id}">
              <i class="fas fa-edit"></i>
            </button>
            <button class="btn btn-danger btn-sm delete-btn" data-id="${app.appointment_id}">
              <i class="fas fa-trash"></i>
            </button>
          </td>
        </tr>
      `;
      tbody.append(row);
    });
    
    // Set up event handlers for the new buttons
    $('.view-btn').on('click', function() {
      const appointmentId = $(this).data('id');
      viewAppointment(appointmentId);
    });
    
    $('.edit-btn').on('click', function() {
      const appointmentId = $(this).data('id');
      loadAppointmentForEdit(appointmentId);
    });
    
    $('.delete-btn').on('click', function() {
      const appointmentId = $(this).data('id');
      $('#deleteAppointmentId').val(appointmentId);
      $('#deleteConfirmModal').modal('show');
    });
    
    // Generate pagination
    generatePagination(totalCount);
  }
  
  function capitalizeFirstLetter(string) {
    return string.charAt(0).toUpperCase() + string.slice(1).replace('_', ' ');
  }
  
  function generatePagination(totalCount) {
    const pageCount = Math.ceil(totalCount / itemsPerPage);
    const paginationContainer = $('#pagination');
    paginationContainer.empty();
    
    if (pageCount <= 1) {
      return;
    }
    
    const pagination = $('<ul class="pagination"></ul>');
    
    // Previous button
    const prevBtn = $(`
      <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
        <a class="page-link" href="#" data-page="${currentPage - 1}">
          <i class="fas fa-chevron-left"></i>
        </a>
      </li>
    `);
    pagination.append(prevBtn);
    
    // Page numbers
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(pageCount, currentPage + 2);
    
    for (let i = startPage; i <= endPage; i++) {
      const pageBtn = $(`
        <li class="page-item ${i === currentPage ? 'active' : ''}">
          <a class="page-link" href="#" data-page="${i}">${i}</a>
        </li>
      `);
      pagination.append(pageBtn);
    }
    
    // Next button
    const nextBtn = $(`
      <li class="page-item ${currentPage === pageCount ? 'disabled' : ''}">
        <a class="page-link" href="#" data-page="${currentPage + 1}">
          <i class="fas fa-chevron-right"></i>
        </a>
      </li>
    `);
    pagination.append(nextBtn);
    
    paginationContainer.append(pagination);
    
    // Add event listener for pagination
    $('.page-link').on('click', function(e) {
      e.preventDefault();
      if (!$(this).parent().hasClass('disabled')) {
        currentPage = parseInt($(this).data('page'));
        loadAppointments();
      }
    });
  }
  
  function viewAppointment(appointmentId) {
    $.ajax({
      url: 'appointment_handler.php',
      type: 'GET',
      data: {
        action: 'view',
        id: appointmentId
      },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          displayAppointmentDetails(response.data);
          $('#viewAppointmentModal').modal('show');
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function() {
        alert('Failed to load appointment details. Please try again.');
      }
    });
  }
  
  function displayAppointmentDetails(appointment) {
    const statusClass = 'status-' + appointment.status;
    const container = $('#appointmentDetailsContent');
    
    const content = `
      <div class="appointment-details" data-appointment-id="${appointment.appointment_id}">
        <div class="row mb-3">
          <div class="col-md-6">
            <h5 class="text-primary">
              <i class="fas fa-user-circle mr-2"></i>Patient Information
            </h5>
            <p><strong>Name:</strong> ${appointment.patient_name}</p>
            <p><strong>ID:</strong> ${appointment.patient_id}</p>
            <p><strong>Contact:</strong> ${appointment.patient_phone}</p>
          </div>
          <div class="col-md-6">
            <h5 class="text-primary">
              <i class="fas fa-user-md mr-2"></i>Doctor Information
            </h5>
            <p><strong>Name:</strong> ${appointment.doctor_name}</p>
            <p><strong>Specialization:</strong> ${appointment.doctor_specialization}</p>
            <p><strong>Department:</strong> ${appointment.doctor_department}</p>
          </div>
        </div>
        
        <h5 class="text-primary">
          <i class="fas fa-info-circle mr-2"></i>Appointment Information
        </h5>
        <div class="row">
          <div class="col-md-6">
            <p><strong>Date:</strong> ${appointment.appointment_date}</p>
            <p><strong>Time:</strong> ${appointment.appointment_time}</p>
            <p>
              <strong>Status:</strong> 
              <span class="appointment-status ${statusClass}">
                ${capitalizeFirstLetter(appointment.status)}
              </span>
            </p>
          </div>
          <div class="col-md-6">
            <p><strong>Purpose:</strong> ${appointment.purpose || 'Not specified'}</p>
            <p><strong>Created:</strong> ${appointment.created_at}</p>
          </div>
        </div>
        
        <h5 class="text-primary mt-3">
          <i class="fas fa-clipboard-list mr-2"></i>Notes
        </h5>
        <div class="p-3 bg-light rounded">
          ${appointment.notes ? appointment.notes : '<em>No notes available</em>'}
        </div>
      </div>
    `;
    
    container.html(content);
    container.data('appointment-id', appointment.appointment_id);
  }
  
  function loadAppointmentForEdit(appointmentId) {
    $.ajax({
      url: 'appointment_handler.php',
      type: 'GET',
      data: {
        action: 'view',
        id: appointmentId
      },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          const app = response.data;
          
          // Populate the form
          $('#modalAction').text('Edit');
          $('#appointmentId').val(app.appointment_id);
          $('#patientSelect').val(app.patient_id);
          $('#doctorSelect').val(app.doctor_id);
          $('#appointmentDate').val(app.appointment_date);
          $('#appointmentTime').val(app.appointment_time);
          $('#appointmentPurpose').val(app.purpose);
          $('#appointmentStatus').val(app.status);
          $('#appointmentNotes').val(app.notes);
          
          // Show the modal
          $('#viewAppointmentModal').modal('hide');
          $('#appointmentFormModal').modal('show');
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function() {
        alert('Failed to load appointment for editing. Please try again.');
      }
    });
  }
  
  function saveAppointment() {
    const formData = $('#appointmentForm').serialize();
    const appointmentId = $('#appointmentId').val();
    const action = appointmentId ? 'update' : 'create';
    
    $.ajax({
      url: 'appointment_handler.php',
      type: 'POST',
      data: formData + '&action=' + action,
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          $('#appointmentFormModal').modal('hide');
          loadAppointments();
          alert(response.message);
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function() {
        alert('Failed to save appointment. Please try again.');
      }
    });
  }
  
  function deleteAppointment(appointmentId) {
    $.ajax({
      url: 'appointment_handler.php',
      type: 'POST',
      data: {
        action: 'delete',
        id: appointmentId
      },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          $('#deleteConfirmModal').modal('hide');
          loadAppointments();
          alert(response.message);
        } else {
          alert('Error: ' + response.message);
        }
      },
      error: function() {
        alert('Failed to delete appointment. Please try again.');
      }
    });
  }
  
  function loadPatients() {
    $.ajax({
      url: 'appointment_handler.php',
      type: 'GET',
      data: { action: 'getPatients' },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          const select = $('#patientSelect');
          select.find('option:not(:first)').remove();
          
          response.data.forEach(function(patient) {
            select.append(`<option value="${patient.patient_id}">${patient.patient_name}</option>`);
          });
        } else {
          console.error('Failed to load patients:', response.message);
        }
      },
      error: function() {
        console.error('AJAX error when loading patients');
      }
    });
  }
  
  function loadDoctors() {
    $.ajax({
      url: 'appointment_handler.php',
      type: 'GET',
      data: { action: 'getDoctors' },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          const select = $('#doctorSelect');
          select.find('option:not(:first)').remove();
          
          response.data.forEach(function(doctor) {
            select.append(
              `<option value="${doctor.doctor_id}">${doctor.doctor_name} (${doctor.specialization})</option>`
            );
          });
        } else {
          console.error('Failed to load doctors:', response.message);
        }
      },
      error: function() {
        console.error('AJAX error when loading doctors');
      }
    });
  }
});
</script>

<script>
$(document).ready(function(){
    // Open modal
    $('#transactionMenuLink').on('click', function(e){
        e.preventDefault();
        $('#transactionsModal').show();

        // Load transactions data via AJAX
        $.ajax({
            url: 'load_transactions.php',
            method: 'GET',
            success: function(response){
                $('#transactionsTable').html(response);
            }
        });
    });

    // Close modal
    $('#closeModal').on('click', function(){
        $('#transactionsModal').hide();
    });

    // Close modal when clicking outside
    $(window).on('click', function(e){
        if(e.target.id === 'transactionsModal') {
            $('#transactionsModal').hide();
        }
    });
});
</script>
<script > document.getElementById('reportButton').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('reportsModal').style.display = 'block';

    // Fetch reports via AJAX
    fetch('fetch_reports.php')
        .then(response => response.text())
        .then(data => {
            document.getElementById('reportsTable').innerHTML = data;
        });
});

document.getElementById('closeReportModal').addEventListener('click', function() {
    document.getElementById('reportsModal').style.display = 'none';
});
</script>
<script>
$(document).ready(function() {
    // Open Medical Services Modal when menu link is clicked
    $('#medicalservicesMenuLink').click(function(e) {
        e.preventDefault();
        $('#medicalServicesModal').modal('show');
        loadServices(1); // Load first page of services on modal open
    });
    
    // Initialize tabs
    $('#medicalServicesTabs a').on('click', function(e) {
        e.preventDefault();
        $(this).tab('show');
        
        // Load appropriate data when tab is shown
        const tabId = $(this).attr('id');
        if (tabId === 'services-tab') {
            loadServices(1);
        } else if (tabId === 'treatments-tab') {
            loadTreatments(1);
        } else if (tabId === 'medications-tab') {
            loadMedications(1);
        }
    });
    
    // SERVICES
    
    // Handle service form open
    $('#addServiceBtn').click(function() {
        $('#serviceForm')[0].reset();
        $('#serviceId').val('');
        $('#serviceFormModalLabel').text('Add New Medical Service');
        $('#serviceFormModal').modal('show');
    });
    
    // Handle service form submission
    $('#serviceForm').submit(function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        const serviceId = $('#serviceId').val();
        const url = serviceId ? 'medical_services_handler.php?action=update' : 'medical_services_handler.php?action=add';
        
        $.ajax({
            type: 'POST',
            url: url,
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#serviceFormModal').modal('hide');
                    loadServices(1);
                    
                    // Show notification
                    showNotification('success', response.message);
                } else {
                    showNotification('error', response.message);
                }
            },
            error: function() {
                showNotification('error', 'An error occurred. Please try again.');
            }
        });
    });
    
    // Handle service filter
    $('#serviceFilterBtn').click(function() {
        loadServices(1);
    });
    
    // Service search input handling
    $('#serviceSearchInput').on('keyup', function(e) {
        if (e.keyCode === 13) {
            loadServices(1);
        }
    });
    
    // TREATMENTS
    
    // Handle treatment form open
    $('#addTreatmentBtn').click(function() {
        $('#treatmentForm')[0].reset();
        $('#treatmentId').val('');
        $('#treatmentFormModalLabel').text('Add New Treatment Record');
        
        // Reset service and medication tables
        $('#treatmentServicesTableBody').html(`
            <tr id="serviceRow0" class="service-row">
                <td>
                    <select class="form-control service-select" name="service_id[]" required>
                        <option value="">Select Service</option>
                    </select>
                </td>
                <td>
                    <input type="number" class="form-control service-quantity" name="quantity[]" value="1" min="1">
                </td>
                <td>
                    <input type="number" step="0.01" class="form-control service-price" name="price[]" required>
                </td>
                <td>
                    <input type="text" class="form-control service-notes" name="notes[]">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-service">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            </tr>
        `);
        
        $('#medicationsTableBodyForm').html(`
            <tr id="medicationRow0" class="medication-row">
                <td>
                    <input type="text" class="form-control medication-name" name="medication_name[]" required>
                </td>
                <td>
                    <input type="text" class="form-control medication-dosage" name="dosage[]" required>
                </td>
                <td>
                    <input type="text" class="form-control medication-frequency" name="frequency[]" placeholder="e.g. 3x daily">
                </td>
                <td>
                    <input type="text" class="form-control medication-duration" name="duration[]" placeholder="e.g. 7 days">
                </td>
                <td>
                    <input type="number" step="0.01" class="form-control medication-price" name="medication_price[]" required>
                </td>
                <td>
                    <input type="number" class="form-control medication-quantity" name="medication_quantity[]" value="1" min="1">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-medication">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            </tr>
        `);
        
        // Load patients, doctors and services for dropdowns
        loadPatientsForDropdown();
        loadDoctorsForDropdown();
        loadServicesForDropdown();
        
        $('#treatmentFormModal').modal('show');
    });
    
    // Add service row button
    $('#addServiceRow').click(function() {
        const rowCount = $('.service-row').length;
        const newRow = `
            <tr id="serviceRow${rowCount}" class="service-row">
                <td>
                    <select class="form-control service-select" name="service_id[]" required>
                        <option value="">Select Service</option>
                    </select>
                </td>
                <td>
                    <input type="number" class="form-control service-quantity" name="quantity[]" value="1" min="1">
                </td>
                <td>
                    <input type="number" step="0.01" class="form-control service-price" name="price[]" required>
                </td>
                <td>
                    <input type="text" class="form-control service-notes" name="notes[]">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-service">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            </tr>
        `;
        
        $('#treatmentServicesTableBody').append(newRow);
        loadServicesForDropdown(rowCount);
    });
    
    // Add medication row button
    $('#addMedicationRow').click(function() {
        const rowCount = $('.medication-row').length;
        const newRow = `
            <tr id="medicationRow${rowCount}" class="medication-row">
                <td>
                    <input type="text" class="form-control medication-name" name="medication_name[]" required>
                </td>
                <td>
                    <input type="text" class="form-control medication-dosage" name="dosage[]" required>
                </td>
                <td>
                    <input type="text" class="form-control medication-frequency" name="frequency[]" placeholder="e.g. 3x daily">
                </td>
                <td>
                    <input type="text" class="form-control medication-duration" name="duration[]" placeholder="e.g. 7 days">
                </td>
                <td>
                    <input type="number" step="0.01" class="form-control medication-price" name="medication_price[]" required>
                </td>
                <td>
                    <input type="number" class="form-control medication-quantity" name="medication_quantity[]" value="1" min="1">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-medication">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            </tr>
        `;
        
        $('#medicationsTableBodyForm').append(newRow);
    });
    
    // Remove service row
    $(document).on('click', '.remove-service', function() {
        if ($('.service-row').length > 1) {
            $(this).closest('tr').remove();
        } else {
            showNotification('warning', 'At least one service is required');
        }
    });
    // Remove medication row
    $(document).on('click', '.remove-medication', function() {
        if ($('.medication-row').length > 1) {
            $(this).closest('tr').remove();
        } else {
            // Clear the fields instead of removing the row
            const row = $(this).closest('tr');
            row.find('input').val('');
            row.find('input.medication-quantity').val('1');
        }
    });
    
    // Handle treatment form submission
    $('#treatmentForm').submit(function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const treatmentId = $('#treatmentId').val();
        const url = treatmentId ? 'treatment_handler.php?action=update' : 'treatment_handler.php?action=add';
        
        $.ajax({
            type: 'POST',
            url: url,
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#treatmentFormModal').modal('hide');
                    loadTreatments(1);
                    
                    // Show notification
                    showNotification('success', response.message);
                } else {
                    showNotification('error', response.message);
                }
            },
            error: function(xhr) {
                let errorMsg = 'An error occurred. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                showNotification('error', errorMsg);
            }
        });
    });
    
    // Handle treatment filter
    $('#treatmentFilterBtn').click(function() {
        loadTreatments(1);
    });
    
    // MEDICATIONS
    
    // Handle medication form open
    $('#addMedicationBtn').click(function() {
        $('#medicationForm')[0].reset();
        $('#medicationId').val('');
        $('#medicationFormModalLabel').text('Add New Medication');
        
        // Load treatments for dropdown
        loadTreatmentsForDropdown();
        
        $('#medicationFormModal').modal('show');
    });
    
    // Handle medication form submission
    $('#medicationForm').submit(function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        const medicationId = $('#medicationId').val();
        const url = medicationId ? 'medication_handler.php?action=update' : 'medication_handler.php?action=add';
        
        $.ajax({
            type: 'POST',
            url: url,
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#medicationFormModal').modal('hide');
                    loadMedications(1);
                    
                    // Show notification
                    showNotification('success', response.message);
                } else {
                    showNotification('error', response.message);
                }
            },
            error: function() {
                showNotification('error', 'An error occurred. Please try again.');
            }
        });
    });
    
    // Handle medication filter
    $('#medicationFilterBtn').click(function() {
        loadMedications(1);
    });
    
    // Load service details for edit
    $(document).on('click', '.edit-service', function() {
        const serviceId = $(this).data('id');
        
        $.ajax({
            type: 'GET',
            url: 'medical_services_handler.php?action=get',
            data: { service_id: serviceId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    const service = response.data;
                    
                    $('#serviceId').val(service.service_id);
                    $('#serviceName').val(service.service_name);
                    $('#serviceCategory').val(service.service_category);
                    $('#basePrice').val(service.base_price);
                    $('#serviceDescription').val(service.description);
                    $('#serviceStatus').val(service.status);
                    
                    $('#serviceFormModalLabel').text('Edit Medical Service');
                    $('#serviceFormModal').modal('show');
                } else {
                    showNotification('error', response.message);
                }
            },
            error: function() {
                showNotification('error', 'Failed to load service details.');
            }
        });
    });
    
    // Load treatment details for edit
    $(document).on('click', '.edit-treatment', function() {
        const treatmentId = $(this).data('id');
        
        $.ajax({
            type: 'GET',
            url: 'treatment_handler.php?action=get',
            data: { treatment_id: treatmentId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    const treatment = response.data.treatment;
                    const services = response.data.services;
                    const medications = response.data.medications;
                    
                    $('#treatmentId').val(treatment.treatment_id);
                    
                    // Load dropdowns
                    loadPatientsForDropdown(treatment.patient_id);
                    loadDoctorsForDropdown(treatment.doctor_id);
                    
                    $('#diagnosisInput').val(treatment.diagnosis);
                    $('#treatmentNotesInput').val(treatment.treatment_notes);
                    $('#followUpDateInput').val(treatment.follow_up_date);
                    $('#treatmentStatusSelect').val(treatment.status);
                    
                    // Add services
                    $('#treatmentServicesTableBody').empty();
                    if (services.length > 0) {
                        services.forEach(function(service, index) {
                            const serviceRow = `
                                <tr id="serviceRow${index}" class="service-row">
                                    <td>
                                        <select class="form-control service-select" name="service_id[]" required>
                                            <option value="">Select Service</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control service-quantity" name="quantity[]" value="${service.quantity}" min="1">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" class="form-control service-price" name="price[]" value="${service.price}" required>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control service-notes" name="notes[]" value="${service.notes || ''}">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-danger btn-sm remove-service">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                            `;
                            
                            $('#treatmentServicesTableBody').append(serviceRow);
                            loadServicesForDropdown(index, service.service_id);
                        });
                    } else {
                        // Add empty row if no services
                        $('#treatmentServicesTableBody').html(`
                            <tr id="serviceRow0" class="service-row">
                                <td>
                                    <select class="form-control service-select" name="service_id[]" required>
                                        <option value="">Select Service</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" class="form-control service-quantity" name="quantity[]" value="1" min="1">
                                </td>
                                <td>
                                    <input type="number" step="0.01" class="form-control service-price" name="price[]" required>
                                </td>
                                <td>
                                    <input type="text" class="form-control service-notes" name="notes[]">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm remove-service">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </td>
                            </tr>
                        `);
                        loadServicesForDropdown(0);
                    }
                    
                    // Add medications
                    $('#medicationsTableBodyForm').empty();
                    if (medications.length > 0) {
                        medications.forEach(function(med, index) {
                            const medRow = `
                                <tr id="medicationRow${index}" class="medication-row">
                                    <td>
                                        <input type="text" class="form-control medication-name" name="medication_name[]" value="${med.medication_name}" required>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control medication-dosage" name="dosage[]" value="${med.dosage}" required>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control medication-frequency" name="frequency[]" value="${med.frequency || ''}" placeholder="e.g. 3x daily">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control medication-duration" name="duration[]" value="${med.duration || ''}" placeholder="e.g. 7 days">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" class="form-control medication-price" name="medication_price[]" value="${med.price}" required>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control medication-quantity" name="medication_quantity[]" value="${med.quantity}" min="1">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-danger btn-sm remove-medication">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                            `;
                            
                            $('#medicationsTableBodyForm').append(medRow);
                        });
                    } else {
                        // Add empty row if no medications
                        $('#medicationsTableBodyForm').html(`
                            <tr id="medicationRow0" class="medication-row">
                                <td>
                                    <input type="text" class="form-control medication-name" name="medication_name[]" required>
                                </td>
                                <td>
                                    <input type="text" class="form-control medication-dosage" name="dosage[]" required>
                                </td>
                                <td>
                                    <input type="text" class="form-control medication-frequency" name="frequency[]" placeholder="e.g. 3x daily">
                                </td>
                                <td>
                                    <input type="text" class="form-control medication-duration" name="duration[]" placeholder="e.g. 7 days">
                                </td>
                                <td>
                                    <input type="number" step="0.01" class="form-control medication-price" name="medication_price[]" required>
                                </td>
                                <td>
                                    <input type="number" class="form-control medication-quantity" name="medication_quantity[]" value="1" min="1">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm remove-medication">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </td>
                            </tr>
                        `);
                    }
                    
                    $('#treatmentFormModalLabel').text('Edit Treatment Record');
                    $('#treatmentFormModal').modal('show');
                } else {
                    showNotification('error', response.message);
                }
            },
            error: function() {
                showNotification('error', 'Failed to load treatment details.');
            }
        });
    });
    
    // Load medication details for edit
    $(document).on('click', '.edit-medication', function() {
        const medicationId = $(this).data('id');
        
        $.ajax({
            type: 'GET',
            url: 'medication_handler.php?action=get',
            data: { medication_id: medicationId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    const medication = response.data;
                    
                    $('#medicationId').val(medication.medication_id);
                    loadTreatmentsForDropdown(medication.treatment_id);
                    
                    $('#medicationName').val(medication.medication_name);
                    $('#medicationDosage').val(medication.dosage);
                    $('#medicationFrequency').val(medication.frequency);
                    $('#medicationDuration').val(medication.duration);
                    $('#medicationQuantity').val(medication.quantity);
                    $('#medicationPrice').val(medication.price);
                    $('#medicationDispensed').val(medication.dispensed);
                    $('#medicationInstructions').val(medication.instructions);
                    
                    $('#medicationFormModalLabel').text('Edit Medication');
                    $('#medicationFormModal').modal('show');
                } else {
                    showNotification('error', response.message);
                }
            },
            error: function() {
                showNotification('error', 'Failed to load medication details.');
            }
        });
    });
    
    // Delete service
    $(document).on('click', '.delete-service', function() {
        if (confirm('Are you sure you want to delete this service?')) {
            const serviceId = $(this).data('id');
            
            $.ajax({
                type: 'POST',
                url: 'medical_services_handler.php?action=delete',
                data: { service_id: serviceId },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        loadServices(1);
                        showNotification('success', response.message);
                    } else {
                        showNotification('error', response.message);
                    }
                },
                error: function() {
                    showNotification('error', 'Failed to delete service.');
                }
            });
        }
    });
    
    // Delete treatment
    $(document).on('click', '.delete-treatment', function() {
        if (confirm('Are you sure you want to delete this treatment record? This will also delete associated services and medications.')) {
            const treatmentId = $(this).data('id');
            
            $.ajax({
                type: 'POST',
                url: 'treatment_handler.php?action=delete',
                data: { treatment_id: treatmentId },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        loadTreatments(1);
                        showNotification('success', response.message);
                    } else {
                        showNotification('error', response.message);
                    }
                },
                error: function() {
                    showNotification('error', 'Failed to delete treatment record.');
                }
            });
        }
    });
    
    // Delete medication
    $(document).on('click', '.delete-medication', function() {
        if (confirm('Are you sure you want to delete this medication?')) {
            const medicationId = $(this).data('id');
            
            $.ajax({
                type: 'POST',
                url: 'medication_handler.php?action=delete',
                data: { medication_id: medicationId },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        loadMedications(1);
                        showNotification('success', response.message);
                    } else {
                        showNotification('error', response.message);
                    }
                },
                error: function() {
                    showNotification('error', 'Failed to delete medication.');
                }
            });
        }
    });
    
    // Toggle medication dispensed status
    $(document).on('click', '.toggle-dispensed', function() {
        const medicationId = $(this).data('id');
        const currentStatus = $(this).data('status');
        const newStatus = currentStatus == 1 ? 0 : 1;
        
        $.ajax({
            type: 'POST',
            url: 'medication_handler.php?action=toggle_dispensed',
            data: { 
                medication_id: medicationId,
                dispensed: newStatus
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    loadMedications(1);
                    showNotification('success', response.message);
                } else {
                    showNotification('error', response.message);
                }
            },
            error: function() {
                showNotification('error', 'Failed to update dispensed status.');
            }
        });
    });
    
    // HELPER FUNCTIONS
    
    // Load services
    function loadServices(page) {
        const search = $('#serviceSearchInput').val();
        const category = $('#serviceCategoryFilter').val();
        
        $.ajax({
            type: 'GET',
            url: 'medical_services_handler.php?action=list',
            data: {
                page: page,
                search: search,
                category: category
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // Populate table
                    $('#servicesTableBody').empty();
                    
                    if (response.data.services.length === 0) {
                        $('#servicesTableBody').html('<tr><td colspan="6" class="text-center">No services found</td></tr>');
                    } else {
                        $.each(response.data.services, function(i, service) {
                            const statusBadge = service.status === 'active' 
                                ? '<span class="badge badge-success">Active</span>' 
                                : '<span class="badge badge-secondary">Inactive</span>';
                                
                            const row = `
                                <tr>
                                    <td>${service.service_id}</td>
                                    <td>${service.service_name}</td>
                                    <td>${service.service_category}</td>
                                    <td>${parseFloat(service.base_price).toFixed(2)}</td>
                                    <td>${statusBadge}</td>
                                    <td>
                                        <button type="button" class="btn btn-info btn-sm edit-service" data-id="${service.service_id}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm delete-service" data-id="${service.service_id}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            `;
                            
                            $('#servicesTableBody').append(row);
                        });
                    }
                    
                    // Pagination
                    renderPagination('servicesPagination', response.data.totalPages, page, loadServices);
                } else {
                    showNotification('error', response.message);
                }
            },
            error: function() {
                showNotification('error', 'Failed to load services.');
            }
        });
    }
    
    // Load treatments
    function loadTreatments(page) {
        const patientSearch = $('#patientSearchInput').val();
        const treatmentDate = $('#treatmentDateFilter').val();
        const status = $('#treatmentStatusFilter').val();
        
        $.ajax({
            type: 'GET',
            url: 'treatment_handler.php?action=list',
            data: {
                page: page,
                search: patientSearch,
                date: treatmentDate,
                status: status
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // Populate table
                    $('#treatmentsTableBody').empty();
                    
                    if (response.data.treatments.length === 0) {
                        $('#treatmentsTableBody').html('<tr><td colspan="7" class="text-center">No treatment records found</td></tr>');
                    } else {
                        $.each(response.data.treatments, function(i, treatment) {
                            let statusBadge;
                            switch(treatment.status) {
                                case 'open':
                                    statusBadge = '<span class="badge badge-primary">Open</span>';
                                    break;
                                case 'closed':
                                    statusBadge = '<span class="badge badge-success">Closed</span>';
                                    break;
                                case 'follow_up':
                                    statusBadge = '<span class="badge badge-info">Follow Up</span>';
                                    break;
                                default:
                                    statusBadge = '<span class="badge badge-secondary">Unknown</span>';
                            }
                                
                            const row = `
                                <tr>
                                    <td>${treatment.treatment_id}</td>
                                    <td>${treatment.patient_name}</td>
                                    <td>${treatment.doctor_name}</td>
                                    <td>${treatment.diagnosis.length > 30 ? treatment.diagnosis.substring(0, 30) + '...' : treatment.diagnosis}</td>
                                    <td>${treatment.treatment_date}</td>
                                    <td>${statusBadge}</td>
                                    <td>
                                        <button type="button" class="btn btn-info btn-sm edit-treatment" data-id="${treatment.treatment_id}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm delete-treatment" data-id="${treatment.treatment_id}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            `;
                            
                            $('#treatmentsTableBody').append(row);
                        });
                    }
                    
                    // Pagination
                    renderPagination('treatmentsPagination', response.data.totalPages, page, loadTreatments);
                } else {
                    showNotification('error', response.message);
                }
            },
            error: function() {
                showNotification('error', 'Failed to load treatment records.');
            }
        });
    }
    
    // Load medications
    function loadMedications(page) {
        const search = $('#medicationSearchInput').val();
        const dispensed = $('#dispensedFilter').val();
        
        $.ajax({
            type: 'GET',
            url: 'medication_handler.php?action=list',
            data: {
                page: page,
                search: search,
                dispensed: dispensed
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // Populate table
                    $('#medicationsTableBody').empty();
                    
                    if (response.data.medications.length === 0) {
                        $('#medicationsTableBody').html('<tr><td colspan="8" class="text-center">No medications found</td></tr>');
                    } else {
                        $.each(response.data.medications, function(i, medication) {
                            const dispensedStatus = medication.dispensed == 1 
                                ? '<span class="badge badge-success">Yes</span>' 
                                : '<span class="badge badge-warning">No</span>';
                                
                            const row = `
                                <tr>
                                    <td>${medication.medication_id}</td>
                                    <td>${medication.treatment_id}</td>
                                    <td>${medication.medication_name}</td>
                                    <td>${medication.dosage}</td>
                                    <td>${medication.quantity}</td>
                                    <td>${parseFloat(medication.price).toFixed(2)}</td>
                                    <td>
                                        <button type="button" class="btn btn-sm ${medication.dispensed == 1 ? 'btn-success' : 'btn-warning'} toggle-dispensed" 
                                            data-id="${medication.medication_id}" data-status="${medication.dispensed}">
                                            ${dispensedStatus}
                                        </button>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-info btn-sm edit-medication" data-id="${medication.medication_id}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm delete-medication" data-id="${medication.medication_id}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            `;
                            
                            $('#medicationsTableBody').append(row);
                        });
                    }
                    
                    // Pagination
                    renderPagination('medicationsPagination', response.data.totalPages, page, loadMedications);
                } else {
                    showNotification('error', response.message);
                }
            },
            error: function() {
                showNotification('error', 'Failed to load medications.');
            }
        });
    }
    
    // Render pagination
    function renderPagination(elementId, totalPages, currentPage, loadFunction) {
        $('#' + elementId).empty();
        
        if (totalPages <= 1) {
            return;
        }
        
        // Previous button
        let prevDisabled = currentPage === 1 ? 'disabled' : '';
        $('#' + elementId).append(`
            <li class="page-item ${prevDisabled}">
                <a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
        `);
        
        // Page numbers
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, startPage + 4);
        
        for (let i = startPage; i <= endPage; i++) {
            const active = i === currentPage ? 'active' : '';
            $('#' + elementId).append(`
                <li class="page-item ${active}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `);
        }
        
        // Next button
        let nextDisabled = currentPage === totalPages ? 'disabled' : '';
        $('#' + elementId).append(`
            <li class="page-item ${nextDisabled}">
                <a class="page-link" href="#" data-page="${currentPage + 1}" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        `);
        
        // Pagination click handler
        $('#' + elementId + ' .page-link').on('click', function(e) {
            e.preventDefault();
            if (!$(this).parent().hasClass('disabled')) {
                const page = $(this).data('page');
                loadFunction(page);
            }
        });
    }
    
    // Load patients for dropdown
    function loadPatientsForDropdown(selectedId = null) {
        $.ajax({
            type: 'GET',
            url: 'dropdown_handler.php?type=patients',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#patientSelect').empty().append('<option value="">Select Patient</option>');
                    
                    $.each(response.data, function(i, patient) {
                        const selected = patient.patient_id == selectedId ? 'selected' : '';
                        $('#patientSelect').append(`<option value="${patient.patient_id}" ${selected}>${patient.patient_name}</option>`);
                    });
                } else {
                    showNotification('error', 'Failed to load patients.');
                }
            }
        });
    }
    
    // Load doctors for dropdown
    function loadDoctorsForDropdown(selectedId = null) {
        $.ajax({
            type: 'GET',
            url: 'dropdown_handler.php?type=doctors',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#doctorSelect').empty().append('<option value="">Select Doctor</option>');
                    
                    $.each(response.data, function(i, doctor) {
                        const selected = doctor.doctor_id == selectedId ? 'selected' : '';
                        $('#doctorSelect').append(`<option value="${doctor.doctor_id}" ${selected}>${doctor.doctor_name} (${doctor.specialization})</option>`);
                    });
                } else {
                    showNotification('error', 'Failed to load doctors.');
                }
            }
        });
    }
    
    // Load services for dropdown
    function loadServicesForDropdown(rowIndex = 0, selectedId = null) {
        $.ajax({
            type: 'GET',
            url: 'dropdown_handler.php?type=services',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $(`#serviceRow${rowIndex} .service-select`).empty().append('<option value="">Select Service</option>');
                    
                    $.each(response.data, function(i, service) {
                        const selected = service.service_id == selectedId ? 'selected' : '';
                        $(`#serviceRow${rowIndex} .service-select`).append(
                            `<option value="${service.service_id}" data-price="${service.base_price}" ${selected}>
                                ${service.service_name} (${service.service_category})
                            </option>`
                        );
                    });
                    
                    // Set price when service is selected
                    if (selectedId) {
                        const price = $(`#serviceRow${rowIndex} .service-select option:selected`).data('price');
                        $(`#serviceRow${rowIndex} .service-price`).val(price);
                    }
                } else {
                    showNotification('error', 'Failed to load services.');
                }
            }
        });
        
        // Add event listener for price update on service selection
        $(document).on('change', '.service-select', function() {
            const price = $(this).find('option:selected').data('price');
            if (price) {
                $(this).closest('tr').find('.service-price').val(price);
            }
        });
    }
    
    // Load treatments for dropdown
   // Load treatments for dropdown
function loadTreatmentsForDropdown(selectedId = null) {
    $.ajax({
        type: 'GET',
        url: 'dropdown_handler.php?type=treatments',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                $('#treatmentIdSelect').empty().append('<option value="">Select Treatment</option>');
                
                $.each(response.data, function(i, treatment) {
                    const selected = treatment.treatment_id == selectedId ? 'selected' : '';
                    $('#treatmentIdSelect').append(
                        `<option value="${treatment.treatment_id}" ${selected}>
                            ID: ${treatment.treatment_id} - Patient: ${treatment.patient_name} - Date: ${treatment.treatment_date}
                        </option>`
                    );
                });
            } else {
                showNotification('error', 'Failed to load treatments.');
            }
        },
        error: function() {
            showNotification('error', 'Failed to load treatments for dropdown.');
        }
    });
}

    
    // Show notification function
    function showNotification(type, message) {
        toastr[type](message);
    }
});
</script>
<script >
    // JavaScript to handle Smart Card operations

// Handle menu click to open the modal
$(document).ready(function() {
    // Click handler for Smart Cards menu item
    $("#smartcardMenuLink").click(function(e) {
        e.preventDefault();
        $("#smartCardModal").modal("show");
        loadPatients();
        loadSmartCards();
        
        // Set today's date as default for issue date
        var today = new Date().toISOString().split('T')[0];
        $("#issueDate").val(today);
        
        // Set default expiry date to 1 year from now
        var nextYear = new Date();
        nextYear.setFullYear(nextYear.getFullYear() + 1);
        $("#expiryDate").val(nextYear.toISOString().split('T')[0]);
    });

    // Form submission for assigning a new card
    $("#assignCardForm").submit(function(e) {
        e.preventDefault();
        assignCardToPatient();
    });
    
    // Form submission for recharging a card
    $("#rechargeCardForm").submit(function(e) {
        e.preventDefault();
        rechargeCard();
    });
    
    // Search functionality for smart cards
    $("#searchPatient").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#smartCardTable tbody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
    
    // Recharge RFID lookup
    $("#rechargeRfid").on("change", function() {
        var rfid = $(this).val();
        if (rfid) {
            lookupCardDetails(rfid);
        }
    });
});

// Function to load patients dropdown
function loadPatients() {
    $.ajax({
        url: "ajax/get_patients.php", // Create this PHP file
        type: "GET",
        dataType: "json",
        success: function(data) {
            var options = '<option value="">-- Select Patient --</option>';
            for (var i = 0; i < data.length; i++) {
                options += '<option value="' + data[i].patient_id + '">' + 
                          data[i].first_name + ' ' + data[i].last_name + 
                          ' (' + data[i].national_id + ')</option>';
            }
            $("#patientSelectt").html(options);
        },
        error: function(xhr, status, error) {
            console.error("Error loading patients:", error);
            alert("Failed to load patients. Please try again.");
        }
    });
}

// Function to load smart cards
function loadSmartCards() {
    $.ajax({
        url: "ajax/get_smart_cards.php", // Create this PHP file
        type: "GET",
        dataType: "json",
        success: function(data) {
            var tableRows = '';
            for (var i = 0; i < data.length; i++) {
                tableRows += '<tr>';
                tableRows += '<td>' + data[i].card_id + '</td>';
                tableRows += '<td>' + data[i].rfid_number + '</td>';
                tableRows += '<td>' + data[i].patient_name + '</td>';
                tableRows += '<td>' + data[i].issue_date + '</td>';
                tableRows += '<td>' + data[i].expiry_date + '</td>';
                tableRows += '<td>' + data[i].current_balance + '</td>';
                tableRows += '<td><span class="badge badge-' + 
                          (data[i].card_status === 'active' ? 'success' : 'danger') + 
                          '">' + data[i].card_status + '</span></td>';
                tableRows += '<td>' +
                          '<div class="btn-group btn-group-sm" role="group">' +
                          '<button type="button" class="btn btn-info btn-view-card" data-id="' + data[i].card_id + '"><i class="fas fa-eye"></i></button>' +
                          '<button type="button" class="btn btn-warning btn-edit-card" data-id="' + data[i].card_id + '"><i class="fas fa-edit"></i></button>' +
                          '<button type="button" class="btn btn-danger btn-disable-card" data-id="' + data[i].card_id + '"><i class="fas fa-ban"></i></button>' +
                          '</div>' +
                          '</td>';
                tableRows += '</tr>';
            }
            $("#smartCardTable tbody").html(tableRows);
            
            // Setup action buttons
            setupCardActionButtons();
        },
        error: function(xhr, status, error) {
            console.error("Error loading smart cards:", error);
            alert("Failed to load smart cards. Please try again.");
        }
    });
}

// Function to assign a new card to a patient
function assignCardToPatient() {
    var formData = {
        patient_id: $("#patientSelectt").val(),
        rfid_number: $("#rfidNumber").val(),
        issue_date: $("#issueDate").val(),
        expiry_date: $("#expiryDate").val(),
        initial_balance: $("#initialBalance").val(),
        pin_code: $("#pinCode").val()
    };
    
    $.ajax({
        url: "ajax/assign_card.php", // Create this PHP file
        type: "POST",
        dataType: "json",
        data: formData,
        success: function(response) {
            if (response.success) {
                alert("Card assigned successfully!");
                $("#assignCardForm")[0].reset();
                loadSmartCards();
                
                // Set default dates again
                var today = new Date().toISOString().split('T')[0];
                $("#issueDate").val(today);
                var nextYear = new Date();
                nextYear.setFullYear(nextYear.getFullYear() + 1);
                $("#expiryDate").val(nextYear.toISOString().split('T')[0]);
            } else {
                alert("Error: " + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error("Error assigning card:", error);
            alert("Failed to assign card. Please try again.");
        }
    });
}

// Function to look up card details by RFID
function lookupCardDetails(rfid) {
    $.ajax({
        url: "ajax/get_card_details.php", // Create this PHP file
        type: "GET",
        dataType: "json",
        data: { rfid: rfid },
        success: function(data) {
            if (data) {
                var html = '<h5 class="card-title">Card Details</h5>' +
                        '<p><strong>Patient:</strong> ' + data.patient_name + '</p>' +
                        '<p><strong>Card Status:</strong> <span class="badge badge-' + 
                        (data.card_status === 'active' ? 'success' : 'danger') + 
                        '">' + data.card_status + '</span></p>' +
                        '<p><strong>Current Balance:</strong> $' + data.current_balance + '</p>' +
                        '<p><strong>Expiry Date:</strong> ' + data.expiry_date + '</p>';
                $("#cardDetails").html(html);
            } else {
                $("#cardDetails").html('<p class="card-text text-center text-danger">Card not found</p>');
            }
        },
        error: function(xhr, status, error) {
            console.error("Error looking up card:", error);
            $("#cardDetails").html('<p class="card-text text-center text-danger">Error looking up card</p>');
        }
    });
}

// Function to recharge a card
function rechargeCard() {
    var formData = {
        rfid_number: $("#rechargeRfid").val(),
        amount: $("#rechargeAmount").val(),
        payment_method: $("#paymentMethod").val(),
        reference_number: $("#referenceNumber").val()
    };
    
    $.ajax({
        url: "ajax/recharge_card.php", // Create this PHP file
        type: "POST",
        dataType: "json",
        data: formData,
        success: function(response) {
            if (response.success) {
                alert("Card recharged successfully!");
                $("#rechargeCardForm")[0].reset();
                $("#cardDetails").html('<p class="card-text text-center text-muted">Card details will appear here</p>');
            } else {
                alert("Error: " + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error("Error recharging card:", error);
            alert("Failed to recharge card. Please try again.");
        }
    });
}

// Function to setup card action buttons
function setupCardActionButtons() {
    // View card details
    $(".btn-view-card").click(function() {
        var cardId = $(this).data("id");
        // Implement view card details functionality
        alert("View card ID: " + cardId);
    });
    
    // Edit card
    $(".btn-edit-card").click(function() {
        var cardId = $(this).data("id");
        // Implement edit card functionality
        alert("Edit card ID: " + cardId);
    });
    
    // Disable card
    $(".btn-disable-card").click(function() {
        var cardId = $(this).data("id");
        if (confirm("Are you sure you want to disable this card?")) {
            disableCard(cardId);
        }
    });
}

// Function to disable a card
function disableCard(cardId) {
    $.ajax({
        url: "ajax/disable_card.php", // Create this PHP file
        type: "POST",
        dataType: "json",
        data: { card_id: cardId },
        success: function(response) {
            if (response.success) {
                alert("Card disabled successfully!");
                loadSmartCards();
            } else {
                alert("Error: " + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error("Error disabling card:", error);
            alert("Failed to disable card. Please try again.");
        }
    });
}

</script>
<!-- Settings link -->
<li>
    <a href="#" id="settingmodel">
        <i class="fas fa-cog"></i>
        <span>Settings</span>
    </a>
</li>

<!-- Popup Modal (Hidden by default) -->
<div id="moneyModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Update Money</h2>
        <form id="updateMoneyForm">
            <label for="currentAmount">Current Amount:</label>
            <input type="text" id="currentAmount" disabled><br>

            <label for="newAmount">New Amount:</label>
            <input type="number" id="newAmount" required><br><br>

            <button type="submit">Update</button>
        </form>
    </div>
</div>

<!-- Add some basic styling for the modal -->
<style>
    .modal {
        display: none; /* Hidden by default */
        position: fixed;
        z-index: 1;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.4);
    }
    
    .modal-content {
        background-color: #fff;
        margin: 15% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 300px;
    }

    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
    }

    .close:hover,
    .close:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }
</style>

<!-- JavaScript to handle Modal and form -->
<script>
    // Get the modal and buttons
    var modal = document.getElementById("moneyModal");
    var settingBtn = document.getElementById("settingmodel");
    var closeBtn = document.getElementsByClassName("close")[0];

    // Show modal when clicking the settings button
    settingBtn.onclick = function() {
        modal.style.display = "block";
        // Fetch current money value from the database when modal opens
        fetchCurrentMoney();
    }

    // Close the modal when clicking the "X" button
    closeBtn.onclick = function() {
        modal.style.display = "none";
    }

    // Close the modal if the user clicks outside the modal content
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    // Fetch current money value and display it in the form
    function fetchCurrentMoney() {
        fetch('fetch_money.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('currentAmount').value = data.amount;
                } else {
                    alert('Error fetching current money value');
                }
            })
            .catch(error => console.log('Error:', error));
    }

    // Update money when the form is submitted
    document.getElementById('updateMoneyForm').onsubmit = function(e) {
        e.preventDefault();
        var newAmount = document.getElementById('newAmount').value;

        // Send the new amount to the server for updating
        fetch('update_money.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ newAmount: newAmount })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Money updated successfully');
                modal.style.display = "none"; // Close the modal
            } else {
                alert('Error updating money');
            }
        })
        .catch(error => console.log('Error:', error));
    }
</script>

</body>
</html>