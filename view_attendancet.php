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
    $teacher_query = $conn->prepare("SELECT name, subjects FROM teachers WHERE username = ?");
    $teacher_query->execute([$teacher_username]);
    $teacher_data = $teacher_query->fetch(PDO::FETCH_ASSOC);
    
    if (!$teacher_data) {
        die("Teacher not found.");
    }
    
    $teacher_name = $teacher_data['name'];
    $subjects = array_map('trim', explode(",", $teacher_data['subjects']));
} catch(PDOException $e) {
    die("Error fetching teacher data: " . $e->getMessage());
}

$students = [];
$batch_code = "";
$selected_subject = "";
$total_students = 0;
$avg_attendance = 0;
$critical_count = 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $selected_subject = $_POST['subject'];
    $batch_code = $_POST['batch_code'];

    try {
        $student_query = $conn->prepare("
            SELECT student_name, 
                   SUM(status = 'present') AS present_days, 
                   COUNT(*) AS total_days 
            FROM attendance 
            WHERE LOWER(teacher_name) = LOWER(?) 
              AND LOWER(subject_name) = LOWER(?) 
              AND LOWER(batch_code) = LOWER(?) 
            GROUP BY student_name
        ");
        $student_query->execute([$teacher_name, $selected_subject, $batch_code]);
        
        while ($row = $student_query->fetch(PDO::FETCH_ASSOC)) {
            $attendance_percentage = ($row['total_days'] > 0) 
                ? round(($row['present_days'] / $row['total_days']) * 100, 2) 
                : 0;
            $students[] = [
                'name' => $row['student_name'],
                'attendance' => $attendance_percentage,
                'present_days' => $row['present_days'],
                'total_days' => $row['total_days']
            ];
            
            // Count for analytics
            if ($attendance_percentage < 60) {
                $critical_count++;
            }
        }
        
        $total_students = count($students);
        if ($total_students > 0) {
            $avg_attendance = round(array_sum(array_column($students, 'attendance')) / $total_students, 2);
        }
        
        // Sort students by attendance (descending)
        usort($students, function($a, $b) {
            return $b['attendance'] <=> $a['attendance'];
        });
        
    } catch(PDOException $e) {
        die("Error fetching attendance data: " . $e->getMessage());
    }
}

// Helper function to get department color
function getDepartmentColor($batch_code) {
    if (strpos($batch_code, 'CSE') !== false) return '#a78bfa';
    if (strpos($batch_code, 'EEE') !== false) return '#9333ea';
    if (strpos($batch_code, 'MECH') !== false) return '#f77f00';
    if (strpos($batch_code, 'ECE') !== false) return '#7209b7';
    return '#c4b5fd'; // default
}

