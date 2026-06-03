<?php
session_name("teacher_session");
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'teacher') {
    die("Unauthorized access");
}

include 'db_connect.php';
include 'db_connect.php';
// $conn is already established in db_connect.php

$teacher_username = $_SESSION['username'];

try {
    $teacher_query = $conn->prepare("SELECT name FROM teachers WHERE username = ?");
    $teacher_query->execute([$teacher_username]);
    $teacher_data = $teacher_query->fetch(PDO::FETCH_ASSOC);
    
    if (!$teacher_data) {
        die("Teacher not found.");
    }
    $teacher_name = $teacher_data['name'];
} catch(PDOException $e) {
    die("Error fetching teacher data: " . $e->getMessage());
}

$status = "";
$date = isset($_POST['date']) ? $_POST['date'] : date('Y-m-d');
$student_name = isset($_POST['student_name']) ? $_POST['student_name'] : '';
$subject_name = isset($_POST['subject_name']) ? $_POST['subject_name'] : '';
$period_number = isset($_POST['period_number']) ? $_POST['period_number'] : '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    try {
        $update_query = $conn->prepare("UPDATE attendance SET status = ? 
                                      WHERE teacher_name = ? AND student_name = ? 
                                      AND subject_name = ? AND period_number = ? 
                                      AND date = ?");
        $update_query->execute([
            $_POST['new_status'],
            $teacher_name,
            $_POST['student_name'],
            $_POST['subject_name'],
            $_POST['period_number'],
            $_POST['date']
        ]);
        $status = "Attendance updated successfully!";
    } catch(PDOException $e) {
        $status = "Error updating attendance.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['check_status'])) {
    try {
        $query = $conn->prepare("SELECT status FROM attendance 
                               WHERE teacher_name = ? AND student_name = ? 
                               AND subject_name = ? AND period_number = ? 
                               AND date = ?");
        $query->execute([
            $teacher_name,
            $_POST['student_name'],
            $_POST['subject_name'],
            $_POST['period_number'],
            $_POST['date']
        ]);
        $result = $query->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $status = $result['status'];
        } else {
            $status = "No record found.";
        }
    } catch(PDOException $e) {
        $status = "Error checking attendance.";
    }
}

