<?php
// seed_admin.php - Seeds an admin user and some sample data
require_once 'includes/db_connect.php';

try {
    // 1. Create Admin User
    $admin_email = 'admin@safehaven.com';
    $admin_pass = password_hash('AdminPassword123', PASSWORD_DEFAULT);
    $admin_id = 'A_1';
    $user_id = 'U_ADMIN_1';

    // Check if user exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM User WHERE email = ?");
    $stmt->execute([$admin_email]);
    if ($stmt->fetchColumn() == 0) {
        // Insert into User table
        $stmt = $pdo->prepare("INSERT INTO User (user_id, email, password_hash, role, created_at) VALUES (?, ?, ?, 'admin', NOW())");
        $stmt->execute([$user_id, $admin_email, $admin_pass]);
        echo "Admin added to User table.\n";
    }

    // Check if admin exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Admin WHERE admin_id = ?");
    $stmt->execute([$admin_id]);
    if ($stmt->fetchColumn() == 0) {
        // Insert into Admin table
        $stmt = $pdo->prepare("INSERT INTO Admin (admin_id, email, password_hash, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$admin_id, $admin_email, $admin_pass]);
        echo "Admin added to Admin table.\n";
    }

    // 2. Add sample resources if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM Resource");
    if ($stmt->fetchColumn() == 0) {
        $resources = [
            ['RES_1', 'Introduction to Facilitation', 'Article', 'Facilitation', $admin_id, 'https://example.com/guide'],
            ['RES_2', 'Crisis Management Protocol', 'PDF', 'Crisis Management', $admin_id, 'assets/uploads/resources/crisis_guide.pdf'],
            ['RES_3', 'Mindfulness Techniques Video', 'Video', 'Mental Health', $admin_id, 'https://youtube.com/watch?v=example']
        ];

        $stmt = $pdo->prepare("INSERT INTO Resource (resource_id, title, type, category, added_by_admin_id, created_at, url) VALUES (?, ?, ?, ?, ?, NOW(), ?)");
        foreach ($resources as $res) {
            $stmt->execute($res);
        }
        echo "Sample resources added.\n";
    }

    echo "Admin connection and seeding completed successfully.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
