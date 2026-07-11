<?php
// scratch/describe_tables.php
require_once __DIR__ . '/../includes/db_connect.php';

$tables = ['client_mood_history', 'therapist', 'resource', 'daily_challenges', 'group_sessions', 'client', 'user'];

foreach ($tables as $t) {
    try {
        echo "\n--- Table $t:\n";
        $stmt = $pdo->query("DESCRIBE `$t`");
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $c) {
            echo "  {$c['Field']} ({$c['Type']}) " . ($c['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . ($c['Key'] ? " KEY:{$c['Key']}" : "") . "\n";
        }
    } catch (Exception $e) {
        echo "  Error: " . $e->getMessage() . "\n";
    }
}
