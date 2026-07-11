<?php
// volunteer/home.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['role'] = 'Volunteer';
header("Location: ../index.php");
exit();
?>
