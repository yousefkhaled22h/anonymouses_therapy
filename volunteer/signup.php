<?php
session_start();

$uploadDir = 'uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$errors    = [];
$firstName = '';
$lastName  = '';
$email     = '';
$location  = '';
$languages = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        require_once '../includes/db_connect.php';

        // ── Sanitize inputs ──────────────────────────────────────────────────
        $firstName = htmlspecialchars(trim($_POST['first_name'] ?? ''));
        $lastName  = htmlspecialchars(trim($_POST['last_name']  ?? ''));
        $email     = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $location  = htmlspecialchars(trim($_POST['location']  ?? ''));
        $languages = htmlspecialchars(trim($_POST['languages'] ?? ''));
        $pass      = $_POST['password'] ?? '';

        // ── Required field validation ────────────────────────────────────────
        if (empty($firstName) || empty($lastName) || empty($email) || empty($pass)) {
            $errors[] = "All fields are required.";
        }

        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        }

        // Flat minimum password length — no complexity rules
        if (strlen($pass) < 6) {
            $errors[] = "Password must be at least 6 characters long.";
        }

        // ── Certificate upload — completely optional ─────────────────────────
        $targetPath = '';
        if (isset($_FILES['certificate']) && $_FILES['certificate']['error'] === UPLOAD_ERR_OK) {
            $file      = $_FILES['certificate'];
            $fileName  = time() . '_' . basename($file['name']);
            $targetPath = $uploadDir . $fileName;
            $fileType  = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));

            $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf'];
            if (!in_array($fileType, $allowedTypes)) {
                $errors[] = "Only JPG, JPEG, PNG & PDF files are allowed.";
            }
            if ($file['size'] > 5000000) {
                $errors[] = "File is too large. Maximum 5 MB allowed.";
            }
        }

        // ── Duplicate email check ────────────────────────────────────────────
        if (empty($errors)) {
            $stmtCheck = $pdo->prepare(
                "SELECT u.user_id, v.volunteer_id
                 FROM user u
                 LEFT JOIN volunteer v ON u.user_id = v.user_id
                 WHERE u.email = ? AND u.role = 'volunteer'"
            );
            $stmtCheck->execute([$email]);
            $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($existing && $existing['volunteer_id']) {
                // Fully registered volunteer — genuine duplicate
                $errors[] = "An account with this email already exists. Please <a href='signin.php'>sign in</a>.";
            } elseif ($existing && !$existing['volunteer_id']) {
                // Orphaned user row from a previous failed signup — clean it up
                $pdo->prepare("DELETE FROM user WHERE user_id = ?")->execute([$existing['user_id']]);
            }
        }

        // ── Move file (only if one was actually provided) ────────────────────
        if (empty($errors)) {
            $fileReady = true;
            if (!empty($targetPath)) {
                $fileReady = move_uploaded_file($file['tmp_name'], $targetPath);
                if (!$fileReady) {
                    $errors[] = "There was an error uploading your file. Please try again.";
                }
            }

            // ── Atomic DB insert ─────────────────────────────────────────────
            if ($fileReady) {
                $hashedPassword = password_hash($pass, PASSWORD_DEFAULT);
                $userId         = 'usr_' . bin2hex(random_bytes(8));
                $volunteerId    = 'vol_' . bin2hex(random_bytes(8));

                $pdo->beginTransaction();
                try {
                    // 1. Parent: user table
                    $stmtUser = $pdo->prepare(
                        "INSERT INTO user (user_id, email, password_hash, role)
                         VALUES (?, ?, ?, 'volunteer')"
                    );
                    $stmtUser->execute([$userId, $email, $hashedPassword]);

                    // 2. Child: volunteer table
                    $stmtVol = $pdo->prepare(
                        "INSERT INTO volunteer
                            (volunteer_id, user_id, first_name, last_name,
                             languages, location, certificates, verification_status)
                         VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')"
                    );
                    $stmtVol->execute([
                        $volunteerId, $userId,
                        $firstName, $lastName,
                        $languages, $location,
                        $targetPath
                    ]);

                    $pdo->commit();

                    // ── Auto-login: set session so volunteer lands on dashboard ──
                    $_SESSION['user_id']      = $userId;
                    $_SESSION['role']         = 'volunteer';
                    $_SESSION['name']         = trim($firstName . ' ' . $lastName);
                    $_SESSION['volunteer_id'] = $volunteerId;

                    header("Location: dashboard.php");
                    exit();

                } catch (Exception $txEx) {
                    $pdo->rollBack();
                    if (!empty($targetPath) && file_exists($targetPath)) {
                        unlink($targetPath);
                    }
                    $errors[] = "Registration failed: " . htmlspecialchars($txEx->getMessage());
                }
            }
        }

    } catch (PDOException $e) {
        $errors[] = "Database Error: " . htmlspecialchars($e->getMessage());
    } catch (Exception $e) {
        $errors[] = "Error: " . htmlspecialchars($e->getMessage());
    }
}
?>
<?php
$path_prefix = '../';
$user_role   = 'Volunteer';
$current_role = 'volunteer';
$body_class  = 'theme-volunteer role-volunteer';
require_once $path_prefix . 'includes/header.php';
?>
<link rel="stylesheet" href="signup.css">

