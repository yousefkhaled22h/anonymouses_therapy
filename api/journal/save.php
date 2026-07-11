<?php
// api/journal/save.php
require_once '../../includes/db_connect.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Client') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); exit();
}

$user_id = $_SESSION['user_id'];
$entry_id = $_POST['entry_id'] ?? null;
$title = trim($_POST['title'] ?? 'Untitled');
$content = $_POST['content'] ?? '';
$mood = trim($_POST['mood'] ?? '');

$client_id = $_SESSION['client_id'] ?? null;
if (!$client_id) {
    $stmt = $pdo->prepare("SELECT client_id FROM client WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $client_id = $stmt->fetchColumn();
    $_SESSION['client_id'] = $client_id;
}

// Ensure table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS journal_entry (
        entry_id INT AUTO_INCREMENT PRIMARY KEY,
        client_id VARCHAR(50) NOT NULL,
        title VARCHAR(255) DEFAULT 'Untitled',
        content LONGTEXT,
        mood VARCHAR(50) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_journal_entry_client FOREIGN KEY (client_id) REFERENCES client(client_id) ON DELETE CASCADE
    )");
} catch (Exception $e) {}

try {
    if ($entry_id) {
        // Update existing
        $stmt = $pdo->prepare("UPDATE journal_entry SET title=?, content=?, mood=? WHERE entry_id=? AND client_id=?");
        $stmt->execute([$title, $content, $mood, $entry_id, $client_id]);
        echo json_encode(['status' => 'success', 'entry_id' => $entry_id]);
    } else {
        // Insert new
        $stmt = $pdo->prepare("INSERT INTO journal_entry (client_id, title, content, mood) VALUES (?,?,?,?)");
        $stmt->execute([$client_id, $title, $content, $mood]);
        $new_id = $pdo->lastInsertId();
        echo json_encode(['status' => 'success', 'entry_id' => $new_id]);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
