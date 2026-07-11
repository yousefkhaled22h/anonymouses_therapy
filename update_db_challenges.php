<?php
require_once 'includes/db_connect.php';

try {
    // Add points column to client table
    $pdo->exec("ALTER TABLE client ADD COLUMN IF NOT EXISTS points INT DEFAULT 0");
    
    // Create completed_challenges table
    $pdo->exec("CREATE TABLE IF NOT EXISTS completed_challenges (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id VARCHAR(50) NOT NULL,
        challenge_id VARCHAR(50) NOT NULL,
        completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_client_challenge (client_id, challenge_id)
    )");
    
    // Ensure daily_challenges table has points column if not present
    // (Already in schema but let's be sure)
    $pdo->exec("ALTER TABLE daily_challenges ADD COLUMN IF NOT EXISTS points INT DEFAULT 10");

    echo "Database updated successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
