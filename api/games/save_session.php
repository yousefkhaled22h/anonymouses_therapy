<?php
require_once '../../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$game_type = $data['game_type'] ?? '';
$duration = $data['duration'] ?? 0;
$user_id = $_SESSION['user_id'];

if (!$game_type || $duration <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

try {
    $stmt = $pdo->prepare("INSERT INTO client_activity_log (user_id, activity_type, game_type, duration_seconds) VALUES (?, 'game_session', ?, ?)");
    $stmt->execute([$user_id, $game_type, $duration]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
