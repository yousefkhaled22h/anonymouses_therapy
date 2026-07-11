<?php
require_once 'includes/db_connect.php';
try {
    $stmt = $pdo->query('SHOW CREATE TABLE journal_entry');
    echo "=== journal_entry ===\n" . $stmt->fetch(PDO::FETCH_NUM)[1] . "\n\n";
    $stmt = $pdo->query('SHOW CREATE TABLE client_mood_history');
    echo "=== client_mood_history ===\n" . $stmt->fetch(PDO::FETCH_NUM)[1] . "\n\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
