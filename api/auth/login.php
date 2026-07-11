<?php
// api/auth/login.php
require_once '../../includes/db_connect.php';

session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit();
}

try {
    // 1. Fetch User from User table filtering by email and role
    $role_locked = strtolower(trim($_POST['role_locked'] ?? ''));
    if (empty($role_locked)) {
        echo json_encode(['success' => false, 'message' => 'Role is required to log in']);
        exit();
    }

    $stmt = $pdo->prepare("SELECT user_id, password_hash, role FROM user WHERE email = ? AND role = ?");
    $stmt->execute([$email, $role_locked]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Check if user is suspended
        $stmt_suspended = $pdo->prepare("SELECT status FROM user WHERE user_id = ?");
        $stmt_suspended->execute([$user['user_id']]);
        $user_status = $stmt_suspended->fetchColumn();
        if ($user_status === 'Suspended') {
            echo json_encode(['success' => false, 'message' => 'Your account has been suspended by the administrator.']);
            exit();
        } elseif ($user_status === 'Deleted') {
            echo json_encode(['success' => false, 'message' => 'Account not found.']);
            exit();
        }

        $user_id = $user['user_id'];
        $role = $user['role']; // ('client', 'therapist', 'volunteer')

        $_SESSION['user_id'] = $user_id;
        $_SESSION['role'] = ucfirst($role);
        $_SESSION['email'] = $email;

        // Update last login and activity score
        $stmt_up = $pdo->prepare("UPDATE user SET last_login = NOW(), activity_score = activity_score + 5 WHERE user_id = ?");
        $stmt_up->execute([$user_id]);
        
        // Fetch profile data based on role
        $profile_data = null;
        $avatar = 'assets/images/default_avatar.png';
        $redirect = 'dashboard.php';

        if ($role === 'client') {
            $stmt = $pdo->prepare("SELECT * FROM client WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $profile_data = $stmt->fetch();

            if ($profile_data) {
                $_SESSION['client_id'] = $profile_data['client_id'];
                $_SESSION['anonymous_id'] = $profile_data['anonymous_id'];
                
                // Get Privacy Settings to determine display name
                $stmt = $pdo->prepare("SELECT privacy_settings FROM user WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $settings = json_decode($stmt->fetchColumn() ?? '{}', true);
                $mode = $settings['display_mode'] ?? 'Anonymous';
                
                $_SESSION['name'] = ($mode === 'Anonymous') ? ($profile_data['anonymous_id'] ?? 'Anonymous') : $profile_data['name'];
                
                if (!empty($profile_data['avatar_path'])) {
                    $avatar = $profile_data['avatar_path'];
                }
            }
            $redirect = 'dashboard.php';

        } elseif ($role === 'therapist') {
            $stmt = $pdo->prepare("SELECT * FROM therapist WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $profile_data = $stmt->fetch();

            if ($profile_data) {
                $_SESSION['therapist_id'] = $profile_data['therapist_id'];
                $_SESSION['name'] = trim(($profile_data['first_name'] ?? '') . ' ' . ($profile_data['last_name'] ?? ''));
                if (empty($_SESSION['name'])) $_SESSION['name'] = 'Therapist';
            }
            $redirect = 'therapist_profile.php';

        } elseif ($role === 'volunteer') {
            $stmt = $pdo->prepare("SELECT * FROM volunteer WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $profile_data = $stmt->fetch();

            if ($profile_data) {
                $_SESSION['volunteer_id'] = $profile_data['volunteer_id'];
                $_SESSION['name'] = $profile_data['first_name'] . ' ' . $profile_data['last_name'];
            }
            $redirect = 'volunteer/dashboard.php';
        } elseif ($role === 'admin') {
            $stmt = $pdo->prepare("SELECT admin_role FROM user WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $admin_role = $stmt->fetchColumn() ?: 'Super Admin';
            $_SESSION['admin_role'] = $admin_role;
            $_SESSION['name'] = 'Admin';
            $redirect = 'admin/index.php';
        }

        $_SESSION['avatar'] = $avatar;

        // Log successful login
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        logAdminAudit($pdo, $user_id, $email, 'Login', 'Successful login with role: ' . $role, $ip_address);

        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'redirect' => $redirect
        ]);

    } else {
        // Log failed login
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        logAdminAudit($pdo, null, $email, 'Failed Login', 'Invalid password or email not found.', $ip_address);

        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>