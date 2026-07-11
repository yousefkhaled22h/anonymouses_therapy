<?php
// api/group/delete_message.php
require_once '../../includes/db_connect.php';
session_start();

header('Content-Type: application/json');

if (strtolower($_SESSION['role'] ?? '') !== 'therapist') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$message_id = $_POST['message_id'] ?? null;
$session_id = $_POST['session_id'] ?? null;

try {
    // Only the session owner can delete any message
    $stmt = $pdo->prepare("
        SELECT gs.therapist_id, t.user_id as therapist_user_id
        FROM group_sessions gs
        JOIN therapist t ON gs.therapist_id = t.therapist_id
        WHERE gs.group_session_id = ?
    ");
    $stmt->execute([$session_id]);
    $session = $stmt->fetch();

    if (!$session || $session['therapist_user_id'] !== $user_id) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized deletion']);
        exit();
    }

    $stmt = $pdo->prepare("DELETE FROM group_session_messages WHERE message_id = ? AND group_session_id = ?");
    $stmt->execute([$message_id, $session_id]);

    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
