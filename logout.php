<?php
// Determine which role is logging out based on which session is active
$session_names = ["admin_session", "teacher_session", "student_session"];
$logged_out = false;

foreach ($session_names as $name) {
    session_name($name);
    session_start();
    
    // Only destroy if this session has a username (i.e., it's actually logged in)
    if (isset($_SESSION['username'])) {
        session_unset();
        session_destroy();
        $logged_out = true;
        // Break if we want to logout only the "first" found active session
        // Or remove break to logout all (user choice)
        // I'll keep it role-flexible but for now let's just use the current one.
    }
    
    // Crucial: close session to allow next session_start in loop to work if needed
    session_write_close(); 
}

header("Location: login.php");
exit();
?>
