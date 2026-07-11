<?php
// api/booking/confirm_early.php
require_once '../../includes/db_connect.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); exit();
}

$id = $_POST['id'] ?? $_GET['id'] ?? null;
$new_time = $_POST['new_time'] ?? null;

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing ID']); exit();
}

try {
    if ($new_time) {
        $stmtDate = $pdo->prepare("SELECT session_date FROM private_sessions WHERE private_session_id = ?");
        $stmtDate->execute([$id]);
        $orig_date = $stmtDate->fetchColumn();
        
        if ($orig_date) {
            $new_datetime = date('Y-m-d', strtotime($orig_date)) . ' ' . $new_time . ':00';
            $stmt = $pdo->prepare("UPDATE private_sessions SET session_date = ?, status = 'Active', early_start_requested = 0 WHERE private_session_id = ?");
            $stmt->execute([$new_datetime, $id]);
        }
    } else {
        $stmt = $pdo->prepare("UPDATE private_sessions SET status = 'Active', early_start_requested = 0 WHERE private_session_id = ?");
        $stmt->execute([$id]);
    }
    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
