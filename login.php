<?php
// login.php
$role = $_GET['role'] ?? 'client';
if (strtolower($role) === 'admin') {
    header("Location: admin/login.php");
    exit();
}
$body_class = 'role-' . $role;
include 'includes/header.php';

if (isset($_SESSION['user_id'])) {
    $r = strtolower($_SESSION['role'] ?? '');
    if ($r === 'volunteer') header("Location: volunteer/dashboard.php");
    elseif ($r === 'therapist') header("Location: therapist_profile.php");
    else header("Location: dashboard.php");
    exit();
}

$role_display = ucfirst($role);
?>

<style>
    .auth-page-wrapper {
        min-height: 70vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
    }

    .back-link {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #5D4037;
        text-decoration: none;
        font-weight: 600;
        margin-bottom: 25px;
        transition: transform 0.2s ease;
        align-self: center;
        max-width: 450px;
        width: 100%;
    }
    .back-link:hover { transform: translateX(-5px); }
    .back-link svg { width: 20px; height: 20px; }

    .auth-card {
        background: #FDFBF8;
        border-radius: 24px;
        padding: 40px;
        box-shadow: var(--shadow-lg);
        border: 1px solid #E0D8CC;
        width: 100%;
        max-width: 450px;
    }

    .auth-header {
        text-align: center;
        margin-bottom: 35px;
    }

    .auth-header .auth-logo {
        margin: 0 auto 20px;
    }

    .auth-header h1 {
        font-size: 2.2rem;
        color: #3E2723;
        margin-bottom: 5px;
    }

    .auth-header p {
        color: #8D6E63;
        font-size: 1rem;
    }

    .form-group {
        margin-bottom: 25px;
    }

    .form-group label {
        display: block;
        font-weight: 700;
        margin-bottom: 8px;
        color: #3E2723;
        font-size: 0.9rem;
    }

    .form-control {
        width: 100%;
        padding: 14px 18px;
        background: #F4F1EA;
        border: 1px solid #D4C5B0;
        border-radius: 12px;
        font-size: 1rem;
        color: #3E2723;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        background: white;
        border-color: #8A7055;
        box-shadow: 0 0 0 4px rgba(138, 112, 85, 0.1);
        outline: none;
    }

    .input-with-icon {
        position: relative;
    }

    .input-with-icon svg {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #8D6E63;
        width: 20px;
        height: 20px;
    }

    .input-with-icon .form-control {
        padding-left: 45px;
    }

    .btn-submit {
        width: 100%;
        padding: 16px;
        border-radius: 12px;
        background: #8A7055;
        color: white;
        font-weight: 700;
        font-size: 1.1rem;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-bottom: 25px;
    }

    .btn-submit:hover {
        background: #6D4C41;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(109, 76, 65, 0.3);
    }

    .auth-footer {
        text-align: center;
        font-size: 0.95rem;
        color: #3E2723;
    }

    .auth-footer a {
        color: #8A7055;
        font-weight: 700;
        text-decoration: none;
    }
</style>

