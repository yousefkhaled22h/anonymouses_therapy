<?php
require_once '../includes/db_connect.php';
foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $t) {
    echo "Table: $t\n";
    foreach ($pdo->query("DESCRIBE `$t`")->fetchAll() as $c) {
        echo "  - {$c['Field']} ({$c['Type']}) " . ($c['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . ($c['Key'] ? " {$c['Key']}" : "") . "\n";
    }
}
?>
