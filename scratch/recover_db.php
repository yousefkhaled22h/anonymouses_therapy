<?php
// recovery.php - Restores the database from db/database_schema.sql

$host = 'localhost';
$db   = 'anonymous-therapy';
$user = 'root';
$pass = '';

try {
    // 1. Connect without DB
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Create Database
    echo "Performing clean restore (dropping old database if exists)...\n";
    $pdo->exec("DROP DATABASE IF EXISTS `$db`");
    $pdo->exec("CREATE DATABASE `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database created successfully.\n";

    // 3. Import Schema
    $mysqlPath = 'C:\\xampp\\mysql\\bin\\mysql.exe';
    $schemaPath = 'db/database_schema.sql';

    if (!file_exists($schemaPath)) {
        die("Error: Schema file not found at $schemaPath\n");
    }

    echo "Importing schema from $schemaPath (ignoring foreign key checks)...\n";
    // Construct the command
    // Using --init-command to disable FK checks
    $command = "\"$mysqlPath\" -u $user " . ($pass ? "-p$pass " : "") . "--init-command=\"SET FOREIGN_KEY_CHECKS=0;\" $db < \"$schemaPath\"";
    
    // Execute via shell
    $output = [];
    $returnVar = 0;
    exec($command, $output, $returnVar);

    if ($returnVar === 0) {
        echo "Database restored successfully!\n";
    } else {
        echo "Error restoring database. Exit code: $returnVar\n";
        print_r($output);
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
