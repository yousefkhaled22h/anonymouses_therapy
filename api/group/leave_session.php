<?php
// api/group/leave_session.php
require_once '../../includes/db_connect.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$session_id = $_POST['session_id'] ?? null;

if (!$session_id) {
    echo json_encode(['status' => 'error']);
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM group_session_participants WHERE group_session_id = ? AND user_id = ?");
    $stmt->execute([$session_id, $user_id]);
    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error']);
}
