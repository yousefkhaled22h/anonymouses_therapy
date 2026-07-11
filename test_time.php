<?php
require 'includes/db_connect.php';

$php_time = date('Y-m-d H:i:s');
$mysql_time = $pdo->query("SELECT NOW()")->fetchColumn();

echo "PHP time: $php_time\n";
echo "MySQL time: $mysql_time\n";
?>
