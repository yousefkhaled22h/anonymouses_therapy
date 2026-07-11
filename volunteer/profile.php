<?php
session_start();

// Prevent browser caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

$message = '';
$isSuccess = false;
$userId = $_SESSION['user_id'];

$volunteer = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'location' => '',
    'bio' => '',
    'skills' => '',
    'languages' => '',
    'verification_status' => '',
    'profile_image' => ''
];

$availabilities = [];

try {
    require_once __DIR__ . '/../includes/db_connect.php';

    // Fetch User Info
    $stmt = $pdo->prepare("
        SELECT v.first_name, v.last_name, u.email, v.bio, v.skills, v.verification_status, v.languages, v.location
        FROM volunteer v
        JOIN user u ON v.user_id = u.user_id
        WHERE u.user_id = ?
    ");
    $stmt->execute([$userId]);
    $volunteer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$volunteer) {
        header("Location: ../login.php");
        exit();
    }

    $volunteer['profile_image'] = '';

    // Fetch Availability
    $availabilities = [];

    // Helper function to decode availability
    $availArr = [];

    $isPending = (strtolower($volunteer['verification_status'] ?? '') === 'pending');

    // Profile Update Logic
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile']) && !$isPending) {
        $first_name = htmlspecialchars(trim($_POST['first_name'] ?? ''));
        $last_name = htmlspecialchars(trim($_POST['last_name'] ?? ''));
        $bio = htmlspecialchars(trim($_POST['bio'] ?? ''));
        $languages = htmlspecialchars(trim($_POST['languages'] ?? ''));
        $location = htmlspecialchars(trim($_POST['location'] ?? ''));
        $new_pass = $_POST['new_password'] ?? '';
        $old_pass = $_POST['old_password'] ?? '';

        if (empty($first_name) || empty($last_name)) {
            $message = "First Name and Last Name cannot be empty.";
            $isSuccess = false;
        } else {
            $password_error = false;
            // Update password if provided
            if (!empty($new_pass)) {
                $stmt_pw_check = $pdo->prepare("SELECT password_hash FROM user WHERE user_id = ?");
                $stmt_pw_check->execute([$userId]);
                $current_hash = $stmt_pw_check->fetchColumn();

                if (empty($old_pass) || !password_verify($old_pass, $current_hash)) {
                    $password_error = true;
                } else {
                    $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                    $stmt_pw = $pdo->prepare("UPDATE user SET password_hash = ? WHERE user_id = ?");
                    $stmt_pw->execute([$hashed, $userId]);
                }
            }

            // Always update volunteer table details
            $stmt = $pdo->prepare("UPDATE volunteer SET first_name = ?, last_name = ?, bio = ?, languages = ?, location = ? WHERE user_id = ?");
            $stmt->execute([$first_name, $last_name, $bio, $languages, $location, $userId]);

            // Update current instance for immediate display
            $volunteer['first_name'] = $first_name;
            $volunteer['last_name'] = $last_name;
            $volunteer['bio'] = $bio;
            $volunteer['languages'] = $languages;
            $volunteer['location'] = $location;

            // Also update session name
            $_SESSION['name'] = $first_name . ' ' . $last_name;

            if ($password_error) {
                $message = "Profile details updated, but password change failed: current password was incorrect.";
                $isSuccess = false;
            } else {
                $message = "Profile updated successfully!";
                $isSuccess = true;
            }
        }
    }





}
catch (PDOException $e) {
    if ($e->getCode() == '42S02' || $e->getCode() == '42S22') {
        $message = "Database Error: Please ensure you have run the updated database schema (Task 1).";
    }
    else {
        $message = "Database Error: " . $e->getMessage();
    }
}

// Use global header (removing sidebar class/wrapper to match client profile)
$body_class = 'role-volunteer';
require_once '../includes/header.php';
?>

