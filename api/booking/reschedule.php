<?php
// api/booking/reschedule.php
require_once '../../includes/db_connect.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Therapist') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); exit();
}

$id = $_POST['session_id'] ?? null;
$new_time = $_POST['new_proposed_time'] ?? null;

if (!$id || !$new_time) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']); exit();
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT status FROM private_sessions WHERE private_session_id = ?");
    $stmt->execute([$id]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) throw new Exception("Session not found");
    
    // Update status to pending_reschedule.
    // Without adding columns (per strict rules), we update the session_date to the proposed time.
    $up1 = $pdo->prepare("UPDATE private_sessions SET status = 'pending_reschedule', session_date = ? WHERE private_session_id = ?");
    $up1->execute([$new_time, $id]);

    $pdo->commit();
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    if($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
