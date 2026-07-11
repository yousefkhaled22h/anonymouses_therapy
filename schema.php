<?php
$pdo = new PDO('mysql:host=localhost;dbname=anonymous-therapy', 'root', '');
$stmt = $pdo->query('DESCRIBE private_sessions');
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
