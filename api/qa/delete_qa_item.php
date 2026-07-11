<?php
// api/qa/delete_qa_item.php
session_start();
require_once '../../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'therapist') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Only therapists can delete content.']);
    exit();
}

$item_type = $_POST['item_type'] ?? ''; // 'post' or 'comment'
$item_id = $_POST['item_id'] ?? '';

if (empty($item_type) || empty($item_id)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit();
}

try {
    if ($item_type === 'post') {
        // Fetch post author role
        $stmt = $pdo->prepare("
            SELECT u.role 
            FROM community_qna q
            JOIN user u ON q.user_id = u.user_id
            WHERE q.post_id = ?
        ");
        $stmt->execute([$item_id]);
        $author_role = strtolower($stmt->fetchColumn() ?: '');

        if ($author_role !== 'client') {
             echo json_encode(['success' => false, 'message' => 'You can only delete content created by Clients.']);
             exit();
        }

        // Soft delete the post
        $stmt = $pdo->prepare("UPDATE community_qna SET report_status = 'DeletedByTherapist' WHERE post_id = ?");
        $stmt->execute([$item_id]);

        echo json_encode(['success' => true, 'message' => 'Post hidden successfully.']);

    } elseif ($item_type === 'comment') {
        // Fetch comment author role
        $stmt = $pdo->prepare("
            SELECT u.role 
            FROM community_qna c
            JOIN user u ON c.user_id = u.user_id
            WHERE c.post_id = ?
        ");
        $stmt->execute([$item_id]);
        $author_role = strtolower($stmt->fetchColumn() ?: '');

        if ($author_role !== 'client') {
             echo json_encode(['success' => false, 'message' => 'You can only delete content created by Clients.']);
             exit();
        }

        // Soft delete the comment
        $stmt = $pdo->prepare("UPDATE community_qna SET report_status = 'DeletedByTherapist' WHERE post_id = ?");
        $stmt->execute([$item_id]);

        echo json_encode(['success' => true, 'message' => 'Comment hidden successfully.']);

    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid item type.']);
    }

} catch (PDOException $e) {
    error_log("QA Delete Item Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
}
?>
