<?php
// db/drop_unused_columns.php
require_once __DIR__ . '/../includes/db_connect.php';

try {
    echo "=== Dropping Unused Columns from private_sessions ===\n";

    // Helper to check if a column exists
    function columnExists($pdo, $table, $column) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
              AND TABLE_NAME = ? 
              AND COLUMN_NAME = ?
        ");
        $stmt->execute([$table, $column]);
        return $stmt->fetchColumn() > 0;
    }

    $cols_to_drop = [];
    if (columnExists($pdo, 'private_sessions', 'summary_content')) {
        $cols_to_drop[] = "DROP COLUMN `summary_content`";
    }
    if (columnExists($pdo, 'private_sessions', 'summary_generated_date')) {
        $cols_to_drop[] = "DROP COLUMN `summary_generated_date`";
    }

    if (!empty($cols_to_drop)) {
        $sql = "ALTER TABLE `private_sessions` " . implode(', ', $cols_to_drop);
        $pdo->exec($sql);
        echo "✅ Successfully dropped columns: " . implode(', ', array_map(function($c) {
            return str_replace('DROP COLUMN ', '', $c);
        }, $cols_to_drop)) . " from table 'private_sessions'\n";
    } else {
        echo "⏭️  No unused summary columns found in table 'private_sessions' (already dropped).\n";
    }

    echo "🎉 Database cleanup completed successfully!\n";

} catch (PDOException $e) {
    echo "❌ Error dropping columns: " . $e->getMessage() . "\n";
}
