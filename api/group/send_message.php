<?php
// api/group/send_message.php
require_once '../../includes/db_connect.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$session_id = $_POST['session_id'] ?? null;
$text = trim($_POST['message'] ?? '');

if (!$session_id || !$text) {
    echo json_encode(['status' => 'error', 'message' => 'Missing data']);
    exit();
}

try {
    // 1. Check session status
    $stmt = $pdo->prepare("SELECT status, admin_comments FROM group_sessions WHERE group_session_id = ?");
    $stmt->execute([$session_id]);
    $session = $stmt->fetch();

    if (!$session || $session['status'] !== 'active') {
        echo json_encode(['status' => 'error', 'message' => 'Session not active']);
        exit();
    }

    // 2. Check if muted
    $muted_users = json_decode($session['admin_comments'] ?? '[]', true);
    if (!is_array($muted_users))
        $muted_users = [];
    if (in_array($user_id, $muted_users)) {
        echo json_encode(['status' => 'error', 'message' => 'You are muted']);
        exit();
    }

    // 3. Send message
    $msg_id = 'msg_' . bin2hex(random_bytes(8));
    $stmt = $pdo->prepare("INSERT INTO group_session_messages (message_id, group_session_id, sender_user_id, message_text, sent_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$msg_id, $session_id, $user_id, $text]);

    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
