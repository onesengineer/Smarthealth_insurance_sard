<?php
// Database configuration
define('DB_SERVER', 'localhost'); // Database host
define('DB_USERNAME', 'root'); // Your database username
define('DB_PASSWORD', ''); // Your database password
define('DB_DATABASE', 'health_insurance_system'); // Your database name

// Create connection
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// You can use $conn to interact with your database in other PHP files
?>
