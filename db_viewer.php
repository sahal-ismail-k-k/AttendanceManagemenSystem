<?php
include 'db_connect.php';

// CSS for styling
echo '<style>
    body { font-family: sans-serif; padding: 20px; background: #f4f4f4; }
    h1 { color: #333; }
    table { width: 100%; border-collapse: collapse; background: white; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
    th { background: #007bff; color: white; }
    tr:nth-child(even) { background: #f9f9f9; }
    a { text-decoration: none; color: #007bff; font-weight: bold; }
    a:hover { text-decoration: underline; }
    .btn { display: inline-block; padding: 10px 15px; background: #333; color: white; border-radius: 5px; margin-bottom: 15px; }
</style>';

echo '<a href="db_viewer.php" class="btn">🏠 Home / List Tables</a><br><br>';

// List all tables
if (!isset($_GET['table'])) {
    echo '<h1>📂 Database Tables</h1>';
    $tables = $conn->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_ASSOC);
    
    echo '<table>';
    echo '<tr><th>Table Name</th><th>Action</th></tr>';
    foreach ($tables as $table) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($table['name']) . '</td>';
        echo '<td><a href="?table=' . urlencode($table['name']) . '">View Data ➡</a></td>';
        echo '</tr>';
    }
    echo '</table>';
} 
// View specific table data
else {
    $tableName = $_GET['table'];
    echo "<h1>📄 Table: " . htmlspecialchars($tableName) . "</h1>";
    
    try {
        $stmt = $conn->query("SELECT * FROM " . $tableName);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($rows)) {
            echo "<p><i>This table is empty.</i></p>";
        } else {
            echo '<table>';
            // Header
            echo '<tr>';
            foreach (array_keys($rows[0]) as $col) {
                echo '<th>' . htmlspecialchars($col) . '</th>';
            }
            echo '</tr>';
            
            // Data
            foreach ($rows as $row) {
                echo '<tr>';
                foreach ($row as $val) {
                    echo '<td>' . htmlspecialchars($val ?? "NULL") . '</td>';
                }
                echo '</tr>';
            }
            echo '</table>';
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>Error reading table: " . $e->getMessage() . "</p>";
    }
}

// SQL Execution Section
echo "<hr style='margin-top:40px'>";
echo "<h2>⚡ Run SQL Command</h2>";
echo "<form method='POST' action=''>
    <textarea name='sql_query' rows='5' style='width:100%; padding:10px; font-family:monospace;' placeholder='UPDATE users SET role = \"admin\" WHERE username = \"test\";'></textarea><br><br>
    <button type='submit' class='btn' style='background: #fb7185;'>Execute Query</button>
</form>";

if (isset($_POST['sql_query'])) {
    $sql = trim($_POST['sql_query']);
    if (!empty($sql)) {
        try {
            $stmt = $conn->prepare($sql);
            if ($stmt->execute()) {
                echo "<div style='padding:15px; background:#d4edda; color:#155724; border:1px solid #c3e6cb; border-radius:5px;'>✅ SQL Executed Successfully. affected rows: " . $stmt->rowCount() . "</div>";
            } else {
                echo "<div style='padding:15px; background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; border-radius:5px;'>❌ Execution Failed.</div>";
            }
        } catch (Exception $e) {
            echo "<div style='padding:15px; background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; border-radius:5px;'>⚠️ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}
?>
