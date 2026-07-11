<?php
// volunteer/community_qna.php
// Redirects volunteer to the global Q&A (community.php) with green volunteer theme applied
session_start();
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'volunteer') {
    header("Location: signin.php");
    exit();
}
// Simply redirect to the volunteer dashboard community page
header("Location: dashboard.php?view=community");
exit();
?>
