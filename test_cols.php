<?php
require 'includes/db_connect.php';
$stmt = $pdo->query('DESCRIBE client');
file_put_contents('test_cols.txt', print_r($stmt->fetchAll(PDO::FETCH_ASSOC), true));

