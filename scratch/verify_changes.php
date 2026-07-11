<?php
// scratch/verify_changes.php
require_once 'includes/db_connect.php';

$test_client_id = 'test_cli_9999';

// Reset existing test client logs/entries if any
try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("DELETE FROM client_mood_history WHERE client_id = '$test_client_id'");
    $pdo->exec("DELETE FROM Journal_Entry WHERE client_id = '$test_client_id'");
} catch (Exception $e) {}

echo "=== Testing Mood Tracking ===\n";

// Ensure table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS client_mood_history (
    history_id VARCHAR(50) PRIMARY KEY,
    client_id VARCHAR(50) NOT NULL,
    mood VARCHAR(50) NOT NULL,
    recorded_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$date = date('Y-m-d');

// 1. Add "Happy"
$history_id = uniqid('mood_');
$stmt = $pdo->prepare("INSERT INTO client_mood_history (history_id, client_id, mood, recorded_date) VALUES (?, ?, ?, ?)");
$stmt->execute([$history_id, $test_client_id, 'Happy', $date]);
echo "Logged Happy.\n";

// 2. Add "Calm"
$history_id = uniqid('mood_');
$stmt = $pdo->prepare("INSERT INTO client_mood_history (history_id, client_id, mood, recorded_date) VALUES (?, ?, ?, ?)");
$stmt->execute([$history_id, $test_client_id, 'Calm', $date]);
echo "Logged Calm.\n";

// 3. Fetch today's moods
$stmt = $pdo->prepare("SELECT mood FROM client_mood_history WHERE client_id = ? AND recorded_date = ?");
$stmt->execute([$test_client_id, $date]);
$moods = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Logged moods for today: " . implode(', ', $moods) . " (Expected: Happy, Calm)\n";

// 4. Delete "Happy"
$stmt = $pdo->prepare("DELETE FROM client_mood_history WHERE client_id = ? AND mood = ? AND recorded_date = ?");
$stmt->execute([$test_client_id, 'Happy', $date]);
echo "Deleted Happy.\n";

// 5. Fetch again
$stmt = $pdo->prepare("SELECT mood FROM client_mood_history WHERE client_id = ? AND recorded_date = ?");
$stmt->execute([$test_client_id, $date]);
$moods = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Logged moods for today after deletion: " . implode(', ', $moods) . " (Expected: Calm)\n";


echo "\n=== Testing Journal Entry Auto-Increment ===\n";

// Ensure table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS Journal_Entry (
    entry_id INT AUTO_INCREMENT PRIMARY KEY,
    client_id VARCHAR(50) NOT NULL,
    title VARCHAR(255) DEFAULT 'Untitled',
    content LONGTEXT,
    mood VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Insert new
$stmt = $pdo->prepare("INSERT INTO Journal_Entry (client_id, title, content, mood) VALUES (?, ?, ?, ?)");
$stmt->execute([$test_client_id, 'Test Title', '<p>Test content 😊</p>', '😊']);
$new_id = $pdo->lastInsertId();
echo "Inserted entry. Generated ID: $new_id (Expected: Integer greater than 0)\n";

// Query and check
$stmt = $pdo->prepare("SELECT * FROM Journal_Entry WHERE entry_id = ?");
$stmt->execute([$new_id]);
$entry = $stmt->fetch();
if ($entry) {
    echo "Retrieved entry correctly! Title: " . $entry['title'] . ", Mood: " . $entry['mood'] . "\n";
} else {
    echo "ERROR: Entry not found!\n";
}

// Clean up test data
try {
    $pdo->exec("DELETE FROM client_mood_history WHERE client_id = '$test_client_id'");
    $pdo->exec("DELETE FROM Journal_Entry WHERE client_id = '$test_client_id'");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "Cleaned up test data.\n";
} catch (Exception $e) {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
}
?>
