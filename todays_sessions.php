<?php
session_name("teacher_session");
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'teacher') {
    die("Unauthorized access");
}

include 'db_connect.php';

$teacher_username = $_SESSION['username'];
$current_date = date("Y-m-d");

// Fetch teacher's name
$teacher_query = $conn->prepare("SELECT name FROM teachers WHERE username = ?");
$teacher_query->execute([$teacher_username]);
$teacher_data = $teacher_query->fetch(PDO::FETCH_ASSOC);

if (!$teacher_data) {
    die("Teacher not found.");
}

$teacher_name = $teacher_data['name'];

// Fetch today's sessions for the teacher
$query = $conn->prepare("SELECT DISTINCT subject_name, batch_code, period_number FROM attendance WHERE teacher_name = ? AND date = ?");
$query->execute([$teacher_name, $current_date]);
$sessions = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Today's Sessions</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/light-theme.css">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: #faf5ff;
            color: var(--text-color);
            line-height: 1.6;
            padding: 0;
            min-height: 100vh;
        }

        .container {
            max-width: 1300px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .dashboard-header {
            background: white; border-left: 4px solid var(--primary-color); color: var(--primary-color);
            padding: 40px 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(50%, -50%);
            z-index: 0;
        }

        .teacher-info {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .teacher-avatar {
            width: 80px;
            height: 80px;
            background-color: var(--secondary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 2px solid white;
        }

        .teacher-details h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .teacher-details span {
            font-size: 16px;
            opacity: 0.9;
            font-weight: 400;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .session-container {
            width: 100%;
            margin-top: 20px;
        }

        .session-card {
            background-color: white;
            border-radius: var(--card-radius);
            margin-bottom: 20px;
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            transition: var(--transition);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .session-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }

        .session-header {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            padding: 20px;
            align-items: center;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .session-header > div {
            padding: 10px;
            text-align: center;
        }

        .session-header-label {
            color: var(--text-light);
            font-size: 0.875rem;
            margin-bottom: 4px;
        }

        .session-header-value {
            font-weight: 500;
        }

        .download-btn {
            background: white; border: 1px solid var(--primary-color); 
            color: var(--primary-color);
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 16px;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .download-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.4);
        }

        details summary {
            padding: 20px;
            cursor: pointer;
            background: var(--secondary-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: var(--transition);
            font-weight: 500;
        }

        details summary:hover {
            background: #e9ecef;
        }

        .view-details {
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .view-details::after {
            content: '\f107';
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            transition: transform 0.3s ease;
        }

        details[open] .view-details::after {
            transform: rotate(180deg);
        }

        .attendance-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 0;
        }

        .attendance-table th,
        .attendance-table td {
            padding: 15px 20px;
            text-align: left;
        }

        .attendance-table th {
            font-weight: 500;
            color: var(--text-light);
            border-bottom: 1px solid rgba(167, 139, 250, 0.15);
        }

        .attendance-table td {
            border-bottom: 1px solid #f0f0f0;
        }

        .attendance-table tr:last-child td {
            border-bottom: none;
        }

        .attendance-table tbody tr {
            transition: var(--transition);
        }

        .attendance-table tbody tr:hover {
            background-color: #f8fafc;
        }

        .status-present {
            color: var(--success-color);
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .status-present::before {
            content: '\f058';
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
        }

        .status-absent {
            color: var(--danger-color);
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .status-absent::before {
            content: '\f057';
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 300px;
            padding: 30px;
            text-align: center;
        }

        .empty-icon {
            width: 70px;
            height: 70px;
            background-color: #faf5ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: var(--text-light);
            margin-bottom: 20px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 24px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 16px;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.2);
        }

        .button:hover {
            background-color: #3a56d4;
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(67, 97, 238, 0.3);
        }

        

        

        .navigation {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }

        @media (max-width: 1024px) {
            .session-header {
                grid-template-columns: 1fr 1fr;
                gap: 15px;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px 15px;
            }

            .dashboard-header {
                padding: 30px 20px;
            }

            .teacher-info {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .teacher-avatar {
                margin: 0 auto;
            }

            .teacher-details h1 {
                font-size: 24px;
            }

            .session-header {
                grid-template-columns: 1fr;
            }

            .session-header > div {
                text-align: left;
            }

            .download-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <div class="teacher-info">
                <div class="teacher-avatar">
                    <?php echo strtoupper(substr($teacher_name, 0, 1)); ?>
                </div>
                <div class="teacher-details">
                    <h1>Welcome, <?php echo htmlspecialchars($teacher_name); ?></h1>
                    <span><i class="fas fa-calendar-day"></i> Today's Sessions Dashboard</span>
                </div>
            </div>
        </div>

        <div class="session-container">
            <?php if (count($sessions) > 0) { ?>
                <?php foreach ($sessions as $row) { 
                    $safeSubject = urlencode($row['subject_name']);
                    $safeBatch = urlencode($row['batch_code']);
                    $safePeriod = urlencode($row['period_number']);
                ?>
                    <div class="session-card">
                        <div class="session-header">
                            <div>
                                <div class="session-header-label">Subject</div>
                                <div class="session-header-value"><?php echo htmlspecialchars($row['subject_name']); ?></div>
                            </div>
                            <div>
                                <div class="session-header-label">Batch</div>
                                <div class="session-header-value"><?php echo htmlspecialchars($row['batch_code']); ?></div>
                            </div>
                            <div>
                                <div class="session-header-label">Period</div>
                                <div class="session-header-value"><?php echo htmlspecialchars($row['period_number']); ?></div>
                            </div>
                            <div>
                                <a class="download-btn" href="download_attendance.php?subject=<?php echo $safeSubject; ?>&batch=<?php echo $safeBatch; ?>&period=<?php echo $safePeriod; ?>">
                                    <i class="fas fa-download"></i> Download
                                </a>
                            </div>
                        </div>

                        <details>
                            <summary>
                                <span class="view-details">View Attendance Details</span>
                            </summary>
                            <div>
                                <table class="attendance-table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Student Name</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $attendance_query = $conn->prepare("SELECT student_name, status FROM attendance WHERE teacher_name = ? AND date = ? AND subject_name = ? AND batch_code = ? AND period_number = ?");
                                    $attendance_query->execute([$teacher_name, $current_date, $row['subject_name'], $row['batch_code'], $row['period_number']]);
                                    $counter = 1;
                                    while ($attendance_row = $attendance_query->fetch(PDO::FETCH_ASSOC)) {
                                        $statusClass = (strtolower($attendance_row['status']) == 'present') ? 'status-present' : 'status-absent';
                                    ?>
                                        <tr>
                                            <td><?php echo $counter++; ?></td>
                                            <td><?php echo htmlspecialchars($attendance_row['student_name']); ?></td>
                                            <td class="<?php echo $statusClass; ?>">
                                                <?php echo htmlspecialchars($attendance_row['status']); ?>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </details>
                    </div>
                <?php } ?>
                <div class="navigation">
                    <a href="teacher_dashboard.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            <?php } else { ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h3>No Sessions Today</h3>
                    <p style="color: var(--text-light); margin-top: 10px; max-width: 500px;">
                        You don't have any scheduled sessions for today. Check your schedule or return to the dashboard.
                    </p>
                    <a href="welcome_teacher.php" class="btn-back" style="margin-top: 20px;">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            <?php } ?>
        </div>
    </div>
</body>
</html>

<?php // PDO closes automatically ?>