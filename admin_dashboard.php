<?php
session_name("admin_session");
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Database connection
include 'db_connect.php';

$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 80px;
            --primary-color: #1e3a8a; /* Deep Academic Blue */
            --primary-light: #3b82f6;
            --primary-dark: #172554;
            --accent-color: #64748b;
            --text-color: #1e293b;
            --text-light: #64748b;
            --background-color: #f8fafc;
            --card-bg: #ffffff;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --border-radius: 12px;
            --box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --transition-speed: 0.25s;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            display: flex;
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: var(--background-color);
            min-height: 100vh;
            overflow-x: hidden;
            color: var(--text-color);
        }

        /* Sidebar Styles - Institutional Look */
        .sidebar {
            width: var(--sidebar-width);
            background: #ffffff;
            color: var(--text-color);
            height: 100vh;
            transition: all var(--transition-speed) cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            box-shadow: 1px 0 0 0 #e2e8f0;
            z-index: 10;
            border-right: 1px solid #e2e8f0;
        }
        
        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        .sidebar-header {
            padding: 24px 20px;
            text-align: center;
            border-bottom: 1px solid #f1f5f9;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .sidebar-header h2 {
            font-size: 1.25rem;
            white-space: nowrap;
            overflow: hidden;
            font-weight: 700;
            color: var(--primary-color);
            transition: opacity var(--transition-speed);
            letter-spacing: -0.025em;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .sidebar.collapsed .sidebar-header h2 {
            opacity: 0;
        }

        .toggle-btn {
            position: absolute;
            right: -12px;
            top: 24px;
            background: white;
            color: var(--primary-color);
            width: 24px;
            height: 24px;
            border-radius: 6px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            z-index: 100;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }
        
        .toggle-btn:hover {
            background: #f8fafc;
            transform: scale(1.1);
        }

        .nav-item {
            position: relative;
            display: block;
            margin: 4px 12px;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .nav-link {
            display: flex;
            color: #475569; /* slate 600 */
            padding: 12px;
            text-decoration: none;
            transition: all 0.2s;
            align-items: center;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .nav-link:hover {
            background: #f1f5f9;
            color: var(--primary-color);
        }
        
        .nav-link.active {
            background: #eff6ff; /* blue 50 */
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .nav-link i {
            margin-right: 12px;
            font-size: 1.1rem;
            min-width: 24px;
            text-align: center;
            transition: transform 0.2s;
        }
        
        .link-text {
            white-space: nowrap;
            overflow: hidden;
            font-size: 0.9375rem;
            transition: opacity var(--transition-speed);
        }
        
        .sidebar.collapsed .link-text {
            opacity: 0;
        }

        /* Main Content Area */
        .main-content {
            flex-grow: 1;
            padding: 25px;
            height: 100vh;
            transition: margin-left var(--transition-speed);
        }

        iframe {
            width: 100%;
            height: 100%;
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            background: var(--card-bg);
            transition: all 0.3s ease;
        }

        /* User Details */
        .user-info {
            margin-top: auto;
            padding: 18px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.9rem;
            position: absolute;
            bottom: 0;
            width: 100%;
            display: flex;
            align-items: center;
            background: rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s;
        }
        
        .user-info:hover {
            background: rgba(0, 0, 0, 0.2);
        }
        
        .user-info i {
            margin-right: 12px;
            font-size: 1.2rem;
            color: var(--secondary-color);
            background: rgba(255, 255, 255, 0.1);
            padding: 8px;
            border-radius: 50%;
        }
        
        .user-name {
            white-space: nowrap;
            overflow: hidden;
            transition: opacity var(--transition-speed);
            font-weight: 500;
        }
        
        .sidebar.collapsed .user-name {
            opacity: 0;
        }

        /* Logout button styling */
        .nav-item:last-of-type .nav-link {
            color: var(--text-color);
            background-color: rgba(231, 76, 60, 0.2);
            transition: all 0.3s;
        }
        
        .nav-item:last-of-type .nav-link:hover {
            background-color: var(--accent-color);
        }

        /* Animation Classes */
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        
        .slide-in {
            animation: slideIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideIn {
            from { transform: translateX(-20px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            :root {
                --sidebar-width: 240px;
            }
            
            .main-content {
                padding: 15px;
            }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    
    
    <button class="toggle-btn" id="toggle-sidebar">
        <i class="fas fa-chevron-left" id="toggle-icon"></i>
    </button>
    
    <div class="nav-item slide-in" style="animation-delay: 0.1s">
        <a href="manage_teachers.php" target="content-frame" class="nav-link">
            <i class="fas fa-chalkboard-teacher"></i>
            <span class="link-text">Manage Teachers</span>
        </a>
    </div>
    
    <div class="nav-item slide-in" style="animation-delay: 0.2s">
        <a href="manage_students.php" target="content-frame" class="nav-link">
            <i class="fas fa-user-graduate"></i>
            <span class="link-text">Manage Students</span>
        </a>
    </div>
    
    <div class="nav-item slide-in" style="animation-delay: 0.3s">
        <a href="manage_batches.php" target="content-frame" class="nav-link">
            <i class="fas fa-users"></i>
            <span class="link-text">Manage Batches</span>
        </a>
    </div>
    
    
    <div class="nav-item slide-in" style="animation-delay: 0.6s">
        <a href="change_passworda.php" target="content-frame" class="nav-link">
            <i class="fas fa-key"></i>
            <span class="link-text">Change Password</span>
        </a>
    </div>
    
    <div class="nav-item slide-in" style="animation-delay: 0.7s">
        <a href="logout.php" class="nav-link">
            <i class="fas fa-sign-out-alt"></i>
            <span class="link-text">Logout</span>
        </a>
    </div>
    
    <div class="user-info">
        <i class="fas fa-user-circle"></i>
        <span class="user-name"><?php echo htmlspecialchars($username); ?></span>
    </div>
</div>

<!-- Main Content Area -->
<div class="main-content" id="main-content">
    <iframe name="content-frame" id="content-frame" src="welcome_admin.php"></iframe>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');
        const toggleBtn = document.getElementById('toggle-sidebar');
        const toggleIcon = document.getElementById('toggle-icon');
        const navLinks = document.querySelectorAll('.nav-link');
        
        // Check if sidebar state is saved in localStorage
        const sidebarCollapsed = localStorage.getItem('adminSidebarCollapsed') === 'true';
        
        // Initialize sidebar state
        if (sidebarCollapsed) {
            sidebar.classList.add('collapsed');
            toggleIcon.classList.remove('fa-chevron-left');
            toggleIcon.classList.add('fa-chevron-right');
        }
        
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            
            // Toggle icon direction
            if (sidebar.classList.contains('collapsed')) {
                toggleIcon.classList.remove('fa-chevron-left');
                toggleIcon.classList.add('fa-chevron-right');
                localStorage.setItem('adminSidebarCollapsed', 'true');
            } else {
                toggleIcon.classList.remove('fa-chevron-right');
                toggleIcon.classList.add('fa-chevron-left');
                localStorage.setItem('adminSidebarCollapsed', 'false');
            }
        });

        // Set active class on nav links when clicked
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                // Remove active class from all links
                navLinks.forEach(l => l.classList.remove('active'));
                // Add active class to clicked link
                this.classList.add('active');
            });
        });

        // Create welcome page content if it doesn't exist yet
        const frame = document.getElementById('content-frame');
        frame.onload = function() {
            if (frame.contentDocument.body.innerHTML.trim() === '') {
                frame.contentDocument.body.innerHTML = `
                <div class="welcome-container fade-in">
                    <h2>Welcome to Admin Dashboard</h2>
                    <p>Manage your institution's attendance system efficiently</p>
                    <div class="dashboard-stats">
                        <div class="stat-card">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <h3>Teachers</h3>
                            <p>Manage faculty records</p>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-user-graduate"></i>
                            <h3>Students</h3>
                            <p>Oversee student enrollment</p>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-users"></i>
                            <h3>Batches</h3>
                            <p>Organize class groups</p>
                        </div>
                    </div>
                </div>`;
                
                // Apply styles to the iframe document
                const style = frame.contentDocument.createElement('style');
                style.textContent = `
                    body { 
                        font-family: 'Poppins', sans-serif;
                        padding: 25px;
                        background-color: #faf5ff;
                        margin: 0;
                    }
                    .welcome-container {
                        text-align: center;
                        padding: 40px;
                        background: white;
                        border-radius: 16px;
                        box-shadow: 0 8px 24px rgba(167, 139, 250, 0.12);
                        margin-bottom: 25px;
                        position: relative;
                        overflow: hidden;
                    }
                    .welcome-container::before {
                        content: '';
                        position: absolute;
                        top: 0;
                        left: 0;
                        right: 0;
                        height: 5px;
                        background: linear-gradient(to right, #a78bfa, #fbbf24);
                    }
                    .welcome-container h2 {
                        color: #a78bfa;
                        margin-bottom: 15px;
                        font-size: 2.2rem;
                        font-weight: 600;
                    }
                    .welcome-container p {
                        color: #666;
                        font-size: 1.1rem;
                        max-width: 600px;
                        margin: 0 auto 30px;
                        line-height: 1.6;
                    }
                    .dashboard-stats {
                        display: flex;
                        justify-content: center;
                        gap: 25px;
                        margin-top: 30px;
                        flex-wrap: wrap;
                    }
                    .stat-card {
                        background: #faf5ff;
                        padding: 25px;
                        border-radius: 16px;
                        flex: 1;
                        min-width: 200px;
                        max-width: 250px;
                        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
                        transition: transform 0.3s, box-shadow 0.3s;
                    }
                    .stat-card:hover {
                        transform: translateY(-5px);
                        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
                    }
                    .stat-card i {
                        font-size: 2.5rem;
                        color: #fbbf24;
                        margin-bottom: 15px;
                    }
                    .stat-card h3 {
                        font-size: 1.3rem;
                        margin-bottom: 10px;
                        color: #a78bfa;
                    }
                    .stat-card p {
                        font-size: 0.9rem;
                        color: #666;
                        margin: 0;
                    }
                    .fade-in {
                        animation: fadeIn 0.6s ease-out;
                    }
                    @keyframes fadeIn {
                        from { opacity: 0; transform: translateY(20px); }
                        to { opacity: 1; transform: translateY(0); }
                    }
                    @media (max-width: 768px) {
                        .dashboard-stats {
                            flex-direction: column;
                            align-items: center;
                        }
                        .stat-card {
                            width: 100%;
                            max-width: 100%;
                        }
                    }
                `;
                // Add Google Fonts link to iframe
                const fontLink = frame.contentDocument.createElement('link');
                fontLink.href = "https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap";
                fontLink.rel = "stylesheet";
                frame.contentDocument.head.appendChild(fontLink);
                
                // Add Font Awesome to iframe
                const faLink = frame.contentDocument.createElement('link');
                faLink.href = "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css";
                faLink.rel = "stylesheet";
                frame.contentDocument.head.appendChild(faLink);
                
                frame.contentDocument.head.appendChild(style);
            }
        };
    });
</script>

</body>
</html>

