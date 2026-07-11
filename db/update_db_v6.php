<?php
// db/update_db_v6.php
require_once __DIR__ . '/../includes/db_connect.php';

try {
    // 1. Update user table
    $alter_user_cols = [
        "status" => "ALTER TABLE `user` ADD COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'Active'",
        "admin_role" => "ALTER TABLE `user` ADD COLUMN `admin_role` VARCHAR(50) DEFAULT NULL",
        "last_login" => "ALTER TABLE `user` ADD COLUMN `last_login` DATETIME DEFAULT NULL",
        "activity_score" => "ALTER TABLE `user` ADD COLUMN `activity_score` INT NOT NULL DEFAULT 0"
    ];

    foreach ($alter_user_cols as $col => $sql) {
        try {
            $pdo->exec($sql);
            echo "✅ Column '$col' added to table 'user'\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "⏭️  Column '$col' already exists in table 'user'\n";
            } else {
                echo "❌ Error adding '$col': " . $e->getMessage() . "\n";
            }
        }
    }

    // 2. Create audit_log table
    $create_audit_log = "CREATE TABLE IF NOT EXISTS `audit_log` (
        `log_id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` VARCHAR(50) NULL,
        `email` VARCHAR(100) NULL,
        `action` VARCHAR(255) NOT NULL,
        `details` TEXT NULL,
        `ip_address` VARCHAR(45) NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    $pdo->exec($create_audit_log);
    echo "✅ Table 'audit_log' created or verified\n";

    // 3. Create notifications table
    $create_notifications = "CREATE TABLE IF NOT EXISTS `notifications` (
        `notification_id` VARCHAR(50) PRIMARY KEY,
        `sender_id` VARCHAR(50) NOT NULL,
        `receiver_id` VARCHAR(50) NULL,
        `target_role` VARCHAR(50) NOT NULL DEFAULT 'all',
        `title` VARCHAR(255) NOT NULL,
        `message` TEXT NOT NULL,
        `type` VARCHAR(50) NOT NULL DEFAULT 'system',
        `scheduled_at` DATETIME NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    $pdo->exec($create_notifications);
    echo "✅ Table 'notifications' created or verified\n";

    // 4. Create notification_reads table
    $create_notification_reads = "CREATE TABLE IF NOT EXISTS `notification_reads` (
        `notification_id` VARCHAR(50) NOT NULL,
        `user_id` VARCHAR(50) NOT NULL,
        `read_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`notification_id`, `user_id`),
        FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`notification_id`) ON DELETE CASCADE,
        FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    $pdo->exec($create_notification_reads);
    echo "✅ Table 'notification_reads' created or verified\n";

    // 5. Seed Super Admin Role for U_ADMIN_1 if present
    $stmt = $pdo->prepare("UPDATE `user` SET `admin_role` = 'Super Admin' WHERE `user_id` = 'U_ADMIN_1'");
    $stmt->execute();
    echo "✅ Seeded Super Admin role to U_ADMIN_1 if it exists\n";

} catch (PDOException $e) {
    echo "❌ CRITICAL DB MIGRATION ERROR: " . $e->getMessage() . "\n";
}
?>
