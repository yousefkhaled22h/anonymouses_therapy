<?php
// api/session/status.php — returns how many participants have joined a session
require_once '../../includes/db_connect.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { echo json_encode(['status'=>'error']); exit; }

$session_id = $_GET['id'] ?? null;
if (!$session_id) { echo json_encode(['joined_count'=>0]); exit; }

try {
    // Clean up stale joins older than 10 minutes (user left)
    $pdo->prepare("DELETE FROM private_session_presence WHERE private_session_id=? AND joined_at < NOW() - INTERVAL 10 MINUTE")->execute([$session_id]);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM private_session_presence WHERE private_session_id=?");
    $stmt->execute([$session_id]);
    $count = (int)$stmt->fetchColumn();

    // Also get session status
    $st = $pdo->prepare("SELECT status, payment_status FROM private_sessions WHERE private_session_id=?");
    $st->execute([$session_id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'status'       => 'success',
        'joined_count' => $count,
        'session_status' => $row['status'] ?? null,
        'payment_status' => $row['payment_status'] ?? null,
    ]);
} catch (PDOException $e) {
    echo json_encode(['joined_count'=>0]);
}
