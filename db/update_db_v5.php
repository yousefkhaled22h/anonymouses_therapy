<?php
require_once '../includes/db_connect.php';
try {
    // 1. Remove ON UPDATE CURRENT_TIMESTAMP from session_date in private_sessions
    $pdo->exec("ALTER TABLE private_sessions MODIFY COLUMN session_date DATETIME NOT NULL");
    
    // 2. Remove ON UPDATE CURRENT_TIMESTAMP from session_date in group_sessions
    $pdo->exec("ALTER TABLE group_sessions MODIFY COLUMN session_date DATETIME NOT NULL");
    
    echo "Database updated successfully: session_date columns fixed.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
