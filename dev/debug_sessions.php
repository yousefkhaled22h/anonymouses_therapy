<?php
require 'includes/db_connect.php';
$stmt = $pdo->query("SELECT * FROM private_sessions");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
