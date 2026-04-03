<?php
// Connect to your database
$mysqli = new mysqli("localhost", "root", "", "health_insurance_system");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Query the transactions and join with users table
$sql = "
    SELECT 
        t.transaction_id, 
        t.card_id, 
        t.transaction_type, 
        t.amount, 
        t.transaction_date, 
        t.status, 
        t.performed_by, 
        u.first_name, 
        u.last_name, 
        u.user_type 
    FROM transactions t
    JOIN users u ON t.performed_by = u.user_id
    ORDER BY t.transaction_date DESC 
    LIMIT 20
";

$result = $mysqli->query($sql);

// Render as HTML table
if ($result->num_rows > 0) {
    echo '<table border="1" width="100%" cellpadding="10">
            <tr>
                <th>ID</th>
                <th>Card ID</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Date</th>
                <th>Status</th>
                <th>Performed By</th>
            </tr>';
    while($row = $result->fetch_assoc()) {
        echo '<tr>
                <td>' . $row["transaction_id"] . '</td>
                <td>' . $row["card_id"] . '</td>
                <td>' . ucfirst($row["transaction_type"]) . '</td>
                <td>' . $row["amount"] . '</td>
                <td>' . $row["transaction_date"] . '</td>
                <td>' . ucfirst($row["status"]) . '</td>
                <td>' . $row["first_name"] . ' ' . $row["last_name"] . ' (' . ucfirst($row["user_type"]) . ')</td>
              </tr>';
    }
    echo '</table>';
} else {
    echo "No transactions found.";
}

$mysqli->close();
?>
