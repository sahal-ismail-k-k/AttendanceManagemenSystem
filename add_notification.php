<?php
session_name("teacher_session");
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit();
}

// Database connection
include 'db_connect.php';

// Get teacher data
$teacher_username = $_SESSION['username'];
$teacher_query = $conn->prepare("SELECT name FROM teachers WHERE username = ?");
$teacher_query->execute([$teacher_username]);
$teacher_data = $teacher_query->fetch(PDO::FETCH_ASSOC);
$teacher_name = $teacher_data ? $teacher_data['name'] : 'Teacher';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $message = $_POST['message'];
    $expiry_time = date('Y-m-d H:i:s', strtotime($_POST['expiry_time']));

    $query = $conn->prepare("INSERT INTO notifications (message, expiry_time) VALUES (?, ?)");
    if ($query->execute([$message, $expiry_time])) {
        $success = "Notification added successfully";
        $redirect = true;
    } else {
        $error = "Error adding notification.";
    }
}

// Current date for the form
$current_date = date("F j, Y");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Notification | Teacher Dashboard</title>
    
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

        .notification-form-wrapper {
            background-color: white;
            border-radius: var(--card-radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(0, 0, 0, 0.05);
            overflow: hidden;
            max-width: 700px;
            margin: 0 auto;
        }

        .form-header {
            padding: 25px 30px;
            border-bottom: 1px solid rgba(167, 139, 250, 0.15);
            background-color: #faf5ff;
        }

        .form-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-subtitle {
            font-size: 14px;
            color: var(--text-light);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-content {
            padding: 30px;
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

        .form-group textarea,
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid rgba(167, 139, 250, 0.15);
            border-radius: 16px;
            font-size: 15px;
            transition: var(--transition);
            background-color: white;
            color: var(--text-color);
            font-family: inherit;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 150px;
        }

        .form-group textarea:focus,
        .form-group input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(167, 139, 250, 0.15);
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
            border-radius: 12px;
            transition: var(--transition);
            width: 100%;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(167, 139, 250, 0.25);
            margin-bottom: 15px;
        }

        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(167, 139, 250, 0.35);
        }

        

        

        .alert {
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .alert-icon {
            font-size: 20px;
        }

        .alert-content {
            flex: 1;
        }

        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #b91c1c;
            border-left: 4px solid #ef4444;
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

            .form-header, .form-content {
                padding: 20px;
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
                    <span><i class="fas fa-bell"></i> Notification Management</span>
                </div>
            </div>
        </div>

        <div class="notification-form-wrapper">
            <div class="form-header">
                <div class="form-title">
                    <i class="fas fa-bell"></i> Add New Notification
                </div>
                <div class="form-subtitle">
                    <i class="fas fa-calendar-alt"></i> <?php echo $current_date; ?>
                </div>
            </div>

            <div class="form-content">
                <?php if (isset($error)): ?>
                    <div class="alert alert-error">
                        <div class="alert-icon"><i class="fas fa-exclamation-circle"></i></div>
                        <div class="alert-content"><?php echo htmlspecialchars($error); ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success">
                        <div class="alert-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="alert-content"><?php echo htmlspecialchars($success); ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="message">
                            <i class="fas fa-comment-alt"></i> Notification Message
                        </label>
                        <textarea id="message" name="message" placeholder="Enter notification details here..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="expiry_time">
                            <i class="fas fa-clock"></i> Expiry Time
                        </label>
                        <input type="datetime-local" id="expiry_time" name="expiry_time" required>
                    </div>

                    <button type="submit" class="button">
                        <i class="fas fa-paper-plane"></i> Add Notification
                    </button>
                    <a href="welcome_teacher.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>