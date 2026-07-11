<?php
session_start();
$_SESSION['user_id'] = 'user_client';
$_SESSION['role'] = 'Client';

require '../../includes/db_connect.php';
$client = $pdo->query("SELECT * FROM Client LIMIT 1")->fetch();
$_SESSION['user_id'] = $client['user_id'];
$client_id = $client['client_id'];

$therapist = $pdo->query("SELECT therapist_id FROM Therapist LIMIT 1")->fetch();
$therapist_id = $therapist['therapist_id'];

// Book a session for TOMORROW
$session_date = date('Y-m-d H:i:s', strtotime('+1 day'));
$stmt = $pdo->prepare("
    INSERT INTO private_sessions (private_session_id, client_id, therapist_id, session_date, duration_minutes, amount, status, communication_method)
    VALUES (?, ?, ?, ?, 60, 500, 'pending', 'Video Session')
");
$stmt->execute(['TEST-PS-1', $client_id, $therapist_id, $session_date]);

require 'fetch_upcoming.php';
?>
