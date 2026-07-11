<?php
require_once 'includes/db_connect.php';
$stmt = $pdo->query("DESCRIBE Journal_Entry");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
