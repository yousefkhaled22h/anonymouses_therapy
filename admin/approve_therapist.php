<?php
// admin_approve_therapist.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db_connect.php';
require_once '../includes/i18n.php';
ob_start('translate_html_buffer');

// In a real application, we would check if $_SESSION['role'] === 'Admin' here.
// For demonstration, we allow access to process approvals.

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';
$msg = '';

if ($action === 'approve' && !empty($id)) {
    try {
        // 1. Update therapist status
        $stmt = $pdo->prepare("UPDATE therapist SET verified = 1 WHERE therapist_id = ?");
        $stmt->execute([$id]);

        // Update the verification record if it exists
        $stmtVer = $pdo->prepare("UPDATE therapist_verification SET verification_status = 'Approved', reviewed_at = CURRENT_TIMESTAMP WHERE therapist_id = ?");
        $stmtVer->execute([$id]);

        // 2. Fetch therapist details to simulate sending an email
        $stmt2 = $pdo->prepare("SELECT u.email, t.first_name FROM therapist t JOIN user u ON t.user_id = u.user_id WHERE t.therapist_id = ?");
        $stmt2->execute([$id]);
        $therapist = $stmt2->fetch();

        if ($therapist) {
            $email = $therapist['email'];
            $name = $therapist['first_name'];

            // Set up real email parameters
            $subject = 'Your Safe Haven Therapist Account is Approved!';
            $body = "
            <html>
            <head>
              <title>Account Approved</title>
            </head>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
              <h2>Welcome to Safe Haven, {$name}!</h2>
              <p>Great news! Our admin team has reviewed your application and verified your credentials.</p>
              <p>Your therapist account is now <strong>fully approved and active</strong>.</p>
              <p>You can now log in to your dashboard to set up your availability and start accepting clients.</p>
              <br>
              <a href='{$base_url}login.php?role=therapist' style='background: #337AB7; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Login to Dashboard</a>
              <br><br>
              <p>Best Regards,<br>The Safe Haven Team</p>
            </body>
            </html>
            ";

            // Headers for HTML email
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: admin@safehaven.local" . "\r\n";

            // Attempt to send real email
            $mail_sent = @mail($email, $subject, $body, $headers);

            if ($mail_sent) {
                $msg = "<div style='background:#d4edda; color:#155724; padding:15px; border-radius:5px; margin-bottom:20px;'>
                            <strong>Success!</strong> Therapist Approved and email successfully sent to {$email}.
                        </div>";
            } else {
                $msg = "<div style='background:#fff3cd; color:#856404; padding:15px; border-radius:5px; margin-bottom:20px;'>
                            <strong>Therapist Approved.</strong><br>
                            However, the email could not be sent to {$email}. <br>
                            <em>Note: To send real emails from Localhost (XAMPP), you must configure sendmail in your php.ini file to use an SMTP server like Gmail.</em>
                        </div>";
            }
        }
    } catch (Exception $e) {
        $msg = "<div style='color:red;'>Error updating database: " . $e->getMessage() . "</div>";
    }
}

// Fetch all pending therapists
try {
    $stmt = $pdo->query("
        SELECT t.therapist_id, t.first_name, t.last_name, u.email, t.created_at, tv.license_file_path
        FROM therapist t 
        JOIN user u ON t.user_id = u.user_id 
        LEFT JOIN therapist_verification tv ON t.therapist_id = tv.therapist_id
        WHERE t.verified = 0 OR t.verified IS NULL
    ");
    $pending = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $pending = [];
    $msg = "<div style='color:red;'>Error fetching data: " . $e->getMessage() . "</div>";
}

?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">

<head>
    <meta charset="UTF-8">
    <title>Admin - Approve Therapists</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f9;
            padding: 40px;
            color: #333;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        h1 {
            margin-top: 0;
            color: #2c3e50;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }

        tr:hover {
            background-color: #f1f3f5;
        }

        .btn-approve {
            background: #28a745;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 0.9em;
            transition: 0.2s;
        }

        .btn-approve:hover {
            background: #218838;
        }

        .btn-view-file {
            background: #e9ecef;
            color: #495057;
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border: 1px solid #ced4da;
            transition: 0.2s;
        }

        .btn-view-file:hover {
            background: #dee2e6;
            color: #212529;
            border-color: #adb5bd;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
    </style>
</head>

<body>

    <div class="container">
        <h1>Pending Therapist Approvals</h1>
        <p>Review and approve new therapist applications below.</p>

        <?php echo $msg; ?>

        <?php if (count($pending) > 0): ?>
            <table class="table table-responsive" >
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Date Applied</th>
                        <th>Verification File</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending as $t): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($t['first_name'] . ' ' . $t['last_name']); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($t['email']); ?>
                            </td>
                            <td>
                                <?php echo date('M d, Y', strtotime($t['created_at'])); ?>
                            </td>
                            <td>
                                <?php if (!empty($t['license_file_path'])): ?>
                                    <a href="../<?php echo htmlspecialchars($t['license_file_path']); ?>" target="_blank" class="btn-view-file">
                                        <i class="fas fa-file-pdf"></i> View License
                                    </a>
                                <?php else: ?>
                                    <span style="color: #999;">No file uploaded</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="?action=approve&id=<?php echo urlencode($t['therapist_id']); ?>" class="btn-approve"
                                    onclick="return confirm('Are you sure you want to approve this therapist?');">Approve
                                    Account</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <h3>No pending approvals</h3>
                <p>All therapist accounts are currently verified.</p>
            </div>
        <?php endif; ?>

        <div style="margin-top: 30px;">
            <a href="index.php" style="color: #6c757d; text-decoration: none;">&larr; Back to Admin Dashboard</a>
        </div>
    </div>

</body>

</html>