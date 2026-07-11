<?php
// api/group/create_session.php
require_once '../../includes/db_connect.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && strtolower($_SESSION['role'] ?? '') === 'therapist') {
    $room_name = trim($_POST['room_name'] ?? '');
    $topic = trim($_POST['topic'] ?? '');
    $session_date_post = trim($_POST['session_date'] ?? '');
    $session_date = !empty($session_date_post) ? date('Y-m-d H:i:s', strtotime($session_date_post)) : date('Y-m-d H:i:s');
    $max_participants = isset($_POST['max_participants']) ? (int) $_POST['max_participants'] : 15;
    $therapist_id = $_SESSION['therapist_id'] ?? null;
    $user_id = $_SESSION['user_id'];

    if (!$therapist_id) {
        $stmt = $pdo->prepare("SELECT therapist_id, verified FROM therapist WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch();
        $therapist_id = $row['therapist_id'] ?? null;
        $is_verified = ($row['verified'] ?? 0) == 1;
        $_SESSION['therapist_id'] = $therapist_id;
    } else {
        $stmt = $pdo->prepare("SELECT verified FROM therapist WHERE therapist_id = ?");
        $stmt->execute([$therapist_id]);
        $is_verified = ($stmt->fetchColumn() ?? 0) == 1;
    }

    if (!$is_verified) {
        header("Location: ../../therapist_dashboard.php");
        exit();
    }

    if (!$room_name) {
        $room_name = $topic;
    }

    if ($topic && $therapist_id) {
        try {
            $gs_id = 'gs_' . bin2hex(random_bytes(8));
            $duration_minutes = isset($_POST['duration_minutes']) ? (int) $_POST['duration_minutes'] : 60;
            $stmt = $pdo->prepare("INSERT INTO group_sessions (group_session_id, room_name, topic, session_date, duration_minutes, status, therapist_id, max_participants, created_at) VALUES (?, ?, ?, ?, ?, 'scheduled', ?, ?, NOW())");
            $stmt->execute([$gs_id, $room_name, $topic, $session_date, $duration_minutes, $therapist_id, $max_participants]);

            header("Location: ../../group_management.php?action=room&id=" . $gs_id);
            exit();
        } catch (PDOException $e) {
            header("Location: ../../group_management.php?error=" . urlencode("Database error."));
            exit();
        }
    }
}
header("Location: ../../group_management.php");
exit();
