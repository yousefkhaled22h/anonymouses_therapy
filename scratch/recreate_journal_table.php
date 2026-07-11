<?php
require_once 'includes/db_connect.php';

try {
    // 1. Drop the broken table
    $pdo->exec("DROP TABLE IF EXISTS Journal_Entry");
    echo "Dropped broken Journal_Entry table.\n";
    
    // 2. Recreate it with the CORRECT schema
    $pdo->exec("CREATE TABLE Journal_Entry (
        entry_id INT AUTO_INCREMENT PRIMARY KEY,
        client_id VARCHAR(50) NOT NULL,
        title VARCHAR(255) DEFAULT 'Untitled',
        content LONGTEXT,
        mood VARCHAR(50) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_client (client_id)
    )");
    echo "Recreated Journal_Entry table with correct schema.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
