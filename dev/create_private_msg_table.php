<?php
// create_private_msg_table.php
require 'includes/db_connect.php';
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS private_session_messages (
        message_id INT AUTO_INCREMENT PRIMARY KEY,
        private_session_id INT NOT NULL,
        user_id INT NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Table created successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
