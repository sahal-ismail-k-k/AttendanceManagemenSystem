<?php
// Previous PHP code remains the same until the HTML
session_name("student_session");
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'student') {
    error_log("Unauthorized access attempt - Redirecting to login");
    exit("Unauthorized access!");
}

include 'db_connect.php';

date_default_timezone_set("Asia/Kolkata");

$username = $_SESSION['username'];

// Fetch student details
$student_query = "SELECT name FROM students WHERE username = ?";
$stmt = $conn->prepare($student_query);
$stmt->execute([$username]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    $student_name = $row['name'];
} else {
    error_log("Student not found for username: " . $username);
    die("Student not found!");
}

// Fetch subjects
$subjects_query = "SELECT DISTINCT subject_name FROM attendance WHERE student_name = ?";
$stmt = $conn->prepare($subjects_query);
$stmt->execute([$student_name]);
$subjects_result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch teachers
$teachers_query = "SELECT DISTINCT teacher_name FROM attendance";
$teachers_stmt = $conn->query($teachers_query);
$teachers_result = $teachers_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Attendance</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/light-theme.css">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Previous CSS styles remain exactly the same */
        

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #faf5ff;
            color: var(--text-color);
            line-height: 1.6;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .dashboard-header {
            background-color: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: 0 8px 24px rgba(167, 139, 250, 0.12);
            margin-bottom: 20px;
        }

        .student-info {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .student-avatar {
            width: 60px;
            height: 60px;
            background-color: var(--secondary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-size: 24px;
            border: 2px solid white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .student-details h1 {
            font-size: 24px;
            color: var(--text-color);
            margin-bottom: 5px;
        }

        .form-card {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: 0 8px 24px rgba(167, 139, 250, 0.12);
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-color);
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.1);
        }

        .attendance-status {
            background-color: var(--secondary-color);
            padding: 15px;
            border-radius: var(--border-radius);
            margin: 20px 0;
        }

        .status-text {
            font-weight: 600;
            color: var(--text-color);
        }

        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            border: none;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            width: 100%;
            text-align: center;
        }

        .button:hover {
            background-color: #357abd;
            transform: translateY(-2px);
        }

        .button.report {
            background-color: var(--danger-color);
            display: none;
        }

        .button.report:hover {
            background-color: #c0392b;
        }

        

        

        

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <div class="student-info">
                <div class="student-avatar">
                    <?php echo strtoupper(substr($student_name, 0, 1)); ?>
                </div>
                <div class="student-details">
                    <h1>Report Attendance Issue</h1>
                    <span><?php echo htmlspecialchars($student_name); ?></span>
                </div>
            </div>
        </div>

        <div class="form-card">
            <form id="attendance-form" method="POST" action="check_attendance.php" target="_blank">
                <div class="form-group">
                    <label for="date"><i class="fas fa-calendar"></i> Date:</label>
                    <input type="date" name="date" id="date" required>
                </div>

                <div class="form-group">
                    <label for="subject"><i class="fas fa-book"></i> Subject:</label>
                    <select name="subject" id="subject" required>
                        <option value="">Select Subject</option>
                        <?php foreach ($subjects_result as $row) { ?>
                            <option value="<?php echo htmlspecialchars($row['subject_name']); ?>">
                                <?php echo htmlspecialchars($row['subject_name']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="teacher"><i class="fas fa-chalkboard-teacher"></i> Teacher:</label>
                    <select name="teacher" id="teacher" required>
                        <option value="">Select Teacher</option>
                        <?php foreach ($teachers_result as $row) { ?>
                            <option value="<?php echo htmlspecialchars($row['teacher_name']); ?>">
                                <?php echo htmlspecialchars($row['teacher_name']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="period"><i class="fas fa-clock"></i> Period:</label>
                    <input type="number" name="period" id="period" min="1" required>
                </div>

                <div class="attendance-status">
                    <i class="fas fa-info-circle"></i> Status: <span id="status-text">Not Checked</span>
                </div>

                <button type="submit" id="submit-btn" class="button report">
                    <i class="fas fa-exclamation-circle"></i> Report Issue
                </button>
            </form>
        </div>

        <a href="welcome_student.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <script>
        $(document).ready(function() {
            function checkAttendance() {
                let date = $("#date").val();
                let subject = $("#subject").val();
                let teacher = $("#teacher").val();
                let period = $("#period").val();

                if (date && subject && teacher && period) {
                    $.ajax({
                        url: "get_attendance_status.php",
                        type: "POST",
                        data: {
                            date: date,
                            subject: subject,
                            teacher: teacher,
                            period: period
                        },
                        success: function(response) {
                            $("#status-text").text(response);
                            
                            if (response === "absent") {
                                $("#submit-btn").show();
                            } else {
                                $("#submit-btn").hide();
                            }
                        }
                    });
                }
            }

            $("#date, #subject, #teacher, #period").change(checkAttendance);
        });
    </script>
</body>
</html>