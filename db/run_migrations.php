<?php
// db/run_migrations.php — Run all required private_sessions migrations
require_once __DIR__ . '/../includes/db_connect.php';

$migrations = [
    "ALTER TABLE private_sessions ADD COLUMN early_start_requested TINYINT(1) NOT NULL DEFAULT 0",
    "ALTER TABLE private_sessions ADD COLUMN proposed_datetime DATETIME NULL",
    "ALTER TABLE private_sessions ADD COLUMN reschedule_requested TINYINT(1) NOT NULL DEFAULT 0",
    "ALTER TABLE private_sessions ADD COLUMN duration_minutes INT NOT NULL DEFAULT 60",
    "ALTER TABLE private_sessions ADD COLUMN payment_status VARCHAR(50) NOT NULL DEFAULT 'paid'",
];

echo "<h2>Migration Results</h2><pre style='font-family:monospace;font-size:14px'>";
foreach ($migrations as $sql) {
    preg_match('/ADD COLUMN (\w+)/', $sql, $m);
    $col = $m[1] ?? '?';
    try {
        $pdo->exec($sql);
        echo "✅ Added: $col\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "⏭️  Exists: $col\n";
        } else {
            echo "❌ ERROR on $col: " . $e->getMessage() . "\n";
        }
    }
}

// Show all columns
echo "\n--- private_sessions columns ---\n";
$cols = $pdo->query("SHOW COLUMNS FROM private_sessions")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "  {$c['Field']} ({$c['Type']}) default=" . ($c['Default'] ?? 'NULL') . "\n";
}

// Show actual session statuses and payment_status in DB
echo "\n--- Actual statuses in DB ---\n";
$rows = $pdo->query("SELECT DISTINCT status, payment_status, COUNT(*) as cnt FROM private_sessions GROUP BY status, payment_status")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo "  status='{$r['status']}' | payment_status='{$r['payment_status']}' | count={$r['cnt']}\n";
}

// Test the exact client query
echo "\n--- Test client query (no session needed) ---\n";
$test = $pdo->query("
    SELECT ps.private_session_id, ps.status, ps.payment_status, ps.communication_method, 
           ps.session_date, CONCAT(t.first_name,' ',t.last_name) as therapist
    FROM private_sessions ps
    JOIN Therapist t ON ps.therapist_id = t.therapist_id
    WHERE LOWER(ps.status) IN ('active','scheduled','reserved','pending','confirmed')
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

if (empty($test)) {
    echo "  No sessions found with those statuses.\n";
} else {
    foreach ($test as $r) {
        echo "  ID={$r['private_session_id']} | status={$r['status']} | payment={$r['payment_status']} | method={$r['communication_method']} | date={$r['session_date']} | therapist={$r['therapist']}\n";
    }
}

echo "\n✅ Done. <a href='../dashboard.php'>Go to Client Dashboard</a> | <a href='../therapist_dashboard.php'>Therapist Dashboard</a>\n";
echo "</pre>";