// Get current date for the report
$current_date = date("F j, Y");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report | Teacher Dashboard</title>
    
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
            background: white; border-left: 4px solid var(--primary-color); 
            color: var(--primary-color);
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
            color: var(--primary-color);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 3px solid white;
        }

        .teacher-details h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .teacher-details span {
            font-size: 16px;
            color: var(--text-muted);
            font-weight: 400;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 25px;
            margin-top: 30px;
        }

        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .attendance-form {
            background-color: white;
            border-radius: var(--card-radius);
            padding: 30px;
            box-shadow: var(--shadow-sm);
            height: fit-content;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .form-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 10px;
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
            width: 100%;
            padding: 12px 15px;
            border: 1px solid rgba(167, 139, 250, 0.15);
            border-radius: 16px;
            font-size: 15px;
            transition: var(--transition);
            background-color: white;
            color: var(--text-color);
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }

        select:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        optgroup {
            font-weight: 600;
            color: var(--text-color);
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
            width: 100%;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.2);
            margin-bottom: 15px;
        }

        .button:hover {
            background-color: #3a56d4;
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(67, 97, 238, 0.3);
        }

        

        

        .analytics-card {
            background-color: white;
            border-radius: var(--card-radius);
            padding: 25px;
            box-shadow: var(--shadow-sm);
            height: fit-content;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .analytics-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .analytics-item {
            background-color: #faf5ff;
            border-radius: 16px;
            padding: 15px;
            text-align: center;
        }

        .analytics-value {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .analytics-label {
            font-size: 14px;
            color: var(--text-light);
        }

        .attendance-table-wrapper {
            background-color: white;
            border-radius: var(--card-radius);
            box-shadow: var(--shadow-sm);
            height: 100%;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .table-header {
            padding: 25px 30px;
            border-bottom: 1px solid rgba(167, 139, 250, 0.15);
            background-color: #faf5ff;
        }

        .report-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .report-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 15px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: var(--text-light);
        }

        .meta-value {
            font-weight: 500;
            color: var(--text-color);
        }

        .batch-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 10px;
            border-radius: var(--border-radius);
            font-size: 14px;
            font-weight: 500;
            color: white;
            background-color: var(--primary-color);
        }

        .table-content {
            padding: 0 30px 30px;
            overflow-x: auto;
            flex: 1;
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

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
        }

        th, td {
            padding: 15px;
            text-align: left;
        }

        th {
            font-weight: 500;
            color: var(--text-light);
            border-bottom: 1px solid rgba(167, 139, 250, 0.15);
            white-space: nowrap;
        }

        td {
            border-bottom: 1px solid #f0f0f0;
            font-weight: 400;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tbody tr {
            transition: var(--transition);
        }

        tbody tr:hover {
            background-color: #f8fafc;
        }

        .student-name {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .student-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: var(--secondary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            color: var(--primary-color);
        }

        .attendance-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: var(--border-radius);
            font-size: 14px;
            font-weight: 500;
            white-space: nowrap;
        }

        .attendance-good {
            background-color: #dcfce7;
            color: #16a34a;
        }

        .attendance-warning {
            background-color: #fef3c7;
            color: #d97706;
        }

        .attendance-poor {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .attendance-detail {
            font-size: 13px;
            color: var(--text-light);
            margin-top: 3px;
        }

        .search-bar {
            position: relative;
            margin-bottom: 20px;
        }

        .search-input {
            width: 100%;
            padding: 12px 15px 12px 40px;
            border: 1px solid rgba(167, 139, 250, 0.15);
            border-radius: 16px;
            font-size: 15px;
            transition: var(--transition);
            background-color: white;
        }

        .search-input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }

        .pagination {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            margin-top: 20px;
            gap: 5px;
        }

        .page-item {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 16px;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-color);
            border: 1px solid rgba(167, 139, 250, 0.15);
            background-color: white;
            cursor: pointer;
            transition: var(--transition);
        }

        .page-item:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .page-item.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }

            .sidebar {
                flex-direction: row;
                flex-wrap: wrap;
            }

            .attendance-form, .analytics-card {
                flex: 1;
                min-width: 300px;
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

            .sidebar {
                flex-direction: column;
            }

            .attendance-form, .analytics-card, .attendance-table-wrapper {
                padding: 20px;
            }

            .table-header {
                padding: 20px;
            }

            .table-content {
                padding: 0 20px 20px;
            }

            .analytics-grid {
                grid-template-columns: 1fr;
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
                    <span><i class="fas fa-chart-line"></i> Attendance Reports Dashboard</span>
                </div>
            </div>
        </div>

        <div class="content-grid">
            <div class="sidebar">
                <div class="attendance-form">
                    <div class="form-title">
                        <i class="fas fa-filter"></i> Filter Options
                    </div>
                    <form method="post">
                        <div class="form-group">
                            <label for="subject">
                                <i class="fas fa-book"></i>
                                Select Subject
                            </label>
                            <div class="select-wrapper">
                                <select name="subject" id="subject" required>
                                    <option value="">Choose a subject...</option>
                                    <?php foreach ($subjects as $subject) { ?>
                                        <option value="<?php echo htmlspecialchars(trim($subject)); ?>" 
                                                <?php echo ($selected_subject == trim($subject)) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars(trim($subject)); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="batch_code">
                                <i class="fas fa-users"></i>
                                Select Batch
                            </label>
                            <div class="select-wrapper">
                                <select name="batch_code" id="batch_code" required>
                                    <option value="">Choose a batch...</option>
                                    <?php 
                                    $departments = ["CSE", "EEE", "MECH", "ECE"];
                                    for ($sem = 1; $sem <= 8; $sem++) {
                                        echo "<optgroup label='Semester {$sem}'>";
                                        foreach ($departments as $dept) {
                                            $value_a = "s{$sem}{$dept}a";
                                            $value_b = "s{$sem}{$dept}b";
                                            echo "<option value='{$value_a}'" . ($batch_code == $value_a ? ' selected' : '') . ">S{$sem} {$dept} A</option>";
                                            echo "<option value='{$value_b}'" . ($batch_code == $value_b ? ' selected' : '') . ">S{$sem} {$dept} B</option>";
                                        }
                                        echo "</optgroup>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="button">
                            <i class="fas fa-sync-alt"></i>
                            Generate Report
                        </button>
                        <a href="welcome_teacher.php" class="btn-back">
                            <i class="fas fa-arrow-left"></i>
                            Back to Dashboard
                        </a>
                    </form>
                </div>

                <?php if (!empty($students)) { ?>
                <div class="analytics-card">
                    <div class="analytics-title">
                        <i class="fas fa-chart-pie"></i> Report Analytics
                    </div>
                    <div class="analytics-grid">
                        <div class="analytics-item">
                            <div class="analytics-value"><?php echo $total_students; ?></div>
                            <div class="analytics-label">Total Students</div>
                        </div>
                        <div class="analytics-item">
                            <div class="analytics-value"><?php echo $avg_attendance; ?>%</div>
                            <div class="analytics-label">Average Attendance</div>
                        </div>
                        <div class="analytics-item">
                            <div class="analytics-value">
                                <?php 
                                    $good_count = count(array_filter($students, function($s) { return $s['attendance'] >= 75; }));
                                    echo $good_count;
                                ?>
                            </div>
                            <div class="analytics-label">Good Standing</div>
                        </div>
                        <div class="analytics-item">
                            <div class="analytics-value"><?php echo $critical_count; ?></div>
                            <div class="analytics-label">Critical Status</div>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>

            <div class="attendance-table-wrapper">
                <?php if (!empty($students)) { 
                    // Set department color
                    $dept_color = getDepartmentColor($batch_code);
                ?>
                <div class="table-header">
                    <div class="report-title">
                        <i class="fas fa-clipboard-list"></i>
                        Attendance Report
                    </div>
                    <div class="report-meta">
                        <div class="meta-item">
                            <i class="fas fa-book"></i>
                            Subject: <span class="meta-value"><?php echo htmlspecialchars($selected_subject); ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="batch-badge" style="background-color: <?php echo $dept_color; ?>">
                                <i class="fas fa-users"></i>
                                <?php echo strtoupper(htmlspecialchars($batch_code)); ?>
                            </span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-calendar-alt"></i>
                            Date: <span class="meta-value"><?php echo $current_date; ?></span>
                        </div>
                    </div>
                </div>
                <div class="table-content">
                    <div class="search-bar">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="searchInput" class="search-input" placeholder="Search for students...">
                    </div>
                    <table id="attendanceTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Student Name</th>
                                <th>Present Days</th>
                                <th>Attendance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $index => $student) { 
                                $attendanceClass = $student['attendance'] >= 75 ? 'attendance-good' : 
                                                ($student['attendance'] >= 60 ? 'attendance-warning' : 'attendance-poor');
                                $initials = implode('', array_map(function($name) { 
                                    return strtoupper(substr($name, 0, 1)); 
                                }, array_slice(explode(' ', $student['name']), 0, 2)));
                            ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <div class="student-name">
                                            <div class="student-avatar"><?php echo $initials; ?></div>
                                            <?php echo htmlspecialchars($student['name']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div><?php echo $student['present_days']; ?> / <?php echo $student['total_days']; ?></div>
                                        <div class="attendance-detail">days present</div>
                                    </td>
                                    <td>
                                        <span class="attendance-badge <?php echo $attendanceClass; ?>">
                                            <?php echo $student['attendance']; ?>%
                                        </span>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                    <div class="pagination">
                        <button class="page-item active">1</button>
                        <button class="page-item">2</button>
                        <button class="page-item">3</button>
                        <button class="page-item"><i class="fas fa-ellipsis-h"></i></button>
                        <button class="page-item"><i class="fas fa-angle-right"></i></button>
                    </div>
                </div>
                <?php } else { ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h3>No Attendance Data</h3>
                    <p style="color: var(--text-light); margin-top: 10px; max-width: 500px;">
                        Select a subject and batch to generate the attendance report. The report will show attendance percentages for all students in the selected batch.
                    </p>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <script>
        // Simple search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const searchValue = this.value.toLowerCase();
                    const table = document.getElementById('attendanceTable');
                    if (table) {
                        const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
                        
                        for (let i = 0; i < rows.length; i++) {
                            const studentName = rows[i].getElementsByTagName('td')[1].textContent.toLowerCase();
                            if (studentName.includes(searchValue)) {
                                rows[i].style.display = "";
                            } else {
                                rows[i].style.display = "none";
                            }
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>