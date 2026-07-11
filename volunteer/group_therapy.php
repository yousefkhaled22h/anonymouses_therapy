<?php
// volunteer/group_therapy.php
// Redirects volunteer to the global group sessions list with green volunteer theme applied
session_start();
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'volunteer') {
    header("Location: signin.php");
    exit();
}
// Simply redirect to the volunteer dashboard group sessions page
header("Location: dashboard.php?view=groups");
exit();
?>
