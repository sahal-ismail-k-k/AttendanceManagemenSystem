<?php
session_name("student_session");
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'student') {
    error_log("Unauthorized access attempt - Redirecting to login");
    exit("Unauthorized access!");
}

include 'db_connect.php';

date_default_timezone_set("Asia/Kolkata");

$username = $_SESSION['username'];

// Fetch student name
$student_query = "SELECT name FROM students WHERE username = ?";
$stmt = $conn->prepare($student_query);
$stmt->execute([$username]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    $student_name = $row['name'];
} else {
    error_log("Student not found for username: " . $username);
    die("Student not found!");
}

// Get form input
$selected_date = $_POST['date'] ?? "";
$selected_subject = $_POST['subject'] ?? "";
$selected_teacher = $_POST['teacher'] ?? "";
$selected_period = (int)($_POST['period'] ?? 0);

if (empty($selected_date) || empty($selected_subject) || empty($selected_teacher) || $selected_period <= 0) {
    die("Invalid input. Please check your selection.");
}

// Fetch teacher's phone number
$phone_query = "SELECT phone_number FROM teachers WHERE name = ?";
$stmt = $conn->prepare($phone_query);
$stmt->execute([$selected_teacher]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$teacher_phone = $row ? $row['phone_number'] : "";

// Fetch attendance status
$status_query = "SELECT status FROM attendance 
                WHERE subject_name = ? 
                AND teacher_name = ? 
                AND date = ? 
                AND period_number = ? 
                AND student_name = ?";
$stmt = $conn->prepare($status_query);
$stmt->execute([$selected_subject, $selected_teacher, $selected_date, $selected_period, $student_name]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$attendance_status = "Not Found";

if ($row) {
    $attendance_status = $row['status'];
}

// Debugging
error_log("Selected Date: $selected_date, Subject: $selected_subject, Teacher: $selected_teacher, Period: $selected_period, Status: $attendance_status");

// Redirect to WhatsApp if absent
if ($attendance_status === "absent" && !empty($teacher_phone)) {
    $message = "On " . $selected_date . ", my attendance was wrongly marked for " . $selected_subject . " during the " . $selected_period . "th period. please do correct it" ;
    $encoded_message = urlencode($message);
    $whatsapp_url = "https://wa.me/" . htmlspecialchars($teacher_phone) . "?text=" . $encoded_message;
    header("Location: $whatsapp_url");
    exit();
} elseif ($attendance_status === "Not Found" || empty($attendance_status)) {
    echo "<script>alert('Attendance data not found or incorrect selection. Please check your inputs.'); window.history.back();</script>";
}

?>
