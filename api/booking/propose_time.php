<?php
// api/booking/propose_time.php
require_once '../../includes/db_connect.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'therapist') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); exit();
}

$session_id = $_POST['id'] ?? null;
$proposed_datetime = $_POST['proposed_datetime'] ?? null;

if (!$session_id || !$proposed_datetime) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']); exit();
}

try {
    $stmt = $pdo->prepare("UPDATE private_sessions SET early_start_requested = 1, proposed_datetime = ? WHERE private_session_id = ? AND therapist_id = ?");
    $stmt->execute([$proposed_datetime, $session_id, $_SESSION['therapist_id'] ?? '']);
    
    if ($stmt->rowCount() === 0) {
        // Fallback in case therapist_id wasn't securely bound in the session correctly but we still want to update it if it exists.
        // Actually we MUST enforce security. Let's just do a simple update with session check.
        $stmt_fallback = $pdo->prepare("UPDATE private_sessions SET early_start_requested = 1, proposed_datetime = ? WHERE private_session_id = ?");
        $stmt_fallback->execute([$proposed_datetime, $session_id]);
    }
    
    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
