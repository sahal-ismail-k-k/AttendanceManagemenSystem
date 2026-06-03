<?php
session_name("teacher_session");
session_start();
include 'db_connect.php';
date_default_timezone_set("Asia/Kolkata");

// Fetch batch codes and subjects
// Fetch batch codes and subjects
$batchSubjectsQuery = "SELECT batch_code, subject_name FROM batch_subjects";
$batchSubjectsStmt = $conn->query($batchSubjectsQuery);

$teacher_username = $_SESSION['username']; 
$error_message = "";
$success_message = "";

$teacher_query = $conn->prepare("SELECT name FROM teachers WHERE username = ?");
$teacher_query->execute([$teacher_username]);
$teacher_data = $teacher_query->fetch(PDO::FETCH_ASSOC);

if ($teacher_data) {
    $teacher_name = $teacher_data['name'];
} else {
    die("Teacher not found.");
}

function getPeriod($startTime, $sessionDate) {
    $dayOfWeek = date('N', strtotime($sessionDate));
    $time = date('H:i', strtotime($startTime));

    if ($dayOfWeek >= 1 && $dayOfWeek <= 4) {
        if ($time >= '09:00' && $time < '09:50') return 1;
        if ($time >= '09:50' && $time < '10:40') return 2;
        if ($time >= '10:50' && $time < '11:40') return 3;
        if ($time >= '11:40' && $time < '12:30') return 4;
        if ($time >= '13:00' && $time < '14:05') return 5;
        if ($time >= '14:05' && $time < '14:55') return 6;
        if ($time >= '15:05' && $time < '15:55') return 7;
    } elseif ($dayOfWeek == 7) {
        if ($time >= '20:00' && $time < '20:05') return 1;
        if ($time >= '21:20' && $time < '21:25') return 2;
        if ($time >= '21:28' && $time < '21:55') return 3;
        if ($time >= '10:00' && $time < '11:00') return 4;
        if ($time >= '17:00' && $time < '18:00') return 5;
        if ($time >= '23:00' && $time < '24:00') return 6;
    }
    return 0;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $batch_code = $_POST['batch_code'];
    $subject_name = $_POST['subject_name'];
    $session_start = date("Y-m-d H:i:s");
    $session_date = date("Y-m-d");
    $session_end = date("Y-m-d H:i:s", strtotime($session_start . ' +10 minutes'));
    $period = getPeriod($session_start, $session_date);

    if ($period == 0) {
        $error_message = "You cannot start a session outside the scheduled class periods.";
    } else {
        // Check if another teacher has already started a session for the same batch & period
        $checkSessionStmt = $conn->prepare("SELECT COUNT(*) FROM attendance_session 
                      WHERE LOWER(batch_code) = LOWER(?) 
                      AND period = ? 
                      AND session_date = ?");
        $checkSessionStmt->execute([$batch_code, $period, $session_date]);
        
        if ($checkSessionStmt->fetchColumn() > 0) {
            $error_message = "You are in the wrong class! Another session is already active for this batch and period.";
        } else {
            // Check if the teacher has already initiated a session for this period
            $checkTeacherSessionStmt = $conn->prepare("SELECT COUNT(*) FROM attendance_session 
                                       WHERE username = ? 
                                       AND period = ? 
                                       AND session_date = ? 
                                       AND session_end > ?");
            $checkTeacherSessionStmt->execute([$teacher_username, $period, $session_date, $session_start]);

            if ($checkTeacherSessionStmt->fetchColumn() > 0) {
                $error_message = "You have already initiated a session for this period.";
            } else {
                // Insert new session
                $insertStmt = $conn->prepare("INSERT INTO attendance_session (batch_code, subject_name, username, session_start, session_end, session_date, period) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
                
                if ($insertStmt->execute([$batch_code, $subject_name, $teacher_username, $session_start, $session_end, $session_date, $period])) {
                    // Get students in the selected batch
                    $studentsStmt = $conn->prepare("SELECT name FROM students WHERE batch_code = ?");
                    $studentsStmt->execute([$batch_code]);
                    $students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);

                    if (count($students) > 0) {
                        // Prepare insert statement outside the loop for efficiency
                        $attendanceStmt = $conn->prepare("INSERT INTO attendance (student_name, teacher_name, subject_name, batch_code, period_number, date, status) 
                                          VALUES (?, ?, ?, ?, ?, ?, 'absent')");
                        
                        foreach ($students as $student) {
                            $attendanceStmt->execute([$student['name'], $teacher_name, $subject_name, $batch_code, $period, $session_date]);
                        }
                    }
                    $success_message = "Attendance session started successfully, and all students are marked as absent.";
                } else {
                    $error_message = "Error initiating session.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Attendance | Teacher Dashboard</title>
    
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
            background: var(--background-color);
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
            box-shadow: var(--box-shadow);
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

        .section-title {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 25px;
            padding-left: 15px;
            border-left: 4px solid var(--primary-color);
            color: var(--text-color);
        }

        .attendance-form {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(30px);
            border-radius: 24px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(167, 139, 250, 0.12);
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        .attendance-form:hover {
            transform: translateY(-3px);
            box-shadow: 0 25px 70px rgba(167, 139, 250, 0.15);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 500;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group label i {
            color: var(--primary-color);
        }

        select {
            width: 100%;
            padding: 14px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 16px;
            font-size: 16px;
            transition: var(--transition);
            background-color: white;
            color: var(--text-color);
            font-family: 'Inter', sans-serif;
        }

        select:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 24px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            text-decoration: none;
            border-radius: 16px;
            transition: var(--transition);
            width: 100%;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 15px;
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
        }

        .button:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 12px 28px rgba(99, 102, 241, 0.4);
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
        }

        

        

        .message {
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .message.error {
            background-color: #fee2e2;
            color: var(--danger-color);
            border: 1px solid #fecaca;
        }

        .message.success {
            background-color: #dcfce7;
            color: var(--success-color);
            border: 1px solid #bbf7d0;
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

            .attendance-form {
                padding: 25px 20px;
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
                    <span><i class="fas fa-user-check"></i> Take Attendance</span>
                </div>
            </div>
        </div>

        <h2 class="section-title">Start Attendance Session</h2>

        <div class="attendance-form">
            <?php if (!empty($error_message)): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php elseif (!empty($success_message)): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label for="batch_code">
                        <i class="fas fa-users"></i>
                        Select Batch Code
                    </label>
                    <select name="batch_code" id="batch_code" required>
                        <option value="">Choose a batch...</option>
                        <?php 
                        // PDO doesn't have data_seek. We must execute the query again or store all rows.
                        // We already have $batchSubjectsStmt (PDOStatement) from earlier.
                        // But we might have iterated it if we did? No, checks above are on other queries.
                        // Wait, we didn't iterate $batchSubjectsStmt yet.
                        // Better to fetchAll() at the top and iterate the array here.
                        $batchSubjects = $batchSubjectsStmt->fetchAll(PDO::FETCH_ASSOC);
                        $uniqueBatches = [];
                        foreach ($batchSubjects as $row) {
                            if (!in_array($row['batch_code'], $uniqueBatches)) {
                                $uniqueBatches[] = $row['batch_code'];
                        ?>
                            <option value="<?php echo htmlspecialchars($row['batch_code']); ?>">
                                <?php echo htmlspecialchars($row['batch_code']); ?>
                            </option>
                        <?php 
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="subject_name">
                        <i class="fas fa-book"></i>
                        Select Subject
                    </label>
                    <select name="subject_name" id="subject_name" required>
                        <option value="">Choose a subject...</option>
                        <?php
                        // Reuse the array fetched earlier
                        $uniqueSubjects = [];
                        foreach ($batchSubjects as $row) {
                            if (!in_array($row['subject_name'], $uniqueSubjects)) {
                                $uniqueSubjects[] = $row['subject_name'];
                        ?>
                            <option value="<?php echo htmlspecialchars($row['subject_name']); ?>">
                                <?php echo htmlspecialchars($row['subject_name']); ?>
                            </option>
                        <?php 
                            }
                        }
                        ?>
                    </select>
                </div>

                <button type="submit" class="button">
                    <i class="fas fa-clock"></i>
                    Start Attendance Session
                </button>

                <a href="welcome_teacher.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
            </form>
        </div>
    </div>
</body>
</html>