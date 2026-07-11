<?php
// api/booking/early_start.php
require_once '../../includes/db_connect.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'therapist') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); exit();
}

$session_id = $_POST['id'] ?? $_GET['id'] ?? null;
$from = $_POST['from'] ?? null;
$to = $_POST['to'] ?? null;

if (!$session_id || !$from || !$to) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']); exit();
}

try {
    $stmt = $pdo->prepare("UPDATE private_sessions SET early_start_requested = 1, early_start_from = ?, early_start_to = ? WHERE private_session_id = ?");
    $stmt->execute([$from, $to, $session_id]);
    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
