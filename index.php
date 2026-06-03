<?php
// Check for existing sessions to allow multi-tab seamless access
$session_names = ["admin_session", "teacher_session", "student_session"];
foreach ($session_names as $s_name) {
    session_name($s_name);
    session_start();
    if (isset($_SESSION['username']) && isset($_SESSION['role'])) {
        $role = $_SESSION['role'];
        if ($role == 'admin') header("Location: admin_dashboard.php");
        elseif ($role == 'teacher') header("Location: teacher_dashboard.php");
        elseif ($role == 'student') header("Location: student_dashboard.php");
        exit();
    }
    session_write_close();
}

header("Location: login.php");
exit();
?>
