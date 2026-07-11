<?php
// Simulate the admin dashboard data fetch
require_once __DIR__ . '/../includes/db_connect.php';

try {
    // 1. Audit Logs — merged from admin.audit_logs JSON column
    $audit_logs = [];
    $admin_rows = $pdo->query("SELECT admin_id, email, audit_logs, notifications_json FROM admin")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($admin_rows as $ar) {
        if (!empty($ar['audit_logs'])) {
            $entries = json_decode($ar['audit_logs'], true);
            if (is_array($entries)) {
                $audit_logs = array_merge($audit_logs, $entries);
            }
        }
    }
    usort($audit_logs, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
    $audit_logs = array_slice($audit_logs, 0, 100);
    echo "✅ Audit logs fetched: " . count($audit_logs) . " entries\n";
    foreach (array_slice($audit_logs, 0, 3) as $l) {
        echo "   - [{$l['log_id']}] {$l['action']} at {$l['created_at']}\n";
    }

    // 2. Notifications — merged from admin.notifications_json JSON column
    $notifications = [];
    foreach ($admin_rows as $ar) {
        if (!empty($ar['notifications_json'])) {
            $notif_entries = json_decode($ar['notifications_json'], true);
            if (is_array($notif_entries)) {
                foreach ($notif_entries as &$ne) {
                    $ne['read_count'] = isset($ne['read_by']) ? count($ne['read_by']) : 0;
                }
                unset($ne);
                $notifications = array_merge($notifications, $notif_entries);
            }
        }
    }
    usort($notifications, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
    echo "✅ Notifications fetched: " . count($notifications) . " entries\n";
    foreach (array_slice($notifications, 0, 3) as $n) {
        echo "   - [{$n['notification_id']}] {$n['title']} | reads={$n['read_count']}\n";
    }

    echo "\n✅ All admin dashboard data queries working correctly!\n";
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
