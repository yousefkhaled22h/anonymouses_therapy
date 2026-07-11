<?php
// api/session/get_messages.php
require_once '../../includes/db_connect.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); exit();
}

$session_id = $_GET['id'] ?? null;
$last_id = $_GET['last_id'] ?? 0;

if (!$session_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing ID']); exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT message_id, user_id, message, created_at 
        FROM private_session_messages 
        WHERE private_session_id = ? AND message_id > ? 
        ORDER BY message_id ASC
    ");
    $stmt->execute([$session_id, $last_id]);
    $msgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status' => 'success', 'messages' => $msgs]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
