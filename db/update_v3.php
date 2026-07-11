<?php
// update_v3.php
require 'includes/db_connect.php';
try {
    $pdo->exec("ALTER TABLE private_sessions ADD COLUMN reschedule_requested TINYINT(1) DEFAULT 0");
    echo 'success';
} catch (Exception $e) {
    echo 'error: ' . $e->getMessage();
}
