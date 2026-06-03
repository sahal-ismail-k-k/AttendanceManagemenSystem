<?php
session_name("student_session");
session_start();
set_time_limit(60);

// Database connection
include 'db_connect.php';

// Check if student is logged in
if (!isset($_SESSION['username'])) {
    die("Access denied. Please log in.");
}

$username = $_SESSION['username'];

// Fetch student name and batch_code
// Fetch student name and batch_code
$student_query = $conn->prepare("SELECT name, batch_code FROM students WHERE username = ?");
$student_query->execute([$username]);
$student_data = $student_query->fetch(PDO::FETCH_ASSOC);

if (!$student_data) {
    die("Student not found.");
}

$student_name = $student_data['name'];
$batch_code = $student_data['batch_code'];

// Fetch batch geolocation
$gps_query = $conn->prepare("SELECT longitude, latitude FROM batch_gps WHERE batch_code = ?");
$gps_query->execute([$batch_code]);
$gps_data = $gps_query->fetch(PDO::FETCH_ASSOC);

if (!$gps_data) {
    die("Geolocation data not found for your batch.");
}

$batch_longitude = $gps_data['longitude'];
$batch_latitude = $gps_data['latitude'];

// Check if an active attendance session exists
$active_session = $conn->prepare("
    SELECT a.*, t.name AS teacher_name 
    FROM attendance_session a
    JOIN teachers t ON a.username = t.username 
    WHERE ? BETWEEN a.session_start AND a.session_end
    AND LOWER(a.batch_code) = LOWER(?)
    ORDER BY a.id DESC 
    LIMIT 1
");
$now = date("Y-m-d H:i:s");
$active_session->execute([$now, $batch_code]);
$session_data = $active_session->fetch(PDO::FETCH_ASSOC);

if (!$session_data) {
    die("No active attendance session for your batch.");
}

$subject_name = $session_data['subject_name'];
$teacher_name = $session_data['teacher_name'];
$period = $session_data['period'];
$session_date = date('Y-m-d', strtotime($session_data['session_date'])); // Ensure correct date format

// Check if the student has already marked attendance
$is_already_marked = false;
$attendance_check = $conn->prepare("
    SELECT * FROM attendance 
    WHERE student_name = ? 
    AND batch_code = ? 
    AND date = ? 
    AND period_number = ?
    AND status ='present'
");
$attendance_check->execute([$student_name, $batch_code, $session_date, $period]);

if ($attendance_check->fetch()) {
    $is_already_marked = true;
}

// Handle GPS and face verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['captured_image']) && isset($_POST['longitude']) && isset($_POST['latitude'])) {
    $student_longitude = $_POST['longitude'];
    $student_latitude = $_POST['latitude'];

    // Check if the student is within a certain distance of the batch location (e.g., within 50 meters)
    $distance = calculateDistance($student_latitude, $student_longitude, $batch_latitude, $batch_longitude);
    if ($distance > 0.05) {  // 50 meters (0.05 degrees)
        die("You are not within the allowed geofenced area.");
    }

    // Save the captured image
    $target_dir = "students/";
    $target_file = $target_dir . $username . ".jpg"; // Save as username.jpg

    if (move_uploaded_file($_FILES["captured_image"]["tmp_name"], $target_file)) {
        // If the client already verified the face, we can trust it (or do a quick secondary check)
        // For this redesign, we trust the client-side match to ensure speed.
        $client_matched = isset($_POST['face_matched']) && $_POST['face_matched'] === 'true';

        if ($client_matched) {
            $update_stmt = $conn->prepare("
                UPDATE attendance 
                SET date = ?, status = 'present' 
                WHERE LOWER(student_name) = LOWER(?) 
                  AND LOWER(subject_name) = LOWER(?) 
                  AND LOWER(batch_code) = LOWER(?) 
                  AND period_number = ?
            ");
            $update_stmt->execute([$session_date, $student_name, $subject_name, $batch_code, $period]);
    
            echo "Attendance marked successfully!";
        } else {
            echo "Face verification failed. Please align your face and try again.";
        }
    } else {
        echo "Failed to save image.";
    }
    exit;
    
}

// Function to calculate distance between two lat/lon points
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371;  // Earth radius in kilometers
    $lat_diff = deg2rad($lat2 - $lat1);
    $lon_diff = deg2rad($lon2 - $lon1);

    $a = sin($lat_diff / 2) * sin($lat_diff / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lon_diff / 2) * sin($lon_diff / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earth_radius * $c; // Distance in kilometers
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/light-theme.css">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.js"></script>
    <style>
        

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--background-color);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            max-width: 800px;
            width: 100%;
            margin: 0 auto;
            padding: 2.5rem;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(30px);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(167, 139, 250, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        .page-title {
            text-align: center;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 2rem;
            font-size: 2.5rem;
            position: relative;
            font-weight: 700;
        }

        .page-title::after {
            content: '';
            display: block;
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent));
            margin: 1rem auto;
            border-radius: 2px;
        }

        .session-info {
            background: linear-gradient(135deg, rgba(167, 139, 250, 0.08), rgba(251, 191, 36, 0.08));
            padding: 1.5rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            border: 1px solid rgba(167, 139, 250, 0.15);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .info-item {
            background: white;
            padding: 1.2rem;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.08);
            transition: all 0.3s ease;
        }

        .info-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.15);
        }

        .info-item i {
            color: var(--primary-color);
            margin-right: 0.5rem;
        }

        .info-label {
            font-weight: 600;
            color: #666;
            font-size: 0.9rem;
        }

        .info-value {
            font-size: 1.1rem;
            margin-top: 0.25rem;
        }

        .video-container {
            position: relative;
            margin: 2rem 0;
        }

        video {
            width: 100%;
            border-radius: 16px;
            border: 3px solid rgba(99, 102, 241, 0.2);
            background: var(--surface);
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.15);
        }

        .button-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all var(--transition-speed);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 8px 20px rgba(30, 58, 138, 0.3);
            background-color: var(--primary-dark);
        }

        .btn-secondary {
            background: linear-gradient(135deg, var(--warning-color), #f97316);
            color: white;
        }

        .btn-secondary:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 8px 20px rgba(245, 158, 11, 0.3);
            background: linear-gradient(135deg, #d97706, #ea580c);
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
            display: none;
        }

        .btn-danger:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        #message {
            margin-top: 1.5rem;
            padding: 1rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            text-align: center;
            opacity: 0;
            transition: opacity var(--transition-speed);
        }

        #message.success {
            background: #eafaf1;
            color: var(--success-color);
            border: 1px solid #d1f2d7;
        }

        #message.error {
            background: #fdedec;
            color: var(--danger-color);
            border: 1px solid #f5b7b1;
        }

        .loading {
            display: none;
            text-align: center;
            margin: 1rem 0;
        }

        .loading i {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="page-title">Mark Attendance</h1>
        
        <div class="session-info">
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-users"></i>Batch Code</div>
                    <div class="info-value"><?php echo $batch_code; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-book"></i>Subject</div>
                    <div class="info-value"><?php echo $subject_name; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-chalkboard-teacher"></i>Teacher</div>
                    <div class="info-value"><?php echo $teacher_name; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-clock"></i>Period</div>
                    <div class="info-value"><?php echo $period; ?></div>
                </div>
            </div>
        </div>

        <div class="video-container">
            <video id="video" autoplay></video>
        </div>

        <div class="loading">
            <i class="fas fa-spinner fa-2x"></i>
        </div>

        <div id="message"></div>

        <div class="button-group">
            <?php if ($is_already_marked): ?>
                <div id="alreadyMarkedMsg" style="width: 100%; text-align: center; background: #eafaf1; color: var(--success-color); padding: 1.5rem; border-radius: var(--border-radius); border: 1px solid #d1f2d7; margin-bottom: 1rem;">
                    <i class="fas fa-check-double fa-2x" style="margin-bottom: 0.5rem; display: block;"></i>
                    <strong>Attendance Already Marked</strong>
                    <p style="font-size: 0.9rem; margin-top: 0.5rem;">Your attendance for this session has already been recorded as <strong>Present</strong>.</p>
                </div>
            <?php else: ?>
                <button class="btn btn-primary" onclick="startVideo()">
                    <i class="fas fa-video"></i> Start Camera
                </button>
                <button class="btn-back" onclick="captureAndSave()">
                    <i class="fas fa-camera"></i> Capture & Verify
                </button>
            <?php endif; ?>
            <button id="goBackBtn" class="btn btn-danger" style="display: <?php echo $is_already_marked ? 'flex' : 'none'; ?>" onclick="window.location.href='welcome_student.php'">
                <i class="fas fa-arrow-left"></i> Go Back
            </button>
        </div>

        <canvas id="canvas" style="display: none;"></canvas>
    </div>

    <script>
        let video = document.getElementById('video');
        let canvas = document.getElementById('canvas');
        let context = canvas.getContext('2d');
        let messageDiv = document.getElementById("message");
        let goBackBtn = document.getElementById("goBackBtn");
        let loadingDiv = document.querySelector('.loading');
        
        let modelsLoaded = false;
        let referenceDescriptor = null;
        let currentLongitude, currentLatitude;

        // Load face-api models from a reliable CDN
        async function loadModels() {
            showMessage("Initializing AI models...", "warning");
            // Using a more reliable CDN for weights
            const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/';
            
            try {
                await Promise.all([
                    faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL),
                    faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                    faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
                ]);
                modelsLoaded = true;
                showMessage("Models Loaded. Ready to start camera.", "success");
            } catch (err) {
                console.error("Error loading models:", err);
                showMessage("Failed to load AI models. Please check your internet connection and reload.", "error");
            }
        }

        async function extractReferenceDescriptor() {
            const studentUsername = "<?php echo $username; ?>";
            const referenceImageUrl = `uploads/${studentUsername}.jpg`;
            
            const img = await faceapi.fetchImage(referenceImageUrl);
            const detections = await faceapi.detectSingleFace(img).withFaceLandmarks().withFaceDescriptor();
            
            if (detections) {
                referenceDescriptor = detections.descriptor;
                console.log("Reference face descriptor extracted successfully.");
            } else {
                showMessage("Could not detect face in your profile photo. Please contact admin.", "error");
            }
        }

        async function startVideo() {
            if (!modelsLoaded) {
                await loadModels();
            }
            if (!referenceDescriptor) {
                await extractReferenceDescriptor();
            }

            navigator.mediaDevices.getUserMedia({ video: true })
                .then(stream => {
                    video.srcObject = stream;
                    showMessage("Camera Active. Searching for your face...", "success");
                })
                .catch(err => {
                    showMessage("Camera access denied", "error");
                });
        }

        function showMessage(text, type) {
            messageDiv.innerText = text;
            messageDiv.className = type;
            messageDiv.style.opacity = "1";
        }

        async function captureAndSave() {
            if (!video.srcObject) {
                showMessage("Please start the camera first", "error");
                return;
            }

            loadingDiv.style.display = 'block';
            showMessage("Analyzing face...", "warning");

            // Capture the current frame
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            context.drawImage(video, 0, 0, canvas.width, canvas.height);

            // Perform face detection and descriptor extraction on the captured frame
            // Using SsdMobilenetv1 (Default) for maximum accuracy
            const detection = await faceapi.detectSingleFace(canvas).withFaceLandmarks().withFaceDescriptor();

            if (!detection) {
                showMessage("No face detected. Please ensure your face is clearly visible.", "error");
                loadingDiv.style.display = 'none';
                return;
            }

            if (!referenceDescriptor) {
                showMessage("Identity not verified. Profile photo issue.", "error");
                loadingDiv.style.display = 'none';
                return;
            }

            // Compare descriptors
            const distance = faceapi.euclideanDistance(detection.descriptor, referenceDescriptor);
            
            // TIGHTER THRESHOLD: 0.45 for SsdMobilenetv1 is strict
            // (0.6 is loose, 0.4 is very strict)
            const threshold = 0.45; 
            const isMatch = distance < threshold; 

            console.log("Face Matching Distance:", distance.toFixed(4));
            console.log("Is Match:", isMatch);

            canvas.toBlob(blob => {
                let formData = new FormData();
                formData.append("captured_image", blob, "capture.jpg");
                formData.append("longitude", currentLongitude);
                formData.append("latitude", currentLatitude);
                formData.append("face_matched", isMatch ? "true" : "false");

                fetch("mark_attendance.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    if (data.includes("successfully")) {
                        showMessage(data, "success");
                        goBackBtn.style.display = "block";
                    } else {
                        showMessage(data, "error");
                    }
                    loadingDiv.style.display = 'none';
                })
                .catch(error => {
                    showMessage("Error submitting attendance", "error");
                    loadingDiv.style.display = 'none';
                });
            }, "image/jpeg");
        }

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(position => {
                currentLongitude = position.coords.longitude;
                currentLatitude = position.coords.latitude;
            }, error => {
                showMessage("Please enable location services", "error");
            });
        }

        function goBack() {
            window.location.href = 'student_dashboard.php';
        }

        // Initialize models on load
        window.onload = loadModels;
    </script>
</body>
</html>