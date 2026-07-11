<?php
require_once 'includes/db_connect.php';
$stmt = $pdo->query("SELECT * FROM Resource");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
