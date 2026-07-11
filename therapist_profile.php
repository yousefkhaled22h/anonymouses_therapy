<?php
// therapist_profile.php
$body_class = 'role-therapist';
require_once 'includes/header.php';
require_once 'includes/db_connect.php';

// 1. Role Check
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Therapist') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
// We might need therapist_id for some FKs, but user_id is unique in therapists table too.
$message = '';
$error = '';

// 2. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete_avatar') {
        try {
            $stmt = $pdo->prepare("SELECT profile_image FROM therapist WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $old_avatar = $stmt->fetchColumn();
            
            if ($old_avatar && file_exists($old_avatar)) {
                unlink($old_avatar);
            }
            
            $stmt = $pdo->prepare("UPDATE therapist SET profile_image = NULL WHERE user_id = ?");
            $stmt->execute([$user_id]);
            unset($_SESSION['avatar_path']);
            $message = "Profile photo deleted successfully!";
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        // Basic Profile Info
        $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $specialties = trim($_POST['specialties'] ?? '');
    $hourly_rate = floatval($_POST['hourly_rate'] ?? 0);
    $years_experience = intval($_POST['years_experience'] ?? 0);
    $education = trim($_POST['education'] ?? '');
    $experience_details = trim($_POST['experience_details'] ?? '');
    $languages = trim($_POST['languages'] ?? '');
    $zoom_link = trim($_POST['zoom_link'] ?? '');

    try {
        // Update Therapist table
        $stmt = $pdo->prepare("UPDATE therapist SET first_name = ?, last_name = ?, bio = ?, specialties = ?, hourly_rate = ?, years_experience = ?, education = ?, experience_details = ?, languages = ?, zoom_link = ? WHERE user_id = ?");
        $stmt->execute([$first_name, $last_name, $bio, $specialties, $hourly_rate, $years_experience, $education, $experience_details, $languages, $zoom_link, $user_id]);


        // Fetch therapist_id
        $stmt = $pdo->prepare("SELECT therapist_id FROM therapist WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $t_id = $stmt->fetchColumn();

        // Handle License Update
        if (isset($_FILES['license']) && $_FILES['license']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['license']['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
            if (in_array($ext, $allowed)) {
                $filename = 'license_' . $t_id . '_' . time() . '.' . $ext;
                $dir = 'assets/uploads/licenses/';
                if (!is_dir($dir)) mkdir($dir, 0777, true);

                if (move_uploaded_file($_FILES['license']['tmp_name'], $dir . $filename)) {
                    $check = $pdo->prepare("SELECT verification_id FROM therapist_verification WHERE therapist_id = ?");
                    $check->execute([$t_id]);
                    if ($check->fetch()) {
                        $stmt = $pdo->prepare("UPDATE therapist_verification SET license_file_path = ?, verification_status = 'Pending' WHERE therapist_id = ?");
                        $stmt->execute([$dir . $filename, $t_id]);
                    } else {
                        $ver_id = 'ver_' . bin2hex(random_bytes(8));
                        $stmt = $pdo->prepare("INSERT INTO therapist_verification (verification_id, therapist_id, license_file_path, verification_status) VALUES (?, ?, ?, 'Pending')");
                        $stmt->execute([$ver_id, $t_id, $dir . $filename]);
                    }
                }
            }
        }

        // Handle Profile Image Update
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png'];
            if (in_array($ext, $allowed)) {
                $img_filename = 'profile_' . $t_id . '_' . time() . '.' . $ext;
                $img_dir = 'assets/uploads/profiles/';
                if (!is_dir($img_dir)) mkdir($img_dir, 0777, true);

                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $img_dir . $img_filename)) {
                    $stmt = $pdo->prepare("UPDATE therapist SET profile_image = ? WHERE therapist_id = ?");
                    $stmt->execute([$img_dir . $img_filename, $t_id]);
                    $_SESSION['avatar_path'] = $img_dir . $img_filename; // Update session for navbar
                }
            }
        }
        
        // Update session name for navbar
        $full_name = trim($first_name . ' ' . $last_name);
        if (!empty($full_name)) {
            $_SESSION['name'] = $full_name;
        } elseif (empty($_SESSION['name'])) {
            $_SESSION['name'] = 'Therapist';
        }

        $message = "Profile updated successfully!";
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
    }
}

