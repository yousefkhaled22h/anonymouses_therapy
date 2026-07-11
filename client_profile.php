<?php
// client_profile.php
$body_class = 'role-client';
require_once 'includes/header.php';
require_once 'includes/db_connect.php';

// 1. Role Check
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Client') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// 2. Handle Form Submissions (Stay on the same page, will refresh data below)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Avatar Upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['avatar']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $new_filename = uniqid('avatar_', true) . '.' . $ext;
            $upload_dir = 'assets/uploads/avatars/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $dest = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) {
                try {
                    $stmt = $pdo->prepare("UPDATE client SET avatar_path = ? WHERE user_id = ?");
                    $stmt->execute([$dest, $user_id]);
                    $_SESSION['avatar'] = $dest;
                    $message = "Avatar updated successfully!";
                } catch (PDOException $e) {
                    $error = "Database error: " . $e->getMessage();
                }
            } else {
                $error = "Failed to upload file.";
            }
        } else {
            $error = "Invalid file type.";
        }
    }

    // Delete Avatar
    if (isset($_POST['action']) && $_POST['action'] === 'delete_avatar') {
        try {
            $stmt = $pdo->prepare("SELECT avatar_path FROM client WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $old_avatar = $stmt->fetchColumn();
            
            if ($old_avatar && file_exists($old_avatar)) {
                unlink($old_avatar);
            }
            
            $stmt = $pdo->prepare("UPDATE client SET avatar_path = NULL WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $message = "Avatar deleted successfully!";
        } catch (PDOException $e) {
            $error = "Error deleting avatar: " . $e->getMessage();
        }
    }

    // Visibility & Name Update
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $new_name = $_POST['full_name'] ?? '';
        $mode = $_POST['display_mode'] ?? 'Anonymous';

        try {
            // Update Client Name
            $stmt = $pdo->prepare("UPDATE client SET name = ? WHERE user_id = ?");
            $stmt->execute([$new_name, $user_id]);

            // Update Visibility in User
            $stmt = $pdo->prepare("SELECT privacy_settings FROM user WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $settings = json_decode($stmt->fetchColumn() ?? '{}', true);
            $settings['display_mode'] = $mode;

            $stmt = $pdo->prepare("UPDATE user SET privacy_settings = ? WHERE user_id = ?");
            $stmt->execute([json_encode($settings), $user_id]);

            // Update Session Name for Consistency
            $stmt = $pdo->prepare("SELECT anonymous_id FROM client WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $anon_id = $stmt->fetchColumn();
            $_SESSION['name'] = ($mode === 'Anonymous') ? ($anon_id ?? 'Anonymous') : $new_name;

            $message = "Profile updated successfully!";
        } catch (PDOException $e) {
            $error = "Error updating profile: " . $e->getMessage();
        }
    }

    // Password Update
    if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $old_pass = $_POST['current_password'];
        $new_pass = $_POST['new_password'];

        $stmt = $pdo->prepare("SELECT password_hash FROM user WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $hash = $stmt->fetchColumn();

        if (password_verify($old_pass, $hash)) {
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE user SET password_hash = ? WHERE user_id = ?");
            $stmt->execute([$new_hash, $user_id]);
            $message = "Password changed successfully!";
        } else {
            $error = "Current password incorrect.";
        }
    }
}

// 3. SECURELY FETCH ALL DATA
try {
    // Get User info (email, joined date)
    $stmt = $pdo->prepare("SELECT email, privacy_settings, created_at FROM user WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user_meta = $stmt->fetch();

    // Get Client info
    $stmt = $pdo->prepare("SELECT client_id, name, anonymous_id, avatar_path FROM client WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $client_data = $stmt->fetch();

    // Stats
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM private_sessions WHERE client_id = ?");
    $stmt->execute([$client_data['client_id']]);
    $total_sessions = $stmt->fetchColumn();

    $settings = json_decode($user_meta['privacy_settings'] ?? '{}', true);
    $current_mode = $settings['display_mode'] ?? 'Anonymous';

    $is_anonymous = ($current_mode === 'Anonymous');
    $display_name = $is_anonymous ? ($client_data['anonymous_id'] ?? 'Anonymous User') : ($client_data['name'] ?? 'User');
    $real_name = $client_data['name'] ?? '';
    $email = $user_meta['email'];
    $joined_date = date('F Y', strtotime($user_meta['created_at']));
    $avatar = $client_data['avatar_path'] ?: 'assets/images/default_avatar.png';

    // Force Session Sync for Consistency
    $_SESSION['name'] = $display_name;
    $_SESSION['avatar'] = $avatar;

    // Calculate Initials for Avatar Placeholder
    $name_parts = preg_split('/[\s\-_]+/', $display_name);
    $initials = '';
    if (count($name_parts) > 1) {
        $initials = strtoupper(substr($name_parts[0], 0, 1) . substr($name_parts[1], 0, 1));
    } else {
        preg_match_all('/[A-Z]/', $display_name, $capitals);
        if (count($capitals[0]) > 1) {
            $initials = $capitals[0][0] . $capitals[0][1];
        } else {
            $initials = strtoupper(substr($display_name, 0, 1));
        }
    }

    // Fetch Mood History (Recent 7 entries) from client_mood_history table
    $pdo->exec("CREATE TABLE IF NOT EXISTS client_mood_history (
        history_id VARCHAR(50) PRIMARY KEY,
        client_id VARCHAR(50) NOT NULL,
        mood VARCHAR(50) NOT NULL,
        recorded_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $stmt = $pdo->prepare("SELECT mood AS content, recorded_date FROM client_mood_history WHERE client_id = ? ORDER BY created_at DESC LIMIT 12");
    $stmt->execute([$client_data['client_id']]);
    $client_mood_history = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Profile Error: " . $e->getMessage());
}
?>

<style>
    :root {
        --profile-tan: #D6BC9D;
        --profile-tan-light: #E8D9C5;
        --profile-brown-text: #5D4037;
        --profile-bg: #FDFBF8;
    }

    .profile-page-wrapper {
        padding: 40px 20px;
        background: var(--profile-bg);
        min-height: 90vh;
    }

    .profile-container {
        max-width: 1000px;
        margin: 0 auto;
    }

    /* HEADER CARD */
    .profile-header-card {
        background-color: #D6BC9D;
        background-image: linear-gradient(135deg, rgba(93, 64, 55, 0.75) 0%, rgba(166, 138, 108, 0.45) 100%), url('assets/images/profile_banner.png');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        border-radius: 24px;
        padding: 60px 40px;
        display: flex;
        align-items: center;
        gap: 35px;
        color: white;
        position: relative;
        box-shadow: 0 10px 30px rgba(181, 150, 118, 0.2);
        margin-bottom: 30px;
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
        flex-shrink: 0;
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
        border: 1px solid #F0E6D8;
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
        color: #8D6E63;
        font-size: 0.85rem;
        margin-bottom: 5px;
        display: block;
    }

    .ic-value {
        color: #3E2723;
        font-size: 1.1rem;
        font-weight: 600;
    }

    .status-badge {
        background: #E8F5E9;
        color: #2E7D32;
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
        border: 1px solid #F0E6D8;
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
        color: #5D4037;
    }

    .form-control {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #D7CCC8;
        border-radius: 10px;
        background: #FAFAFA;
        transition: border 0.2s;
    }

    .form-control:focus {
        border-color: #A1887F;
        outline: none;
    }

    .btn-save {
        background: #8D6E63;
        color: white;
        padding: 12px 25px;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        width: 100%;
        font-weight: 600;
        margin-top: 10px;
    }

    .btn-save:hover {
        background: #795548;
    }

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

        <?php if ($message): ?>
            <div
                style="background: #E8F5E9; color: #2E7D32; padding: 15px 25px; border-radius: 15px; margin-bottom: 25px; border: 1px solid #C8E6C9;">
                ✓ <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div
                style="background: #FFEBEE; color: #C62828; padding: 15px 25px; border-radius: 15px; margin-bottom: 25px; border: 1px solid #FFCDD2;">
                ✕ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- VIEW MODE -->
        <div id="viewSection">
            <!-- Header Card -->
            <div class="profile-header-card">
                <div class="p-avatar">
                    <?php if ($avatar && $avatar != 'assets/images/default_avatar.png'): ?>
                        <img class="img-fluid"  src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar">
                    <?php else: ?>
                        <span style="font-weight: 700; letter-spacing: -1px;"><?php echo htmlspecialchars($initials); ?></span>
                    <?php endif; ?>
                </div>
                <div class="p-info">
                    <h1><?php echo htmlspecialchars($display_name); ?></h1>
                    <p><?php echo htmlspecialchars($email); ?></p>
                    <div class="p-badges">
                        <div class="p-badge">Client</div>
                        <?php if ($is_anonymous): ?>
                            <div class="p-badge">
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                </svg>
                                Anonymous
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <button class="btn-edit-toggle" onclick="toggleEdit()">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                    <span class="btn-edit-text">Edit Profile</span>
                </button>
            </div>

            <!-- Stats Grid -->
            <div class="profile-grid">
                <div class="info-card">
                    <div class="ic-header">
                        <div class="ic-icon">👤</div>
                        <h2>Personal Information</h2>
                    </div>
                    <div class="ic-item">
                        <span class="ic-label"><?php echo $is_anonymous ? 'Display Name (Alias)' : 'Full Name'; ?></span>
                        <span class="ic-value"><?php echo htmlspecialchars($display_name); ?></span>
                    </div>
                    <?php if ($is_anonymous): ?>
                    <div class="ic-item">
                        <span class="ic-label">Real Name (Private)</span>
                        <span class="ic-value"><?php echo htmlspecialchars($real_name ?: 'Not Provided'); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="ic-item">
                        <span class="ic-label">Email Address</span>
                        <span class="ic-value"><?php echo htmlspecialchars($email); ?></span>
                    </div>
                    <div class="ic-item">
                        <span class="ic-label">Account Type</span>
                        <span class="ic-value">Client</span>
                    </div>
                </div>

                <div class="info-card">
                    <div class="ic-header">
                        <div class="ic-icon">📅</div>
                        <h2>Account Stats</h2>
                    </div>
                    <div class="ic-item">
                        <span class="ic-label">Member Since</span>
                        <span class="ic-value"><?php echo $joined_date; ?></span>
                    </div>
                    <div class="ic-item">
                        <span class="ic-label">Total Sessions</span>
                        <span class="ic-value"><?php echo $total_sessions; ?></span>
                    </div>
                    <div class="ic-item">
                        <span class="ic-label">Status</span>
                        <span class="ic-value"><span class="status-badge">Active</span></span>
                    </div>
                </div>

                <div class="info-card" style="grid-column: 1 / -1;">
                    <div class="ic-header">
                        <div class="ic-icon" style="background: #e0f2fe; color: #0284c7;">📊</div>
                        <h2>Mood Tracker History</h2>
                    </div>
                    <?php if (empty($client_mood_history)): ?>
                        <p style="color: #64748b; font-size: 0.9rem;">No mood records found yet. Log your mood on the dashboard!</p>
                    <?php else: ?>
                        <div style="display: flex; gap: 15px; overflow-x: auto; padding-bottom: 10px;">
                            <?php foreach ($client_mood_history as $log): 
                                $m = $log['content'];
                                $icon = '<i class="fas fa-face-meh"></i>';
                                $color = '#e0f2fe';
                                $text_color = '#0284c7';
                                if ($m === 'Happy') { $icon = '<i class="fas fa-face-laugh-beam"></i>'; $color = '#fef9c3'; $text_color = '#ca8a04'; }
                                elseif ($m === 'Calm') { $icon = '<i class="fas fa-face-smile-beam"></i>'; $color = '#dcfce7'; $text_color = '#16a34a'; }
                                elseif ($m === 'Neutral') { $icon = '<i class="fas fa-face-meh"></i>'; $color = '#f1f5f9'; $text_color = '#64748b'; }
                                elseif ($m === 'Sad') { $icon = '<i class="fas fa-face-frown"></i>'; $color = '#ede9fe'; $text_color = '#7c3aed'; }
                                elseif ($m === 'Stressed') { $icon = '<i class="fas fa-face-tired"></i>'; $color = '#fee2e2'; $text_color = '#dc2626'; }
                            ?>
                                <div style="min-width: 90px; text-align: center; background: #fafafa; border: 1px solid #f0f0f0; border-radius: 15px; padding: 15px 10px;">
                                    <div style="width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px auto; background: <?php echo $color; ?>; color: <?php echo $text_color; ?>; font-size: 1.2rem;">
                                        <?php echo $icon; ?>
                                    </div>
                                    <div style="font-weight: 700; font-size: 0.85rem; color: #334155; margin-bottom: 4px;"><?php echo htmlspecialchars($m); ?></div>
                                    <div style="font-size: 0.7rem; color: #94a3b8;"><?php echo date('M j', strtotime($log['recorded_date'])); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- EDIT MODE -->
        <div id="editSection">
            <h2 style="margin-bottom: 25px; color: var(--profile-brown-text);">Edit Profile</h2>
            <div class="edit-grid">
                <!-- Basic Info -->
                <div class="form-section">
                    <h3>Basic Information</h3>
                    <form action="" method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="form-group">
                            <label><?php echo $is_anonymous ? 'Real Name (Private)' : 'Full Name'; ?></label>
                            <input type="text" name="full_name" class="form-control"
                                value="<?php echo htmlspecialchars($real_name); ?>" placeholder="Enter your real name">
                            <?php if ($is_anonymous): ?>
                            <p style="font-size: 0.8rem; color: #8D6E63; margin-top: 5px;">This name is only visible to you. Others will see your alias: <strong><?php echo htmlspecialchars($client_data['anonymous_id']); ?></strong></p>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label>Display Mode</label>
                            <select name="display_mode" class="form-control">
                                <option value="Anonymous" <?php echo $is_anonymous ? 'selected' : ''; ?>>Anonymous
                                    (Hidden)</option>
                                <option value="Real" <?php echo !$is_anonymous ? 'selected' : ''; ?>>Real Name (Visible)
                                </option>
                            </select>
                        </div>
                        <button type="submit" class="btn-save">Save Changes</button>
                    </form>
                </div>

                <!-- Avatar & Security -->
                <div class="form-section">
                    <h3>Avatar & Security</h3>

                    <!-- Avatar Upload -->
                    <form action="" method="POST" enctype="multipart/form-data" style="margin-bottom: 30px;">
                        <div class="form-group">
                            <label>Profile Picture</label>
                            <input type="file" name="avatar" class="form-control">
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" class="btn-save" style="background: #A1887F;">Upload Avatar</button>
                            <?php if ($client_data['avatar_path']): ?>
                                <button type="submit" name="action" value="delete_avatar" formnovalidate class="btn-save" style="background: #ef4444;" onclick="return confirm('Are you sure you want to delete your avatar?');">Delete Avatar</button>
                            <?php endif; ?>
                        </div>
                    </form>

                    <!-- Password -->
                    <form action="" method="POST">
                        <input type="hidden" name="action" value="change_password">
                        <div class="form-group">
                            <label>Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn-save" style="background: #A1887F;">Change Password</button>
                    </form>
                </div>
            </div>

            <button onclick="toggleEdit()"
                style="margin-top: 25px; background: none; border: 1px solid #D7CCC8; padding: 10px 20px; border-radius: 10px; cursor: pointer; color: #5D4037; font-weight: 600;">
                ← Back to View Mode
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
</script>

<?php require_once 'includes/footer.php'; ?>