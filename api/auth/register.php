<?php
// api/auth/register.php
require_once '../../includes/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$role_input = $_POST['role'] ?? ''; // 'Client', 'Therapist', 'Volunteer'
$role = strtolower($role_input);
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($role) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and Password are required']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit();
}

$allowed_roles = ['client', 'therapist', 'volunteer'];
if (!in_array($role, $allowed_roles)) {
    echo json_encode(['success' => false, 'message' => 'Invalid role selected']);
    exit();
}

try {
    $pdo->beginTransaction();

    // Check if email exists for this specific role
    $stmt = $pdo->prepare("SELECT user_id FROM user WHERE email = ? AND role = ?");
    $stmt->execute([$email, $role]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Email already registered for this role']);
        exit();
    }

    // Generate USER ID (String)
    $user_id = 'usr_' . bin2hex(random_bytes(8));
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insert into User table
    $stmt = $pdo->prepare("INSERT INTO user (user_id, email, password_hash, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $email, $password_hash, $role]);

    $message = 'Registration successful!';

    if ($role === 'client') {
        $name = trim($_POST['name'] ?? '');
        $is_anonymous = isset($_POST['is_anonymous']) && $_POST['is_anonymous'] == '1';
        $client_id = 'cli_' . bin2hex(random_bytes(8));

        $anonymous_id = null;
        if ($is_anonymous) {
            $adjectives = ['Happy', 'Calm', 'Brave', 'Gentle', 'Kind', 'Wise', 'Bright', 'Impartial', 'Silent', 'Blue'];
            $nouns = ['River', 'Mountain', 'Sky', 'Star', 'Sugar', 'Leaf', 'Panda', 'Eagle', 'Ocean', 'Moon'];
            $anonymous_id = $adjectives[array_rand($adjectives)] . $nouns[array_rand($nouns)] . rand(1000, 9999);
        }

        $stmt = $pdo->prepare("INSERT INTO client (client_id, user_id, name, anonymous_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$client_id, $user_id, $name, $anonymous_id]);

        // Initialize Privacy Settings in User table
        $privacy_settings = json_encode(['display_mode' => $is_anonymous ? 'Anonymous' : 'Real']);
        $stmt = $pdo->prepare("UPDATE user SET privacy_settings = ? WHERE user_id = ?");
        $stmt->execute([$privacy_settings, $user_id]);

        if ($is_anonymous) {
            $message = "Welcome! Your anonymous ID is: $anonymous_id";
        } else {
            $message = "Welcome, $name!";
        }

        // Auto-login: set session so client lands on onboarding
        session_start();
        $_SESSION['user_id']    = $user_id;
        $_SESSION['role']       = 'Client';
        $_SESSION['name']       = $is_anonymous ? $anonymous_id : $name;
        $_SESSION['client_id']  = $client_id;
        if ($is_anonymous) $_SESSION['anonymous_id'] = $anonymous_id;

    } elseif ($role === 'therapist') {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $specialization = $_POST['specialization'] ?? '';
        $therapist_id = 'the_' . bin2hex(random_bytes(8));

        $stmt = $pdo->prepare("INSERT INTO therapist (therapist_id, user_id, first_name, last_name, specialties) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$therapist_id, $user_id, $first_name, $last_name, $specialization]);

        // Handle License Upload
        if (isset($_FILES['license']) && $_FILES['license']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['license']['name'], PATHINFO_EXTENSION));
            $filename = 'license_' . $therapist_id . '_' . time() . '.' . $ext;
            $upload_dir = '../../assets/uploads/licenses/';

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            if (move_uploaded_file($_FILES['license']['tmp_name'], $upload_dir . $filename)) {
                $license_path = 'assets/uploads/licenses/' . $filename;
                $verification_id = 'ver_' . bin2hex(random_bytes(8));

                $stmt = $pdo->prepare("INSERT INTO therapist_verification (verification_id, therapist_id, license_file_path, verification_status) VALUES (?, ?, ?, 'Pending')");
                $stmt->execute([$verification_id, $therapist_id, $license_path]);
            }
        }
        $message = "Application submitted! Our team will review your license and get back to you soon.";

        // Auto-login: set session so therapist lands on profile
        session_start();
        $_SESSION['user_id']      = $user_id;
        $_SESSION['role']         = 'Therapist';
        $_SESSION['name']         = trim($first_name . ' ' . $last_name);
        if (empty($_SESSION['name'])) $_SESSION['name'] = 'Therapist';
        $_SESSION['therapist_id'] = $therapist_id;

    } elseif ($role === 'volunteer') {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $volunteer_id = 'vol_' . bin2hex(random_bytes(8));

        $stmt = $pdo->prepare("INSERT INTO volunteer (volunteer_id, user_id, first_name, last_name, verification_status) VALUES (?, ?, ?, ?, 'Pending')");
        $stmt->execute([$volunteer_id, $user_id, $first_name, $last_name]);

        $message = "Welcome to the community, $first_name! Your volunteer account is ready.";
    }

    $pdo->commit();
    $redirect = ($role === 'client') ? 'onboarding.php' : (($role === 'volunteer') ? 'volunteer/signin.php?registered=1' : 'therapist_profile.php');
    echo json_encode(['success' => true, 'message' => $message, 'redirect' => $redirect]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>