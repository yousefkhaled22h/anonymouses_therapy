<?php
session_start();
$message = '';
$isError = false;
$emailValue = '';

// Check if there is an error passed via URL (from other pages redirecting here)
if (isset($_GET['error'])) {
    $isError = true;
    $message = "Please sign in to access this page.";
}

// Show success message after registration
$isSuccess = false;
if (isset($_GET['registered'])) {
    $isSuccess = true;
    $message = "Account created successfully! Please sign in.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        require_once '../includes/db_connect.php';

        if (isset($_POST['email'])) {
            $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
            $emailValue = $email; // retain the email for the form
        } else {
            $email = '';
        }

        $pass = isset($_POST['password']) ? $_POST['password'] : '';

        if (empty($email) || empty($pass)) {
            throw new Exception("Please enter both email and password.");
        }

        $stmt = $pdo->prepare("SELECT u.user_id as id, v.volunteer_id, v.first_name, v.last_name, u.password_hash FROM user u JOIN volunteer v ON u.user_id = v.user_id WHERE u.email = ? AND u.role = 'volunteer'");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($pass, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['volunteer_id'] = $user['volunteer_id'];
            $_SESSION['name'] = trim($user['first_name'] . ' ' . $user['last_name']);
            $_SESSION['role'] = 'Volunteer';

            header("Location: dashboard.php");
            exit();
        } else {
            $isError = true;
            $message = "Check email or password";
        }

    } catch (PDOException $e) {
        $isError = true;
        $message = "Database Error: " . htmlspecialchars($e->getMessage()) . "<br><strong>Did you run the database.sql script?</strong>";
    } catch (Exception $e) {
        $isError = true;
        $message = "Error: " . htmlspecialchars($e->getMessage());
    }
}

$path_prefix = '../';
$user_role = 'Volunteer'; // Force green theme
$current_role = 'volunteer';
$body_class = 'theme-volunteer role-volunteer';
require_once $path_prefix . 'includes/header.php';
?>
<link rel="stylesheet" href="signin.css">

<style>
    /* ── Volunteer Signin: green navbar & footer ── */
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
        max-width: 420px;
        width: 100%;
        transition: transform 0.2s ease, color 0.2s;
    }
    .vol-back-link:hover { transform: translateX(-4px); color: #1B4332; }
    .vol-back-link svg { width: 18px; height: 18px; flex-shrink: 0; }

    /* No background on logo inside the form card */
    .logo-box { background: transparent !important; box-shadow: none; }
</style>

<main class="auth-page-wrapper" style="flex-direction: column;">

    <a href="../auth_handler.php?action=role_selection" class="vol-back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        Back to Role Selection
    </a>

    <div class="auth-card-container login-card">
        
        <!-- Logo -->
        <div class="logo-container" style="text-align: center; margin-bottom: 25px;">
            <a href="../index.php">
                <img src="../assets/images/logo.png" alt="Safe Haven Logo" style="width: 80px; height: auto;">
            </a>
        </div>

        <!-- Header -->
        <h1 class="title">Welcome Back</h1>
        <p class="subtitle">Sign in to your account</p>

        <!-- Error Message Container (Rendered by PHP if $isError is true) -->
        <?php if ($isError): ?>
        <div id="loginAlert" style="background-color: #fee2e2; color: #dc2626; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-size: 14.5px; font-weight: 500; border: 1px solid #fca5a5;">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Success Message (shown after registration) -->
        <?php if ($isSuccess): ?>
        <div style="background-color: #dcfce7; color: #16a34a; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-size: 14.5px; font-weight: 500; border: 1px solid #86efac;">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <!-- Form submits to itself -->
        <form action="signin.php" method="POST" id="loginForm">
            
            <!-- Email Input -->
            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <div class="input-wrapper">
                    <span class="input-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                    </span>
                    <input type="email" id="email" name="email" class="input-field input-email" placeholder="your@email.com" value="<?php echo htmlspecialchars($emailValue); ?>" required>
                </div>
            </div>

            <!-- Password Input -->
            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <label class="form-label" for="password" style="margin-bottom: 0;">Password</label>
                    <a href="../auth_handler.php?action=forgot_password" style="font-size: 0.85rem; color: #2D6A4F; font-weight: 600; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#1B4332'" onmouseout="this.style.color='#2D6A4F'">Forgot Password?</a>
                </div>
                <div class="input-wrapper">
                    <span class="input-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0110 0v4"></path>
                        </svg>
                    </span>
                    <input type="password" id="password" name="password" class="input-field input-password" placeholder="Enter password" required>
                    <!-- Toggles password via script.js -->
                    <button type="button" class="toggle-password" id="togglePassword" aria-label="Toggle password visibility">
                        <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="submit-btn" id="loginBtn">Sign In</button>
        </form>

        <!-- Redirection Link -->
        <p class="signup-text">
            Don't have an account? <a href="signup.php">Sign up</a>
        </p>
    </div>
</main>

    <!-- Integrates existing toggle functionality -->
    <script src="signin.js"></script>
<?php include $path_prefix . 'includes/footer.php'; ?>

