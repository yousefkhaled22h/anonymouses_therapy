<?php
// api/admin/get_summary.php
session_start();
require_once '../../includes/db_connect.php';

header('Content-Type: application/json');

try {
    $total_users        = (int) $pdo->query("SELECT COUNT(*) FROM user")->fetchColumn();
    $active_therapists  = (int) $pdo->query("SELECT COUNT(*) FROM therapist WHERE verified = 1")->fetchColumn();
    $total_sessions     = (int) $pdo->query("SELECT COUNT(*) FROM private_sessions")->fetchColumn();
    $active_group       = (int) $pdo->query("SELECT COUNT(*) FROM group_sessions WHERE status = 'active'")->fetchColumn();
    $total_group        = (int) $pdo->query("SELECT COUNT(*) FROM group_sessions")->fetchColumn();
    $total_messages     = (int) $pdo->query("SELECT COUNT(*) FROM group_session_messages")->fetchColumn();
    $messages_today     = (int) $pdo->query("SELECT COUNT(*) FROM group_session_messages WHERE DATE(sent_at) = CURDATE()")->fetchColumn();
    $messages_24h       = (int) $pdo->query("SELECT COUNT(*) FROM group_session_messages WHERE sent_at > DATE_SUB(NOW(), INTERVAL 1 DAY)")->fetchColumn();
    $total_payments     = (int) $pdo->query("SELECT COUNT(*) FROM private_sessions WHERE payment_status = 'completed'")->fetchColumn();
    $monthly_revenue    = (float) ($pdo->query("SELECT COALESCE(SUM(amount), 0) FROM private_sessions WHERE payment_status = 'completed' AND MONTH(payment_date) = MONTH(CURRENT_DATE())")->fetchColumn() ?: 0);
    $total_revenue      = (float) ($pdo->query("SELECT COALESCE(SUM(amount), 0) FROM private_sessions WHERE payment_status = 'completed'")->fetchColumn() ?: 0);
    $total_community    = (int) $pdo->query("SELECT COUNT(*) FROM community_qna WHERE parent_id IS NULL")->fetchColumn();
    $pending_therapists = (int) $pdo->query("SELECT COUNT(*) FROM therapist WHERE verified = 0 OR verified IS NULL")->fetchColumn();

    echo json_encode([
        'status' => 'success',
        'data'   => [
            'totalUsers'         => $total_users,
            'activeTherapists'   => $active_therapists,
            'pendingTherapists'  => $pending_therapists,
            'totalSessions'      => $total_sessions,
            'activeGroupSessions'=> $active_group,
            'totalGroupSessions' => $total_group,
            'totalMessages'      => $total_messages,
            'messagesToday'      => $messages_today,
            'messagesLast24h'    => $messages_24h,
            'totalPayments'      => $total_payments,
            'monthlyRevenue'     => $monthly_revenue,
            'totalRevenue'       => $total_revenue,
            'totalCommunityPosts'=> $total_community,
        ]
    ]);

} catch (PDOException $e) {
    error_log('[Admin API] get_summary error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>
