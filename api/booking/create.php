<?php
// api/booking/create.php
require_once '../../includes/db_connect.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'client') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized. Must be a client to book.']);
    exit();
}

$client_user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT client_id FROM client WHERE user_id = ?");
$stmt->execute([$client_user_id]);
$client_row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client_row) {
    echo json_encode(['status' => 'error', 'message' => 'Client profile not found.']);
    exit();
}

$client_id = $client_row['client_id'];

$therapist_id = $_POST['therapist_id'] ?? null;
$method = $_POST['method'] ?? null;
$date = $_POST['date'] ?? null;
$time = $_POST['time'] ?? null;
$total = $_POST['total'] ?? 0;

if (!$therapist_id || !$method || !$date || !$time) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Check wallet balance
    $stmt = $pdo->prepare("SELECT wallet_balance FROM client WHERE client_id = ?");
    $stmt->execute([$client_id]);
    $wallet_balance = $stmt->fetchColumn();

    $wallet_usage = min($wallet_balance, $total);
    $card_usage = $total - $wallet_usage;

    // Deduct from wallet if used
    if ($wallet_usage > 0) {
        $up = $pdo->prepare("UPDATE client SET wallet_balance = wallet_balance - ? WHERE client_id = ?");
        $up->execute([$wallet_usage, $client_id]);
    }

    $private_session_id = 'PS-' . uniqid();
    $session_datetime = $date . ' ' . $time . ':00';
    $duration_minutes = intval($_POST['duration'] ?? 60);

    $zoom_meeting_id = null;
    $zoom_join_url = null;
    
    // Validate mock Payment Gateway (Simulation)
    $payment_type = $_POST['payment_type'] ?? 'Card';
    $payment_gateway_response = 200; // Mocking a 200 OK from gateway
    $final_status = ($payment_gateway_response === 200) ? 'RESERVED' : 'PENDING';

    $method_text = ($card_usage > 0) ? "$payment_type + Wallet" : "Wallet";

    $stmt = $pdo->prepare("
        INSERT INTO private_sessions (private_session_id, client_id, therapist_id, session_date, duration_minutes, amount, status, payment_status, payment_method, payment_date, communication_method, zoom_meeting_id, zoom_join_url)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'completed', ?, NOW(), ?, ?, ?)
    ");
    
    // Auto-generate Zoom Meeting if it is a Video Session
    if ($method === 'Video Session') {
        // Mocking a Zoom Web Meeting SDK payload
        $zoom_meeting_id = rand(1000000000, 9999999999);
        $zoom_join_url = "https://zoom.us/j/" . $zoom_meeting_id . "?pwd=" . uniqid();
    }

    $stmt->execute([$private_session_id, $client_id, $therapist_id, $session_datetime, $duration_minutes, $total, $final_status, $method_text, $method, $zoom_meeting_id, $zoom_join_url]);

    $pdo->commit();
    echo json_encode(['status' => 'success', 'private_session_id' => $private_session_id]);
} catch (Exception $e) {
    if($pdo->inTransaction()) $pdo->rollBack();
    error_log("Booking Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error during booking.']);
}
