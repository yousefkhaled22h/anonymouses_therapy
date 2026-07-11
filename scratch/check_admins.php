<?php
require_once __DIR__ . '/../includes/db_connect.php';
try {
    $stmt = $pdo->query("SELECT * FROM admin");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Admins:\n";
    print_r($admins);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
