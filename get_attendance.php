<?php 
session_name("student_session");
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

$username = $_SESSION['username'];

// Fetch student name and batch code
$student_query = "SELECT name, batch_code FROM students WHERE username = ?";
$stmt = $conn->prepare($student_query);
$stmt->execute([$username]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    $student_name = $row['name'];
    $batch_code = $row['batch_code'];
} else {
    echo "Student not found!";
    exit;
}

if (!isset($_GET['subject'])) {
    echo "Subject not specified!";
    exit();
}

$subject_name = urldecode($_GET['subject']);

// ✅ Fetch attendance records including Period Number
$attendance_details_query = "SELECT `date`, `period_number`, `status` FROM `attendance` 
                             WHERE `batch_code` = ? AND `subject_name` = ? AND `student_name` = ? 
                             ORDER BY `date` ASC, `period_number` ASC";
$stmt = $conn->prepare($attendance_details_query);
$stmt->execute([$batch_code, $subject_name, $student_name]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($rows) === 0) {
    echo "No attendance records found!";
    exit();
}

// ✅ Prepare text content with Date, Period Number & Status
$file_content = "Student Name: $student_name\n";
$file_content .= "Subject Name: $subject_name\n";
$file_content .= "-----------------------------------------------\n";
$file_content .= "Date       | Period | Status\n";
$file_content .= "-----------------------------------------------\n";

foreach ($rows as $row) {
    $file_content .= "{$row['date']} |   {$row['period_number']}    | " . ucfirst($row['status']) . "\n";
}

// ✅ Set headers for TXT download
header("Content-Type: text/plain");
header("Content-Disposition: attachment; filename=\"" . preg_replace("/[^a-zA-Z0-9_-]/", "_", $student_name . "_" . $subject_name) . "_attendance.txt\"");

echo $file_content;
exit();
