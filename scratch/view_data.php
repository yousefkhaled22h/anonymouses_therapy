<?php
// scratch/view_data.php
require_once __DIR__ . '/../includes/db_connect.php';

$tables = ['therapist', 'resource', 'daily_challenges', 'group_sessions', 'client_mood_history'];

foreach ($tables as $t) {
    echo "\n--- Rows in $t:\n";
    try {
        $stmt = $pdo->query("SELECT * FROM `$t` LIMIT 3");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $idx => $r) {
            echo "Row $idx:\n";
            foreach ($r as $col => $val) {
                // truncate long text
                if (strlen($val) > 100) $val = substr($val, 0, 97) . '...';
                echo "  $col: $val\n";
            }
        }
        if (empty($rows)) {
            echo "  (No rows found)\n";
        }
    } catch (Exception $e) {
        echo "  Error: " . $e->getMessage() . "\n";
    }
}
