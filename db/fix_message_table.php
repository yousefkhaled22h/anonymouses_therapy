<?php
// db/fix_message_table.php
require_once __DIR__ . '/../includes/db_connect.php';

try {
    $pdo->exec("ALTER TABLE private_session_messages MODIFY private_session_id VARCHAR(50) NOT NULL");
    $pdo->exec("ALTER TABLE private_session_messages MODIFY user_id VARCHAR(50) NOT NULL");
    echo "private_session_messages table schema fixed successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
