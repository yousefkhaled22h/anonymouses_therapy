<?php
// auth_handler.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/db_connect.php';

$action = $_GET['action'] ?? 'role_selection';

// If user is already logged in, redirect home (for forgot/reset password)
if (isset($_SESSION['user_id']) && $action !== 'role_selection') {
    header("Location: index.php");
    exit();
}

if ($action === 'forgot_password') {
    $body_class = 'login-page';
    require_once __DIR__ . '/includes/header.php';
    ?>
    <style>
        .auth-wrapper {
            min-height: calc(100vh - 80px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            background: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%);
        }

        .auth-container {
            width: 100%;
            max-width: 450px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .auth-header h2 {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 10px;
            font-family: var(--font-heading);
        }

        .auth-header p {
            color: #7f8c8d;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #34495e;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
            background: #fdfdfd;
        }

        .form-control:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .btn-primary {
            width: 100%;
            padding: 14px;
            background: #2c3e50;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            background: #1a252f;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .auth-links {
            text-align: center;
            margin-top: 25px;
        }

        .auth-links a {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .auth-links a:hover {
            text-decoration: underline;
        }

        .message-box {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: none;
            text-align: center;
            font-weight: 500;
        }

        .message-box.success {
            display: block;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message-box.error {
            display: block;
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>

    <div class="auth-wrapper">
        <div class="auth-container animate-up">
            <div class="auth-header">
                <h2>Reset Password</h2>
                <p>Enter your email below and we'll send you a secure link to reset your password.</p>
            </div>

            <div id="statusMessage" class="message-box"></div>

            <form id="forgotPasswordForm">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" required
                        placeholder="name@example.com">
                </div>

                <button type="submit" class="btn-primary" id="submitBtn">Send Reset Link</button>
            </form>

            <div class="auth-links">
                <a href="login.php">&larr; Back to Login</a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('forgotPasswordForm').addEventListener('submit', async function (e) {
            e.preventDefault();

            const btn = document.getElementById('submitBtn');
            const msgBox = document.getElementById('statusMessage');
            const email = document.getElementById('email').value;

            btn.textContent = 'Sending...';
            btn.disabled = true;
            msgBox.className = 'message-box'; // reset
            msgBox.innerHTML = '';

            try {
                const formData = new FormData();
                formData.append('email', email);

                const response = await fetch('api/auth/forgot_password.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    msgBox.className = 'message-box success';
                    msgBox.innerHTML = "Success! Please check your email for the reset link.";
                    document.getElementById('forgotPasswordForm').reset();
                } else {
                    msgBox.className = 'message-box error';
                    msgBox.innerHTML = data.message || "Error sending reset link.";
                }
            } catch (err) {
                msgBox.className = 'message-box error';
                msgBox.innerHTML = "A network error occurred. Please try again.";
            } finally {
                btn.textContent = 'Send Reset Link';
                btn.disabled = false;
            }
        });
    </script>
    <?php
    require_once __DIR__ . '/includes/footer.php';

} elseif ($action === 'reset_password') {
    $body_class = 'login-page';
    require_once __DIR__ . '/includes/header.php';

    $token = $_GET['token'] ?? '';
    $is_valid_token = false;

    // Basic validation to check if token exists and hasn't expired
    if (!empty($token)) {
        try {
            $stmt = $pdo->prepare("SELECT user_id FROM user WHERE reset_token = ? AND reset_expires > NOW()");
            $stmt->execute([$token]);
            if ($stmt->fetch()) {
                $is_valid_token = true;
            }
        } catch (PDOException $e) {
            // Handle silently
        }
    }
    ?>
    <style>
        .auth-wrapper {
            min-height: calc(100vh - 80px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            background: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%);
        }

        .auth-container {
            width: 100%;
            max-width: 450px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .auth-header h2 {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .auth-header p {
            color: #7f8c8d;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #34495e;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
            background: #fdfdfd;
        }

        .form-control:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .btn-primary {
            width: 100%;
            padding: 14px;
            background: #2c3e50;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            background: #1a252f;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .message-box {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: none;
            text-align: center;
            font-weight: 500;
        }

        .message-box.success {
            display: block;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message-box.error {
            display: block;
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>

    <div class="auth-wrapper">
        <div class="auth-container animate-up">
            <?php if (!$is_valid_token): ?>
                <div class="auth-header">
                    <h2 style="color: #e74c3c;">Invalid Token</h2>
                    <p>This password reset link is invalid or has expired.</p>
                </div>
                <div style="text-align:center;">
                    <a href="auth_handler.php?action=forgot_password" style="color: #3498db; text-decoration: none; font-weight: bold;">Request a new link</a>
                </div>
            <?php else: ?>
                <div class="auth-header">
                    <h2>Create New Password</h2>
                    <p>Please enter your new password below.</p>
                </div>

                <div id="statusMessage" class="message-box"></div>

                <form id="resetPasswordForm">
                    <input class="form-control" type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" required minlength="8">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="8">
                    </div>

                    <button type="submit" class="btn-primary" id="submitBtn">Reset Password</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        <?php if ($is_valid_token): ?>
            document.getElementById('resetPasswordForm').addEventListener('submit', async function (e) {
                e.preventDefault();

                const btn = document.getElementById('submitBtn');
                const msgBox = document.getElementById('statusMessage');
                const newPass = document.getElementById('new_password').value;
                const confPass = document.getElementById('confirm_password').value;

                if (newPass !== confPass) {
                    msgBox.className = 'message-box error';
                    msgBox.innerHTML = "Passwords do not match.";
                    return;
                }

                btn.textContent = 'Updating...';
                btn.disabled = true;
                msgBox.className = 'message-box'; // reset
                msgBox.innerHTML = '';

                try {
                    const formData = new FormData(this);

                    const response = await fetch('api/auth/reset_password.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        msgBox.className = 'message-box success';
                        msgBox.innerHTML = "Password updated successfully!";
                        document.getElementById('resetPasswordForm').style.display = 'none';
                        setTimeout(() => {
                            window.location.href = 'login.php';
                        }, 2000);
                    } else {
                        msgBox.className = 'message-box error';
                        msgBox.innerHTML = data.message || "Error updating password.";
                        btn.disabled = false;
                        btn.textContent = 'Reset Password';
                    }
                } catch (err) {
                    msgBox.className = 'message-box error';
                    msgBox.innerHTML = "A network error occurred. Please try again.";
                    btn.disabled = false;
                    btn.textContent = 'Reset Password';
                }
            });
        <?php endif; ?>
    </script>
    <?php
    require_once __DIR__ . '/includes/footer.php';

} else {
    // Default: role_selection
    $body_class = 'role-selection-page';
    require_once __DIR__ . '/includes/header.php';
    ?>
    <style>
        .role-selection-wrapper {
            min-height: 80vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            text-align: center;
        }

        .role-header {
            margin-bottom: 50px;
        }

        .role-header h1 {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #3E2723;
            font-family: 'Lora', serif;
        }

        .role-header p {
            font-size: 1.1rem;
            color: #6D4C41;
            max-width: 600px;
            margin: 0 auto;
        }

        .role-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
            width: 100%;
            max-width: 1100px;
            margin-bottom: 40px;
        }

        .role-card {
            border-radius: 24px;
            padding: 40px 30px;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            cursor: pointer;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0,0,0,0.03);
        }

        .role-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }

        /* Abstract Background Waves */
        .role-card::before, .role-card::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            z-index: 0;
            opacity: 0.6;
        }
        .role-card::before {
            width: 200px; height: 200px;
            top: -50px; left: -50px;
        }
        .role-card::after {
            width: 250px; height: 250px;
            bottom: -80px; right: -50px;
        }

        .role-card.client { background-color: #FCF9F5; }
        .role-card.client::before { background-color: rgba(235, 225, 215, 0.4); }
        .role-card.client::after { background-color: rgba(235, 225, 215, 0.4); }

        .role-card.therapist { background-color: #F8F9FC; }
        .role-card.therapist::before { background-color: rgba(220, 230, 245, 0.4); }
        .role-card.therapist::after { background-color: rgba(220, 230, 245, 0.4); }

        .role-card.volunteer { background-color: #F6FAF6; }
        .role-card.volunteer::before { background-color: rgba(220, 240, 220, 0.4); }
        .role-card.volunteer::after { background-color: rgba(220, 240, 220, 0.4); }

        .role-card-content {
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
        }

        .role-card .icon-circle {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
            position: relative;
        }

        /* The leaf decoration on the circle */
        .role-card .leaf-decor {
            position: absolute;
            bottom: -5px;
            right: -10px;
            width: 50px;
            height: 50px;
        }

        .role-card.client .icon-circle { background-color: #F2E8DF; }
        .role-card.client .icon-circle svg { color: #8A5A44; }
        
        .role-card.therapist .icon-circle { background-color: #EBF0F9; }
        .role-card.therapist .icon-circle svg { color: #2B4C7E; }

        .role-card.volunteer .icon-circle { background-color: #EAF3E9; }
        .role-card.volunteer .icon-circle svg { color: #3A7340; }

        .role-card h2 {
            font-size: 2rem;
            margin-bottom: 20px;
            font-family: 'Lora', serif;
            font-weight: 700;
        }
        
        .role-card.client h2 { color: #3E2723; }
        .role-card.therapist h2 { color: #1A365D; }
        .role-card.volunteer h2 { color: #1B4332; }

        .role-card p {
            color: #555;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 30px;
            padding: 0 10px;
            height: 50px; /* fixed height to align buttons */
        }

        .role-card .btn-role {
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
            text-decoration: none;
            transition: all 0.2s;
        }

        .role-card.client .btn-role { background-color: #C1A28A; }
        .role-card.client .btn-role:hover { background-color: #AD8C73; }

        .role-card.therapist .btn-role { background-color: #34609E; }
        .role-card.therapist .btn-role:hover { background-color: #294D80; }

        .role-card.volunteer .btn-role { background-color: #849C7D; }
        .role-card.volunteer .btn-role:hover { background-color: #6C8266; }

        .footer-note {
            font-size: 0.95rem;
            color: #8D6E63;
            font-style: italic;
        }

        .icon-circle svg.main-icon {
            width: 45px;
            height: 45px;
        }
    </style>

    <div class="container">
        <div class="role-selection-wrapper">
            <div class="role-header">
                <div class="logo-container" style="margin-bottom: 20px;">
                    <img class="img-fluid" src="assets/images/logo.png" alt="Safe Haven Logo" style="width: 100px; height: auto;">
                </div>
                <h1>Welcome to Safe Haven</h1>
                <p>Choose your journey with anonymous and professional support</p>
            </div>

            <div class="role-cards">
                <!-- Client Card -->
                <a href="login.php?role=client" class="role-card client">
                    <div class="role-card-content">
                        <div class="icon-circle">
                            <svg class="main-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="8" r="4"></circle>
                                <path d="M20 21a8 8 0 0 0-16 0"></path>
                            </svg>
                            <svg class="leaf-decor" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22c-3-3-6-8-6-12 0-3 2-5 5-5s5 2 5 5c0 4-3 9-6 12z"></path>
                                <path d="M12 22V10"></path>
                                <path d="M12 16c3-1 5-3 5-6"></path>
                                <path d="M12 18c-2-1-4-2-4-5"></path>
                            </svg>
                        </div>
                        <h2>Client</h2>
                        <p>Seeking mental health support and guidance.</p>
                        <div class="btn-role">
                            <span style="flex-grow:1; text-align:center;">I'm a Client</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </div>
                    </div>
                </a>

                <!-- Therapist Card -->
                <a href="login.php?role=therapist" class="role-card therapist">
                    <div class="role-card-content">
                        <div class="icon-circle">
                            <svg class="main-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="7" r="3"></circle>
                                <path d="M8 11h8v5H8z"></path>
                                <path d="M6 14v4"></path>
                                <path d="M18 14v4"></path>
                                <path d="M8 16h8"></path>
                            </svg>
                            <svg class="leaf-decor" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22c-3-3-6-8-6-12 0-3 2-5 5-5s5 2 5 5c0 4-3 9-6 12z"></path>
                                <path d="M12 22V10"></path>
                                <path d="M12 16c3-1 5-3 5-6"></path>
                                <path d="M12 18c-2-1-4-2-4-5"></path>
                            </svg>
                        </div>
                        <h2>Therapist</h2>
                        <p>Licensed professional providing therapy.</p>
                        <div class="btn-role">
                            <span style="flex-grow:1; text-align:center;">I'm a Therapist</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </div>
                    </div>
                </a>

                <!-- Volunteer Card -->
                <a href="volunteer/signin.php" class="role-card volunteer">
                    <div class="role-card-content">
                        <div class="icon-circle">
                            <svg class="main-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"></path>
                                <path d="M9 13l2 2 4-4" stroke-width="2"></path>
                            </svg>
                            <svg class="leaf-decor" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22c-3-3-6-8-6-12 0-3 2-5 5-5s5 2 5 5c0 4-3 9-6 12z"></path>
                                <path d="M12 22V10"></path>
                                <path d="M12 16c3-1 5-3 5-6"></path>
                                <path d="M12 18c-2-1-4-2-4-5"></path>
                            </svg>
                        </div>
                        <h2>Volunteer</h2>
                        <p>Community support helper and advocate.</p>
                        <div class="btn-role">
                            <span style="flex-grow:1; text-align:center;">I'm a Volunteer</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </div>
                    </div>
                </a>
            </div>

            <div style="margin-top: 30px;">
                <p class="footer-note">All roles maintain complete anonymity and privacy</p>
            </div>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/includes/footer.php';
}
?>
