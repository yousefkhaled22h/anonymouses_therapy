<?php
// api/qa/submit_answer.php
header('Content-Type: application/json');
require_once '../../includes/db_connect.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Ensure user is a Therapist (Bypassed verification check for Q&A interactivity)
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'therapist') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$post_id = $_POST['post_id'] ?? '';
$answer = trim($_POST['answer'] ?? '');
$therapist_id = $_SESSION['user_id'];

if (empty($post_id) || empty($answer)) {
    echo json_encode(['success' => false, 'message' => 'Please provide an answer.']);
    exit();
}

try {
    // Validate that the question exists and is a Q&A question
    $chk = $pdo->prepare("SELECT category FROM community_qna WHERE post_id = ? AND post_type = 'qna'");
    $chk->execute([$post_id]);
    $category = $chk->fetchColumn();
    if (!$category) {
        echo json_encode(['success' => false, 'message' => 'Question not found or invalid.']);
        exit();
    }

    // Insert therapist answer as a reply
    $reply_id = 'cmt_ans_' . bin2hex(random_bytes(8));
    $stmt = $pdo->prepare("INSERT INTO community_qna (post_id, user_id, parent_id, post_type, category, content, created_at) VALUES (?, ?, ?, 'reply', ?, ?, ?)");
    $stmt->execute([$reply_id, $therapist_id, $post_id, $category, $answer, date('Y-m-d H:i:s')]);

    echo json_encode(['success' => true, 'message' => 'Answer submitted successfully!']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>