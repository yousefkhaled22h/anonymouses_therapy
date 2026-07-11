<?php
require_once 'includes/db_connect.php';

try {
    $pdo->exec("ALTER TABLE private_session_message MODIFY paid_session_id VARCHAR(50) NOT NULL");
    $pdo->exec("ALTER TABLE private_session_message MODIFY user_id VARCHAR(50) NOT NULL");
    echo "Fixed private_session_message schema.\n";
} catch (Exception $e) {
    echo "Error fixing private_session_message: " . $e->getMessage() . "\n";
}
?>
