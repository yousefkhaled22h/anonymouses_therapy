<?php
// update_v2.php
require 'includes/db_connect.php';
try {
    // 1. Add wallet balance to Client
    $pdo->exec("ALTER TABLE client ADD COLUMN wallet_balance DECIMAL(10,2) DEFAULT 0.00");
    
    // 2. Add early start request flag to private_sessions
    $pdo->exec("ALTER TABLE private_sessions ADD COLUMN early_start_requested TINYINT(1) DEFAULT 0");
    
    // 3. Update existing stats if needed (none for now)
    
    echo 'success';
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo 'success';
    } else {
        echo 'error: ' . $e->getMessage();
    }
}
