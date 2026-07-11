<?php
chdir(__DIR__);
require_once '../includes/db_connect.php';

echo "=== FULL ADMIN DASHBOARD VERIFICATION ===\n\n";

$tests = [
    'total_users'     => "SELECT COUNT(*) FROM user",
    'active_therapists' => "SELECT COUNT(*) FROM therapist WHERE verified=1",
    'total_sessions'  => "SELECT COUNT(*) FROM private_sessions",
    'monthly_revenue' => "SELECT COALESCE(SUM(amount),0) FROM private_sessions WHERE payment_status='completed' AND MONTH(payment_date)=MONTH(CURRENT_DATE())",
    'total_group_sessions' => "SELECT COUNT(*) FROM group_sessions",
    'total_payments'  => "SELECT COUNT(*) FROM private_sessions WHERE payment_status='completed'",
    'total_messages'  => "SELECT COUNT(*) FROM group_session_messages",
    'total_community' => "SELECT COUNT(*) FROM community_qna WHERE parent_id IS NULL",
];

foreach ($tests as $k => $q) {
    try {
        echo "$k: " . $pdo->query($q)->fetchColumn() . "\n";
    } catch (Exception $e) {
        echo "$k: ❌ " . $e->getMessage() . "\n";
    }
}

echo "\n--- Complex Query Tests ---\n";

// Test the exact queries used in index.php
$complex = [
    'all_users (fixed, no status)' =>
        "SELECT u.user_id, u.email, u.role, u.created_at, 'Active' as status,
        CASE WHEN u.role='client' THEN c.name WHEN u.role='therapist' THEN CONCAT(t.first_name,' ',t.last_name) WHEN u.role='volunteer' THEN CONCAT(v.first_name,' ',v.last_name) ELSE 'User' END as display_name
        FROM user u LEFT JOIN client c ON u.user_id=c.user_id LEFT JOIN therapist t ON u.user_id=t.user_id LEFT JOIN volunteer v ON u.user_id=v.user_id ORDER BY u.created_at DESC LIMIT 3",
    'sessions_join' =>
        "SELECT ps.private_session_id, ps.status, c.name as client_name, CONCAT(t.first_name,' ',t.last_name) as therapist_name FROM private_sessions ps JOIN client c ON ps.client_id=c.client_id JOIN therapist t ON ps.therapist_id=t.therapist_id ORDER BY ps.session_date DESC LIMIT 3",
    'payments_join' =>
        "SELECT p.private_session_id as payment_id, p.amount, c.name as client_name, u.email FROM private_sessions p JOIN client c ON p.client_id=c.client_id JOIN user u ON c.user_id=u.user_id WHERE p.payment_status='completed' ORDER BY p.payment_date DESC LIMIT 3",
    'group_sessions_join' =>
        "SELECT gs.topic, gs.status, CONCAT(t.first_name,' ',t.last_name) as therapist_name, (SELECT COUNT(*) FROM group_session_participants gsu WHERE gsu.group_session_id=gs.group_session_id) as participants FROM group_sessions gs LEFT JOIN therapist t ON gs.therapist_id=t.therapist_id LIMIT 3",
];

foreach ($complex as $k => $q) {
    try {
        $rows = $pdo->query($q)->fetchAll(PDO::FETCH_ASSOC);
        echo "$k: ✅ " . count($rows) . " rows → " . json_encode($rows[0] ?? 'empty') . "\n";
    } catch (Exception $e) {
        echo "$k: ❌ " . $e->getMessage() . "\n";
    }
}

echo "\nDone.\n";
?>
