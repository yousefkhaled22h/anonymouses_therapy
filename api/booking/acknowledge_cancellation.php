<?php
// api/booking/acknowledge_cancellation.php
require_once '../../includes/db_connect.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Client') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
$session_id = $data['session_id'] ?? null;

if (!$session_id) {
    echo json_encode(['success' => false, 'message' => 'Missing session ID']);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE private_sessions SET cancellation_acknowledged = 1 WHERE private_session_id = ? AND client_id = ?");
    $stmt->execute([$session_id, $_SESSION['client_id']]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Cancellation acknowledged successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to acknowledge cancellation.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
