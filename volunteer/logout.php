<?php
session_start();
session_unset();
session_destroy();
// Clear session cookie completely
if(isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}
header("Location: signin.php");
exit();
?>
