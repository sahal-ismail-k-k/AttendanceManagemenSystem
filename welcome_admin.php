<?php
session_name("admin_session");
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Database connection
include 'db_connect.php';

// Get admin username
$username = $_SESSION['username'];

// Get total counts for dashboard stats
// Get total counts for dashboard stats
$teacherCount = $conn->query("SELECT COUNT(*) FROM teachers")->fetchColumn();
$studentCount = $conn->query("SELECT COUNT(*) FROM students")->fetchColumn();
$batchCount = $conn->query("SELECT COUNT(*) FROM batch_gps")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Welcome</title>
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
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-top: 32px;
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
            background: var(--secondary-color);
            color: var(--primary-color);
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
        .date-time {
            margin-top: 40px;
            color: var(--text-muted);
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
    </style>
</head>
<body>
    <div class="welcome-container fade-in">
        <h2 style="color: var(--primary-color); margin-bottom: 8px; font-weight: 700;">Institutional Administration</h2>
        <p style="color: var(--text-muted); font-size: 1.1rem; max-width: 800px;">Centrally manage faculty, student enrollment, and academic batch configurations for the Attendance Management System.</p>
        
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                <div>
                    <div class="stat-label">Faculty Members</div>
                    <div class="count"><?php echo $teacherCount; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
                <div>
                    <div class="stat-label">Total Students</div>
                    <div class="count"><?php echo $studentCount; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
                <div>
                    <div class="stat-label">Active Batches</div>
                    <div class="count"><?php echo $batchCount; ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card fade-in">
        <h3 style="margin-bottom: 16px; font-weight: 600;">System Operations</h3>
        <p style="margin-bottom: 24px; color: var(--text-muted)">Access core administrative modules.</p>
        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <a href="manage_teachers.php" class="btn btn-primary">
                <i class="fas fa-user-tie"></i> Faculty Registry
            </a>
            <a href="manage_students.php" class="btn-back">
                <i class="fas fa-users"></i> Student Registry
            </a>
            <a href="manage_batches.php" class="btn-back">
                <i class="fas fa-table"></i> Batch Setup
            </a>
        </div>
    </div>
    
    <div class="date-time fade-in">
        <i class="fas fa-calendar-alt"></i> Administrative Session: <span id="datetime"></span>
    </div>

    <script>
        function updateDateTime() {
            const now = new Date();
            const options = { 
                weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
                hour: '2-digit', minute: '2-digit', second: '2-digit'
            };
            document.getElementById('datetime').textContent = now.toLocaleDateString('en-US', options);
        }
        updateDateTime();
        setInterval(updateDateTime, 1000);
    </script>
</body>
</html>

// PDO closes automatically