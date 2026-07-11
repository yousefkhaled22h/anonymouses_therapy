<?php
// api/group/fetch_participants.php
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
        echo json_encode(['status' => 'success', 'session_ended' => true, 'participants' => [], 'muted_users' => []]);
        exit();
    }

    // 1. Fetch participants
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.role,
        COALESCE(c.anonymous_id, CONCAT(t.first_name, ' ', t.last_name), CONCAT(v.first_name, ' ', v.last_name), 'User') as name
        FROM group_session_participants gsu
        JOIN user u ON gsu.user_id = u.user_id
        LEFT JOIN client c ON u.user_id = c.user_id
        LEFT JOIN therapist t ON u.user_id = t.user_id
        LEFT JOIN volunteer v ON u.user_id = v.user_id
        WHERE gsu.group_session_id = ?
    ");
    $stmt->execute([$session_id]);
    $participants = $stmt->fetchAll();

    // 2. Fetch muted list from session comments
    $stmt = $pdo->prepare("SELECT admin_comments FROM group_sessions WHERE group_session_id = ?");
    $stmt->execute([$session_id]);
    $comments = $stmt->fetchColumn();
    $muted_users = json_decode($comments ?? '[]', true);
    if (!is_array($muted_users))
        $muted_users = [];

    echo json_encode(['status' => 'success', 'participants' => $participants, 'muted_users' => $muted_users]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