// 3. Fetch Data
try {
    // Get User Info
    // Schema: User(user_id, email...)
    $stmt = $pdo->prepare("SELECT email FROM user WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user_common = $stmt->fetch();

    // Get Profile Info
    $stmt = $pdo->prepare("SELECT * FROM therapist WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch();

    if (!$profile) {
        // Error state
        die("Therapist profile not found.");
    }

    $t_id = $profile['therapist_id'];

    // Get Verification Status from Verification table
    // Table: Therapist_Verification
    $stmt = $pdo->prepare("SELECT verification_status FROM therapist_verification WHERE therapist_id = ?");
    $stmt->execute([$t_id]);
    $verification = $stmt->fetch();

    $is_verified = $profile['verified'] == 1;
    $status = $is_verified ? 'Approved' : ($verification['verification_status'] ?? 'Not Submitted');

    $education = $profile['education'] ?? '';
    $experience_details = $profile['experience_details'] ?? '';
    $languages = $profile['languages'] ?? '';
    $profile_image = $profile['profile_image'] ?? 'assets/images/default_avatar.jpg';

    // Parse existing data
    $bio = $profile['bio'] ?? '';
    $specialties = $profile['specialties'] ?? '';
    $hourly_rate = $profile['hourly_rate'] ?? 0.00;
    $years_experience = $profile['years_experience'] ?? 0;
    $rating = $profile['rating'] ?? 5.0;
    $first_name = $profile['first_name'] ?? '';
    $last_name = $profile['last_name'] ?? '';
    $display_name = trim($first_name . ' ' . $last_name);

        if (empty($display_name)) {
            $email_part = explode('@', $user_common['email'])[0];
            $display_name = ucwords(str_replace(['.', '_', '-'], ' ', $email_part));
        }

        // Calculate Initials
        $t_initials = '';
        if (!empty($first_name) && !empty($last_name)) {
            $t_initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
        } else {
            $name_parts = explode(' ', $display_name);
            if (count($name_parts) > 1) {
                $t_initials = strtoupper(substr($name_parts[0], 0, 1) . substr($name_parts[1], 0, 1));
            } else {
                $t_initials = strtoupper(substr($display_name, 0, 1));
            }
        }
} catch (PDOException $e) {
    die("Error fetching profile: " . $e->getMessage());
}
?>



<style>
    :root {
        --profile-blue-main: #337AB7;
        --profile-blue-dark: #286090;
        --profile-blue-light: #F4F9FD;
        --profile-blue-text: #1A4D80;
        --profile-card-bg: #ffffff;
    }

    .profile-hero {
        background: url('assets/images/therapist_profile_banner.png') center/cover no-repeat;
        background-color: var(--profile-blue-main);
        padding: 60px 40px;
        border-radius: 30px;
        display: flex;
        align-items: center;
        gap: 35px;
        color: white;
        position: relative;
        box-shadow: 0 10px 30px rgba(51, 122, 183, 0.2);
        margin-bottom: 30px;
        overflow: hidden;
    }

    .profile-hero::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, rgba(30,41,59,0.7) 0%, rgba(37,99,235,0.3) 100%);
        z-index: 1;
    }

    .profile-hero > * {
        z-index: 2;
        position: relative;
    }

    .p-avatar {
        width: 140px;
        height: 140px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3.5rem;
        border: 4px solid rgba(255, 255, 255, 0.4);
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        flex-shrink: 0;
    }

    .p-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .p-info h1 {
        font-size: 3rem;
        margin: 0 0 5px 0;
        font-weight: 700;
    }

    .p-info p {
        font-size: 1.1rem;
        opacity: 0.9;
        margin: 0 0 15px 0;
    }

    .p-badges {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .p-badge {
        padding: 8px 16px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        backdrop-filter: blur(8px);
    }

    .btn-edit-toggle {
        position: absolute;
        top: 40px;
        right: 40px;
        background: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white;
        padding: 12px 24px;
        border-radius: 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
        backdrop-filter: blur(8px);
    }

    .btn-edit-toggle:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: translateY(-2px);
    }

    .profile-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
    }

    .info-card {
        background: white;
        border-radius: 24px;
        padding: 35px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05);
        border: 1px solid #e1e8ed;
        transition: transform 0.3s ease;
    }

    .ic-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 1px solid #f0f4f8;
    }

    .ic-icon {
        width: 50px;
        height: 50px;
        background: var(--profile-blue-light);
        color: var(--profile-blue-dark);
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
    }

    .ic-header h2 {
        font-size: 1.4rem;
        color: var(--profile-blue-text);
        margin: 0;
        font-weight: 700;
    }

    .ic-item {
        margin-bottom: 24px;
    }

    .ic-label {
        color: #7f8c8d;
        font-size: 0.9rem;
        margin-bottom: 6px;
        display: block;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }

    .ic-value {
        color: #2c3e50;
        font-size: 1.15rem;
        font-weight: 600;
    }

    .status-badge {
        background: #e1f5fe;
        color: #0288d1;
        padding: 6px 14px;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 700;
    }

    #editSection {
        display: none;
        animation: fadeIn 0.4s ease-out;
        margin-top: 40px;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #34495e;
    }

    .form-control {
        width: 100% !important;
        padding: 12px 16px;
        border: 1px solid #dcdde1;
        border-radius: 10px;
        font-size: 1rem;
        transition: border-color 0.2s;
    }

    .form-control:focus {
        border-color: var(--profile-blue-main);
        outline: none;
    }

    .btn-save {
        background: var(--profile-blue-main);
        color: white;
        padding: 14px 30px;
        border-radius: 12px;
        border: none;
        font-weight: 600;
        font-size: 1.1rem;
        cursor: pointer;
        width: 100%;
        margin-top: 20px;
        transition: background 0.3s;
    }

    .btn-save:hover {
        background: var(--profile-blue-dark);
    }

    @media (max-width: 768px) {
        .profile-grid,
        .edit-grid {
            grid-template-columns: 1fr;
        }

        .profile-hero {
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
        .profile-hero {
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

<div class="container" style="padding-top: 40px; padding-bottom: 80px;">
    <!-- HERO SECTION -->
    <div class="profile-hero">
        <div class="p-avatar">
            <?php if ($profile_image && strpos($profile_image, 'default_avatar') === false): ?>
                <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile">
            <?php else: ?>
                <span style="font-weight: 700; font-size: 2.5rem; letter-spacing: -1px;"><?php echo htmlspecialchars($t_initials); ?></span>
            <?php endif; ?>
        </div>
        <div class="p-info">
            <h1><?php echo htmlspecialchars($display_name); ?></h1>
            <p><?php echo htmlspecialchars($specialties ?: 'Mental Health Professional'); ?></p>
            <div class="p-badges">
                <div class="p-badge">
                    <i class="fa-solid fa-star" style="color: #f1c40f;"></i>
                    <?php echo number_format($rating, 1); ?> Rating
                </div>
                <div class="p-badge">
                    <i class="fa-solid fa-briefcase"></i>
                    <?php echo $years_experience; ?>+ Years Exp.
                </div>
                <?php if ($is_verified): ?>
                    <div class="p-badge" style="background: rgba(46, 204, 113, 0.3);">
                        <i class="fa-solid fa-circle-check"></i> Verified Provider
                    </div>
                <?php endif; ?>
                <div class="p-badge">
                    <span class="status-badge" style="background: rgba(255,255,255,0.2); color: white;">
                        Status: <?php echo htmlspecialchars($status); ?>
                    </span>
                </div>
            </div>
        </div>

        <button class="btn-edit-toggle" onclick="toggleEdit()">
            <i class="fa-solid fa-pen-to-square"></i>
            <span class="btn-edit-text">Edit Profile</span>
        </button>
    </div>

    <?php if ($message): ?>
        <div style="background: #d4edda; color: #155724; padding: 20px; border-radius: 12px; margin-bottom: 30px; border-left: 5px solid #28a745;">
            <i class="fa-solid fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 20px; border-radius: 12px; margin-bottom: 30px; border-left: 5px solid #dc3545;">
            <i class="fa-solid fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- VIEW SECTION -->
    <div id="viewSection">
        <div class="profile-grid">
            <!-- Professional Card -->
            <div class="info-card">
                <div class="ic-header">
                    <div class="ic-icon"><i class="fa-solid fa-stethoscope"></i></div>
                    <h2>Professional Bio</h2>
                </div>
                <div class="ic-item">
                    <span class="ic-label">About My Approach</span>
                    <div class="ic-value" style="font-weight: 400; line-height: 1.6; color: #444;">
                        <?php echo nl2br(htmlspecialchars($bio ?: 'No bio provided yet. Click edit to add your professional story.')); ?>
                    </div>
                </div>
                <div class="ic-item">
                    <span class="ic-label">Primary Specialties</span>
                    <div class="ic-value"><?php echo htmlspecialchars($specialties ?: 'General Practice'); ?></div>
                </div>
                <div class="ic-item">
                    <span class="ic-label">Academic Background</span>
                    <div class="ic-value" style="font-weight: 500;"><?php echo nl2br(htmlspecialchars($education ?: 'Not specified')); ?></div>
                </div>
            </div>

            <!-- Account/Personal Card -->
            <div class="info-card">
                <div class="ic-header">
                    <div class="ic-icon"><i class="fa-solid fa-user-gear"></i></div>
                    <h2>Account Details</h2>
                </div>
                <div class="ic-item">
                    <span class="ic-label">Official Email</span>
                    <div class="ic-value"><?php echo htmlspecialchars($user_common['email']); ?></div>
                </div>
                <div class="ic-item">
                    <span class="ic-label">Session Rate</span>
                    <div class="ic-value">$<?php echo number_format($hourly_rate, 2); ?> per hour</div>
                </div>
                <div class="ic-item">
                    <span class="ic-label">Spoken Languages</span>
                    <div class="ic-value"><?php echo htmlspecialchars($languages ?: 'English'); ?></div>
                </div>
                <div class="ic-item">
                    <span class="ic-label">Clinical Experience</span>
                    <div class="ic-value" style="font-weight: 400; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($experience_details ?: 'Details not added yet.')); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- EDIT SECTION -->
    <div id="editSection">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="profile-grid">
                <div class="info-card">
                    <div class="ic-header">
                        <div class="ic-icon"><i class="fa-solid fa-user-pen"></i></div>
                        <h2>Basic Information</h2>
                    </div>
                    <div style="display: flex; gap: 15px; margin-bottom: 20px;">
                        <div class="form-group" style="flex: 1;">
                            <label>First Name</label>
                            <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($first_name); ?>" required>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>Last Name</label>
                            <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($last_name); ?>" required>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label>Hourly Rate ($)</label>
                        <input type="number" step="0.01" name="hourly_rate" class="form-control" value="<?php echo $hourly_rate; ?>">
                    </div>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label>Years of Experience</label>
                        <input type="number" name="years_experience" class="form-control" value="<?php echo $years_experience; ?>">
                    </div>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label>Update Profile Photo</label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="file" name="profile_image" class="form-control" style="flex: 1;">
                            <?php if (!empty($profile['profile_image'])): ?>
                                <button type="submit" name="action" value="delete_avatar" formnovalidate class="btn-save" style="margin-top: 0; background: #ef4444; width: auto;" onclick="return confirm('Are you sure you want to delete your avatar?');">Delete Avatar</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Academic Background</label>
                        <textarea name="education" rows="4" class="form-control"><?php echo htmlspecialchars($education); ?></textarea>
                    </div>
                </div>

                <div class="info-card">
                    <div class="ic-header">
                        <div class="ic-icon"><i class="fa-solid fa-id-card-clip"></i></div>
                        <h2>Professional Details</h2>
                    </div>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label>Specialties (comma separated)</label>
                        <input type="text" name="specialties" class="form-control" value="<?php echo htmlspecialchars($specialties); ?>">
                    </div>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label>Personal Zoom Meeting Link</label>
                        <input type="url" name="zoom_link" class="form-control" value="<?php echo htmlspecialchars($profile['zoom_link'] ?? ''); ?>" placeholder="https://zoom.us/j/1234567890">
                        <small style="color: #666;">This link will be sent to clients for Video Sessions.</small>
                    </div>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label>Languages Spoken</label>
                        <input type="text" name="languages" class="form-control" value="<?php echo htmlspecialchars($languages); ?>">
                    </div>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label>Professional Bio</label>
                        <textarea name="bio" rows="6" class="form-control"><?php echo htmlspecialchars($bio); ?></textarea>
                    </div>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label>Clinical Experience Details</label>
                        <textarea name="experience_details" rows="6" class="form-control"><?php echo htmlspecialchars($experience_details); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Update License/Certificate</label>
                        <input type="file" name="license" class="form-control">
                        <small style="color: #666;">Uploading a new license will set your status to Pending for review.</small>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn-save shadow-sm">
                <i class="fa-solid fa-floppy-disk"></i> Save All Changes
            </button>
        </form>
    </div>
</div>

<script>
    function toggleEdit() {
        const view = document.getElementById('viewSection');
        const edit = document.getElementById('editSection');
        const btnText = document.querySelector('.btn-edit-toggle span');
        const btnIcon = document.querySelector('.btn-edit-toggle i');
        const form = edit.querySelector('form');

        if (edit.style.display === 'block') {
            edit.style.display = 'none';
            view.style.display = 'block';
            btnText.innerText = 'Edit Profile';
            btnIcon.className = 'fa-solid fa-pen-to-square';
        } else {
            edit.style.display = 'block';
            view.style.display = 'none';
            btnText.innerText = 'Cancel Editing';
            btnIcon.className = 'fa-solid fa-xmark';
        }

        if (form && !form.dataset.validated) {
            form.addEventListener('submit', function(e) {
                const firstName = form.querySelector('input[name="first_name"]').value.trim();
                const lastName = form.querySelector('input[name="last_name"]').value.trim();
                if (!firstName || !lastName) {
                    e.preventDefault();
                    alert('First Name and Last Name are required.');
                }
            });
            form.dataset.validated = "true";
        }
    }
</script>

<?php require_once 'includes/footer.php'; ?>