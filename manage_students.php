<?php
session_name("admin_session");
session_start();

// Ensure the user is logged in and has the correct role
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect form data
    $username = $_POST['username'];
    $name = $_POST['name'];
    $semester = $_POST['semester'];
    $branch = $_POST['branch'];
    $batch = $_POST['batch'];
    $capturedImage = $_POST['capturedImage']; // Base64 image data

    // Generate batch code
    $semester_code = 's' . substr($semester, -1);
    $branch_code = strtolower($branch);
    $batch_code = $semester_code . $branch_code . strtolower($batch);

    // Database connection
include 'db_connect.php';

    // Insert into users table
    $raw_password = 'student';
    $hashed_password = password_hash($raw_password, PASSWORD_DEFAULT);
    $role = 'student';
    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->execute([$username, $hashed_password, $role]);

    // Insert into students table
    $stmt = $conn->prepare("INSERT INTO students (username, name, semester, branch, batch_code) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$username, $name, $semester, $branch, $batch_code]);

    // Save image to uploads folder
    if (!empty($capturedImage)) {
        $imageData = str_replace('data:image/jpeg;base64,', '', $capturedImage);
        $imageData = base64_decode($imageData);
        $imagePath = "uploads/" . $username . ".jpg";
        file_put_contents($imagePath, $imageData);
    }

    echo "Student added successfully!";
    // PDO connection closes automatically
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/light-theme.css">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--background-color);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
            padding: 30px 15px;
        }

        .container {
            max-width: 850px;
            margin: 0 auto;
            padding: 2.5rem;
            background: var(--card-background);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            position: relative;
            overflow: hidden;
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color), var(--accent-color));
        }

        .page-title {
            text-align: center;
            color: var(--primary-color);
            margin-bottom: 2.5rem;
            font-size: 2.2rem;
            font-weight: 700;
            position: relative;
            display: inline-block;
            left: 50%;
            transform: translateX(-50%);
        }

        .page-title::after {
            content: '';
            display: block;
            width: 70px;
            height: 4px;
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            margin: 0.5rem auto 0;
            border-radius: 2px;
        }

        .form-header {
            margin-bottom: 2rem;
            text-align: center;
        }

        .form-header p {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        .input-group {
            position: relative;
            margin-bottom: 1.8rem;
        }

        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
            font-size: 0.95rem;
        }

        .input-icon-wrapper {
            position: relative;
        }

        .input-icon-wrapper i {
            position: absolute;
            left: 1.2rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            transition: all var(--transition-speed);
        }

        input, select {
            width: 100%;
            padding: 1rem 1.2rem 1rem 3rem;
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            transition: all var(--transition-speed);
            color: var(--text-primary);
            background-color: #ffffff;
        }

        input:focus, select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(167, 139, 250, 0.15);
            outline: none;
        }

        input:focus + i {
            color: var(--primary-color);
        }

        select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%234361ee' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1.5em;
            padding-right: 3rem;
        }

        .notification {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            display: none;
            font-weight: 500;
            align-items: center;
            animation: slideInDown 0.5s ease-out;
        }

        .notification i {
            margin-right: 10px;
            font-size: 1.2rem;
        }

        .notification.success {
            background-color: rgba(52, 211, 153, 0.1);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        .notification.error {
            background-color: rgba(244, 63, 94, 0.1);
            color: var(--error-color);
            border-left: 4px solid var(--error-color);
        }

        .camera-container {
            background: linear-gradient(to right, #faf5ff, #f1f5f9);
            padding: 1.8rem;
            border-radius: var(--border-radius);
            margin-top: 1.5rem;
            text-align: center;
            border: 1px solid var(--border-color);
        }

        .camera-container h3 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        video, #preview {
            width: 320px;
            height: 240px;
            border-radius: var(--border-radius);
            margin: 1.5rem auto;
            background: #000;
            object-fit: cover;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .button-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-top: 2.5rem;
        }

        .btn {
            padding: 1rem 1.5rem;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-speed);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.7rem;
            font-family: 'Inter', sans-serif;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.2);
            transition: width 0.3s ease;
            z-index: -1;
        }

        .btn:hover::before {
            width: 100%;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            box-shadow: 0 4px 12px rgba(30, 58, 138, 0.2);
        }

        .btn-primary:hover {
            box-shadow: 0 6px 16px rgba(167, 139, 250, 0.35);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: linear-gradient(to right, #64748b, #94a3b8);
            color: white;
            box-shadow: 0 4px 10px rgba(100, 116, 139, 0.3);
        }

        .btn-secondary:hover {
            box-shadow: 0 6px 15px rgba(100, 116, 139, 0.4);
            transform: translateY(-3px);
        }

        .loading {
            display: none;
            text-align: center;
            margin: 1.5rem 0;
        }

        .spinner {
            width: 40px;
            height: 40px;
            margin: 0 auto;
            border: 4px solid rgba(67, 97, 238, 0.1);
            border-radius: 50%;
            border-top-color: var(--primary-color);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes slideInDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 1.5rem;
            }
            
            .button-group {
                grid-template-columns: 1fr;
            }
            
            video, #preview {
                width: 100%;
                height: auto;
            }
        }

        .floating-label {
            position: absolute;
            left: 3rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1rem;
            color: var(--text-light);
            pointer-events: none;
            transition: all var(--transition-speed);
        }

        input:focus ~ .floating-label,
        input:not(:placeholder-shown) ~ .floating-label,
        select:focus ~ .floating-label,
        select:not([value=""]):not(:focus) ~ .floating-label {
            top: 0;
            left: 1rem;
            font-size: 0.75rem;
            padding: 0 5px;
            background-color: white;
            color: var(--primary-color);
        }

        input::placeholder {
            color: transparent;
        }

        .badge {
            display: inline-block;
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 600;
            line-height: 1;
            color: #fff;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
            background-color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="page-title">Student Management</h1>
        
        <div class="form-header">
            <p>Add new students to the system by filling out the form below</p>
        </div>
        
        <div id="notification" class="notification"></div>
        
        <form id="studentForm" method="POST">
            <div class="input-group">
                <label for="username">Username <span class="badge">Required</span></label>
                <div class="input-icon-wrapper">
                    <input type="text" id="username" name="username" placeholder=" " required>
                    <i class="fas fa-user"></i>
                    <span class="floating-label">Enter username</span>
                </div>
            </div>

            <div class="input-group">
                <label for="name">Full Name <span class="badge">Required</span></label>
                <div class="input-icon-wrapper">
                    <input type="text" id="name" name="name" placeholder=" " required>
                    <i class="fas fa-id-card"></i>
                    <span class="floating-label">Enter full name</span>
                </div>
            </div>

            <div class="input-group">
                <label for="semester">Semester <span class="badge">Required</span></label>
                <div class="input-icon-wrapper">
                    <select id="semester" name="semester" required>
                        <option value="">Select Semester</option>
                        <option value="Semester 1">Semester 1</option>
                        <option value="Semester 2">Semester 2</option>
                        <option value="Semester 3">Semester 3</option>
                        <option value="Semester 4">Semester 4</option>
                        <option value="Semester 5">Semester 5</option>
                        <option value="Semester 6">Semester 6</option>
                        <option value="Semester 7">Semester 7</option>
                        <option value="Semester 8">Semester 8</option>
                    </select>
                    <i class="fas fa-graduation-cap"></i>
                </div>
            </div>

            <div class="input-group">
                <label for="branch">Branch <span class="badge">Required</span></label>
                <div class="input-icon-wrapper">
                    <select id="branch" name="branch" required>
                        <option value="">Select Branch</option>
                        <option value="CSE">Computer Science Engineering</option>
                        <option value="EEE">Electrical & Electronics Engineering</option>
                        <option value="CIVIL">Civil Engineering</option>
                    </select>
                    <i class="fas fa-code-branch"></i>
                </div>
            </div>

            <div class="input-group">
                <label for="batch">Batch <span class="badge">Required</span></label>
                <div class="input-icon-wrapper">
                    <select id="batch" name="batch" required>
                        <option value="">Select Batch</option>
                        <option value="A">Batch A</option>
                        <option value="B">Batch B</option>
                    </select>
                    <i class="fas fa-users"></i>
                </div>
            </div>

            <div class="camera-container">
                <h3><i class="fas fa-camera"></i> Student Photo <span class="badge">Required</span></h3>
                <video id="video" autoplay></video>
                <canvas id="canvas" style="display:none;"></canvas>
                <img id="preview" src="" alt="Captured Image" style="display:none;">
                <input type="hidden" id="capturedImage" name="capturedImage">
                <button type="button" id="capture" class="btn-back">
                    <i class="fas fa-camera"></i> Capture Photo
                </button>
            </div>

            <div class="loading">
                <div class="spinner"></div>
            </div>

            <div class="button-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Add Student
                </button>
                <button type="button" class="btn-back" onclick="window.location.href='welcome_admin.php'">
                    <i class="fas fa-arrow-left"></i> Go Back
                </button>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        // Enhanced notification function
        function showNotification(message, type) {
            const notification = $('#notification');
            notification.removeClass('success error').addClass(type);
            notification.html(message).fadeIn();
            setTimeout(() => notification.fadeOut(), 5000);
        }

        let video = document.getElementById('video');
        let canvas = document.getElementById('canvas');
        let preview = document.getElementById('preview');
        let capturedImageInput = document.getElementById('capturedImage');

        // Initialize camera
        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            navigator.mediaDevices.getUserMedia({ 
                video: { 
                    facingMode: "user",
                    width: { ideal: 320 },
                    height: { ideal: 240 }
                } 
            })
            .then((stream) => {
                video.srcObject = stream;
                video.play();
            })
            .catch((err) => {
                console.error("Error accessing webcam: ", err);
                showNotification('<i class="fas fa-exclamation-circle"></i> Please allow camera access to capture student photo', 'error');
            });
        }

        // Capture photo
        $('#capture').click(function() {
            let context = canvas.getContext('2d');
            canvas.width = video.videoWidth || 320;
            canvas.height = video.videoHeight || 240;
            context.drawImage(video, 0, 0, canvas.width, canvas.height);

            let imageData = canvas.toDataURL('image/jpeg', 0.8);
            preview.src = imageData;
            video.style.display = 'none';
            preview.style.display = 'block';
            capturedImageInput.value = imageData;
            
            $(this).html('<i class="fas fa-redo"></i> Retake Photo')
                .off('click')
                .click(function() {
                    video.style.display = 'block';
                    preview.style.display = 'none';
                    $(this).html('<i class="fas fa-camera"></i> Capture Photo');
                });
        });

        // Form submission
        $('#studentForm').on('submit', function(e) {
            e.preventDefault();
            $('.loading').show();
            
            $.ajax({
                type: 'POST',
                url: 'manage_students.php',
                data: $(this).serialize(),
                success: function(response) {
                    showNotification('<i class="fas fa-check-circle"></i> Student added successfully!', 'success');
                    $('#studentForm')[0].reset();
                    video.style.display = 'block';
                    preview.style.display = 'none';
                    $('#capture').html('<i class="fas fa-camera"></i> Capture Photo');
                },
                error: function() {
                    showNotification('<i class="fas fa-exclamation-triangle"></i> Error adding student. Please try again.', 'error');
                },
                complete: function() {
                    $('.loading').hide();
                }
            });
        });
    });
    </script>
</body>
</html>