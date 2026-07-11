<?php
// db/alter_private_sessions_time.php
require_once __DIR__ . '/../includes/db_connect.php';

try {
    $pdo->exec("ALTER TABLE private_sessions ADD COLUMN proposed_datetime DATETIME NULL AFTER early_start_to");
    echo "Column proposed_datetime added successfully.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column proposed_datetime already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
