<?php
// api/session/submit_feedback.php
require_once '../../includes/db_connect.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$session_id = $_POST['session_id'] ?? null;
$rating = $_POST['rating'] ?? null;
$therapist_rating = $_POST['therapist_rating'] ?? null;
$feedback = $_POST['feedback'] ?? '';

if (!$session_id || !$rating || !$therapist_rating) {
    echo json_encode(['status' => 'error', 'message' => 'Missing data']);
    exit();
}

try {
    // Verify that the session belongs to the current user (client)
    $stmt = $pdo->prepare("
        SELECT ps.private_session_id, ps.therapist_id
        FROM private_sessions ps
        JOIN Client c ON ps.client_id = c.client_id
        WHERE ps.private_session_id = ? AND c.user_id = ?
    ");
    $stmt->execute([$session_id, $_SESSION['user_id']]);
    $session_data = $stmt->fetch();
    if (!$session_data) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid session']);
        exit();
    }

    $therapist_id = $session_data['therapist_id'];
    $formatted_feedback = "[Therapist Rating: " . intval($therapist_rating) . "] " . $feedback;

    $stmt = $pdo->prepare("UPDATE private_sessions SET rating = ?, feedback = ? WHERE private_session_id = ?");
    $stmt->execute([$rating, $formatted_feedback, $session_id]);

    // Recalculate average therapist rating
    if ($therapist_id) {
        $stmt_all = $pdo->prepare("SELECT feedback FROM private_sessions WHERE therapist_id = ? AND feedback LIKE '[Therapist Rating:%'");
        $stmt_all->execute([$therapist_id]);
        $feedbacks = $stmt_all->fetchAll(PDO::FETCH_COLUMN) ?: [];

        $total_rating = 0;
        $count = 0;
        foreach ($feedbacks as $fb) {
            if (preg_match('/^\[Therapist Rating:\s*([1-5])\]/', $fb, $matches)) {
                $total_rating += intval($matches[1]);
                $count++;
            }
        }

        if ($count > 0) {
            $avg_rating = $total_rating / $count;
            $stmt_up = $pdo->prepare("UPDATE therapist SET rating = ?, review_count = ? WHERE therapist_id = ?");
            $stmt_up->execute([$avg_rating, $count, $therapist_id]);
        }
    }

    echo json_encode(['status' => 'success', 'message' => 'Feedback submitted!']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
