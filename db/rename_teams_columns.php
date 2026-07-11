<?php
// db/rename_teams_columns.php
require_once __DIR__ . '/../includes/db_connect.php';

try {
    echo "=== Renaming Columns in private_sessions ===\n";

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

    if (columnExists($pdo, 'private_sessions', 'teams_meeting_id')) {
        $pdo->exec("ALTER TABLE `private_sessions` CHANGE `teams_meeting_id` `zoom_meeting_id` VARCHAR(255) DEFAULT NULL");
        echo "✅ Renamed teams_meeting_id to zoom_meeting_id\n";
    } else {
        echo "⏭️  teams_meeting_id already renamed or does not exist\n";
    }

    if (columnExists($pdo, 'private_sessions', 'teams_join_url')) {
        $pdo->exec("ALTER TABLE `private_sessions` CHANGE `teams_join_url` `zoom_join_url` VARCHAR(500) DEFAULT NULL");
        echo "✅ Renamed teams_join_url to zoom_join_url\n";
    } else {
        echo "⏭️  teams_join_url already renamed or does not exist\n";
    }

    echo "🎉 Database columns renamed successfully!\n";

} catch (PDOException $e) {
    echo "❌ Error renaming columns: " . $e->getMessage() . "\n";
}
