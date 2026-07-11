<?php
// api/mood/log.php
header('Content-Type: application/json');
require_once '../../includes/db_connect.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Client') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$mood = trim($data['mood'] ?? '');
$action = $data['action'] ?? 'add';
$user_id = $_SESSION['user_id'];

if (empty($mood)) {
    echo json_encode(['success' => false, 'message' => 'Mood is required.']);
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

    // Ensure client_mood_history table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS client_mood_history (
        history_id VARCHAR(50) PRIMARY KEY,
        client_id VARCHAR(50) NOT NULL,
        mood VARCHAR(50) NOT NULL,
        recorded_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $date = date('Y-m-d');
    
    if ($action === 'delete') {
        // Delete today's specific mood entry
        $stmt = $pdo->prepare("DELETE FROM client_mood_history WHERE client_id = ? AND mood = ? AND recorded_date = ?");
        $stmt->execute([$client_id, $mood, $date]);
        echo json_encode(['success' => true, 'message' => 'Mood removed successfully!']);
    } else {
        // Check if ANY mood is already logged today for this client
        $stmt = $pdo->prepare("SELECT mood FROM client_mood_history WHERE client_id = ? AND recorded_date = ?");
        $stmt->execute([$client_id, $date]);
        $any_moods = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

        if (!empty($any_moods)) {
            if (in_array($mood, $any_moods)) {
                echo json_encode(['success' => true, 'message' => 'Mood logged successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'You have already logged a mood today. Please unselect it first.']);
            }
            exit();
        }

        // Insert new mood in client_mood_history
        $history_id = uniqid('mood_');
        $stmt = $pdo->prepare("INSERT INTO client_mood_history (history_id, client_id, mood, recorded_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$history_id, $client_id, $mood, $date]);
        echo json_encode(['success' => true, 'message' => 'Mood logged successfully!']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
