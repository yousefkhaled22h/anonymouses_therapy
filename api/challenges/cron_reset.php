<?php
// api/challenges/cron_reset.php
header('Content-Type: application/json');
require_once '../../includes/db_connect.php';

try {
    // Delete completed challenges from previous days for all clients
    $deleted_count = $pdo->exec("DELETE FROM completed_challenges WHERE DATE(completed_at) < CURRENT_DATE()");
    echo json_encode([
        'success' => true,
        'message' => 'Daily challenges successfully reset/renewed for all clients.',
        'deleted_records' => $deleted_count
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
