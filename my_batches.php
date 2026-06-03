<?php
session_name("student_session");
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

// Database connection
include 'db_connect.php';

$username = $_SESSION['username'];

// Fetch student details including name and batch code
$query = "SELECT name, batch_code FROM students WHERE username = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$username]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if ($student) {
    $student_name = $student['name'];
    $batch_code = $student['batch_code'];

    // Fetch the subjects and their notes links
    $subjects_query = "SELECT subject_name, notes_link FROM batch_subjects WHERE batch_code = ?";
    $stmt = $conn->prepare($subjects_query);
    $stmt->execute([$batch_code]);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $batch_code = null;
    $subjects = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Subjects</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/light-theme.css">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        

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
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .dashboard-header {
            background-color: var(--primary-color);
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
            background-color: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-size: 24px;
        }

        .student-details h1 {
            font-size: 24px;
            color: var(--text-color);
            margin-bottom: 5px;
        }

        .batch-code {
            color: #666;
            font-size: 16px;
        }

        .subjects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .subject-card {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: 0 8px 24px rgba(167, 139, 250, 0.12);
            transition: transform 0.2s;
        }

        .subject-card:hover {
            transform: translateY(-5px);
        }

        .subject-name {
            font-size: 20px;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .subject-icon {
            font-size: 36px;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .notes-status {
            margin: 15px 0;
            font-size: 14px;
            color: #666;
        }

        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.2s;
            width: 100%;
            text-align: center;
        }

        .button:hover {
            background-color: #357abd;
            transform: translateY(-2px);
        }

        

        

        .no-notes {
            color: var(--danger-color);
            font-weight: 500;
        }

        

        @media (max-width: 768px) {
            .subjects-grid {
                grid-template-columns: 1fr;
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
                    <h1><?php echo htmlspecialchars($student_name); ?></h1>
                    <span class="batch-code">Batch: <?php echo htmlspecialchars($batch_code); ?></span>
                </div>
            </div>
        </div>

        <?php if ($batch_code && count($subjects) > 0): ?>
            <div class="subjects-grid">
                <?php foreach ($subjects as $subject): ?>
                    <div class="subject-card">
                        <div class="subject-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="subject-name">
                            <?php echo htmlspecialchars($subject['subject_name']); ?>
                        </div>
                        <div class="notes-status">
                            <?php if (!empty($subject['notes_link'])): ?>
                                <i class="fas fa-check-circle" style="color: var(--success-color);"></i> Notes available
                            <?php else: ?>
                                <i class="fas fa-times-circle" style="color: var(--danger-color);"></i> Notes not available
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($subject['notes_link'])): ?>
                            <a href="<?php echo htmlspecialchars($subject['notes_link']); ?>" target="_blank" class="button">
                                <i class="fas fa-file-alt"></i> View Notes
                            </a>
                        <?php else: ?>
                            <span class="button" style="background-color: var(--danger-color);">
                                <i class="fas fa-exclamation-circle"></i> No Notes Available
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="subject-card" style="text-align: center;">
                <div class="subject-icon">
                    <i class="fas fa-exclamation-triangle" style="color: var(--danger-color);"></i>
                </div>
                <p style="font-size: 1.2em; margin: 20px 0;">No subjects found for your batch.</p>
            </div>
        <?php endif; ?>

        <a href="welcome_student.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</body>
</html>

<?php

?>