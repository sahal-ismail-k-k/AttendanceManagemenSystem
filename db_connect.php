<?php
date_default_timezone_set("Asia/Kolkata");
$database_file = __DIR__ . '/database.sqlite';

try {
    $conn = new PDO("sqlite:" . $database_file);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
