<?php
require 'includes/db_connect.php';
$stmt = $pdo->query("SELECT client_id, name, anonymous_id FROM client");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
