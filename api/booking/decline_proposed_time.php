<?php
// api/booking/decline_proposed_time.php
require_once '../../includes/db_connect.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); exit();
}

$session_id = $_REQUEST['id'] ?? null;

if (!$session_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing ID']); exit();
}

try {
    $update = $pdo->prepare("UPDATE private_sessions SET status = 'RESERVED', early_start_requested = 0, reschedule_requested = 0, proposed_datetime = NULL, early_start_to = NULL WHERE private_session_id = ?");
    $update->execute([$session_id]);

    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