// Current date for the report
$current_date = date("F j, Y");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Attendance | Teacher Dashboard</title>
    
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
            max-width: 1200px;
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

        .content-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 25px;
            margin-top: 30px;
        }

        .form-card {
            background-color: white;
            border-radius: var(--card-radius);
            padding: 30px;
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .card-header {
            margin-bottom: 25px;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
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

        .form-group input, 
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid rgba(167, 139, 250, 0.15);
            border-radius: 16px;
            font-size: 15px;
            transition: var(--transition);
            background-color: white;
            color: var(--text-color);
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .select-wrapper {
            position: relative;
        }

        .select-wrapper:after {
            content: "\f107";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            pointer-events: none;
        }

        select {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
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

        .button-secondary {
            background-color: white;
            color: var(--text-color);
            border: 1px solid rgba(0, 0, 0, 0.1);
            box-shadow: var(--shadow-sm);
        }

        .button-secondary:hover {
            background-color: #faf5ff;
            box-shadow: var(--shadow-md);
            color: var(--primary-color);
        }

        .button-danger {
            background-color: var(--danger-color);
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.2);
        }

        .button-danger:hover {
            background-color: #c0392b;
            box-shadow: 0 7px 20px rgba(231, 76, 60, 0.3);
        }

        .button-block {
            width: 100%;
            margin-bottom: 10px;
        }

        .buttons-container {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .message {
            padding: 15px;
            border-radius: 16px;
            margin: 20px 0;
            text-align: center;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .message-success {
            background-color: #dcfce7;
            color: #16a34a;
            border: 1px solid #86efac;
        }

        .message-error {
            background-color: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .message-info {
            background-color: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }

        .divider {
            height: 1px;
            background-color: rgba(167, 139, 250, 0.15);
            margin: 25px 0;
        }

        .update-form {
            background-color: #faf5ff;
            border-radius: var(--border-radius);
            padding: 25px;
            margin-top: 20px;
            border: 1px solid rgba(167, 139, 250, 0.15);
        }

        .update-form-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: var(--border-radius);
            font-size: 14px;
            font-weight: 500;
        }

        .status-present {
            background-color: #dcfce7;
            color: #16a34a;
        }

        .status-absent {
            background-color: #fee2e2;
            color: #dc2626;
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

            .form-card {
                padding: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .buttons-container {
                flex-direction: column;
            }

            .button {
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
                    <span><i class="fas fa-user-edit"></i> Edit Attendance Records</span>
                </div>
            </div>
        </div>

        <div class="content-grid">
            <div class="form-card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-search"></i> Find Attendance Record
                    </div>
                </div>
                
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="date"><i class="fas fa-calendar-alt"></i> Date</label>
                            <input type="date" name="date" id="date" value="<?php echo htmlspecialchars($date); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="student_name"><i class="fas fa-user-graduate"></i> Student Name</label>
                            <input type="text" name="student_name" id="student_name" 
                                value="<?php echo htmlspecialchars($student_name); ?>" 
                                placeholder="Enter student name" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="subject_name"><i class="fas fa-book"></i> Subject Name</label>
                            <input type="text" name="subject_name" id="subject_name" 
                                value="<?php echo htmlspecialchars($subject_name); ?>" 
                                placeholder="Enter subject name" required>
                        </div>

                        <div class="form-group">
                            <label for="period_number"><i class="fas fa-clock"></i> Period Number</label>
                            <input type="number" name="period_number" id="period_number" 
                                value="<?php echo htmlspecialchars($period_number); ?>" 
                                placeholder="Enter period number" min="1" max="8" required>
                        </div>
                    </div>

                    <div class="buttons-container">
                        <button type="submit" name="check_status" class="button button-block">
                            <i class="fas fa-search"></i> Check Attendance
                        </button>
                        <a href="welcome_teacher.php" class="button button-secondary button-block">
                            <i class="fas fa-arrow-left"></i> Return to Dashboard
                        </a>
                    </div>
                </form>

                <?php if ($status): ?>
                    <?php 
                        $messageClass = '';
                        $icon = '';
                        
                        if ($status == "Attendance updated successfully!") {
                            $messageClass = 'message-success';
                            $icon = '<i class="fas fa-check-circle"></i>';
                        } elseif ($status == "No record found.") {
                            $messageClass = 'message-error';
                            $icon = '<i class="fas fa-exclamation-circle"></i>';
                        } elseif ($status == "Present" || $status == "present") {
                            $messageClass = 'message-success';
                            $icon = '<i class="fas fa-check-circle"></i>';
                            $status = 'Present';
                        } elseif ($status == "Absent" || $status == "absent") {
                            $messageClass = 'message-error';
                            $icon = '<i class="fas fa-times-circle"></i>';
                            $status = 'Absent';
                        } else {
                            $messageClass = 'message-info';
                            $icon = '<i class="fas fa-info-circle"></i>';
                        }
                    ?>
                    
                    <div class="message <?php echo $messageClass; ?>">
                        <?php echo $icon; ?> 
                        <?php 
                            if ($status == "Present" || $status == "Absent") {
                                echo "Current Status: <strong>" . htmlspecialchars($status) . "</strong>";
                            } else {
                                echo htmlspecialchars($status);
                            }
                        ?>
                    </div>

                    <?php if ($status !== "No record found." && $status !== "Attendance updated successfully!" && ($status == "Present" || $status == "Absent")): ?>
                        <div class="update-form">
                            <div class="update-form-title">
                                <i class="fas fa-edit"></i> Update Attendance Status
                            </div>

                            <form method="POST">
                                <input type="hidden" name="date" value="<?php echo htmlspecialchars($date); ?>">
                                <input type="hidden" name="student_name" value="<?php echo htmlspecialchars($student_name); ?>">
                                <input type="hidden" name="subject_name" value="<?php echo htmlspecialchars($subject_name); ?>">
                                <input type="hidden" name="period_number" value="<?php echo htmlspecialchars($period_number); ?>">
                                
                                <div class="form-group">
                                    <label for="new_status"><i class="fas fa-user-check"></i> New Status</label>
                                    <div class="select-wrapper">
                                        <select name="new_status" id="new_status">
                                            <option value="Present" <?php echo (strtolower($status) == 'present') ? 'selected' : ''; ?>>Present</option>
                                            <option value="Absent" <?php echo (strtolower($status) == 'absent') ? 'selected' : ''; ?>>Absent</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <button type="submit" name="update_status" class="button">
                                    <i class="fas fa-save"></i> Update Attendance
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>