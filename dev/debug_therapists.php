<?php
require 'includes/db_connect.php';
$stmt = $pdo->query("SELECT therapist_id, user_id, first_name, last_name FROM therapist");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
