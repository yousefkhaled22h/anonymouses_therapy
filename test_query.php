<?php
require 'includes/db_connect.php';

$client_id = $pdo->query("SELECT client_id FROM Client LIMIT 1")->fetchColumn();
$therapist_id = $pdo->query("SELECT therapist_id FROM Therapist LIMIT 1")->fetchColumn();

echo "Testing for client_id: $client_id, therapist_id: $therapist_id\n";

$date = date('Y-m-d H:i:s', strtotime('+1 day'));
$pdo->exec("INSERT INTO private_sessions (private_session_id, client_id, therapist_id, session_date, duration_minutes, amount, status, payment_status, payment_method, payment_date, communication_method) VALUES ('PS-TEST2', '$client_id', '$therapist_id', '$date', 60, 500, 'pending', 'paid', 'Wallet', NOW(), 'Video')");

echo "Inserted test session for tomorrow: $date\n";

$query = "
    SELECT ps.private_session_id as id, ps.session_date, ps.status, ps.duration_minutes
    FROM private_sessions ps 
    WHERE ps.client_id = ? 
      AND (
          (LOWER(ps.status) IN ('active', 'scheduled', 'reserved', 'pending', 'confirmed', 'pending_reschedule') AND DATE_ADD(ps.session_date, INTERVAL ps.duration_minutes MINUTE) >= NOW())
          OR (LOWER(ps.status) = 'cancelled' AND IFNULL(ps.cancellation_acknowledged, 0) = 0)
      )
";
$stmt = $pdo->prepare($query);
$stmt->execute([$client_id]);
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

print_r($sessions);

$pdo->exec("DELETE FROM private_sessions WHERE private_session_id = 'PS-TEST2'");
?>
