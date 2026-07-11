<?php
// api/challenges/complete.php
header('Content-Type: application/json');
require_once '../../includes/db_connect.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$challenge_id = $data['challenge_id'] ?? '';
$user_id = $_SESSION['user_id'];

if (empty($challenge_id)) {
    echo json_encode(['success' => false, 'message' => 'Missing challenge ID']);
    exit();
}

try {
    // 1. Get client_id
    $stmt = $pdo->prepare("SELECT client_id FROM client WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $client_id = $stmt->fetchColumn();

    if (!$client_id) {
        echo json_encode(['success' => false, 'message' => 'Client not found']);
        exit();
    }

    // 2. Check if already completed
    $stmt = $pdo->prepare("SELECT id FROM completed_challenges WHERE client_id = ? AND challenge_id = ?");
    $stmt->execute([$client_id, $challenge_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Challenge already completed']);
        exit();
    }

    // 3. Get challenge points
    $stmt = $pdo->prepare("SELECT points FROM daily_challenges WHERE challenge_id = ?");
    $stmt->execute([$challenge_id]);
    $points = $stmt->fetchColumn() ?: 10;

    // 4. Record completion and update points in a transaction
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO completed_challenges (client_id, challenge_id) VALUES (?, ?)");
    $stmt->execute([$client_id, $challenge_id]);

    $stmt = $pdo->prepare("UPDATE client SET points = points + ? WHERE client_id = ?");
    $stmt->execute([$points, $client_id]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Challenge completed!', 'points_earned' => $points]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
