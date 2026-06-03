<?php
include 'db_connect.php';

// Set time zone
date_default_timezone_set("Asia/Kolkata");

echo "<h1>🚀 Generating Bulk Data...</h1>";

try {
    // 1. Define Data
    $teacher_pass = password_hash('teacher', PASSWORD_DEFAULT);
    $student_pass = password_hash('student', PASSWORD_DEFAULT);

    $teachers_data = [
        ['username' => 'alice_t', 'name' => 'Alice Thompson', 'phone' => '9876543210', 'subjects' => 'Maths,Physics'],
        ['username' => 'bob_j', 'name' => 'Bob Johnson', 'phone' => '9876543211', 'subjects' => 'Web Dev,Databases'],
        ['username' => 'charlie_d', 'name' => 'Charlie Davis', 'phone' => '9876543212', 'subjects' => 'Operating Systems,Networks']
    ];

    $batches = ['s1csea', 's1cseb'];
    $batch_subjects = [
        's1csea' => ['Maths', 'Physics', 'Web Dev'],
        's1cseb' => ['Databases', 'Operating Systems', 'Networks']
    ];

    $student_names = [
        's1csea' => ['John Doe', 'Jane Smith', 'Mike Brown', 'Sarah Wilson', 'Kevin Lee'],
        's1cseb' => ['Anna Bell', 'Chris Evans', 'David Gandy', 'Emma Watson', 'Frank Miller']
    ];

    // Optional Cleanup
    if (isset($_GET['clean'])) {
        echo "Cleaning old data...<br>";
        $conn->exec("DELETE FROM users WHERE role != 'admin'");
        $conn->exec("DELETE FROM teachers");
        $conn->exec("DELETE FROM students");
        $conn->exec("DELETE FROM attendance");
        $conn->exec("DELETE FROM attendance_session");
        $conn->exec("DELETE FROM batch_subjects");
    }

    echo "Adding Teachers and Syncing Subjects...<br>";
    $all_mock_subjects = implode(",", array_unique(array_merge(...array_values($batch_subjects))));
    
    foreach ($teachers_data as $t) {
        $stmt = $conn->prepare("INSERT OR IGNORE INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute([$t['username'], $teacher_pass, 'teacher']);
        
        $stmt = $conn->prepare("INSERT OR IGNORE INTO teachers (username, name, subjects, phone_number) VALUES (?, ?, ?, ?)");
        $stmt->execute([$t['username'], $t['name'], $t['subjects'], $t['phone']]);
    }

    // Force all existing teachers to have these subjects so they can see labels in dropdown
    $stmt = $conn->prepare("UPDATE teachers SET subjects = ?");
    $stmt->execute([$all_mock_subjects]);
    echo "Synced subjects for all teachers: $all_mock_subjects<br>";

    echo "Adding Students and Batch Subjects...<br>";
    foreach ($batches as $batch) {
        // Add subjects to batch
        foreach ($batch_subjects[$batch] as $subj) {
            $stmt = $conn->prepare("INSERT OR IGNORE INTO batch_subjects (batch_code, subject_name) VALUES (?, ?)");
            $stmt->execute([$batch, $subj]);
        }

        // Add students
        foreach ($student_names[$batch] as $index => $name) {
            $user = strtolower(str_replace(' ', '_', $name));
            $stmt = $conn->prepare("INSERT OR IGNORE INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->execute([$user, $student_pass, 'student']);

            $stmt = $conn->prepare("INSERT OR IGNORE INTO students (username, name, batch_code, semester, branch) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user, $name, $batch, 'Semester 1', 'CSE']);
        }
    }

    echo "Generating 30 Days of Attendance...<br>";
    // Fetch all teachers including the new ones and any user-created ones
    $stmt = $conn->query("SELECT name, username FROM teachers");
    $all_teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($all_teachers)) {
        throw new Exception("No teachers found to assign data to.");
    }

    // Go back 30 days
    for ($i = 30; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $dayNum = date('N', strtotime($date));
        if ($dayNum == 7) continue; // Skip Sundays

        foreach ($batches as $batch) {
            // Pick 3 random subjects for this batch today
            $daily_subjects = (array) array_rand(array_flip($batch_subjects[$batch]), count($batch_subjects[$batch]));
            
            foreach ($daily_subjects as $index => $subject) {
                $period = $index + 1;
                // Pick a random teacher from the WHOLE database
                $teacher = $all_teachers[array_rand($all_teachers)];
                
                $start = "$date 09:00:00";
                $end = "$date 09:10:00";
                
                // Create Session
                $stmt = $conn->prepare("INSERT INTO attendance_session (batch_code, subject_name, username, session_start, session_end, session_date, period) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$batch, $subject, $teacher['username'], $start, $end, $date, $period]);

                // Create Attendance for each student
                $studentsStmt = $conn->prepare("SELECT name FROM students WHERE batch_code = ?");
                $studentsStmt->execute([$batch]);
                $students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($students as $student) {
                    $status = (rand(1, 10) > 2) ? 'present' : 'absent'; // 80% presence
                    $stmt = $conn->prepare("INSERT INTO attendance (student_name, teacher_name, subject_name, batch_code, period_number, date, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$student['name'], $teacher['name'], $subject, $batch, $period, $date, $status]);
                }
            }
        }
    }

    echo "🎉 <b>Success!</b> Bulk data generated successfully.<br>";
} catch (Exception $e) {
    echo "❌ <b>Error:</b> " . $e->getMessage();
}
?>
