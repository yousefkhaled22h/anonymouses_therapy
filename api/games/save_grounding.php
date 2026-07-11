<?php
require_once '../../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$responses = $data['responses'] ?? [];
$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("INSERT INTO client_activity_log (user_id, activity_type, payload) VALUES (?, 'grounding_session', ?)");
    $stmt->execute([$user_id, json_encode($responses)]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