<style>
    :root {
        --profile-tan: #2D6A4F;
        --profile-tan-light: #F4F9F5;
        --profile-brown-text: #081C15;
        --profile-bg: #EAF2EC;
        --profile-accent: #2D6A4F;
    }

    body {
        background-color: var(--profile-bg);
    }

    .profile-page-wrapper {
        padding: 40px 20px;
        background: var(--profile-bg);
        min-height: 90vh;
        font-family: 'Outfit', sans-serif;
    }

    .profile-container {
        max-width: 1000px;
        margin: 0 auto;
    }

    /* HEADER CARD */
    .profile-header-card {
        background: url('../assets/images/volunteer_banner.png') center/cover no-repeat;
        border-radius: 24px;
        padding: 60px 40px;
        display: flex;
        align-items: center;
        gap: 35px;
        color: white;
        position: relative;
        box-shadow: 0 10px 30px rgba(45, 106, 79, 0.2);
        margin-bottom: 30px;
        overflow: hidden;
    }

    .profile-header-card::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, rgba(27, 67, 50, 0.85) 0%, rgba(45, 106, 79, 0.5) 100%);
        z-index: 1;
    }

    .profile-header-card > * {
        position: relative;
        z-index: 2;
    }

    .p-avatar {
        width: 130px;
        height: 130px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        border: 4px solid rgba(255, 255, 255, 0.4);
        overflow: hidden;
    }

    .p-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .p-info h1 {
        font-size: 2.8rem;
        margin: 0 0 5px 0;
        font-weight: 600;
    }

    .p-info p {
        font-size: 1.1rem;
        opacity: 0.9;
        margin: 0 0 15px 0;
    }

    .p-badges {
        display: flex;
        gap: 10px;
    }

    .p-badge {
        padding: 6px 14px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 6px;
        backdrop-filter: blur(5px);
    }

    .btn-edit-toggle {
        position: absolute;
        top: 40px;
        right: 40px;
        background: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white;
        padding: 10px 20px;
        border-radius: 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        transition: all 0.2s;
        backdrop-filter: blur(5px);
    }

    .btn-edit-toggle:hover {
        background: rgba(255, 255, 255, 0.3);
    }

    /* GRID LAYOUT */
    .profile-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 25px;
    }

    .info-card {
        background: white;
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.03);
        border: 1px solid #D8F3DC;
    }

    .ic-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 25px;
    }

    .ic-icon {
        width: 45px;
        height: 45px;
        background: var(--profile-tan-light);
        color: var(--profile-brown-text);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }

    .ic-header h2 {
        font-size: 1.25rem;
        color: var(--profile-brown-text);
        margin: 0;
    }

    .ic-item {
        margin-bottom: 20px;
    }

    .ic-label {
        color: #5A8A72;
        font-size: 0.85rem;
        margin-bottom: 5px;
        display: block;
    }

    .ic-value {
        color: #0F3D28;
        font-size: 1.1rem;
        font-weight: 600;
    }

    .status-badge {
        background: #EAF7F1;
        color: #3EB489;
        padding: 4px 12px;
        border-radius: 10px;
        font-size: 0.8rem;
        font-weight: 700;
    }

    /* EDIT SECTION */
    #editSection {
        display: none;
        animation: fadeIn 0.3s ease;
        margin-top: 30px;
    }

    .edit-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 25px;
    }

    .form-section {
        background: white;
        border-radius: 20px;
        padding: 30px;
        border: 1px solid #C5E8D8;
    }

    .form-section h3 {
        margin-bottom: 20px;
        color: var(--profile-brown-text);
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #0F3D28;
    }

    .form-control {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #C5E8D8;
        border-radius: 10px;
        background: #ffffff;
        transition: border 0.2s;
        font-family: inherit;
    }

    .form-control:focus {
        border-color: #3EB489;
        outline: none;
    }

    .btn-save {
        background: #3EB489;
        color: white;
        padding: 12px 25px;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        width: 100%;
        font-weight: 600;
        margin-top: 10px;
        font-family: inherit;
    }

    .btn-save:hover {
        background: #2D9E77;
    }
    
    .btn-danger { background-color: transparent; color: #e63946; border: 1px solid #e63946; font-size: 12px; padding: 6px 12px; border-radius: 6px; cursor: pointer;}
    .btn-danger:hover { background-color: #e63946; color: white; }

    .availability-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .availability-table th { background: #EAF7F1; color: var(--profile-brown-text); font-weight: 600; text-align: left; padding: 14px; border-radius: 8px 8px 0 0; }
    .availability-table td { padding: 14px; border-bottom: 1px solid #C5E8D8; color: #0F3D28; font-size: 14px; }


    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (max-width: 768px) {
        .profile-grid,
        .edit-grid {
            grid-template-columns: 1fr;
        }

        .profile-header-card {
            padding: 40px 30px;
            gap: 24px;
        }

        .p-info h1 {
            font-size: 2.2rem;
        }

        .btn-edit-toggle {
            position: absolute;
            top: 24px;
            right: 24px;
            padding: 8px 16px;
        }
    }

    @media (max-width: 425px) {
        .profile-header-card {
            flex-direction: column;
            text-align: center;
            padding: 50px 20px 30px 20px;
        }

        .p-avatar {
            width: 110px;
            height: 110px;
        }

        .p-info h1 {
            font-size: 1.8rem;
        }

        .p-badges {
            justify-content: center;
        }

        .btn-edit-toggle {
            position: absolute;
            top: 16px;
            right: 16px;
            padding: 8px;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            justify-content: center;
        }

        .btn-edit-text {
            display: none;
        }
    }
</style>

<div class="profile-page-wrapper">
    <div class="profile-container">

        <!-- AWAITING APPROVAL ALERT -->
        <?php if ($isPending): ?>
            <div style="background: #FFF9C4; color: #856404; padding: 20px; border-radius: 16px; margin-bottom: 30px; border-left: 6px solid #FBC02D; display: flex; align-items: center; gap: 20px; text-align: left;">
                <div style="font-size: 2rem;"><i class="fa-solid fa-clock-rotate-left"></i></div>
                <div>
                    <h3 style="margin: 0; font-size: 1.2rem; font-weight: 800;">Awaiting Account Approval</h3>
                    <p style="margin: 5px 0 0; font-size: 0.95rem; opacity: 0.9;">Your volunteer application is currently being reviewed. Access to facilitating group sessions is restricted until your account is approved by an administrator.</p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div style="background: <?php echo $isSuccess ? '#E8F5E9' : '#FFEBEE'; ?>; color: <?php echo $isSuccess ? '#2E7D32' : '#C62828'; ?>; padding: 15px 25px; border-radius: 15px; margin-bottom: 25px; border: 1px solid <?php echo $isSuccess ? '#C8E6C9' : '#FFCDD2'; ?>;">
                <?php echo $isSuccess ? '✓' : '✕'; ?> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- VIEW MODE -->
        <div id="viewSection">
            <!-- Header Card -->
            <div class="profile-header-card">
                <div class="p-avatar">
                    <?php 
                        $avatar = !empty($volunteer['profile_image']) ? htmlspecialchars($volunteer['profile_image']) : '';
                        $initials = strtoupper(substr($volunteer['first_name'] ?? '', 0, 1) . substr($volunteer['last_name'] ?? '', 0, 1));
                        if (!$initials) $initials = 'U';
                    ?>
                    <?php if ($avatar): ?>
                        <img src="<?php echo $avatar; ?>" alt="Avatar">
                    <?php else: ?>
                        <span style="font-weight: 700; letter-spacing: -1px;"><?php echo htmlspecialchars($initials); ?></span>
                    <?php endif; ?>
                </div>
                <div class="p-info">
                    <h1><?php echo htmlspecialchars($volunteer['first_name'] . ' ' . $volunteer['last_name']); ?></h1>
                    <p><?php echo htmlspecialchars($volunteer['email']); ?></p>
                    <div class="p-badges">
                        <div class="p-badge">Volunteer</div>
                        <div class="p-badge">Status: <?php echo htmlspecialchars(ucfirst($volunteer['verification_status'] ?? 'Active')); ?></div>
                    </div>
                </div>
                <?php if (!$isPending): ?>
                <button class="btn-edit-toggle" onclick="toggleEdit()">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                    <span class="btn-edit-text">Edit Profile</span>
                </button>
                <?php endif; ?>
            </div>

            <!-- Stats Grid -->
            <div class="profile-grid">
                <div class="info-card">
                    <div class="ic-header">
                        <div class="ic-icon">👤</div>
                        <h2>Personal Information</h2>
                    </div>
                    <div class="ic-item">
                        <span class="ic-label">Full Name</span>
                        <span class="ic-value"><?php echo htmlspecialchars($volunteer['first_name'] . ' ' . $volunteer['last_name']); ?></span>
                    </div>
                    <div class="ic-item">
                        <span class="ic-label">Email Address</span>
                        <span class="ic-value"><?php echo htmlspecialchars($volunteer['email']); ?></span>
                    </div>
                    <div class="ic-item">
                        <span class="ic-label">Account Type</span>
                        <span class="ic-value">Volunteer</span>
                    </div>
                </div>

                <div class="info-card">
                    <div class="ic-header">
                        <div class="ic-icon">🌍</div>
                        <h2>Additional Details</h2>
                    </div>
                    <div class="ic-item">
                        <span class="ic-label">Location</span>
                        <span class="ic-value"><?php echo htmlspecialchars($volunteer['location'] ?: 'Not provided'); ?></span>
                    </div>
                    <div class="ic-item">
                        <span class="ic-label">Languages</span>
                        <span class="ic-value"><?php echo htmlspecialchars($volunteer['languages'] ?: 'Not provided'); ?></span>
                    </div>
                    <div class="ic-item">
                        <span class="ic-label">Bio</span>
                        <span class="ic-value" style="font-size:0.95rem; line-height: 1.5; font-weight: normal;"><?php echo htmlspecialchars($volunteer['bio'] ?: 'Not provided'); ?></span>
                    </div>
                </div>
                


            </div>
        </div>

        <!-- EDIT MODE -->
        <div id="editSection">
            <h2 style="margin-bottom: 25px; color: var(--profile-brown-text);">Edit Profile</h2>
            <form action="" method="POST">
                <input type="hidden" name="update_profile" value="1">
                <div class="edit-grid">
                    <!-- Column 1: Account Settings -->
                    <div class="form-section">
                        <h3>Account Settings</h3>
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="first_name" class="form-control" required
                                value="<?php echo htmlspecialchars($volunteer['first_name'] ?? ''); ?>" placeholder="John">
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="last_name" class="form-control" required
                                value="<?php echo htmlspecialchars($volunteer['last_name'] ?? ''); ?>" placeholder="Doe">
                        </div>
                        <div class="form-group">
                            <label>Location</label>
                            <input type="text" name="location" class="form-control"
                                value="<?php echo htmlspecialchars($volunteer['location'] ?? ''); ?>" placeholder="e.g. Amman, Jordan">
                        </div>
                        <div class="form-group">
                            <label>Languages</label>
                            <input type="text" name="languages" class="form-control"
                                value="<?php echo htmlspecialchars($volunteer['languages'] ?? ''); ?>" placeholder="English, Arabic">
                        </div>
                        <div class="form-group" style="margin-top: 30px; border-top: 1px solid #EAF2EC; padding-top: 20px;">
                            <label style="font-weight: 700; color: #1B4D3E; margin-bottom: 5px;">Change Password</label>
                        </div>
                        <div class="form-group">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <label style="margin: 0;">Current Password</label>
                                <button type="button" id="btnForgotPassword" class="forgot-pass-btn" style="background: none; border: none; color: #2D6A4F; font-size: 0.85rem; cursor: pointer; text-decoration: underline; font-weight: 600;" onclick="sendPasswordResetEmail()">Forgot Password?</button>
                            </div>
                            <input type="password" name="old_password" class="form-control" placeholder="••••••••">
                        </div>
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="new_password" class="form-control" placeholder="••••••••">
                        </div>
                    </div>

                    <!-- Column 2: About & Description -->
                    <div class="form-section" style="display: flex; flex-direction: column;">
                        <h3>About Me</h3>
                        <div class="form-group" style="flex: 1; display: flex; flex-direction: column;">
                            <label>Biography</label>
                            <textarea name="bio" class="form-control" style="flex: 1; min-height: 180px; resize: vertical;" placeholder="Tell us about yourself, your background, and why you wanted to join Safe Haven..."><?php echo htmlspecialchars($volunteer['bio'] ?? ''); ?></textarea>
                        </div>
                        <div style="margin-top: 20px;">
                            <button type="submit" class="btn-save" style="width: 100%;">Save Changes</button>
                        </div>
                    </div>
                </div>
            </form>

            <button onclick="toggleEdit()"
                style="margin-top: 25px; background: none; border: 1px solid #C5E8D8; padding: 10px 20px; border-radius: 10px; cursor: pointer; color: #0F3D28; font-weight: 600;">
                &larr; Back to View Mode
            </button>
        </div>

    </div>
</div>

<script>
    function toggleEdit() {
        const view = document.getElementById('viewSection');
        const edit = document.getElementById('editSection');

        if (view.style.display === 'none') {
            view.style.display = 'block';
            edit.style.display = 'none';
        } else {
            view.style.display = 'none';
            edit.style.display = 'block';
        }
    }

    async function sendPasswordResetEmail() {
        const btn = document.getElementById('btnForgotPassword');
        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Sending...';

        try {
            const formData = new FormData();
            formData.append('email', <?php echo json_encode($volunteer['email']); ?>);

            const response = await fetch('../api/auth/forgot_password.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                alert('A password reset/verification email has been sent. If this is a local environment, check the file test_debug_reset_link.txt in the project root directory.');
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            console.error('Error sending reset email:', error);
            alert('Failed to send verification email. Please try again.');
        } finally {
            btn.disabled = false;
            btn.textContent = originalText;
        }
    }
</script>

<?php require_once '../includes/footer.php'; ?>
