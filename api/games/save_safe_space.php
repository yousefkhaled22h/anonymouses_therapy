<?php
require_once '../../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$design = $data['design'] ?? [];
$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT activity_id FROM client_activity_log WHERE user_id = ? AND activity_type = 'user_safe_space'");
    $stmt->execute([$user_id]);
    $activity_id = $stmt->fetchColumn();

    $design_json = json_encode($design);
    if ($activity_id) {
        $stmt = $pdo->prepare("UPDATE client_activity_log SET payload = ?, updated_at = CURRENT_TIMESTAMP WHERE activity_id = ?");
        $stmt->execute([$design_json, $activity_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO client_activity_log (user_id, activity_type, payload) VALUES (?, 'user_safe_space', ?)");
        $stmt->execute([$user_id, $design_json]);
    }
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
