<?php
session_name("teacher_session");
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'teacher') {
    die("Unauthorized access");
}

include 'db_connect.php';

date_default_timezone_set("Asia/Kolkata");
$teacher_username = $_SESSION['username'];
$current_date = date("Y-m-d");
$current_time = date("H:i:s");
$dayOfWeek = date("N"); // 1 = Monday, 7 = Sunday

// Function to determine the current period
function getCurrentPeriod($dayOfWeek, $time) {
    if ($dayOfWeek >= 1 && $dayOfWeek <= 4) { // Monday to Thursday
        if ($time >= '09:00' && $time < '09:50') return 1;
        if ($time >= '09:50' && $time < '10:40') return 2;
        if ($time >= '10:40' && $time < '11:40') return 3;
        if ($time >= '11:40' && $time < '12:30') return 4;
        if ($time >= '13:15' && $time < '14:05') return 5;
        if ($time >= '14:05' && $time < '14:55') return 6;
        if ($time >= '15:05' && $time < '15:55') return 7;
    } elseif ($dayOfWeek == 5) { // Friday
        if ($time >= '09:00' && $time < '09:50') return 1;
        if ($time >= '09:50' && $time < '10:35') return 2;
        if ($time >= '10:40' && $time < '11:40') return 3;
        if ($time >= '11:40' && $time < '12:30') return 4;
        if ($time >= '14:00' && $time < '15:00') return 5;
        if ($time >= '15:00' && $time < '16:00') return 6;
    }
    return null; // No active period
}

$current_period = getCurrentPeriod($dayOfWeek, $current_time);

// If no period is found, show no active session
if ($current_period === null) {
    die("<h2>No active session</h2>");
}

// Check if the logged-in teacher has an active session in the `attendance` table
$query = $conn->prepare("
    SELECT student_name, status, batch_code, subject_name 
    FROM attendance 
    WHERE teacher_name = (SELECT name FROM teachers WHERE username = ?) 
    AND date = ? 
    AND period_number = ?
");

if (!$query) {
    die("SQL Error.");
}

$query->execute([$teacher_username, $current_date, $current_period]);
$rows = $query->fetchAll(PDO::FETCH_ASSOC);

// If no records found, say "No active session"
if (count($rows) === 0) {
    die("<h2>No active session</h2>");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Session</title>
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid black; padding: 8px; text-align: center; }
    </style>
</head>
<body>
    <h2>Active Session for Period <?php echo $current_period; ?> on <?php echo $current_date; ?></h2>
    <table>
        <tr>
            <th>Student Name</th>
            <th>Batch Code</th>
            <th>Subject</th>
            <th>Status</th>
        </tr>
        <?php foreach ($rows as $row) { ?>
            <tr>
                <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                <td><?php echo htmlspecialchars($row['batch_code']); ?></td>
                <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                <td><?php echo htmlspecialchars($row['status']); ?></td>
            </tr>
        <?php } ?>
    </table>
</body>
</html>

<?php // PDO closes automatically ?>
