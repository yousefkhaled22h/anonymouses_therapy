<?php
require_once 'includes/db_connect.php';

try {
    // 1. Alter Journal_Entry table if it exists
    $pdo->exec("ALTER TABLE Journal_Entry MODIFY client_id VARCHAR(50) NOT NULL");
    echo "Altered Journal_Entry successfully.\n";
} catch (Exception $e) {
    echo "Error altering table (it might not exist yet): " . $e->getMessage() . "\n";
    
    // 2. Try creating it correctly if it doesn't exist
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS Journal_Entry (
            entry_id INT AUTO_INCREMENT PRIMARY KEY,
            client_id VARCHAR(50) NOT NULL,
            title VARCHAR(255) DEFAULT 'Untitled',
            content LONGTEXT,
            mood VARCHAR(50) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        echo "Created Journal_Entry successfully.\n";
    } catch (Exception $e2) {
        echo "Error creating table: " . $e2->getMessage() . "\n";
    }
}
?>
