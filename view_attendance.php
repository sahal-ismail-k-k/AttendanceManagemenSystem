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

// Fetch subjects for the batch
$subjects_query = "SELECT subject_name FROM batch_subjects WHERE batch_code = ?";
$stmt = $conn->prepare($subjects_query);
$stmt->execute([$batch_code]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch attendance percentage
$attendance_data = [];
foreach ($subjects as $subject) {
    $subject_name = $subject['subject_name'];

    // Total classes conducted
    $total_classes_query = "SELECT COUNT(*) FROM attendance_session WHERE LOWER(batch_code) = LOWER(?) AND LOWER(subject_name) = LOWER(?)";
    $stmt = $conn->prepare($total_classes_query);
    $stmt->execute([$batch_code, $subject_name]);
    $total_classes = $stmt->fetchColumn();

    // Classes attended by student
    $attended_classes_query = "SELECT COUNT(*) FROM attendance WHERE LOWER(batch_code) = LOWER(?) AND LOWER(subject_name) = LOWER(?) AND LOWER(student_name) = LOWER(?) AND status = 'present'";
    $stmt = $conn->prepare($attended_classes_query);
    $stmt->execute([$batch_code, $subject_name, $student_name]);
    $attended_classes = $stmt->fetchColumn();

    // Calculate percentage
    $attendance_percentage = ($total_classes > 0) ? round(($attended_classes / $total_classes) * 100, 2) : 0;

    $attendance_data[] = [
        'subject_name' => $subject_name,
        'attendance_percentage' => $attendance_percentage,
        'total_classes' => $total_classes,
        'attended_classes' => $attended_classes
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Attendance Dashboard</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/light-theme.css">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .dashboard-header {
            background: white; border-left: 4px solid var(--primary-color); 
            color: var(--primary-color);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 25px;
        }

        .student-info {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .student-avatar {
            width: 65px;
            height: 65px;
            background: var(--secondary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-size: 26px;
            font-weight: 600;
            border: 2px solid white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .student-details h1 {
            font-size: 24px;
            color: var(--primary-color);
            margin-bottom: 5px;
            font-weight: 600;
        }

        .batch-code {
            color: var(--text-muted);
            font-size: 16px;
        }

        .attendance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .subject-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: 0 8px 24px rgba(167, 139, 250, 0.12);
            transition: all 0.3s ease;
            border: 1px solid rgba(167, 139, 250, 0.1);
        }

        .subject-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 12px 32px rgba(167, 139, 250, 0.18);
        }

        .subject-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            color: var(--primary-color);
        }

        .attendance-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .stat {
            text-align: center;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
        }

        .stat-label {
            font-size: 14px;
            color: #666;
        }

        .attendance-bar {
            height: 8px;
            background-color: #eee;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 15px;
        }

        .attendance-progress {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .download-button {
            display: inline-block;
            padding: 10px 20px;
            background: white; 
            border: 1px solid var(--primary-color); 
            color: var(--primary-color);
            text-decoration: none;
            border-radius: var(--border-radius);
            transition: all 0.3s ease;
            font-weight: 500;
            box-shadow: var(--box-shadow);
        }

        .download-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(167, 139, 250, 0.35);
        }

        .download-button i {
            margin-right: 5px;
        }

        

        

        

        @media (max-width: 768px) {
            .attendance-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="welcome_student.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Go Back
        </a>

        <div class="dashboard-header">
            <div class="student-info">
                <div class="student-avatar">
                    <?php echo strtoupper(substr($student_name, 0, 1)); ?>
                </div>
                <div class="student-details">
                    <h1><?php echo htmlspecialchars($student_name); ?></h1>
                    <span class="batch-code">Batch: <?php echo htmlspecialchars($batch_code); ?></span>
                </div>
            </div>
        </div>

        <div class="attendance-grid">
            <?php foreach ($attendance_data as $data) { 
                $percentage = $data['attendance_percentage'];
                $color = $percentage >= 75 ? 'var(--success-color)' : 
                         ($percentage >= 60 ? 'var(--warning-color)' : 'var(--danger-color)');
            ?>
                <div class="subject-card">
                    <div class="subject-name"><?php echo htmlspecialchars($data['subject_name']); ?></div>
                    
                    <div class="attendance-stats">
                        <div class="stat">
                            <div class="stat-value"><?php echo $data['attended_classes']; ?></div>
                            <div class="stat-label">Classes Attended</div>
                        </div>
                        <div class="stat">
                            <div class="stat-value"><?php echo $data['total_classes']; ?></div>
                            <div class="stat-label">Total Classes</div>
                        </div>
                        <div class="stat">
                            <div class="stat-value"><?php echo $percentage; ?>%</div>
                            <div class="stat-label">Attendance</div>
                        </div>
                    </div>
                    
                    <div class="attendance-bar">
                        <div class="attendance-progress" style="width: <?php echo $percentage; ?>%; background-color: <?php echo $color; ?>"></div>
                    </div>

                    <a href="get_attendance.php?subject=<?php echo urlencode($data['subject_name']); ?>" class="download-button">
                        <i class="fas fa-download"></i> Download Report
                    </a>
                </div>
            <?php } ?>
        </div>
    </div>
</body>
</html>