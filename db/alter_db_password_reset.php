<?php
require_once '../includes/db_connect.php';
try {
    // Add reset_token and reset_expires columns to the User table
    $sql = "ALTER TABLE User 
            ADD COLUMN IF NOT EXISTS reset_token VARCHAR(64) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS reset_expires DATETIME DEFAULT NULL,
            ADD UNIQUE KEY IF NOT EXISTS `reset_token_index` (`reset_token`);";
    $pdo->exec($sql);
    echo "Success: Added reset_token and reset_expires to User table.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>