<style>
    /* ── Volunteer Signup: green navbar & footer ── */
    #main-header {
        background-color: rgba(240, 247, 244, 0.85) !important;
        border-bottom: 1px solid rgba(45, 106, 79, 0.15) !important;
    }
    #main-header.scrolled {
        background-color: rgba(240, 247, 244, 0.95) !important;
    }
    #main-header .nav-links a:not(.btn-logout):not(.btn-get-started) {
        color: #1B4D3E !important;
    }
    #main-header .nav-links a:not(.btn-logout):not(.btn-get-started):hover {
        color: #1B4332 !important;
    }
    #main-header .logo span { color: #2D6A4F !important; }
    
    .btn-get-started, .btn-primary, .nav-links .btn-primary { 
        background: #2D6A4F !important; 
        background-image: none !important;
        background-color: #2D6A4F !important;
        border-color: #2D6A4F !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(45, 106, 79, 0.3) !important;
    }
    .btn-get-started:hover, .btn-primary:hover, .nav-links .btn-primary:hover {
        background: #1B4332 !important;
        background-color: #1B4332 !important;
        border-color: #1B4332 !important;
        box-shadow: 0 6px 16px rgba(27, 67, 50, 0.4) !important;
    }

    .minimal-footer {
        background: #EAF2EC !important;
        background-color: #EAF2EC !important;
        box-shadow: none !important;
        border-top: 1px solid rgba(45, 106, 79, 0.15) !important;
    }
    .minimal-footer p,
    .minimal-footer .footer-desc,
    .minimal-footer .footer-col-title,
    .minimal-footer .footer-bottom-minimal,
    .minimal-footer .footer-link-list a,
    .minimal-footer .footer-contact-list li,
    .minimal-footer .footer-contact-list li i {
        color: #2D6A4F !important;
    }
    .minimal-footer .footer-col-title {
        border-bottom: 2px solid rgba(45, 106, 79, 0.3) !important;
    }
    .minimal-footer .footer-link-list a:hover { color: #1B4332 !important; }
    .footer-divider { background-color: rgba(45, 106, 79, 0.15) !important; }

    /* Green background ONLY on footer logo */
    .minimal-footer .footer-brand-img {
        background: #2D6A4F;
        border-radius: 12px;
        padding: 8px;
        display: inline-flex;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }

    /* Back-to-role link above the card */
    .vol-back-link {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #2D6A4F;
        font-weight: 700;
        font-size: 0.92rem;
        text-decoration: none;
        margin-bottom: 18px;
        max-width: 450px;
        width: 100%;
        transition: transform 0.2s ease, color 0.2s;
    }
    .vol-back-link:hover { transform: translateX(-4px); color: #1B4332; }
    .vol-back-link svg { width: 18px; height: 18px; flex-shrink: 0; }

    /* Remove logo background in the form card */
    .auth-card-container .logo {
        background: transparent !important;
        border-radius: 0;
    }

    /* Remove any white background from navbar logo */
    #main-header .logo img,
    #main-header .logo a,
    #main-header .logo {
        background: transparent !important;
        box-shadow: none !important;
    }
</style>

<main class="auth-page-wrapper" style="flex-direction: column;">

    <a href="../auth_handler.php?action=role_selection" class="vol-back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        Back to Role Selection
    </a>

    <div class="auth-card-container">
        <div class="logo-container" style="text-align: center; margin-bottom: 20px;">
            <img src="../assets/images/logo.png" alt="Safe Haven Logo" style="width: 80px; height: auto;">
        </div>

        <h1 class="title">Become a Volunteer</h1>
        <p class="subtitle">Community Helper Portal</p>

        <?php if (!empty($errors)): ?>
        <div class="server-error-banner">
            <strong>Registration failed:</strong>
            <ul style="margin-top: 5px; padding-left: 20px;">
                <?php foreach ($errors as $err): ?>
                    <li><?php echo $err; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form action="signup.php" method="POST" enctype="multipart/form-data" id="registrationForm">

            <div class="form-row">
                <div class="form-group half">
                    <label for="firstName">First Name</label>
                    <input type="text" id="firstName" name="first_name" placeholder="First name"
                           value="<?php echo htmlspecialchars($firstName); ?>" required>
                </div>
                <div class="form-group half">
                    <label for="lastName">Last Name</label>
                    <input type="text" id="lastName" name="last_name" placeholder="Last name"
                           value="<?php echo htmlspecialchars($lastName); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <div class="input-wrapper">
                    <span class="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="20" height="16" x="2" y="4" rx="2" />
                            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                        </svg>
                    </span>
                    <input type="email" id="email" name="email" placeholder="your@email.com"
                           value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <span class="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                        </svg>
                    </span>
                    <input type="password" id="password" name="password"
                           placeholder="Enter password" required>
                    <button type="button" class="toggle-password" id="togglePassword"
                            aria-label="Toggle password visibility" title="Show password">
                        <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label for="certificate">Upload Certificate <span style="font-weight:400; color:#52796f;">(optional)</span></label>
                <div class="input-wrapper file-wrapper">
                    <input type="file" id="certificate" name="certificate" accept=".pdf,.jpg,.jpeg,.png">
                </div>
                <small class="hint">Optional — PDF, JPG, PNG accepted (Max 5MB)</small>
            </div>

            <button type="submit" class="submit-btn" id="submitBtn">Join Community</button>

        </form>

        <p class="login-link">
            Already have an account? <a href="signin.php">Sign in</a>
        </p>

        <div class="footer-box">
            <p>Help others on their mental wellness journey by providing peer support</p>
        </div>
    </div>

    <script src="signup.js"></script>

<?php include $path_prefix . 'includes/footer.php'; ?>
