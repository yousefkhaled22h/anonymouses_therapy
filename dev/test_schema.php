<?php
require 'includes/db_connect.php';
$stmt = $pdo->query('SHOW COLUMNS FROM private_sessions');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
