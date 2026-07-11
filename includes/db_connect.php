<?php
// includes/db_connect.php
// Set root path dynamically
if (!isset($root)) {
    $root = (strpos($_SERVER['PHP_SELF'] ?? '', '/test2/') === 0) ? '/test2/' : '/';
}

// Check if running on localhost
$is_localhost = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', '::1']) || (strpos($_SERVER['HTTP_HOST'] ?? '', '192.168.') === 0) || !isset($_SERVER['HTTP_HOST']);

if ($is_localhost) {
    date_default_timezone_set('Etc/GMT-3');
} else {
    date_default_timezone_set('Africa/Cairo');
}

// Set base URL dynamically
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? '') == 443) ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'] ?? 'localhost';
$domainName = filter_var($domainName, FILTER_SANITIZE_URL);
$base_url = $protocol . $domainName . $root;

if ($is_localhost) {
    $host = 'localhost';
    $db = 'anonymous-therapy';
    $user = 'root';
    $pass = '';
} else {
    // Production database credentials (Hostinger)
    $host = 'localhost'; 
    $db = 'YOUR_HOSTINGER_DB_NAME'; // REPLACE WITH YOUR HOSTINGER DATABASE NAME
    $user = 'YOUR_HOSTINGER_DB_USER'; // REPLACE WITH YOUR HOSTINGER DATABASE USERNAME
    $pass = 'YOUR_HOSTINGER_DB_PASSWORD'; // REPLACE WITH YOUR HOSTINGER DATABASE PASSWORD
}
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // Sync MySQL session timezone with PHP timezone offset
    $pdo->exec("SET time_zone = '" . date('P') . "'");
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int) $e->getCode());
}

// Create the message table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS group_session_messages (
  message_id VARCHAR(50) PRIMARY KEY,
  group_session_id VARCHAR(50) NOT NULL,
  sender_user_id VARCHAR(50) NOT NULL,
  message_text TEXT NOT NULL,
  sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (group_session_id) REFERENCES group_sessions(group_session_id) ON DELETE CASCADE,
  FOREIGN KEY (sender_user_id) REFERENCES user(user_id) ON DELETE CASCADE
)";
$pdo->exec($sql);

// Helper function to log administrative audit events in JSON column in admin table
if (!function_exists('logAdminAudit')) {
    function logAdminAudit($pdo, $user_id, $email, $action, $details, $ip_address = null) {
        try {
            $admin_id = null;
            if (!empty($user_id)) {
                $stmt = $pdo->prepare("SELECT admin_id FROM admin WHERE admin_id = ?");
                $stmt->execute([$user_id]);
                $admin_id = $stmt->fetchColumn();
            }
            if (!$admin_id && !empty($email)) {
                $stmt = $pdo->prepare("SELECT admin_id FROM admin WHERE email = ?");
                $stmt->execute([$email]);
                $admin_id = $stmt->fetchColumn();
            }
            if (!$admin_id) {
                $stmt = $pdo->query("SELECT admin_id FROM admin ORDER BY created_at ASC LIMIT 1");
                $admin_id = $stmt->fetchColumn();
            }

            if (!$admin_id) {
                return false;
            }

            $use_tx = !$pdo->inTransaction();
            if ($use_tx) {
                $pdo->beginTransaction();
            }

            $stmt = $pdo->prepare("SELECT audit_logs FROM admin WHERE admin_id = ? FOR UPDATE");
            $stmt->execute([$admin_id]);
            $current_logs = $stmt->fetchColumn();

            $logs = [];
            if (!empty($current_logs)) {
                $logs = json_decode($current_logs, true);
                if (!is_array($logs)) $logs = [];
            }

            // Sequential log_id generation system-wide
            $max_id = 0;
            $stmt_all = $pdo->query("SELECT audit_logs FROM admin");
            while ($row = $stmt_all->fetch(PDO::FETCH_ASSOC)) {
                if (!empty($row['audit_logs'])) {
                    $arr = json_decode($row['audit_logs'], true);
                    if (is_array($arr)) {
                        foreach ($arr as $l) {
                            if (isset($l['log_id']) && $l['log_id'] > $max_id) {
                                $max_id = (int)$l['log_id'];
                            }
                        }
                    }
                }
            }
            $new_id = $max_id + 1;

            $new_log = [
                'log_id' => $new_id,
                'user_id' => $user_id,
                'email' => $email,
                'action' => $action,
                'details' => $details,
                'ip_address' => $ip_address ?: ($_SERVER['REMOTE_ADDR'] ?? null),
                'created_at' => date('Y-m-d H:i:s')
            ];
            $logs[] = $new_log;

            $stmt_up = $pdo->prepare("UPDATE admin SET audit_logs = ? WHERE admin_id = ?");
            $stmt_up->execute([json_encode($logs), $admin_id]);

            if ($use_tx) {
                $pdo->commit();
            }
            return true;
        } catch (Exception $e) {
            if (isset($use_tx) && $use_tx && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("logAdminAudit failed: " . $e->getMessage());
            return false;
        }
    }
}

// Helper function to schedule announcements in JSON column in admin table
if (!function_exists('scheduleAdminNotification')) {
    function scheduleAdminNotification($pdo, $sender_id, $target_role, $title, $message, $type, $scheduled_at) {
        try {
            $admin_id = null;
            if (!empty($sender_id)) {
                $stmt = $pdo->prepare("SELECT admin_id FROM admin WHERE admin_id = ?");
                $stmt->execute([$sender_id]);
                $admin_id = $stmt->fetchColumn();
            }
            if (!$admin_id) {
                $stmt = $pdo->query("SELECT admin_id FROM admin ORDER BY created_at ASC LIMIT 1");
                $admin_id = $stmt->fetchColumn();
            }

            if (!$admin_id) {
                return false;
            }

            $use_tx = !$pdo->inTransaction();
            if ($use_tx) {
                $pdo->beginTransaction();
            }

            $stmt = $pdo->prepare("SELECT notifications_json FROM admin WHERE admin_id = ? FOR UPDATE");
            $stmt->execute([$admin_id]);
            $current_notifs = $stmt->fetchColumn();

            $notifs = [];
            if (!empty($current_notifs)) {
                $notifs = json_decode($current_notifs, true);
                if (!is_array($notifs)) $notifs = [];
            }

            $notif_id = 'nt_' . uniqid();
            $new_notif = [
                'notification_id' => $notif_id,
                'sender_id' => $sender_id,
                'target_role' => $target_role,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'scheduled_at' => $scheduled_at ?: date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
                'read_by' => []
            ];
            $notifs[] = $new_notif;

            $stmt_up = $pdo->prepare("UPDATE admin SET notifications_json = ? WHERE admin_id = ?");
            $stmt_up->execute([json_encode($notifs), $admin_id]);

            if ($use_tx) {
                $pdo->commit();
            }
            return true;
        } catch (Exception $e) {
            if (isset($use_tx) && $use_tx && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("scheduleAdminNotification failed: " . $e->getMessage());
            return false;
        }
    }
}
?>