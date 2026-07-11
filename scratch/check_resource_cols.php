<?php
require_once __DIR__ . '/../includes/db_connect.php';
try {
    $cols = $pdo->query("SHOW COLUMNS FROM resource")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo "  {$c['Field']} ({$c['Type']})\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
