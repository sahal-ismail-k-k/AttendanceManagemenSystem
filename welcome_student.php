<?php
session_name("student_session");
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

// Database connection
include 'db_connect.php';

// Get student username
$username = $_SESSION['username'];

// Fetch student data
// Fetch student data
$stmt = $conn->prepare("SELECT * FROM students WHERE username=?");
$stmt->execute([$username]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch active notifications
$now = date("Y-m-d H:i:s");
$notif_query = "SELECT message FROM notifications WHERE expiry_time > ?";

$stmt = $conn->prepare($notif_query);
$stmt->execute([$now]);

$notifications = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $notifications[] = $row['message'];
}

// Get attendance statistics - updating to use student_name instead of student_id
$attendance_query = "SELECT 
                        COUNT(*) as total_classes,
                        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
                        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count
                     FROM attendance 
                     WHERE student_name = ?";
$stmt = $conn->prepare($attendance_query);
$stmt->execute([$student['name']]);
$attendance_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate attendance percentage
$attendance_percentage = 0;
if ($attendance_stats['total_classes'] > 0) {
    $attendance_percentage = round(($attendance_stats['present_count'] / $attendance_stats['total_classes']) * 100);
}

// Get subject count for the student's batch
$batch_query = "SELECT COUNT(*) as subject_count FROM batch_subjects WHERE batch_code = ?";
$stmt = $conn->prepare($batch_query);
$stmt->execute([$student['batch_code']]);
$subject_count = $stmt->fetchColumn();

// PDO closes automatically
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Welcome</title>
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
        .notification-container {
            margin-bottom: 32px;
            background: #fffbeb;
            border-radius: var(--border-radius);
            overflow: hidden;
            border: 1px solid #fde68a;
            border-left: 4px solid var(--warning-color);
        }
        .notification-header {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            background-color: #fef3c7;
            border-bottom: 1px solid #fde68a;
        }
        .notification-title {
            font-weight: 700;
            color: #92400e;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .notification-strip {
            display: flex;
            padding: 12px 16px;
            animation: scroll-left 45s linear infinite;
        }
        .notification-content {
            white-space: nowrap;
            padding-right: 3rem;
            color: #92400e;
            font-weight: 600;
            display: flex;
            align-items: center;
            font-size: 0.9375rem;
        }
        .notification-dot {
            width: 8px;
            height: 8px;
            background-color: var(--warning-color);
            border-radius: 50%;
            margin-right: 12px;
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
        @keyframes scroll-left {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }
    </style>
</head>
<body>
    <?php if (!empty($notifications)): ?>
    <div class="notification-container fade-in">
        <div class="notification-header">
            <div class="notification-title">
                <i class="fas fa-bullhorn"></i> Institutional Bulletins
            </div>
            <div class="badge badge-warning" style="margin-left: 12px;"><?php echo count($notifications); ?> New</div>
        </div>
        <div class="notification-strip">
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-content">
                    <div class="notification-dot"></div>
                    <span><?php echo htmlspecialchars($notification); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="welcome-container fade-in">
        <h2 style="color: var(--primary-color); margin-bottom: 8px; font-weight: 700;">Student Academic Portal</h2>
        <p style="color: var(--text-muted); font-size: 1.1rem; max-width: 800px; margin-bottom: 32px;">Verified Student Access. Review your enrollment details, subject assignments, and cumulative attendance reports.</p>
        
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                    <i class="fas fa-percentage"></i>
                </div>
                <div>
                    <div class="stat-label">Cumulative Attendance</div>
                    <div class="count"><?php echo $attendance_percentage; ?>%</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                    <i class="fas fa-book"></i>
                </div>
                <div>
                    <div class="stat-label">Enrolled Subjects</div>
                    <div class="count"><?php echo $subject_count; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div>
                    <div class="stat-label">Classes Recorded</div>
                    <div class="count"><?php echo $attendance_stats['total_classes']; ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;" class="fade-in">
        <div class="card">
            <h3 style="margin-bottom: 16px; font-weight: 600;">Student Profile</h3>
            <div style="margin-top: 16px;">
                <div style="display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f1f5f9;">
                    <span style="color: var(--text-muted); font-weight: 500;">Legal Name</span>
                    <span style="font-weight: 600;"><?php echo htmlspecialchars($student['name']); ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f1f5f9;">
                    <span style="color: var(--text-muted); font-weight: 500;">Batch Code</span>
                    <span style="font-weight: 600;"><?php echo htmlspecialchars($student['batch_code']); ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 12px 0;">
                    <span style="color: var(--text-muted); font-weight: 500;">Institutional Role</span>
                    <span class="badge badge-success">Verified Student</span>
                </div>
            </div>
        </div>

        <div class="card">
            <h3 style="margin-bottom: 16px; font-weight: 600;">Quick Academic Links</h3>
            <p style="margin-bottom: 20px; color: var(--text-muted)">Institutional tools for student tracking.</p>
            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <button class="btn btn-primary" onclick="parent.document.querySelector('a[href=\'view_attendance.php\']').click()">
                    <i class="fas fa-chart-bar"></i> Full Report
                </button>
                <button class="btn-back" onclick="parent.document.querySelector('a[href=\'change_passwords.php\']').click()">
                    <i class="fas fa-key"></i> Security Settings
                </button>
            </div>
        </div>
    </div>

    <script>
        function updateDateTime() {
            const now = new Date();
            const options = { 
                weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
                hour: '2-digit', minute: '2-digit', second: '2-digit'
            };
            if(document.getElementById('datetime')) {
                document.getElementById('datetime').textContent = now.toLocaleDateString('en-US', options);
            }
        }
        updateDateTime();
        setInterval(updateDateTime, 1000);
    </script>
</body>
</html>