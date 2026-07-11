<?php
// api/book_session.php
require_once '../includes/db_connect.php';

session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Client') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized or invalid role']);
    exit();
}

$client_id = $_SESSION['user_id'];
$therapist_id = $_POST['therapist_id'] ?? '';
$session_date = $_POST['session_date'] ?? '';
$amount = floatval($_POST['amount'] ?? 0);

if (empty($therapist_id) || empty($session_date)) {
    echo json_encode(['success' => false, 'message' => 'Missing details']);
    exit();
}

$requested_time = strtotime($session_date);
$day_of_week = date('l', $requested_time); // "Monday", "Tuesday", etc.
$time_of_day = date('H:i:s', $requested_time); // "14:30:00"

try {
    // 0. Check Availability
    $check_stmt = $pdo->prepare("SELECT * FROM therapist_availability WHERE therapist_id = ? AND day_of_week = ? AND is_available = 1");
    $check_stmt->execute([$therapist_id, $day_of_week]);
    $availability = $check_stmt->fetch();

    if (!$availability) {
        echo json_encode(['success' => false, 'message' => "The therapist is not available on {$day_of_week}s."]);
        exit();
    }

    if ($time_of_day < $availability['start_time'] || $time_of_day > $availability['end_time']) {
        echo json_encode(['success' => false, 'message' => "Requested time is outside the therapist's working hours ({$availability['start_time']} - {$availability['end_time']})."]);
        exit();
    }
    // 1. Mock Payment Processing
    // In real app, verify card details here.
    $payment_id = uniqid('PAY_');
    $status = 'Completed';

    // 3. Insert into Paid Session (must be done FIRST due to foreign key constraints in payment table)
    $session_id = uniqid('SESS_');
    // We need the client_id string from the Client table, not user_id!
    $client_stmt = $pdo->prepare("SELECT client_id FROM client WHERE user_id = ?");
    $client_stmt->execute([$client_id]);
    $real_client_id = $client_stmt->fetchColumn();

    if (!$real_client_id) {
        echo json_encode(['success' => false, 'message' => 'Client profile not found.']);
        exit();
    }

    // Default the communication method to Video Session if it's the standard booking.
    $method = 'Video Session';
    $duration_minutes = 60;
    
    // Auto-generate Teams Meeting
    $teams_mid = null;
    $teams_url = null;
    require_once __DIR__ . '/teams_create.php';
    
    $startDtIso = gmdate('Y-m-d\TH:i:s\Z', strtotime($session_date));
    $endDtIso = gmdate('Y-m-d\TH:i:s\Z', strtotime($session_date) + ($duration_minutes * 60));
    
    $teamsRes = createTeamsMeeting($startDtIso, $endDtIso, "Safe Haven Therapy Session");
    if ($teamsRes['success']) {
        $teams_mid = $teamsRes['meetingId'];
        $teams_url = $teamsRes['joinUrl'];
    }

    $stmt = $pdo->prepare("INSERT INTO private_sessions (private_session_id, client_id, therapist_id, session_date, status, amount, communication_method, duration_minutes, zoom_meeting_id, zoom_join_url, payment_status, payment_method, payment_date) VALUES (?, ?, ?, ?, 'Active', ?, ?, ?, ?, ?, ?, 'Credit Card', NOW())");
    $stmt->execute([$session_id, $real_client_id, $therapist_id, $session_date, $amount, $method, $duration_minutes, $teams_mid, $teams_url, $status]);

    echo json_encode(['success' => true, 'message' => 'Booking confirmed!']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>