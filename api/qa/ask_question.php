<?php
// api/qa/ask_question.php
header('Content-Type: application/json');
require_once '../../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$content = trim($_POST['content'] ?? '');
$category = trim($_POST['category'] ?? 'General');

if (empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Please provide your question.']);
    exit();
}

try {
    $post_id = 'q_' . bin2hex(random_bytes(8));

    // We use NULL or 'anonymous' for user_id to represent an anonymous public question
    $stmt = $pdo->prepare("INSERT INTO community_qna (post_id, user_id, parent_id, post_type, category, content, created_at) VALUES (?, NULL, NULL, 'qna', ?, ?, ?)");
    $stmt->execute([$post_id, $category, $content, date('Y-m-d H:i:s')]);

    echo json_encode(['success' => true, 'message' => 'Your question has been submitted anonymously! Our therapists will review and answer it soon.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>