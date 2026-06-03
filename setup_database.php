<?php
// setup_database.php

$database_file = __DIR__ . '/database.sqlite';

try {
    $conn = new PDO("sqlite:" . $database_file);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create Users Table
    $conn->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        role TEXT NOT NULL
    )");

    // Create Students Table
    $conn->exec("CREATE TABLE IF NOT EXISTS students (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        name TEXT NOT NULL,
        semester TEXT,
        branch TEXT,
        batch_code TEXT,
        FOREIGN KEY(username) REFERENCES users(username)
    )");

    // Create Teachers Table
    $conn->exec("CREATE TABLE IF NOT EXISTS teachers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        name TEXT NOT NULL,
        subjects TEXT,
        phone_number TEXT,
        FOREIGN KEY(username) REFERENCES users(username)
    )");

    // Create Attendance Table
    $conn->exec("CREATE TABLE IF NOT EXISTS attendance (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        teacher_name TEXT NOT NULL,
        student_name TEXT NOT NULL,
        subject_name TEXT NOT NULL,
        period_number INTEGER NOT NULL,
        date TEXT NOT NULL,
        status TEXT NOT NULL,
        batch_code TEXT
    )");

    // Create Batch GPS Table
    $conn->exec("CREATE TABLE IF NOT EXISTS batch_gps (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        batch_code TEXT UNIQUE NOT NULL,
        longitude REAL,
        latitude REAL
    )");

    // Create Batch Subjects Table
    $conn->exec("CREATE TABLE IF NOT EXISTS batch_subjects (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        batch_code TEXT NOT NULL,
        subject_name TEXT NOT NULL,
        notes_link TEXT,
        UNIQUE(batch_code, subject_name)
    )");
    
    // Cleanup duplicate subjects that might have been created
    $conn->exec("DELETE FROM batch_subjects WHERE id NOT IN (SELECT MIN(id) FROM batch_subjects GROUP BY batch_code, subject_name)");

    // Create Notifications Table
    $conn->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        message TEXT NOT NULL,
        expiry_time TEXT
    )");

    // Create Attendance Session Table
    $conn->exec("CREATE TABLE IF NOT EXISTS attendance_session (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        batch_code TEXT NOT NULL,
        subject_name TEXT NOT NULL,
        username TEXT NOT NULL,
        session_start TEXT NOT NULL,
        session_end TEXT NOT NULL,
        session_date TEXT NOT NULL,
        period INTEGER NOT NULL,
        FOREIGN KEY(username) REFERENCES users(username)
    )");

    // Insert Default Admin if not exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $admin_pass = password_hash('admin123', PASSWORD_DEFAULT); // Modern Hashing
        $conn->prepare("INSERT INTO users (username, password, role) VALUES ('admin', ?, 'admin')")->execute([$admin_pass]);
        echo "Default admin account created (admin/admin123).<br>";
    }

    echo "Database initialized successfully at " . $database_file;

} catch (PDOException $e) {
    die("Database setup failed: " . $e->getMessage());
}
?>
