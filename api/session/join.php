<?php
// api/session/join.php — marks the calling user as joined for a session
require_once '../../includes/db_connect.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { echo json_encode(['status'=>'error','message'=>'Not logged in']); exit; }

$session_id = $_GET['id'] ?? null;
$start_flag = isset($_GET['start']) ? 1 : 0;

if (!$session_id) { echo json_encode(['status'=>'error','message'=>'No session ID']); exit; }

if (strtolower($_SESSION['role'] ?? '') === 'therapist') {
    $stmt_v = $pdo->prepare("SELECT verified FROM therapist WHERE user_id = ?");
    $stmt_v->execute([$_SESSION['user_id']]);
    if (($stmt_v->fetchColumn() ?? 0) != 1) {
        echo json_encode(['status'=>'error','message'=>'Account pending approval']);
        exit;
    }
}

try {
    // Ensure private_session_presence table exists (create on first call)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS private_session_presence (
            id INT AUTO_INCREMENT PRIMARY KEY,
            private_session_id VARCHAR(50) NOT NULL,
            user_id VARCHAR(50) NOT NULL,
            joined_at DATETIME DEFAULT NOW(),
            UNIQUE KEY uniq_sp (private_session_id, user_id),
            CONSTRAINT fk_presence_private_sessions FOREIGN KEY (private_session_id) REFERENCES private_sessions(private_session_id) ON DELETE CASCADE,
            CONSTRAINT fk_presence_user FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Upsert: mark this user as joined
    $stmt = $pdo->prepare("
        INSERT INTO private_session_presence (private_session_id, user_id, joined_at)
        VALUES (?, ?, NOW())
        ON DUPLICATE KEY UPDATE joined_at = NOW()
    ");
    $stmt->execute([$session_id, $_SESSION['user_id']]);

    // If start flag, mark session as Active
    if ($start_flag) {
        $pdo->prepare("UPDATE private_sessions SET status='Active' WHERE private_session_id=?")->execute([$session_id]);
    }

    echo json_encode(['status'=>'success']);
} catch (PDOException $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
