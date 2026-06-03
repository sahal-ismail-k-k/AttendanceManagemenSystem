<?php
session_name("student_session");
session_start();

include 'db_connect.php';

$username = $_SESSION['username'];

// Fetch student name
$student_query = "SELECT name FROM students WHERE username = ?";
$stmt = $conn->prepare($student_query);
$stmt->execute([$username]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$student_name = $row ? $row['name'] : "";

if (!$student_name) {
    die("Error: Student not found.");
}

// Get input from AJAX
$date = $_POST['date'] ?? "";
$subject = $_POST['subject'] ?? "";
$teacher = $_POST['teacher'] ?? "";
$period = (int)($_POST['period'] ?? 0);

if (empty($date) || empty($subject) || empty($teacher) || $period <= 0) {
    die("Invalid Input");
}

// Fetch attendance status
$status_query = "SELECT status FROM attendance 
                WHERE subject_name = ? 
                AND teacher_name = ? 
                AND date = ? 
                AND period_number = ? 
                AND student_name = ?";
$stmt = $conn->prepare($status_query);
$stmt->execute([$subject, $teacher, $date, $period, $student_name]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    echo $row['status'];
} else {
    echo "Not Found";
}
?>
