<?php
// api/auth/forgot_password.php
require_once '../../includes/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$email = trim($_POST['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit();
}

try {
    // Check if user exists (ignoring role for this - they'll reset the single User account password)
    $stmt = $pdo->prepare("SELECT user_id, email FROM user WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Generate Token
        // Using bin2hex(random_bytes) creates a cryptographically secure random 64-character hex string
        $token = bin2hex(random_bytes(32));

        // Expiration time: 1 hour from now
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Save token to database
        $update_stmt = $pdo->prepare("UPDATE user SET reset_token = ?, reset_expires = ? WHERE email = ?");
        $update_stmt->execute([$token, $expires, $email]);

        // Construct reset link
        // In a real app, you would dynamically get the domain
        $reset_link = $base_url . "auth_handler.php?action=reset_password&token=" . $token;

        // Send Email
        $subject = 'Reset Your Safe Haven Password';
        $body = "
        <html>
        <head>
          <title>Password Reset Request</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
          <h2>Password Reset Request</h2>
          <p>We received a request to reset the password for your Safe Haven account associated with this email address.</p>
          <p>If you made this request, click the button below to securely create a new password. This link will expire in 1 hour.</p>
          <br>
          <a href='{$reset_link}' style='background: #2c3e50; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;'>Reset My Password</a>
          <br><br>
          <p>If you did not request a password reset, you can safely ignore this email.</p>
          <br>
          <p>Best Regards,<br>The Safe Haven Team</p>
        </body>
        </html>
        ";

        $from_domain = $domainName ?? 'safehaven.com';
        if (strpos($from_domain, ':') !== false) {
            $from_domain = explode(':', $from_domain)[0];
        }
        if (in_array($from_domain, ['localhost', '127.0.0.1', '::1'])) {
            $from_domain = 'safehaven.com';
        }
        $from_email = 'no-reply@' . $from_domain;

        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Safe Haven <" . $from_email . ">" . "\r\n";
        $headers .= "Reply-To: " . $from_email . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

        // Try to send email
        $mail_sent = @mail($email, $subject, $body, $headers);

        // Note: Even if mail fails locally, we say "success" so we don't leak "this email is registered" to attackers.
        // However, for testing purposes locally on XAMPP, we might want to log the link so the dev can click it.
        // I will write the link to a local debug file if the mail fails so you can test it without an SMTP server.
        if (!$mail_sent) {
            file_put_contents('../../test_debug_reset_link.txt', "LATEST RESET LINK GOES HERE:\n" . $reset_link . "\n\n(This file was created because your XAMPP could not send the real email via SMTP)");
        }
    }

    // Always return success to prevent email enumeration attacks (security best practice)
    echo json_encode(['success' => true, 'message' => 'If an account matches that email, a reset link has been sent.']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()]);
}
?>