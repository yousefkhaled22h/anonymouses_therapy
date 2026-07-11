<?php
// volunteer/resource_library.php
// Redirects volunteer to the global resource library with green volunteer theme applied
session_start();
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'volunteer') {
    header("Location: signin.php");
    exit();
}
// Simply redirect to the volunteer dashboard resource page
header("Location: dashboard.php?view=resources");
exit();
?>
