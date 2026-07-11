<?php
// db/update_db_v7_no_new_tables.php
require_once __DIR__ . '/../includes/db_connect.php';

try {
    $pdo->beginTransaction();

    // 1. Drop the separate log and notification tables
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("DROP TABLE IF EXISTS `notification_reads`");
    echo "✅ Table 'notification_reads' dropped\n";

    $pdo->exec("DROP TABLE IF EXISTS `notifications`");
    echo "✅ Table 'notifications' dropped\n";

    $pdo->exec("DROP TABLE IF EXISTS `audit_log`");
    echo "✅ Table 'audit_log' dropped\n";

    // 2. Add audit_logs and notifications_json columns to admin table if they don't exist
    $alter_admin_cols = [
        "audit_logs" => "ALTER TABLE `admin` ADD COLUMN `audit_logs` LONGTEXT DEFAULT NULL",
        "notifications_json" => "ALTER TABLE `admin` ADD COLUMN `notifications_json` LONGTEXT DEFAULT NULL"
    ];

    foreach ($alter_admin_cols as $col => $sql) {
        try {
            $pdo->exec($sql);
            echo "✅ Column '$col' added to table 'admin'\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "⏭️  Column '$col' already exists in table 'admin'\n";
            } else {
                echo "❌ Error adding '$col': " . $e->getMessage() . "\n";
            }
        }
    }

    // 3. Rename A_1 to U_ADMIN_1 if A_1 exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `admin` WHERE `admin_id` = 'A_1'");
    $stmt->execute();
    if ($stmt->fetchColumn() > 0) {
        // Update admin table ID
        $pdo->exec("UPDATE `admin` SET `admin_id` = 'U_ADMIN_1' WHERE `admin_id` = 'A_1'");
        // Update resource table references
        $pdo->exec("UPDATE `resource` SET `added_by_admin_id` = 'U_ADMIN_1' WHERE `added_by_admin_id` = 'A_1'");
        echo "✅ Renamed admin ID 'A_1' to 'U_ADMIN_1' and updated resource references\n";
    }

    // 4. Ensure consistent admin user U_ADMIN_1 exists in admin table
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `admin` WHERE `admin_id` = 'U_ADMIN_1'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $stmt_ins = $pdo->prepare("INSERT INTO `admin` (`admin_id`, `email`, `password_hash`, `created_at`) VALUES (?, ?, ?, NOW())");
        $hash = password_hash('admin123', PASSWORD_BCRYPT);
        $stmt_ins->execute(['U_ADMIN_1', 'admin@safehaven.com', $hash]);
        echo "✅ Seeded new admin user 'U_ADMIN_1' to 'admin' table\n";
    } else {
        echo "⏭️  Admin user 'U_ADMIN_1' now verified to exist in 'admin' table\n";
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    $pdo->commit();
    echo "🎉 Database migration completed successfully!\n";

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "❌ CRITICAL DB MIGRATION ERROR: " . $e->getMessage() . "\n";
}
?>
