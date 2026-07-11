<?php
// register.php
$role = isset($_GET['role']) ? $_GET['role'] : '';
$body_class = 'role-' . ($role ?: 'client');
include 'includes/header.php';

$valid_roles = ['client', 'therapist', 'volunteer'];

if (!in_array($role, $valid_roles)) {
    header("Location: auth_handler.php?action=role_selection");
    exit();
}

$role_title = ucfirst($role);
$form_id = "registerForm" . $role_title;
?>

<style>
    /* ── Shared base ── */
    .reg-container {
        max-width: 500px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .back-link {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #5D4037;
        text-decoration: none;
        font-weight: 600;
        margin-bottom: 30px;
        transition: transform 0.2s ease;
    }
    .back-link:hover { transform: translateX(-5px); }

    .reg-card {
        background: #FDFBF8;
        border-radius: 24px;
        padding: 40px;
        box-shadow: var(--shadow-lg);
        border: 1px solid #E0D8CC;
    }

    .reg-header { text-align: center; margin-bottom: 35px; }
    .reg-header h1 { font-size: 2.2rem; color: #3E2723; margin-bottom: 5px; }
    .reg-header p { color: #8D6E63; font-size: 1rem; font-weight: 500; }

    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-weight: 700; margin-bottom: 8px; color: #3E2723; font-size: 0.9rem; }

    .form-control {
        width: 100%; padding: 14px 18px;
        background: #F4F1EA; border: 1px solid #D4C5B0;
        border-radius: 12px; font-size: 1rem; color: #3E2723;
        transition: all 0.3s ease;
    }
    .form-control:focus {
        background: white; border-color: #8A7055;
        box-shadow: 0 0 0 4px rgba(138,112,85,0.1); outline: none;
    }

    .input-with-icon { position: relative; }
    .input-with-icon > i, .input-with-icon > svg {
        position: absolute; left: 15px; top: 50%; transform: translateY(-50%);
        color: #8D6E63; width: 20px; height: 20px;
        pointer-events: none;
    }
    .input-with-icon .form-control { padding-left: 45px; padding-right: 45px; }
    .password-toggle {
        position: absolute; right: 15px; top: 50%;
        transform: translateY(-50%); cursor: pointer; color: #8D6E63;
        display: flex; align-items: center; justify-content: center;
        padding: 4px;
    }
    .password-toggle i, .password-toggle svg {
        position: static; transform: none; width: auto; height: auto;
    }

    .anonymous-toggle {
        background: #F4F1EA; padding: 20px; border-radius: 15px;
        display: flex; align-items: flex-start; gap: 15px;
        margin-bottom: 25px; cursor: pointer; transition: background 0.2s ease;
    }
    .anonymous-toggle:hover { background: #EDE9E1; }
    .anonymous-checkbox { width: 22px; height: 22px; margin-top: 3px; accent-color: #8A7055; }
    .anonymous-text h3 { font-size: 1rem; margin-bottom: 2px; color: #3E2723; }
    .anonymous-text p { font-size: 0.85rem; color: #8D6E63; }

    .file-upload-box {
        border: 2px dashed #D4C5B0; border-radius: 12px; padding: 25px;
        text-align: center; cursor: pointer; transition: all 0.3s ease; margin-bottom: 8px;
    }
    .file-upload-box:hover { border-color: #8A7055; background: #F4F1EA; }
    .file-upload-box svg { width: 24px; height: 24px; color: #8D6E63; margin-bottom: 10px; }
    .file-upload-box p { font-size: 0.9rem; color: #6D4C41; font-weight: 500; }
    .file-hint { font-size: 0.75rem; color: #8D6E63; margin-bottom: 20px; }

    .btn-submit {
        width: 100%; padding: 16px; border-radius: 12px;
        background: #8A7055; color: white; font-weight: 700;
        font-size: 1.1rem; border: none; cursor: pointer;
        transition: all 0.3s ease; margin-bottom: 20px;
    }
    .btn-submit:hover {
        background: #6D4C41; transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(109,76,65,0.3);
    }

    .auth-footer { text-align: center; font-size: 0.95rem; color: #3E2723; }
    .auth-footer a { color: #8A7055; font-weight: 700; text-decoration: none; }

    .notice-box {
        background: rgba(138,112,85,0.08); border-radius: 12px;
        padding: 15px; text-align: center; margin-top: 25px;
        font-size: 0.85rem; color: #5D4037; font-weight: 500;
        border: 1px solid rgba(138,112,85,0.2);
    }
</style>

<?php if ($role === 'therapist'): ?>
<style>
    body { background-color: #F0F7FF; }
    #main-header { background-color: #E3F2FD; border-bottom: 1px solid #BBDEFB; }
    .reg-card { background: #FFFFFF; border-color: #D1E5F7; box-shadow: 0 15px 35px rgba(51, 122, 183, 0.1); }
    .back-link, .reg-header h1, .form-group label, .auth-footer { color: #1A4D80; }
    .reg-header .role-icon { background-color: #337AB7; }
    .reg-header p, .input-with-icon i, .input-with-icon svg, .password-toggle, .file-hint { color: #4A7AAB; }
    .form-control { background: #F8FBFF; border-color: #B8D4EF; color: #1A4D80; }
    .form-control:focus { background: #FFFFFF; border-color: #337AB7; box-shadow: 0 0 0 4px rgba(51,122,183,0.1); }
    .file-upload-box { border-color: #B8D4EF; background: #F8FBFF; }
    .file-upload-box:hover { border-color: #337AB7; background: #FFFFFF; }
    .file-upload-box svg { color: #4A7AAB; }
    .file-upload-box p { color: #286090; }
    .btn-submit { background: #337AB7; }
    .btn-submit:hover { background: #286090; box-shadow: 0 5px 15px rgba(40,96,144,0.3); }
    .auth-footer a { color: #337AB7; }
    .notice-box { background: rgba(51,122,183,0.08); color: #1A4D80; border-color: rgba(51,122,183,0.2); }
    
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
</style>
<?php endif; ?>

<?php if ($role === 'volunteer'): ?>
<style>
    body { background-color: #F0FDF4; }
    #main-header { background-color: #DCFCE7; border-bottom: 1px solid #BBF7D0; }
    .reg-card { background: #ffffff; border-color: #bbf7d0; box-shadow: 0 15px 35px rgba(34, 197, 94, 0.1); }
    .back-link, .reg-header h1, .form-group label, .auth-footer { color: #14532d; }
    .reg-header p, .input-with-icon i, .input-with-icon svg, .password-toggle { color: #16a34a; }
    .form-control { background: #ffffff; border-color: #86efac; color: #14532d; }
    .form-control:focus { border-color: #22c55e; box-shadow: 0 0 0 4px rgba(34,197,94,0.12); }
    .btn-submit { background: linear-gradient(135deg, #16a34a, #22c55e); box-shadow: 0 4px 16px rgba(34,197,94,.3); }
    .btn-submit:hover { background: linear-gradient(135deg, #15803d, #16a34a); box-shadow: 0 8px 24px rgba(34,197,94,.4); }
    .auth-footer a { color: #16a34a; }
    .notice-box { background: rgba(34,197,94,.08); color: #15803d; border-color: rgba(34,197,94,.2); }
    .back-link:hover { color: #16a34a; }
</style>
<?php endif; ?>

<?php if ($role === 'client'): ?>
<style>
    body { background-color: #F4F1EA; }
    #main-header { background-color: #EDE9E1; border-bottom: 1px solid #E0D8CC; }
    .back-link:hover { color: #8A7055; }
</style>
<?php endif; ?>

<!-- Unified Single Column Layout for All Roles -->
<div class="reg-container">
    <a href="auth_handler.php?action=role_selection" class="back-link">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        Back to role selection
    </a>

    <div class="reg-card">
        <div class="reg-header">
            <div class="auth-logo" style="margin-bottom: 20px;">
                <img class="img-fluid" src="assets/images/logo.png" alt="Safe Haven" style="width: 80px; height: auto;">
            </div>
            <?php if ($role === 'therapist'): ?>
                <h1>Join as Therapist</h1>
                <p>Professional Portal</p>
            <?php elseif ($role === 'volunteer'): ?>
                <h1>Become a Volunteer</h1>
                <p>Community Helper Portal</p>
            <?php else: ?>
                <h1>Create Your Account</h1>
                <p>Join thousands who've found support on Safe Haven.</p>
            <?php endif; ?>
        </div>

        <form id="registerForm" action="api/auth/register.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="role" value="<?php echo ucfirst($role); ?>">

            <?php if ($role === 'client'): ?>
                <div class="form-group">
                    <label for="name">Your Name</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="name" name="name" class="form-control" placeholder="How should we call you?" required>
                    </div>
                </div>
            <?php else: ?>
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" id="first_name" name="first_name" class="form-control" placeholder="First name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" id="last_name" name="last_name" class="form-control" placeholder="Last name" required>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($role === 'therapist'): ?>
                <div class="form-group">
                    <label for="specialization">Specialist Area</label>
                    <select id="specialization" name="specialization" class="form-control" required>
                        <option value="">Select your specialization</option>
                        <option value="Anxiety">Anxiety & Stress</option>
                        <option value="Depression">Depression</option>
                        <option value="Relationships">Relationship Issues</option>
                        <option value="Trauma">Trauma & PTSD</option>
                        <option value="Childhood">Childhood Development</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Upload License</label>
                    <div class="file-upload-box" onclick="document.getElementById('license').click()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        <p id="file-name">Click to upload license document</p>
                    </div>
                    <input type="file" id="license" name="license" style="display: none;" onchange="updateFileName(this)">
                    <p class="file-hint">Accepted formats: PDF, JPG, PNG (Max 5MB)</p>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-with-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" class="form-control" placeholder="your@email.com" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Create a secure password" required>
                    <span class="password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </span>
                </div>
            </div>

            <?php if ($role === 'client'): ?>
                <label class="anonymous-toggle" for="is_anonymous">
                    <input type="checkbox" id="is_anonymous" name="is_anonymous" class="anonymous-checkbox" value="1">
                    <div class="anonymous-text">
                        <h3>Join anonymously</h3>
                        <p>Your real identity stays completely private. We'll assign you an alias.</p>
                    </div>
                </label>
            <?php endif; ?>

            <button type="submit" class="btn-submit">
                <?php if ($role === 'therapist'): ?>Submit for Approval
                <?php elseif ($role === 'volunteer'): ?>Join Community
                <?php else: ?>Create My Account<?php endif; ?>
            </button>

            <div class="auth-footer">
                Already have an account? <a href="login.php<?php echo $role ? '?role=' . $role : ''; ?>">Sign in</a>
            </div>

            <?php if ($role === 'therapist'): ?>
                <div class="notice-box">Your license will be verified by our admin team within 24-48 hours</div>
            <?php elseif ($role === 'volunteer'): ?>
                <div class="notice-box">Help others on their mental wellness journey by providing peer support</div>
            <?php elseif ($role === 'client'): ?>
                <div style="margin-top: 25px; display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                    <div style="display:flex;align-items:center;gap:6px;font-size:0.78rem;color:#8D6E63;">
                        <i class="fas fa-shield-alt" style="color:#8A7055;"></i> Secure & Encrypted
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;font-size:0.78rem;color:#8D6E63;">
                        <i class="fas fa-user-secret" style="color:#8A7055;"></i> Anonymous Option
                    </div>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<script>
    function updateFileName(input) {
        const fileName = input.files[0] ? input.files[0].name : "Click to upload license document";
        document.getElementById('file-name').textContent = fileName;
    }

    function togglePassword() {
        const p = document.getElementById('password');
        const isText = p.getAttribute('type') === 'text';
        p.setAttribute('type', isText ? 'password' : 'text');
        const icon = document.getElementById('eyeIcon') || document.getElementById('eye-icon');
        if (icon && icon.tagName === 'I') {
            icon.className = isText ? 'fas fa-eye' : 'fas fa-eye-slash';
        }
    }

    const anonCheckbox = document.getElementById('is_anonymous');
    if (anonCheckbox) {
        function generateNickname() {
            const adjectives = ["Brave", "Calm", "Peaceful", "Quiet", "Gentle", "Resilient", "Kind", "Wise", "Serene", "Strong"];
            const nouns = ["River", "Mountain", "Oak", "Falcon", "Dolphin", "Meadow", "Cloud", "Star", "Phoenix", "Forest"];
            const adj = adjectives[Math.floor(Math.random() * adjectives.length)];
            const noun = nouns[Math.floor(Math.random() * nouns.length)];
            const num = Math.floor(Math.random() * 900) + 100;
            return `${adj}${noun}_${num}`;
        }

        anonCheckbox.addEventListener('change', function() {
            const nameInput = document.getElementById('name');
            if (this.checked) {
                nameInput.value = generateNickname();
                nameInput.style.backgroundColor = "#f8fafc";
                nameInput.style.color = "#64748b";
                nameInput.readOnly = true;
            } else {
                nameInput.value = "";
                nameInput.style.backgroundColor = "";
                nameInput.style.color = "";
                nameInput.readOnly = false;
                nameInput.placeholder = "How should we call you?";
            }
        });
    }

    document.getElementById('registerForm')?.addEventListener('submit', async function (e) {
        e.preventDefault();
        
        // Basic Validation for Therapist
        const role = this.querySelector('input[name="role"]').value;
        if (role === 'Therapist') {
            const firstName = document.getElementById('first_name').value.trim();
            const lastName = document.getElementById('last_name').value.trim();
            if (!firstName || !lastName) {
                alert('First Name and Last Name are required for therapists.');
                return;
            }
        }

        const formData = new FormData(this);
        const submitBtn = this.querySelector('.btn-submit');
        const originalBtnText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Creating account...';
        try {
            const response = await fetch('api/auth/register.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                alert(result.message);
                window.location.href = result.redirect || 'login.php';
            } else {
                alert(result.message || 'Registration failed');
                submitBtn.disabled = false;
                submitBtn.textContent = originalBtnText;
            }
        } catch (error) {
            alert('An error occurred. Please try again.');
            submitBtn.disabled = false;
            submitBtn.textContent = originalBtnText;
        }
    });
</script><?php include 'includes/footer.php'; ?>
