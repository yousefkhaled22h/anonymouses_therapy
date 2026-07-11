<?php
require_once '../../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT activity_id as artwork_id, game_type as artwork_type, payload as svg_data, updated_at FROM client_activity_log WHERE user_id = ? AND activity_type = 'user_artwork' ORDER BY updated_at DESC");
    $stmt->execute([$user_id]);
    $artworks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'artworks' => $artworks]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
