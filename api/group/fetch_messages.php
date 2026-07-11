<?php
// api/group/fetch_messages.php
require_once '../../includes/db_connect.php';
session_start();

header('Content-Type: application/json');

$session_id = $_GET['session_id'] ?? null;
if (!$session_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing session ID']);
    exit();
}

try {
    // Auto-close and delete expired group sessions globally
    try {
        $stmt_expired = $pdo->prepare("
            SELECT group_session_id 
            FROM group_sessions 
            WHERE DATE_ADD(session_date, INTERVAL duration_minutes MINUTE) < NOW()
        ");
        $stmt_expired->execute();
        $expired_ids = $stmt_expired->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($expired_ids)) {
            $in_ph = implode(',', array_fill(0, count($expired_ids), '?'));
            
            $stmt_del_m = $pdo->prepare("DELETE FROM group_session_messages WHERE group_session_id IN ($in_ph)");
            $stmt_del_m->execute($expired_ids);
            
            $stmt_close = $pdo->prepare("UPDATE group_sessions SET status = 'ended' WHERE group_session_id IN ($in_ph)");
            $stmt_close->execute($expired_ids);
        }
    } catch (Exception $e) {}

    $check_stmt = $pdo->prepare("SELECT status FROM group_sessions WHERE group_session_id = ?");
    $check_stmt->execute([$session_id]);
    if ($check_stmt->fetch() === false) {
        echo json_encode(['status' => 'success', 'session_ended' => true, 'messages' => []]);
        exit();
    }

    // For simplicity with polling, we'll fetch last 50 messages. 
    // Usually we'd use a since_id parameter.
    $stmt = $pdo->prepare("
        SELECT gsm.*, u.role,
        COALESCE(c.anonymous_id, CONCAT(t.first_name, ' ', t.last_name), CONCAT(v.first_name, ' ', v.last_name), 'User') as author_name
        FROM group_session_messages gsm
        LEFT JOIN user u ON gsm.sender_user_id = u.user_id
        LEFT JOIN client c ON u.user_id = c.user_id
        LEFT JOIN therapist t ON u.user_id = t.user_id
        LEFT JOIN volunteer v ON u.user_id = v.user_id
        WHERE gsm.group_session_id = ?
        ORDER BY gsm.sent_at ASC
        LIMIT 50
    ");
    $stmt->execute([$session_id]);
    $messages = $stmt->fetchAll();

    // Sanitize output
    foreach ($messages as &$msg) {
        $msg['message_text'] = nl2br(htmlspecialchars($msg['message_text']));
        $msg['author_name'] = htmlspecialchars($msg['author_name']);
        $msg['sent_at'] = date('H:i', strtotime($msg['sent_at']));
    }

    echo json_encode(['status' => 'success', 'messages' => $messages]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
