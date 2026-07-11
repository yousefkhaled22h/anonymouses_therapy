<?php
// api/qa/helpful.php — Toggle helpful vote on a post or comment
require_once '../../includes/db_connect.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit();
}

// Restricted for unverified therapists (Bypassed for Q&A interactivity)

$data    = json_decode(file_get_contents('php://input'), true) ?? [];
$type    = $data['type']    ?? '';   // 'post' or 'comment'
$item_id = $data['item_id'] ?? '';
$user_id = $_SESSION['user_id'];

if (empty($type) || empty($item_id)) {
    echo json_encode(['success' => false, 'message' => 'Missing params']);
    exit();
}

try {
    // Ensure the helpful_votes table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS helpful_votes (
        vote_id    VARCHAR(32)  PRIMARY KEY,
        user_id    VARCHAR(50)  NOT NULL,
        item_id    VARCHAR(60)  NOT NULL,
        item_type  VARCHAR(10)  NOT NULL,
        created_at DATETIME DEFAULT NOW(),
        UNIQUE KEY uq_vote (user_id, item_id, item_type),
        CONSTRAINT fk_helpful_votes_user FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
    )");

    // Check if already voted
    $check = $pdo->prepare("SELECT vote_id FROM helpful_votes WHERE user_id=? AND item_id=? AND item_type=?");
    $check->execute([$user_id, $item_id, $type]);
    $existing = $check->fetchColumn();

    if ($existing) {
        // Remove vote (toggle off)
        $del = $pdo->prepare("DELETE FROM helpful_votes WHERE user_id=? AND item_id=? AND item_type=?");
        $del->execute([$user_id, $item_id, $type]);
        if ($type === 'post') {
            $pdo->prepare("UPDATE community_qna SET helpful_count = GREATEST(helpful_count - 1, 0) WHERE post_id=?")->execute([$item_id]);
        }
        $voted = false;
    } else {
        // Add vote
        $vote_id = 'v_' . bin2hex(random_bytes(8));
        $ins = $pdo->prepare("INSERT INTO helpful_votes (vote_id, user_id, item_id, item_type) VALUES (?,?,?,?)");
        $ins->execute([$vote_id, $user_id, $item_id, $type]);
        if ($type === 'post') {
            $pdo->prepare("UPDATE community_qna SET helpful_count = helpful_count + 1 WHERE post_id=?")->execute([$item_id]);
        }
        $voted = true;
    }

    // Get fresh count
    if ($type === 'post') {
        $cnt = $pdo->prepare("SELECT helpful_count FROM community_qna WHERE post_id=?");
        $cnt->execute([$item_id]);
        $count = (int)$cnt->fetchColumn();
    } else {
        $cnt = $pdo->prepare("SELECT COUNT(*) FROM helpful_votes WHERE item_id=? AND item_type=?");
        $cnt->execute([$item_id, $type]);
        $count = (int)$cnt->fetchColumn();
    }

    echo json_encode(['success' => true, 'voted' => $voted, 'count' => $count]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
