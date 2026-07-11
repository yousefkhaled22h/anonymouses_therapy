<?php
// api/booking/cancel.php
require_once '../../includes/db_connect.php';
session_start();
header('Content-Type: application/json');

$user_role = $_SESSION['role'] ?? '';
if (!isset($_SESSION['user_id']) || !in_array($user_role, ['Therapist', 'Client'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); exit();
}

$id = $_POST['session_id'] ?? $_GET['id'] ?? null;
$raw_reason = $_POST['cancel_reason'] ?? $_GET['reason'] ?? '';

if (empty($raw_reason)) {
    if ($user_role === 'Client') {
        $reason = 'Cancelled by Client';
    } else {
        $reason = 'Cancelled by Therapist';
    }
} else {
    if ($user_role === 'Client') {
        $reason = 'Cancelled by Client: ' . $raw_reason;
    } else {
        $reason = 'Cancelled by Therapist: ' . $raw_reason;
    }
}

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']); exit();
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT client_id, amount, status FROM private_sessions WHERE private_session_id = ?");
    $stmt->execute([$id]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) throw new Exception("Session not found");
    if ($session['status'] === 'cancelled') throw new Exception("Session already cancelled");

    if ($user_role === 'Client') {
        // Find client_id for the logged-in client
        $stmt_cli = $pdo->prepare("SELECT client_id FROM client WHERE user_id = ?");
        $stmt_cli->execute([$_SESSION['user_id']]);
        $client_id = $stmt_cli->fetchColumn();
        if (!$client_id || $session['client_id'] !== $client_id) {
            throw new Exception("Unauthorized access");
        }
    }

    // Update status to 'cancelled', set cancel_reason and reset cancellation_acknowledged
    $up1 = $pdo->prepare("UPDATE private_sessions SET status = 'cancelled', cancel_reason = ?, cancellation_acknowledged = 0 WHERE private_session_id = ?");
    $up1->execute([$reason, $id]);

    // Calculate balance refund and apply to client's wallet
    $up2 = $pdo->prepare("UPDATE client SET wallet_balance = wallet_balance + ? WHERE client_id = ?");
    $up2->execute([$session['amount'], $session['client_id']]);

    $pdo->commit();
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    if($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