<?php if ($role === 'therapist'): ?>
    <style>
        body { background-color: #F0F7FF; }
        #main-header { background-color: #E3F2FD; border-bottom: 1px solid #BBDEFB; }
        .auth-card {
            background: #FFFFFF;
            border-color: #D1E5F7;
            box-shadow: 0 15px 35px rgba(51, 122, 183, 0.1);
        }

        .auth-header h1,
        .form-group label,
        .auth-footer,
        .back-link {
            color: #1A4D80;
        }

        .auth-header p,
        .input-with-icon svg {
            color: #4A7AAB;
        }

        .form-control {
            background: #F8FBFF;
            border-color: #B8D4EF;
            color: #1A4D80;
        }

        .form-control:focus {
            background: #FFFFFF;
            border-color: #337AB7;
            box-shadow: 0 0 0 4px rgba(51, 122, 183, 0.1);
        }

        .btn-submit {
            background: #337AB7;
        }

        .btn-submit:hover {
            background: #286090;
            box-shadow: 0 5px 15px rgba(40, 96, 144, 0.3);
        }

        .auth-footer a {
            color: #337AB7;
        }

        /* Force Navbar & Footer to Therapist Blue */
        .logo span { color: #337AB7 !important; }
        body[data-logged-in="false"][data-role="client"] .btn-get-started,
        body:not([data-logged-in="true"])[data-role="client"] .btn-get-started,
        .btn-get-started, .btn-primary, .nav-links .btn-primary { 
            background: #337AB7 !important; 
            background-image: none !important;
            background-color: #337AB7 !important;
            border-color: #337AB7 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(51, 122, 183, 0.3) !important;
        }
        .btn-get-started:hover, .btn-primary:hover, .nav-links .btn-primary:hover {
            background: #286090 !important;
            background-color: #286090 !important;
            border-color: #286090 !important;
            box-shadow: 0 6px 16px rgba(40, 96, 144, 0.4) !important;
        }
        #main-header .nav-links a:not(.btn-logout):not(.btn-get-started):hover {
            color: #337AB7 !important;
        }
        .minimal-footer { 
            background: transparent !important; 
            color: #1A4D80 !important; 
            box-shadow: none !important;
            border-top: 1px solid rgba(26, 77, 128, 0.15) !important;
        }
        .footer-brand-img { 
            background: #337AB7 !important; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.2) !important;
        }
        .minimal-footer p,
        .minimal-footer .footer-desc,
        .minimal-footer .footer-col-title, 
        .minimal-footer .footer-bottom-minimal,
        .minimal-footer .footer-link-list a,
        .minimal-footer .footer-contact-list li,
        .minimal-footer .footer-contact-list li i { 
            color: #1A4D80 !important; 
            opacity: 1 !important;
        }
        .minimal-footer .footer-col-title {
            border-bottom: 2px solid rgba(51, 122, 183, 0.3) !important;
        }
        .minimal-footer .footer-link-list a:hover {
            color: #337AB7 !important;
        }
        .footer-divider {
            background-color: rgba(26, 77, 128, 0.15) !important;
        }

        .back-link:hover { color: #337AB7; }
        a[href='auth_handler.php?action=forgot_password'] { color: #337AB7 !important; }
    </style>
<?php endif; ?>

<?php if ($role === 'volunteer'): ?>
    <style>
        body { background-color: #F0FDF4; }
        #main-header { background-color: #DCFCE7; border-bottom: 1px solid #BBF7D0; }
        .auth-card {
            background: #ffffff;
            border-color: #bbf7d0;
            box-shadow: 0 15px 35px rgba(34, 197, 94, 0.1);
        }

        .auth-header h1,
        .form-group label,
        .auth-footer,
        .back-link {
            color: #14532d;
        }

        .auth-header p,
        .input-with-icon svg {
            color: #16a34a;
        }

        .form-control {
            background: #ffffff;
            border-color: #86efac;
            color: #14532d;
        }

        .form-control:focus {
            border-color: #22c55e;
            box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.12);
        }

        .btn-submit {
            background: linear-gradient(135deg, #16a34a, #22c55e);
            box-shadow: 0 4px 16px rgba(34, 197, 94, .3);
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, #15803d, #16a34a);
            box-shadow: 0 8px 24px rgba(34, 197, 94, .4);
        }

        .auth-footer a { color: #16a34a; }
        .back-link:hover { color: #16a34a; }
        a[href='auth_handler.php?action=forgot_password'] { color: #16a34a !important; }
    </style>
<?php endif; ?>

<?php if ($role === 'client'): ?>
    <style>
        body { background-color: #F4F1EA; }
        #main-header { background-color: #EDE9E1; border-bottom: 1px solid #E0D8CC; }
        .back-link:hover { color: #8A7055; }
        a[href='auth_handler.php?action=forgot_password'] { color: #8A7055 !important; }
    </style>
<?php endif; ?>

<div class="auth-page-wrapper">
    <a href="auth_handler.php?action=role_selection" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        Back to Role Selection
    </a>
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">
                <img class="img-fluid" src="assets/images/logo.png" alt="Safe Haven" style="width: 80px; height: auto;">
            </div>
            <h1>Sign In</h1>
            <p>Welcome back, <?php echo $role_display; ?>!</p>
        </div>

        <form id="loginForm" action="api/auth/login.php" method="POST">
            <input type="hidden" name="role_locked" value="<?php echo htmlspecialchars($role); ?>">
            <div class="form-group">
                <label for="email">Email</label>
                <div class="input-with-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    <input type="email" id="email" name="email" class="form-control" placeholder="your@email.com"
                        required>
                </div>
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <label for="password" style="margin-bottom: 0;">Password</label>
                    <a href="auth_handler.php?action=forgot_password"
                        style="font-size: 0.85rem; color: #8A7055; font-weight: 600; text-decoration: none;">Forgot
                        Password?</a>
                </div>
                <div class="input-with-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    <input type="password" id="password" name="password" class="form-control"
                        placeholder="Enter password" required>
                </div>
            </div>

            <button type="submit" class="btn-submit">Sign In</button>

            <div class="auth-footer">
                New here? <a href="register.php?role=<?php echo $role; ?>">Register as a
                    <?php echo $role_display; ?></a>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById('loginForm').addEventListener('submit', async function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = this.querySelector('.btn-submit');
        const originalBtnText = submitBtn.textContent;

        submitBtn.disabled = true;
        submitBtn.textContent = 'Signing in...';

        try {
            const response = await fetch('api/auth/login.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                window.location.href = result.redirect || 'dashboard.php';
            } else {
                alert(result.message || 'Login failed');
                submitBtn.disabled = false;
                submitBtn.textContent = originalBtnText;
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
            submitBtn.disabled = false;
            submitBtn.textContent = originalBtnText;
        }
    });
</script>

<?php include 'includes/footer.php'; ?>