<?php
$pdo = new PDO("mysql:host=localhost;dbname=anonymous-therapy;charset=utf8mb4", "root", "", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);
$stmt = $pdo->query("DESCRIBE therapist");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
