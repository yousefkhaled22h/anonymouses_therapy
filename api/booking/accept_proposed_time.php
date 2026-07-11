<?php
// api/booking/accept_proposed_time.php
require_once '../../includes/db_connect.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); exit();
}

$session_id = $_REQUEST['id'] ?? null;
$selected_time = $_REQUEST['selected_time'] ?? null;

if (!$session_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing ID']); exit();
}

try {
    $stmt = $pdo->prepare("SELECT proposed_datetime FROM private_sessions WHERE private_session_id = ?");
    $stmt->execute([$session_id]);
    $proposed = $stmt->fetchColumn();

    if (!$proposed) {
        throw new PDOException("No proposed time found for this session.");
    }

    $final_time = $selected_time ? $selected_time : $proposed;

    $update = $pdo->prepare("UPDATE private_sessions SET session_date = ?, status = 'RESERVED', early_start_requested = 0, reschedule_requested = 0, proposed_datetime = NULL, early_start_to = NULL WHERE private_session_id = ?");
    $update->execute([$final_time, $session_id]);

    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
