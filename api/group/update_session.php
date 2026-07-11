<?php
// api/group/update_session.php
require_once '../../includes/db_connect.php';
session_start();

header('Content-Type: application/json');

if (strtolower($_SESSION['role'] ?? '') !== 'therapist') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$session_id = $_POST['session_id'] ?? null;
$action = $_POST['action'] ?? null;

try {
    // Verify ownership
    $stmt = $pdo->prepare("
        SELECT gs.therapist_id, t.user_id as therapist_user_id, gs.admin_comments 
        FROM group_sessions gs
        JOIN therapist t ON gs.therapist_id = t.therapist_id
        WHERE gs.group_session_id = ?
    ");
    $stmt->execute([$session_id]);
    $session = $stmt->fetch();

    if (!$session || $session['therapist_user_id'] !== $user_id) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized access to this session']);
        exit();
    }

    if ($action === 'status') {
        $status = $_POST['status'] ?? 'scheduled';

        if ($status === 'ended') {
            // Delete messages to save database space
            $stmt1 = $pdo->prepare("DELETE FROM group_session_messages WHERE group_session_id = ?");
            $stmt1->execute([$session_id]);

            // Keep the session and participants records for statistics and history, but set status to 'ended'
            $stmt3 = $pdo->prepare("UPDATE group_sessions SET status = 'ended' WHERE group_session_id = ?");
            $stmt3->execute([$session_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE group_sessions SET status = ? WHERE group_session_id = ?");
            $stmt->execute([$status, $session_id]);
        }
        echo json_encode(['status' => 'success']);

    } elseif ($action === 'mute') {
        $target_user_id = $_POST['user_id'];
        $mute = $_POST['mute'] === 'true';

        $muted_users = json_decode($session['admin_comments'] ?? '[]', true);
        if (!is_array($muted_users))
            $muted_users = [];

        if ($mute) {
            if (!in_array($target_user_id, $muted_users))
                $muted_users[] = $target_user_id;
        } else {
            $muted_users = array_values(array_filter($muted_users, fn($id) => $id !== $target_user_id));
        }

        $stmt = $pdo->prepare("UPDATE group_sessions SET admin_comments = ? WHERE group_session_id = ?");
        $stmt->execute([json_encode($muted_users), $session_id]);
        echo json_encode(['status' => 'success']);

    } elseif ($action === 'remove') {
        $target_user_id = $_POST['user_id'];
        $stmt = $pdo->prepare("DELETE FROM group_session_participants WHERE group_session_id = ? AND user_id = ?");
        $stmt->execute([$session_id, $target_user_id]);
        echo json_encode(['status' => 'success']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
