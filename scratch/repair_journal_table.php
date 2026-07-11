<?php
require_once 'includes/db_connect.php';

try {
    // 1. Ensure entry_id is primary key and auto increment
    // First check if primary key exists
    $stmt = $pdo->query("SHOW INDEX FROM Journal_Entry WHERE Key_name = 'PRIMARY'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE Journal_Entry ADD PRIMARY KEY (entry_id)");
        echo "Added Primary Key.\n";
    }
    
    // 2. Add Auto Increment
    $pdo->exec("ALTER TABLE Journal_Entry MODIFY entry_id INT AUTO_INCREMENT");
    echo "Added Auto Increment.\n";
    
    // 3. Ensure client_id is VARCHAR(50)
    $pdo->exec("ALTER TABLE Journal_Entry MODIFY client_id VARCHAR(50) NOT NULL");
    echo "Fixed client_id type.\n";
    
    // 4. Add index on client_id
    $stmt = $pdo->query("SHOW INDEX FROM Journal_Entry WHERE Column_name = 'client_id'");
    if (!$stmt->fetch()) {
        $pdo->exec("CREATE INDEX idx_journal_client ON Journal_Entry(client_id)");
        echo "Added index on client_id.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
