<?php
// api/notifications/mark_read.php
require_once '../../includes/db_connect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true) ?? $_POST;
$notif_id = $data['id'] ?? null;
$mark_all = $data['all'] ?? null;

if (!isset($_SESSION['read_notifications'])) {
    $_SESSION['read_notifications'] = [];
    $cookie_name = 'sh_read_notifs_' . $_SESSION['user_id'];
    if (isset($_COOKIE[$cookie_name])) {
        $cookie_decoded = json_decode($_COOKIE[$cookie_name], true);
        if (is_array($cookie_decoded)) {
            $_SESSION['read_notifications'] = $cookie_decoded;
        }
    }
}

if ($mark_all) {
    $ids = $data['ids'] ?? [];
    if (is_array($ids)) {
        foreach ($ids as $id) {
            $id = filter_var($id, FILTER_DEFAULT);
            if (!in_array($id, $_SESSION['read_notifications'])) {
                $_SESSION['read_notifications'][] = $id;
            }
            // Acknowledge cancellation in DB if it's a cancellation notification
            if (strpos($id, 'session_cancelled_') === 0) {
                $session_id = substr($id, strlen('session_cancelled_'));
                try {
                    $stmt = $pdo->prepare("UPDATE private_sessions SET cancellation_acknowledged = 1 WHERE private_session_id = ?");
                    $stmt->execute([$session_id]);
                } catch (Exception $e) {
                    // silent
                }
            } elseif (strpos($id, 'client_cancelled_') === 0) {
                $session_id = substr($id, strlen('client_cancelled_'));
                try {
                    $stmt = $pdo->prepare("UPDATE private_sessions SET cancellation_acknowledged = 1 WHERE private_session_id = ?");
                    $stmt->execute([$session_id]);
                } catch (Exception $e) {
                    // silent
                }
            }
        }
    }
    // Save to persistent cookie
    $cookie_name = 'sh_read_notifs_' . $_SESSION['user_id'];
    $cookie_read_notifs = $_SESSION['read_notifications'];
    if (count($cookie_read_notifs) > 100) {
        $cookie_read_notifs = array_slice($cookie_read_notifs, -100);
    }
    setcookie($cookie_name, json_encode($cookie_read_notifs), time() + (86400 * 30), '/');

    echo json_encode(['status' => 'success']);
    exit();
}

if ($notif_id) {
    if (!in_array($notif_id, $_SESSION['read_notifications'])) {
        $_SESSION['read_notifications'][] = $notif_id;
    }
    
    // Acknowledge cancellation in DB if it's a cancellation notification
    if (strpos($notif_id, 'session_cancelled_') === 0) {
        $session_id = substr($notif_id, strlen('session_cancelled_'));
        try {
            $stmt = $pdo->prepare("UPDATE private_sessions SET cancellation_acknowledged = 1 WHERE private_session_id = ?");
            $stmt->execute([$session_id]);
        } catch (Exception $e) {
            // silent
        }
    } elseif (strpos($notif_id, 'client_cancelled_') === 0) {
        $session_id = substr($notif_id, strlen('client_cancelled_'));
        try {
            $stmt = $pdo->prepare("UPDATE private_sessions SET cancellation_acknowledged = 1 WHERE private_session_id = ?");
            $stmt->execute([$session_id]);
        } catch (Exception $e) {
            // silent
        }
    }
    
    // Save to persistent cookie
    $cookie_name = 'sh_read_notifs_' . $_SESSION['user_id'];
    $cookie_read_notifs = $_SESSION['read_notifications'];
    if (count($cookie_read_notifs) > 100) {
        $cookie_read_notifs = array_slice($cookie_read_notifs, -100);
    }
    setcookie($cookie_name, json_encode($cookie_read_notifs), time() + (86400 * 30), '/');

    echo json_encode(['status' => 'success']);
    exit();
}

echo json_encode(['status' => 'error', 'message' => 'Missing notification ID or parameters']);
