<?php
// api/auth/logout.php
session_start();
require_once '../../includes/db_connect.php';

if (isset($_SESSION['user_id'])) {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    logAdminAudit($pdo, $_SESSION['user_id'], $_SESSION['email'] ?? null, 'Logout', 'User logged out.', $ip_address);
}

$redirect_url = '../../index.php';
if (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin') {
    $redirect_url = '../../admin/login.php';
}

session_destroy();
header("Location: " . $redirect_url);
exit();
?>