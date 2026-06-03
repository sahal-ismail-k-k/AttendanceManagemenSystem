<?php
session_name("teacher_session");
session_start();

// Ensure user is logged in and has 'teacher' role
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit();
}

// Database connection
include 'db_connect.php';

$username = $_SESSION['username'];

// Fetch teacher data
$stmt = $conn->prepare("SELECT * FROM teachers WHERE username = ?");
$stmt->execute([$username]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

// Get current date
$today = date("Y-m-d");

// Get count of today's sessions for this teacher
$stmt = $conn->prepare("SELECT COUNT(*) FROM attendance_session
                        WHERE username = ? AND session_date = ?");
$stmt->execute([$teacher['username'], $today]);
$today_sessions = $stmt->fetchColumn();

// Get total subjects taught by the teacher
$stmt = $conn->prepare("SELECT subjects FROM teachers WHERE username = ?");
$stmt->execute([$teacher['username']]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

$total_subjects = 0;
$subject_list = [];

if (!empty($data['subjects'])) {
    // Convert comma-separated subjects into an array and count unique values
    $subject_list = array_unique(array_filter(array_map('trim', explode(',', $data['subjects']))));
    $total_subjects = count($subject_list);
}

// Get pending attendance entries
$pending_attendance = 0;

if (!empty($subject_list)) {
    // Create placeholders for the IN clause (?,?,?)
    $placeholders = implode(',', array_fill(0, count($subject_list), '?'));

    // Updated query to fetch pending attendance from the correct table
    $sql = "SELECT COUNT(*) FROM attendance 
            WHERE date = ? 
            AND status = 'absent' 
            AND subject_name IN ($placeholders)";

    // Prepare the SQL statement
    $stmt = $conn->prepare($sql);
    
    // Bind parameters dynamically (PDO makes this easy)
    $params = array_merge([$today], $subject_list);
    $stmt->execute($params);

    // Fetch result
    $pending_attendance = $stmt->fetchColumn();
}

// PDO closes automatically or explicitly
$conn = null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Welcome</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/light-theme.css">
    <style>
        body { 
            padding: 32px; 
            background-color: var(--background-color); 
        }
        .welcome-container {
            padding: 40px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 32px;
            border-left: 4px solid var(--primary-color);
        }
        .date-display {
            background: var(--secondary-color);
            padding: 8px 16px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--primary-color);
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
        }
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
        }
        .stat-card {
            background: white;
            padding: 24px;
            border-radius: var(--border-radius);
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            border-color: var(--primary-light);
            box-shadow: var(--box-shadow);
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        .count {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-color);
        }
        .stat-label {
            font-size: 0.875rem;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }
    </style>
</head>
<body>
    <div class="welcome-container fade-in">
        <div class="date-display">
            <i class="fas fa-calendar-day"></i> Academic Session: <?php echo date("l, F j, Y"); ?>
        </div>
        <h2 style="color: var(--primary-color); margin-bottom: 8px; font-weight: 700;">Faculty Portal: <?php echo htmlspecialchars($teacher['name']); ?></h2>
        <p style="color: var(--text-muted); font-size: 1.1rem; max-width: 800px; margin-bottom: 32px;">Verified Faculty Management System. Access and manage your instructional responsibilities.</p>
        
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                    <i class="fas fa-book-reader"></i>
                </div>
                <div>
                    <div class="stat-label">Assigned Subjects</div>
                    <div class="count"><?php echo $total_subjects; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <div class="stat-label">Today's Sessions</div>
                    <div class="count"><?php echo $today_sessions; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div>
                    <div class="stat-label">Pending Records</div>
                    <div class="count"><?php echo $pending_attendance; ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card fade-in">
        <h3 style="margin-bottom: 16px; font-weight: 600;">Academic Operations</h3>
        <p style="margin-bottom: 24px; color: var(--text-muted)">Institutional tools for classroom management and student reporting.</p>
        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <button class="btn btn-primary" onclick="parent.document.querySelector('a[href=\'take_attendance.php\']').click()">
                <i class="fas fa-clipboard-check"></i> Mark Attendance
            </button>
            <button class="btn-back" onclick="parent.document.querySelector('a[href=\'view_attendancet.php\']').click()">
                <i class="fas fa-chart-line"></i> Performance Reports
            </button>
            <button class="btn-back" onclick="parent.document.querySelector('a[href=\'todays_sessions.php\']').click()">
                <i class="fas fa-calendar-day"></i> Daily Schedule
            </button>
            <button class="btn-back" onclick="parent.document.querySelector('a[href=\'add_notification.php\']').click()">
                <i class="fas fa-bell"></i> Send Bulletin
            </button>
        </div>
    </div>
</body>
</html>