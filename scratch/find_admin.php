<?php
// scratch/find_admin.php
require_once __DIR__ . '/../includes/db_connect.php';

try {
    // 1. Find admin users
    $stmt = $pdo->query("SELECT user_id, email, password_hash, role, admin_role, status FROM user WHERE role = 'admin'");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($admins)) {
        echo "No admin user found. Creating a new one...\n";
        
        $new_admin_id = 'U_ADMIN_1';
        $new_email = 'admin@safehaven.com';
        $new_password = 'admin123';
        $hash = password_hash($new_password, PASSWORD_BCRYPT);
        
        $stmt_ins = $pdo->prepare("INSERT INTO user (user_id, email, password_hash, role, admin_role, status) VALUES (?, ?, ?, 'admin', 'Super Admin', 'Active')");
        $stmt_ins->execute([$new_admin_id, $new_email, $hash, 'Super Admin']);
        
        echo "Successfully created admin user:\n";
        echo "  Email: $new_email\n";
        echo "  Password: $new_password\n";
    } else {
        echo "Found the following admin user(s):\n";
        foreach ($admins as $admin) {
            echo "  User ID: {$admin['user_id']}\n";
            echo "  Email: {$admin['email']}\n";
            echo "  Status: {$admin['status']}\n";
            echo "  Role: {$admin['admin_role']}\n";
            
            // Let's reset the password to 'admin123' to make it easy to log in
            $new_password = 'admin123';
            $hash = password_hash($new_password, PASSWORD_BCRYPT);
            
            $stmt_up = $pdo->prepare("UPDATE user SET password_hash = ?, status = 'Active' WHERE user_id = ?");
            $stmt_up->execute([$hash, $admin['user_id']]);
            
            echo "  Password reset successful! Set password to: $new_password\n\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
