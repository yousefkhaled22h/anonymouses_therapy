<?php
require_once 'includes/db_connect.php';
$stmt = $pdo->query("SELECT count(*) FROM Journal_Entry WHERE entry_id = 0");
echo "Entries with ID 0: " . $stmt->fetchColumn() . "\n";
$stmt = $pdo->query("SELECT * FROM Journal_Entry LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
