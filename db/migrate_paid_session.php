<?php
// db/migrate_private_sessions.php
// Adds missing columns to private_sessions table that the app requires.
// Safe to run multiple times — ignores "Duplicate column" errors.
require_once __DIR__ . '/../includes/db_connect.php';

$migrations = [
    "ALTER TABLE private_sessions ADD COLUMN early_start_requested TINYINT(1) NOT NULL DEFAULT 0",
    "ALTER TABLE private_sessions ADD COLUMN proposed_datetime DATETIME NULL",
    "ALTER TABLE private_sessions ADD COLUMN reschedule_requested TINYINT(1) NOT NULL DEFAULT 0",
    "ALTER TABLE private_sessions ADD COLUMN duration_minutes INT NOT NULL DEFAULT 60",
    "ALTER TABLE private_sessions ADD COLUMN payment_status VARCHAR(50) NOT NULL DEFAULT 'paid'",
];

echo "<h2>private_sessions Migration</h2><pre>";
foreach ($migrations as $sql) {
    try {
        $pdo->exec($sql);
        echo "✅ OK: " . substr($sql, 0, 80) . "\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "⏭️  SKIP (already exists): " . substr($sql, 36, 30) . "\n";
        } else {
            echo "❌ ERROR: " . $e->getMessage() . "\n";
        }
    }
}

// Verify current columns
$stmt = $pdo->query("SHOW COLUMNS FROM private_sessions");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "\n--- Current private_sessions columns ---\n";
foreach ($cols as $c) {
    echo "  " . $c['Field'] . " (" . $c['Type'] . ") default=" . ($c['Default'] ?? 'NULL') . "\n";
}

// Show a sample of recent rows
$stmt = $pdo->query("SELECT private_session_id, status, payment_status, communication_method FROM private_sessions ORDER BY private_session_id DESC LIMIT 5");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "\n--- Recent sessions ---\n";
foreach ($rows as $r) {
    echo "  " . implode(' | ', $r) . "\n";
}
echo "</pre>";
