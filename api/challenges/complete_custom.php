<?php
// api/challenges/complete_custom.php
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
$description = trim($data['description'] ?? '');
$user_id = $_SESSION['user_id'];

if (empty($description) || strlen($description) < 3) {
    echo json_encode(['success' => false, 'message' => 'Please describe what you did.']);
    exit();
}

try {
    // Get client_id
    $stmt = $pdo->prepare("SELECT client_id FROM client WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $client_id = $stmt->fetchColumn();

    if (!$client_id) {
        echo json_encode(['success' => false, 'message' => 'Client not found']);
        exit();
    }

    // Insert custom challenge and award 10 points
    $pdo->beginTransaction();

    $challenge_id = 'cust_' . bin2hex(random_bytes(8));

    // Insert into daily_challenges
    $stmt = $pdo->prepare("INSERT INTO daily_challenges (challenge_id, title, description, difficulty, points, client_id) VALUES (?, 'Custom Challenge', ?, 'Custom', 10, ?)");
    $stmt->execute([$challenge_id, $description, $client_id]);

    // Insert into completed_challenges
    $stmt = $pdo->prepare("INSERT INTO completed_challenges (client_id, challenge_id) VALUES (?, ?)");
    $stmt->execute([$client_id, $challenge_id]);

    // Update Client points
    $stmt = $pdo->prepare("UPDATE client SET points = points + 10 WHERE client_id = ?");
    $stmt->execute([$client_id]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Custom challenge completed!', 'points_earned' => 10]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
