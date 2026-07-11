<?php
require 'includes/db_connect.php';
$cols = $pdo->query("SHOW COLUMNS FROM private_sessions WHERE Field = 'status'")->fetch();
print_r($cols);
?>
