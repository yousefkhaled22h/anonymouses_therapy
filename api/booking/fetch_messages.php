<?php
// api/booking/fetch_messages.php
require_once '../../includes/db_connect.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); exit();
}

$session_id = $_GET['id'] ?? null;
if (!$session_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing ID']); exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT m.message, m.created_at, m.user_id 
        FROM private_session_messages m
        WHERE m.private_session_id = ? 
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$session_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'messages' => $messages, 'current_user' => $_SESSION['user_id']]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
