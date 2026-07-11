<?php
// admin/login.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/i18n.php';
ob_start('translate_html_buffer');

// If admin session is already active, redirect straight to the dashboard
if (isset($_SESSION['user_id']) && strtolower($_SESSION['role'] ?? '') === 'admin') {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Safe Haven | Admin Login Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --primary-glow: rgba(99, 102, 241, 0.35);
            --bg-dark: #070b13;
            --card-bg: rgba(15, 23, 42, 0.65);
            --text-primary: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: rgba(255, 255, 255, 0.08);
            --success: #10b981;
            --danger: #ef4444;
            --font-main: 'Plus Jakarta Sans', sans-serif;
            --font-heading: 'Outfit', sans-serif;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-primary);
            font-family: var(--font-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            overflow: hidden;
            position: relative;
        }

        /* Animated Glowing Mesh Background */
        .bg-glow-container {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
            pointer-events: none;
        }

        .glow-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(120px);
            opacity: 0.15;
            mix-blend-mode: screen;
            animation: floatOrb 25s infinite alternate ease-in-out;
        }

        .orb-1 {
            width: 500px;
            height: 500px;
            background: var(--primary);
            top: -10%;
            left: -10%;
            animation-duration: 20s;
        }

        .orb-2 {
            width: 600px;
            height: 600px;
            background: #ec4899;
            bottom: -15%;
            right: -10%;
            animation-duration: 28s;
            animation-delay: -5s;
        }

        .orb-3 {
            width: 400px;
            height: 400px;
            background: #3b82f6;
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation-duration: 24s;
            animation-delay: -10s;
        }

        @keyframes floatOrb {
            0% {
                transform: translate(0, 0) scale(1);
            }
            50% {
                transform: translate(80px, 50px) scale(1.1);
            }
            100% {
                transform: translate(-40px, -60px) scale(0.9);
            }
        }

        /* Portal Container and Card */
        .portal-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 460px;
            animation: fadeInCard 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes fadeInCard {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .back-to-site {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 24px;
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-color);
            border-radius: 30px;
            transition: var(--transition);
        }

        .back-to-site i {
            font-size: 0.75rem;
            transition: transform 0.2s ease;
        }

        .back-to-site:hover {
            color: var(--text-primary);
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.15);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .back-to-site:hover i {
            transform: translateX(-3px);
        }

        .login-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px) saturate(190%);
            -webkit-backdrop-filter: blur(20px) saturate(190%);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 48px 40px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3), 
                        inset 0 1px 1px rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), #ec4899, #3b82f6);
        }

        .card-header {
            text-align: center;
            margin-bottom: 36px;
        }

        .logo-box {
            width: 64px;
            height: 64px;
            margin: 0 auto 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }

        .logo-box img {
            width: 42px;
            height: auto;
        }

        .card-header h1 {
            font-family: var(--font-heading);
            font-size: 1.85rem;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.5px;
            margin-bottom: 6px;
        }

        .card-header p {
            color: var(--text-muted);
            font-size: 0.925rem;
            font-weight: 400;
        }

        /* Form Controls */
        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-group {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            color: var(--text-muted);
            font-size: 1.1rem;
            pointer-events: none;
            transition: var(--transition);
        }

        .form-control {
            width: 100%;
            padding: 14px 16px 14px 48px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 0.95rem;
            font-family: var(--font-main);
            transition: var(--transition);
        }

        /* For password field to have space for view toggle button */
        .form-control.password-input {
            padding-right: 52px;
        }

        .form-control:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-glow);
        }

        .form-control:focus + .input-icon {
            color: var(--primary);
        }

        /* Password Reveal Button Style */
        .password-toggle-btn {
            position: absolute;
            right: 8px;
            height: 38px;
            width: 38px;
            border: none;
            background: transparent;
            color: var(--text-muted);
            cursor: pointer;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
            z-index: 5;
        }

        .password-toggle-btn:hover {
            color: var(--text-primary);
            background: rgba(255, 255, 255, 0.08);
        }

        .password-toggle-btn:focus-visible {
            outline: 2px solid var(--primary);
        }

        /* Submit Button */
        .btn-submit {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-hover) 100%);
            color: white;
            font-weight: 700;
            font-size: 1.05rem;
            font-family: var(--font-heading);
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.25);
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
            filter: brightness(1.1);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .btn-submit:disabled {
            background: rgba(255, 255, 255, 0.06);
            color: var(--text-muted);
            box-shadow: none;
            cursor: not-allowed;
            transform: none;
        }

        /* Error/Alert box styling */
        .alert-box {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            padding: 14px 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 0.9rem;
            display: none;
            align-items: center;
            gap: 12px;
            animation: shake 0.4s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-6px); }
            40%, 80% { transform: translateX(6px); }
        }

        .alert-box.success-alert {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #a7f3d0;
        }

        /* Footer links */
        .card-footer {
            text-align: center;
            margin-top: 28px;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .card-footer a {
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }

        .card-footer a:hover {
            color: var(--primary);
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 32px 20px;
            }
            .card-header {
                margin-bottom: 24px;
            }
            .card-header h2 {
                font-size: 1.5rem !important;
            }
        }
    </style>
