<?php
// api/booking/end_session.php
require_once '../../includes/db_connect.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); exit();
}

$session_id = $_POST['id'] ?? $_GET['id'] ?? null;
if (!$session_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing ID']); exit();
}

try {
    $stmt = $pdo->prepare("UPDATE private_sessions SET status = 'Completed' WHERE private_session_id = ?");
    $stmt->execute([$session_id]);
    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
