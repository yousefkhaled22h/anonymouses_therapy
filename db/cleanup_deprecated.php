<?php
// db/cleanup_deprecated.php - Drop deprecated and orphaned tables
require_once __DIR__ . '/../includes/db_connect.php';

echo "<h2>Cleaning Up Deprecated Tables</h2><pre style='font-family:monospace;font-size:14px'>";

$tables_to_drop = [
    'community_comment',
    'community_qna',
    'game_session',
    'grounding_session',
    'user_artwork',
    'user_safe_space',
    'emotional_dashboard',
    'user_resource',
    'therapist_cv_section',
    'session_summary'
];

// Temporarily disable foreign key checks to allow dropping tables safely
try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    
    foreach ($tables_to_drop as $table) {
        $pdo->exec("DROP TABLE IF EXISTS `$table`;");
        echo "🗑️ Dropped table: $table\n";
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "\n🎉 Deprecated tables cleanup completed successfully!\n";
} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
