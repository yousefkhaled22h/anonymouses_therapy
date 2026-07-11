<?php
// api/booking/submit_message.php
require_once '../../includes/db_connect.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); exit();
}

$session_id = $_POST['id'] ?? null;
$message = trim($_POST['message'] ?? '');

if (!$session_id || empty($message)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing data']); exit();
}

try {
    $stmt = $pdo->prepare("INSERT INTO private_session_messages (private_session_id, user_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$session_id, $_SESSION['user_id'], $message]);
    
    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
