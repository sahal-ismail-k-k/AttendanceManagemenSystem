<?php
// Check for existing sessions to allow multi-tab seamless access
$session_names = ["admin_session", "teacher_session", "student_session"];
foreach ($session_names as $s_name) {
    session_name($s_name);
    session_start();
    if (isset($_SESSION['username']) && isset($_SESSION['role'])) {
        $role = $_SESSION['role'];
        if ($role == 'admin') header("Location: admin_dashboard.php");
        elseif ($role == 'teacher') header("Location: teacher_dashboard.php");
        elseif ($role == 'student') header("Location: student_dashboard.php");
        exit();
    }
    session_write_close(); // Close to check next one
}

// Proceed with standard login logic if no session found
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $raw_password = trim($_POST['password']);

    include 'db_connect.php';

    // Fetch user by username
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($raw_password, $user['password'])) {
        error_log("Login Success - User Found: " . $user['username'] . " Role: " . $user['role']);
        $role = $user['role'];

        // Assign unique session name based on role
        if ($role == 'student') {
            session_name("student_session");
        } elseif ($role == 'teacher') {
            session_name("teacher_session");
        } elseif ($role == 'admin') {
            session_name("admin_session");
        }
        session_start();

        // Store session data
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $role;

        // Redirect based on role
        if ($role == 'student') {
            header("Location: student_dashboard.php");
        } elseif ($role == 'teacher') {
            header("Location: teacher_dashboard.php");
        } elseif ($role == 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            echo "<script>alert('Invalid role detected. Contact administrator.');</script>";
        }
    } else {
        echo "<script>alert('Invalid Username or Password');</script>";
    }

    // PDO connection closes automatically, or we can explicitly set to null
    $conn = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Attendance Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1e3a8a; /* Deep Academic Blue */
            --primary-light: #3b82f6;
            --primary-dark: #1e3a8a;
            --accent-color: #64748b;
            --text-color: #0f172a;
            --light-text: #64748b;
            --bg-color: #f8fafc;
            --border-radius: 12px;
            --box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--bg-color);
            background-image: 
                radial-gradient(at 0% 0%, rgba(30, 58, 138, 0.05) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(59, 130, 246, 0.05) 0px, transparent 50%);
            position: relative;
        }

        /* Subtle decorative element */
        .page-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%231e3a8a' fill-opacity='0.02'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .login-wrapper {
            width: 100%;
            max-width: 1000px;
            display: flex;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
            margin: 20px;
        }

        .login-side-panel {
            flex: 1;
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            padding: 60px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .login-side-panel::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
        }

        .login-side-panel h1 {
            font-size: 42px;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 20px;
        }

        .login-side-panel p {
            font-size: 18px;
            opacity: 0.8;
            line-height: 1.6;
        }

        .institution-badge {
            margin-top: 40px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .institution-badge i {
            font-size: 40px;
        }

        .login-container {
            flex: 1;
            padding: 60px;
            background: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .logo {
            margin-bottom: 30px;
            color: var(--primary-color);
        }

        .logo i {
            font-size: 48px;
        }

        h2 {
            color: var(--text-color);
            margin-bottom: 10px;
            font-size: 28px;
            font-weight: 700;
        }

        .subtitle {
            color: var(--light-text);
            margin-bottom: 35px;
            font-size: 16px;
        }

        .input-group {
            position: relative;
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-color);
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i:not(.toggle-password) {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--light-text);
            font-size: 18px;
        }

        .input-field {
            width: 100%;
            padding: 14px 16px 14px 48px;
            border: 1px solid #e2e8f0;
            border-radius: var(--border-radius);
            font-size: 16px;
            color: var(--text-color);
            transition: var(--transition);
            background: #f8fafc;
        }

        .input-field:focus {
            border-color: var(--primary-color);
            background: white;
            outline: none;
            box-shadow: 0 0 0 4px rgba(30, 58, 138, 0.1);
        }

        .toggle-password {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--light-text);
            padding: 5px;
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 10px;
        }

        .login-btn:hover {
            background: #1e40af;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(30, 58, 138, 0.2);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #f1f5f9;
            color: var(--light-text);
            font-size: 14px;
            text-align: center;
        }

        @media (max-width: 900px) {
            .login-side-panel {
                display: none;
            }
            .login-wrapper {
                max-width: 450px;
            }
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 40px 25px;
            }
        }
    </style>
</head>
<body>
    <div class="page-background"></div>

    <div class="login-wrapper">
        <div class="login-side-panel">
            <h1>Attendance Management System</h1>
            <p>A comprehensive solution for student tracking, faculty management, and institutional reporting.</p>
            
            <div class="institution-badge">
                <i class="fas fa-university"></i>
                <div>
                    <strong>College Administration</strong><br>
                    <span>Secure Gateway</span>
                </div>
            </div>
        </div>

        <div class="login-container">
            <div class="logo">
                <i class="fas fa-shield-alt"></i>
            </div>
            
            <h2>Secure Sign In</h2>
            <p class="subtitle">Please enter your institutional credentials.</p>
            
            <form method="POST" action="login.php">
                <div class="input-group">
                    <label>Username</label>
                    <div class="input-wrapper">
                        <input type="text" name="username" class="input-field" placeholder="Administrative ID" required>
                        <i class="fas fa-user-circle"></i>
                    </div>
                </div>
                
                <div class="input-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="password" id="login_password" class="input-field" placeholder="••••••••" required>
                        <i class="fas fa-key"></i>
                        <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                    </div>
                </div>
                
                <button type="submit" class="login-btn">
                    Authenticate Session
                </button>
            </form>
        
        <script>
            const togglePassword = document.querySelector('#togglePassword');
            const password = document.querySelector('#login_password');

            togglePassword.addEventListener('click', function (e) {
                // toggle the type attribute
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                // toggle the eye slash icon
                this.classList.toggle('fa-eye-slash');
            });
        </script>
        
        <div class="footer">
            &copy; <?php echo date('Y'); ?> Attendance Management System | Institutional Access
        </div>
    </div>
</body>
</html>