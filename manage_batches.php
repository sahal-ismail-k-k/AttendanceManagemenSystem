<?php
session_name("admin_session");
session_start();

// Ensure the user is logged in and has the correct role
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collecting form data
    $batch_code = $_POST['batch_code'];
    $longitude = $_POST['longitude'];
    $latitude = $_POST['latitude'];
    $num_subjects = intval($_POST['num_subjects']); // Convert input to integer

    // Database connection
include 'db_connect.php';

    // Insert batch details into batch_gps table
    $stmt = $conn->prepare("INSERT INTO batch_gps (batch_code, longitude, latitude) VALUES (?, ?, ?)");
    $stmt->execute([$batch_code, $longitude, $latitude]);

    // Insert subjects and notes into batch_subjects table
    $stmt = $conn->prepare("INSERT INTO batch_subjects (batch_code, subject_name, notes_link) VALUES (?, ?, ?)");
    
    for ($i = 1; $i <= $num_subjects; $i++) {
        $subject_name = $_POST["subject_$i"];
        $notes_link = $_POST["notes_$i"];
        $stmt->execute([$batch_code, $subject_name, $notes_link]);
    }
    
    $conn = null;

    echo json_encode(['status' => 'success', 'message' => 'Batch and subjects added successfully!']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Batches</title>
    
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

        .subjects-container {
            background: linear-gradient(to right, #faf5ff, #f1f5f9);
            padding: 1.8rem;
            border-radius: var(--border-radius);
            margin-top: 1.5rem;
            border: 1px solid var(--border-color);
        }

        .subjects-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .subjects-title {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        .subject-input {
            background-color: white;
            border-radius: calc(var(--border-radius) - 4px);
            padding: 0.8rem;
            margin-bottom: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: transform var(--transition-speed);
        }

        .subject-input:hover {
            transform: translateY(-2px);
        }

        .subject-input:last-child {
            margin-bottom: 0;
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
            border: 4px solid rgba(167, 139, 250, 0.15);
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
        input:not(:placeholder-shown) ~ .floating-label {
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
        <h1 class="page-title">Batch Management</h1>
        
        <div class="form-header">
            <p>Add new batches to the system by filling out the form below</p>
        </div>
        
        <div id="notification" class="notification"></div>
        
        <form id="batchForm" method="POST">
            <div class="input-group">
                <label for="batch_code">Batch Code <span class="badge">Required</span></label>
                <div class="input-icon-wrapper">
                    <input type="text" id="batch_code" name="batch_code" placeholder=" " required>
                    <i class="fas fa-fingerprint"></i>
                    <span class="floating-label">Enter batch code</span>
                </div>
            </div>

            <div class="input-group">
                <label for="longitude">Longitude <span class="badge">Required</span></label>
                <div class="input-icon-wrapper">
                    <input type="number" step="0.0000001" id="longitude" name="longitude" placeholder=" " required>
                    <i class="fas fa-map-marker-alt"></i>
                    <span class="floating-label">Enter longitude</span>
                </div>
            </div>

            <div class="input-group">
                <label for="latitude">Latitude <span class="badge">Required</span></label>
                <div class="input-icon-wrapper">
                    <input type="number" step="0.0000001" id="latitude" name="latitude" placeholder=" " required>
                    <i class="fas fa-location-arrow"></i>
                    <span class="floating-label">Enter latitude</span>
                </div>
            </div>

            <div class="input-group">
                <label for="num_subjects">Number of Subjects <span class="badge">Required</span></label>
                <div class="input-icon-wrapper">
                    <input type="number" id="num_subjects" name="num_subjects" placeholder=" " min="1" required>
                    <i class="fas fa-list-ol"></i>
                    <span class="floating-label">Enter number of subjects</span>
                </div>
            </div>

            <div class="subjects-container" id="subjectsContainer">
                <div class="subjects-header">
                    <span class="subjects-title">Subject Information</span>
                </div>
                <p class="text-secondary">Please specify the number of subjects above to add subject details</p>
            </div>

            <div class="loading">
                <div class="spinner"></div>
            </div>

            <div class="button-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Add Batch
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
        function showNotification(message, type) {
            const notification = $('#notification');
            notification.removeClass('success error').addClass(type);
            notification.html(message).fadeIn();
            setTimeout(() => notification.fadeOut(), 5000);
        }

        function updateSubjectFields() {
            const numSubjects = parseInt($('#num_subjects').val()) || 0;
            const container = $('#subjectsContainer');
            container.empty();
            
            if (numSubjects > 0) {
                container.append(`
                    <div class="subjects-header">
                        <span class="subjects-title">Subject Information</span>
                        <span class="badge">${numSubjects} Subject${numSubjects > 1 ? 's' : ''}</span>
                    </div>
                `);
                
                for (let i = 1; i <= numSubjects; i++) {
                    container.append(`
                        <div class="subject-input">
                            <div class="input-group" style="margin-bottom: 0;">
                                <div class="input-icon-wrapper">
                                    <input type="text" id="subject_${i}" name="subject_${i}" 
                                           placeholder=" " required>
                                    <i class="fas fa-book"></i>
                                    <span class="floating-label">Enter subject ${i} name</span>
                                </div>
                            </div>
                        </div>
                        <div class="subject-input">
                            <div class="input-group" style="margin-bottom: 0;">
                                <div class="input-icon-wrapper">
                                    <input type="url" id="notes_${i}" name="notes_${i}" 
                                           placeholder=" " required>
                                    <i class="fas fa-link"></i>
                                    <span class="floating-label">Enter notes link for subject ${i}</span>
                                </div>
                            </div>
                        </div>
                    `);
                }
            } else {
                container.append(`
                    <div class="subjects-header">
                        <span class="subjects-title">Subject Information</span>
                    </div>
                    <p class="text-secondary">Please specify the number of subjects above to add subject details</p>
                `);
            }
        }

        $('#num_subjects').on('input', updateSubjectFields);

        if ("geolocation" in navigator) {
            navigator.geolocation.getCurrentPosition(function(position) {
                $('#longitude').val(position.coords.longitude);
                $('#latitude').val(position.coords.latitude);
            });
        }

        $('#batchForm').on('submit', function(e) {
            e.preventDefault();
            $('.loading').show();
            
            $.ajax({
                type: 'POST',
                url: 'manage_batches.php',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        showNotification('<i class="fas fa-check-circle"></i> ' + response.message, 'success');
                        $('#batchForm')[0].reset();
                        updateSubjectFields();
                    } else {
                        showNotification('<i class="fas fa-exclamation-circle"></i> ' + response.message, 'error');
                    }
                },
                error: function() {
                    showNotification('<i class="fas fa-exclamation-triangle"></i> Error adding batch. Please try again.', 'error');
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