<?php
session_name("teacher_session");
session_start();
include 'db_connect.php';

if (!isset($_SESSION['username'])) {
    die("Access denied. Please log in.");
}

$teacher_username = $_SESSION['username'];

$teacherQuery = "SELECT name, subjects FROM teachers WHERE username = ?";
$stmt = $conn->prepare($teacherQuery);
$stmt->execute([$teacher_username]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    $teacher_name = $row['name'];
    $subjects = explode(",", $row['subjects']);
} else {
    die("No subjects assigned.");
}

// Get subject icons based on subject name
function getSubjectIcon($subject) {
    $subject = strtolower($subject);
    if (strpos($subject, 'math') !== false) return 'fa-calculator';
    if (strpos($subject, 'science') !== false) return 'fa-flask';
    if (strpos($subject, 'english') !== false) return 'fa-book';
    if (strpos($subject, 'history') !== false) return 'fa-landmark';
    if (strpos($subject, 'geography') !== false) return 'fa-globe';
    if (strpos($subject, 'computer') !== false) return 'fa-laptop-code';
    if (strpos($subject, 'art') !== false) return 'fa-palette';
    if (strpos($subject, 'music') !== false) return 'fa-music';
    if (strpos($subject, 'physical') !== false) return 'fa-running';
    return 'fa-book-open'; // Default icon
}

// Generate random pastel color for subjects
function getSubjectColor($subject) {
    $hash = crc32($subject);
    $r = (($hash & 0xFF0000) >> 16) % 156 + 100;
    $g = (($hash & 0x00FF00) >> 8) % 156 + 100;
    $b = ($hash & 0x0000FF) % 156 + 100;
    return "rgb($r, $g, $b)";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Subjects | Teacher Dashboard</title>
    
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

        .subjects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(330px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .subject-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: var(--card-radius);
            padding: 30px;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(167, 139, 250, 0.1);
        }

        .subject-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: var(--shadow-lg);
        }

        .subject-info {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 22px;
        }

        .subject-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .subject-name {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-color);
        }

        .subject-code {
            font-size: 14px;
            color: var(--text-light);
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 5px;
        }

        .notes-status {
            padding: 15px 0;
            font-size: 15px;
            color: var(--text-light);
            display: flex;
            align-items: center;
            gap: 10px;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 24px;
            background: white; border-left: 4px solid var(--primary-color); color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            transition: var(--transition);
            width: 100%;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(167, 139, 250, 0.25);
        }

        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(167, 139, 250, 0.35);
        }

        .button.disabled {
            background-color: rgba(167, 139, 250, 0.15);
            color: #718096;
            cursor: not-allowed;
            box-shadow: none;
        }

        .button.disabled:hover {
            transform: none;
            box-shadow: none;
        }

        

        

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background-color: white;
            border-radius: var(--card-radius);
            box-shadow: var(--shadow-sm);
        }

        .empty-icon {
            width: 80px;
            height: 80px;
            background-color: #edf2f7;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            color: var(--text-light);
            margin: 0 auto 20px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px 15px;
            }

            .dashboard-header {
                padding: 30px 20px;
            }

            .subjects-grid {
                grid-template-columns: 1fr;
                gap: 20px;
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

            .subject-card {
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
                    <span><i class="fas fa-chalkboard-teacher"></i> Teacher Dashboard</span>
                </div>
            </div>
        </div>

        <h2 class="section-title">My Subjects</h2>

        <div class="subjects-grid">
            <?php if (!empty($subjects)): ?>
                <?php foreach ($subjects as $subject): ?>
                    <?php
                    $subject = trim($subject);
                    $notesQuery = "SELECT notes_link FROM batch_subjects WHERE subject_name = ? LIMIT 1";
                    $stmtNotes = $conn->prepare($notesQuery);
                    $stmtNotes->execute([$subject]);
                    $noteRow = $stmtNotes->fetch(PDO::FETCH_ASSOC);
                    $notesLink = $noteRow ? $noteRow['notes_link'] : null;
                    
                    // Generate subject code (placeholder - you can replace with actual code from database)
                    $subjectCode = strtoupper(substr(str_replace(' ', '', $subject), 0, 3)) . rand(100, 999);
                    
                    // Get icon and color for subject
                    $icon = getSubjectIcon($subject);
                    $color = getSubjectColor($subject);
                    ?>
                    <div class="subject-card">
                        <div class="subject-info">
                            <div class="subject-icon" style="background-color: <?php echo $color; ?>">
                                <i class="fas <?php echo $icon; ?>"></i>
                            </div>
                            <div>
                                <div class="subject-name"><?php echo htmlspecialchars($subject); ?></div>
                                <div class="subject-code"><i class="fas fa-tag"></i> <?php echo $subjectCode; ?></div>
                            </div>
                        </div>
                        <div class="notes-status">
                            <?php if ($notesLink): ?>
                                <span class="status-indicator" style="background-color: var(--success-color);"></span>
                                <span>Study materials available</span>
                            <?php else: ?>
                                <span class="status-indicator" style="background-color: var(--danger-color);"></span>
                                <span>No study materials uploaded yet</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($notesLink): ?>
                            <a href="<?php echo htmlspecialchars($notesLink); ?>" target="_blank" class="button">
                                <i class="fas fa-book"></i>
                                View Study Materials
                            </a>
                        <?php else: ?>
                            <button class="button disabled">
                                <i class="fas fa-file-alt"></i>
                                No Materials Available
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-chalkboard"></i>
                    </div>
                    <h3>No subjects assigned yet</h3>
                    <p style="color: var(--text-light); margin-top: 10px;">
                        Contact the administrator to get subjects assigned to your profile.
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <a href="welcome_teacher.php" class="btn-back">
            <i class="fas fa-arrow-left"></i>
            Back to Dashboard
        </a>
    </div>
</body>
</html>