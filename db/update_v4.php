<?php
// update_v4.php
require 'includes/db_connect.php';
try {
    $pdo->exec("ALTER TABLE therapist ADD COLUMN zoom_link VARCHAR(255) DEFAULT 'https://zoom.us/test'");
    echo 'success';
} catch (Exception $e) {
    echo 'error: ' . $e->getMessage();
}
