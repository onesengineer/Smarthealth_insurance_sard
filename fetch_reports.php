<?php
$servername = "localhost";
$username = "root"; // or your DB username
$password = "";     // or your DB password
$dbname = "health_insurance_system";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$query = "SELECT r.*, u.first_name, u.last_name FROM reports r JOIN users u ON r.created_by = u.user_id ORDER BY r.created_at DESC";
$result = mysqli_query($conn, $query);

echo '<table style="width:100%; border-collapse: collapse;">
    <thead>
        <tr>
            <th style="background:#f5f7fa; padding:12px; border-bottom:1px solid #ddd;">Report Name</th>
            <th style="background:#f5f7fa; padding:12px; border-bottom:1px solid #ddd;">Type</th>
            <th style="background:#f5f7fa; padding:12px; border-bottom:1px solid #ddd;">Created By</th>
            <th style="background:#f5f7fa; padding:12px; border-bottom:1px solid #ddd;">Date</th>
        </tr>
    </thead>
    <tbody>';

while($row = mysqli_fetch_assoc($result)){
    echo '<tr>
            <td style="padding:12px; border-bottom:1px solid #eee;">'.htmlspecialchars($row['report_name']).'</td>
            <td style="padding:12px; border-bottom:1px solid #eee;">'.htmlspecialchars($row['report_type']).'</td>
            <td style="padding:12px; border-bottom:1px solid #eee;">'.htmlspecialchars($row['first_name'] . ' ' . $row['last_name']).'</td>
            <td style="padding:12px; border-bottom:1px solid #eee;">'.htmlspecialchars($row['created_at']).'</td>
        </tr>';
}

echo '</tbody></table>';

?>
