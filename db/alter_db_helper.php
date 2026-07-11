<?php
require 'includes/db_connect.php';
try {
    $pdo->exec("ALTER TABLE private_sessions ADD COLUMN communication_method VARCHAR(50) DEFAULT NULL AFTER session_date");
    echo 'success';
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo 'success';
    } else {
        echo 'error: ' . $e->getMessage();
    }
}
