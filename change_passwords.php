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

// Fetch student name for the avatar
$query = "SELECT name FROM students WHERE username = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$username]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
$student_name = $student['name'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New password and confirm password do not match.";
    } elseif (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        $query = "SELECT password FROM users WHERE username=?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            if (!password_verify($old_password, $user['password'])) {
                $error = "Old password is incorrect.";
            } else {
                $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_query = "UPDATE users SET password=? WHERE username=?";
                $stmt = $conn->prepare($update_query);
                
                if ($stmt->execute([$new_hashed_password, $username])) {
                    $success = "Password changed successfully.";
                    $redirect = true;
                } else {
                    $error = "Failed to update password. Please try again.";
                }
            }
        } else {
            $error = "User not found.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password | Student Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/light-theme.css">
    <style>
        body { 
            padding: 32px; 
            background-color: var(--background-color); 
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
        }
        .dashboard-header {
            background: white; border-left: 4px solid var(--primary-color); 
            color: var(--primary-color);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 25px;
        }
        .user-avatar {
            width: 64px;
            height: 64px;
            background: var(--secondary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-size: 24px;
            font-weight: 700;
            border: 2px solid white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .notification {
            padding: 16px;
            border-radius: var(--border-radius);
            margin-bottom: 24px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .notification.error {
            background: #fee2e2;
            color: var(--danger-color);
            border: 1px solid #fecaca;
        }
        .notification.success {
            background: #dcfce7;
            color: var(--success-color);
            border: 1px solid #bbf7d0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="dashboard-header fade-in">
            <div style="display: flex; align-items: center; gap: 20px;">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($student_name, 0, 1)); ?>
                </div>
                <div>
                    <h1 style="font-size: 1.5rem; color: var(--primary-color);">Security Management</h1>
                    <p style="color: var(--text-muted);">Verified Student: <?php echo htmlspecialchars($student_name); ?></p>
                </div>
            </div>
        </div>
        
        <div class="card fade-in">
            <h3 style="margin-bottom: 20px;"><i class="fas fa-key"></i> Update Credentials</h3>
            
            <?php if (isset($error)): ?>
                <div class="notification error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="notification success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="change_passwords.php">
                <div class="form-group">
                    <label for="old_password">Current Password</label>
                    <input type="password" id="old_password" name="old_password" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>

                <div style="display: flex; gap: 12px; margin-top: 24px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Commit Change
                    </button>
                    <a href="welcome_student.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Return
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>