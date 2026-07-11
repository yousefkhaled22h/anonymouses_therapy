<?php
require 'includes/db_connect.php';
try {
    $pdo->exec("ALTER TABLE group_sessions ADD COLUMN room_name VARCHAR(255) DEFAULT NULL AFTER group_session_id;");
    echo "Success";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>