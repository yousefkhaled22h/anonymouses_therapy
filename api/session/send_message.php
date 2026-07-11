<?php
// api/session/send_message.php
require_once '../../includes/db_connect.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); exit();
}

$user_id = $_SESSION['user_id'];
$session_id = $_POST['session_id'] ?? null;
$message = $_POST['message'] ?? null;

if (!$session_id || !$message) {
    echo json_encode(['status' => 'error', 'message' => 'Missing fields']); exit();
}

try {
    $stmt = $pdo->prepare("INSERT INTO private_session_messages (private_session_id, user_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$session_id, $user_id, $message]);
    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
