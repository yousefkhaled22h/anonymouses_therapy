<?php
chdir(__DIR__);
require_once '../includes/db_connect.php';

$cols = $pdo->query('DESCRIBE user')->fetchAll(PDO::FETCH_COLUMN);
echo "user columns: " . implode(', ', $cols) . "\n";
?>
