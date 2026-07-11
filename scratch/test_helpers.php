<?php
require_once __DIR__ . '/../includes/db_connect.php';

// Test logAdminAudit
$result = logAdminAudit($pdo, 'U_ADMIN_1', 'admin@safehaven.com', 'Test Action', 'Verifying JSON log storage.', '127.0.0.1');
echo "logAdminAudit result: " . ($result ? "SUCCESS" : "FAILED") . "\n";

// Test scheduleAdminNotification
$result2 = scheduleAdminNotification($pdo, 'U_ADMIN_1', 'all', 'Test Announcement', 'This is a test notification.', 'system', null);
echo "scheduleAdminNotification result: " . ($result2 ? "SUCCESS" : "FAILED") . "\n";

// Verify stored data
$stmt = $pdo->prepare("SELECT audit_logs, notifications_json FROM admin WHERE admin_id = 'U_ADMIN_1'");
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$logs = json_decode($row['audit_logs'], true);
$notifs = json_decode($row['notifications_json'], true);

echo "\nAudit Logs (" . count($logs) . " entries):\n";
foreach (array_slice($logs, -3) as $l) {
    echo "  [{$l['log_id']}] {$l['action']} - {$l['created_at']}\n";
}

echo "\nNotifications (" . count($notifs) . " entries):\n";
foreach (array_slice($notifs, -3) as $n) {
    echo "  [{$n['notification_id']}] {$n['title']} - target:{$n['target_role']}\n";
}
