<?php
require_once '../../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT payload FROM client_activity_log WHERE user_id = ? AND activity_type = 'user_artwork' ORDER BY updated_at DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $artwork = $stmt->fetchColumn();
    
    echo json_encode(['success' => true, 'svg_data' => $artwork]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
