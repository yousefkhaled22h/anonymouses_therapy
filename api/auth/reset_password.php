<?php
// api/auth/reset_password.php
require_once '../../includes/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$token = $_POST['token'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (empty($token) || empty($new_password) || empty($confirm_password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit();
}

if ($new_password !== $confirm_password) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
    exit();
}

if (strlen($new_password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long.']);
    exit();
}

try {
    // Verify Token again just to be safe before updating
    $stmt = $pdo->prepare("SELECT user_id FROM user WHERE reset_token = ? AND reset_expires > NOW() LIMIT 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired reset token.']);
        exit();
    }

    // Hash the new password securely
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update password AND clear the token so it cannot be used again
    $update_stmt = $pdo->prepare("UPDATE user SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE user_id = ?");
    $update_stmt->execute([$hashed_password, $user['user_id']]);

    echo json_encode(['success' => true, 'message' => 'Password has been successfully reset.']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()]);
}
?>