</head>
<body>

    <!-- Glowing Background Elements -->
    <div class="bg-glow-container">
        <div class="glow-orb orb-1"></div>
        <div class="glow-orb orb-2"></div>
        <div class="glow-orb orb-3"></div>
    </div>

    <div class="portal-wrapper">
        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; max-width: 450px; margin-bottom: 20px;">
            <a href="../index.php" class="back-to-site" style="margin-bottom: 0;">
                <i class="fa-solid fa-arrow-left"></i> Back to Safe Haven
            </a>
            <a href="?lang=<?php echo $lang === 'en' ? 'ar' : 'en'; ?>" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem; font-weight: 600; display: flex; align-items: center; gap: 6px;">
                <i class="fa-solid fa-globe"></i> <?php echo $lang === 'en' ? 'العربية' : 'English'; ?>
            </a>
        </div>

        <div class="login-card">
            <div class="card-header">
                <div class="logo-box">
                    <img src="../assets/images/logo.png" alt="Safe Haven">
                </div>
                <h1>Admin Portal</h1>
                <p>Enter your credentials to access the console</p>
            </div>

            <!-- Custom alert container -->
            <div id="errorAlert" class="alert-box">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span id="errorMsg">Invalid email or password.</span>
            </div>

            <form id="adminLoginForm">
                <!-- Keep role_locked as admin to pass verify checks in api/auth/login.php -->
                <input type="hidden" name="role_locked" value="admin">
                
                <div class="form-group">
                    <label for="email">Administrator Email</label>
                    <div class="input-group">
                        <input type="email" id="email" name="email" class="form-control" placeholder="admin@safehaven.com" required autocomplete="email">
                        <i class="fa-solid fa-envelope input-icon"></i>
                    </div>
                </div>

                <div class="form-group">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                        <label for="password" style="margin-bottom:0;">Password</label>
                    </div>
                    <div class="input-group">
                        <input type="password" id="password" name="password" class="form-control password-input" placeholder="••••••••" required autocomplete="current-password">
                        <i class="fa-solid fa-lock input-icon"></i>
                        
                        <!-- View Password toggle button -->
                        <button type="button" class="password-toggle-btn" id="passwordToggleBtn" aria-label="Toggle password visibility">
                            <i class="fa-solid fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-submit" id="submitBtn">
                    <span>Sign In to Console</span>
                    <i class="fa-solid fa-arrow-right-to-bracket"></i>
                </button>

                <div class="card-footer">
                    <span>Authorized Personnel Only</span>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Password Visibility Toggle Handler
        const passwordField = document.getElementById('password');
        const toggleBtn = document.getElementById('passwordToggleBtn');
        const eyeIcon = document.getElementById('eyeIcon');

        toggleBtn.addEventListener('click', function() {
            // Toggle type attribute
            const isPassword = passwordField.getAttribute('type') === 'password';
            passwordField.setAttribute('type', isPassword ? 'text' : 'password');
            
            // Toggle eye icon class
            if (isPassword) {
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        });

        // AJAX Form Submission Handler
        const loginForm = document.getElementById('adminLoginForm');
        const submitBtn = document.getElementById('submitBtn');
        const errorAlert = document.getElementById('errorAlert');
        const errorMsg = document.getElementById('errorMsg');

        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            // Reset UI states
            errorAlert.style.display = 'none';
            errorAlert.classList.remove('success-alert');
            submitBtn.disabled = true;
            const originalBtnHtml = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span>Verifying credentials...</span><i class="fa-solid fa-spinner fa-spin"></i>';

            const formData = new FormData(this);

            try {
                // Post directly to the standard authentication endpoint
                const response = await fetch('../api/auth/login.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Success UI feedback
                    errorAlert.className = 'alert-box success-alert';
                    errorAlert.style.display = 'flex';
                    errorAlert.querySelector('i').className = 'fa-solid fa-circle-check';
                    errorMsg.textContent = 'Verification successful. Access granted!';
                    submitBtn.innerHTML = '<span>Entering console...</span><i class="fa-solid fa-check"></i>';
                    
                    // Smooth redirection delay
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1000);
                } else {
                    // Error response handler
                    errorAlert.style.display = 'flex';
                    errorMsg.textContent = result.message || 'Invalid email or password.';
                    
                    // Re-enable form
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;
                }
            } catch (err) {
                console.error('Login error:', err);
                errorAlert.style.display = 'flex';
                errorMsg.textContent = 'A network error occurred. Please try again.';
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnHtml;
            }
        });
    </script>
</body>
</html>
