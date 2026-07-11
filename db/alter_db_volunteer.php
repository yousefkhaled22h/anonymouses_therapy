<?php
/**
 * db/alter_db_volunteer.php
 * Migration: Ensure the `volunteer` table matches the new schema.
 *
 * New schema columns:
 *   volunteer_id, user_id, first_name, last_name, bio,
 *   languages, skills, availability,
 *   total_sessions, rating, status,
 *   created_at, updated_at
 *
 * Run once via browser: http://localhost/test2/db/alter_db_volunteer.php
 */
require_once '../includes/db_connect.php';

$results = [];

// Helper: check if a column exists in the table
function columnExists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME   = :table
           AND COLUMN_NAME  = :column"
    );
    $stmt->execute([':table' => $table, ':column' => $column]);
    return (bool) $stmt->fetchColumn();
}

$alterations = [
    // Column name => ADD SQL (run only if column is missing)
    'languages'   => "ALTER TABLE `volunteer` ADD COLUMN `languages` TEXT DEFAULT NULL AFTER `bio`",
    'skills'      => "ALTER TABLE `volunteer` ADD COLUMN `skills` TEXT DEFAULT NULL AFTER `languages`",
    'availability'=> "ALTER TABLE `volunteer` ADD COLUMN `availability` TEXT DEFAULT NULL AFTER `skills`",
    'total_sessions'=> "ALTER TABLE `volunteer` ADD COLUMN `total_sessions` INT(11) DEFAULT 0 AFTER `availability`",
    'rating'      => "ALTER TABLE `volunteer` ADD COLUMN `rating` DECIMAL(3,2) DEFAULT NULL AFTER `total_sessions`",
    'status'      => "ALTER TABLE `volunteer` ADD COLUMN `status` VARCHAR(50) DEFAULT NULL AFTER `rating`",
    'updated_at'  => "ALTER TABLE `volunteer` ADD COLUMN `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP() AFTER `created_at`",
];

foreach ($alterations as $col => $sql) {
    try {
        if (!columnExists($pdo, 'volunteer', $col)) {
            $pdo->exec($sql);
            $results[] = "✅ Added column <b>$col</b>.";
        } else {
            $results[] = "⏭️ Column <b>$col</b> already exists — skipped.";
        }
    } catch (PDOException $e) {
        $results[] = "❌ Error adding <b>$col</b>: " . htmlspecialchars($e->getMessage());
    }
}

// Ensure PRIMARY KEY + UNIQUE KEY are in place (safe to run even if already set)
try {
    // Primary key – ignore error if already exists
    $pdo->exec("ALTER TABLE `volunteer` ADD PRIMARY KEY (`volunteer_id`)");
    $results[] = "✅ Primary key set on <b>volunteer_id</b>.";
} catch (PDOException $e) {
    $results[] = "⏭️ Primary key already exists — skipped.";
}

try {
    $pdo->exec("ALTER TABLE `volunteer` ADD UNIQUE KEY `user_id` (`user_id`)");
    $results[] = "✅ Unique key set on <b>user_id</b>.";
} catch (PDOException $e) {
    $results[] = "⏭️ Unique key on user_id already exists — skipped.";
}

// Ensure FK to user table (safe to ignore if already set)
try {
    $pdo->exec("ALTER TABLE `volunteer` ADD CONSTRAINT `volunteer_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE");
    $results[] = "✅ Foreign key volunteer → user added.";
} catch (PDOException $e) {
    $results[] = "⏭️ Foreign key already exists — skipped.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Volunteer DB Migration</title>
    <style>
        body { font-family: sans-serif; max-width: 700px; margin: 40px auto; background: #f5f7fa; }
        h2   { color: #27ae60; }
        li   { padding: 6px 0; font-size: 15px; }
    </style>
</head>
<body>
    <h2>Volunteer Table Migration</h2>
    <ul>
        <?php foreach ($results as $r): ?>
            <li><?= $r ?></li>
        <?php endforeach; ?>
    </ul>
    <p><a href="../volunteer_dashboard.php">← Back to Volunteer Dashboard</a></p>
</body>
</html>
