<?php
require_once __DIR__ . '/../includes/db_connect.php';

$tables = [
    'group_sessions' => 'admin_id',
    'report' => 'admin_id',
    'resource' => 'added_by_admin_id',
    'therapist_verification' => 'admin_id'
];

foreach ($tables as $table => $col) {
    try {
        $stmt1 = $pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE `$col` = 'A_1'");
        $stmt1->execute();
        $cntA = $stmt1->fetchColumn();

        $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE `$col` = 'U_ADMIN_1'");
        $stmt2->execute();
        $cntU = $stmt2->fetchColumn();

        echo "$table.$col: A_1 count = $cntA, U_ADMIN_1 count = $cntU\n";
    } catch (Exception $e) {
        echo "Error checking $table: " . $e->getMessage() . "\n";
    }
}
