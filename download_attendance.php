<?php
session_name("teacher_session");
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'teacher') {
    die("Unauthorized access");
}

include 'db_connect.php';

$subject = urldecode($_GET['subject']);
$batch = urldecode($_GET['batch']);
$period = urldecode($_GET['period']);
$current_date = date("Y-m-d");

// Fetch attendance details
$query = $conn->prepare("SELECT student_name, status FROM attendance WHERE subject_name = ? AND batch_code = ? AND period_number = ? AND date = ?");
$query->execute([$subject, $batch, $period, $current_date]);
$rows = $query->fetchAll(PDO::FETCH_ASSOC);

if (count($rows) == 0) {
    die("No attendance data found.");
}

$filename = "Attendance_{$subject}_{$batch}_Period{$period}.txt";
header('Content-Type: text/plain');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Format the header
$output = "========================================\n";
$output .= "         ATTENDANCE REPORT\n";
$output .= "========================================\n";
$output .= "Subject   : $subject\n";
$output .= "Batch     : $batch\n";
$output .= "Period    : $period\n";
$output .= "Date      : $current_date\n";
$output .= "========================================\n\n";

// Table Header
$output .= sprintf("%-25s | %-10s\n", "Student Name", "Status");
$output .= "----------------------------------------\n";

// Data Rows
foreach ($rows as $row) {
    $output .= sprintf("%-25s | %-10s\n", $row['student_name'], strtoupper($row['status']));
}

// Output the formatted text
echo $output;

// $conn = null; // PDO closes automatically
?>
