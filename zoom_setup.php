<?php
// zoom_setup.php — Dedicated Zoom Meeting Room Setup for Therapists
require_once 'includes/db_connect.php';
require_once 'includes/header.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Therapist') {
    header("Location: login.php"); exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$msg_type = 'success';

// Fetch current zoom_link and therapist name
$stmt = $pdo->prepare("SELECT therapist_id, zoom_link, first_name, last_name FROM therapist WHERE user_id = ?");
$stmt->execute([$user_id]);
$therapist = $stmt->fetch(PDO::FETCH_ASSOC);
$therapist_id = $therapist['therapist_id'];
$current_link = $therapist['zoom_link'] ?? '';
$name = trim(($therapist['first_name'] ?? '') . ' ' . ($therapist['last_name'] ?? ''));

// Determine connection status
$placeholder_urls = ['https://zoom.us/test', 'https://zoom.us/', '', '0', 'null'];
$is_connected = $current_link && !in_array(trim($current_link), $placeholder_urls);

// Handle form save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_link') {
        $new_link = trim($_POST['zoom_link'] ?? '');
        // Validate URL format
        if (!empty($new_link) && !filter_var($new_link, FILTER_VALIDATE_URL)) {
            $message = 'Invalid URL format. Please enter a valid Zoom meeting link.';
            $msg_type = 'error';
        } elseif (!empty($new_link) && !preg_match('#zoom\.us#i', $new_link)) {
            $message = 'The link does not appear to be a Zoom URL. Please use a link from zoom.us';
            $msg_type = 'error';
        } else {
            $stmt = $pdo->prepare("UPDATE therapist SET zoom_link = ? WHERE user_id = ?");
            $stmt->execute([$new_link, $user_id]);
            $current_link = $new_link;
            $is_connected = $new_link && !in_array(trim($new_link), $placeholder_urls);
            $message = $new_link ? '✓ Zoom meeting link saved successfully!' : 'Zoom link removed.';
        }
    } elseif ($_POST['action'] === 'remove_link') {
        $stmt = $pdo->prepare("UPDATE therapist SET zoom_link = '' WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $current_link = '';
        $is_connected = false;
        $message = 'Zoom link removed.';
        $msg_type = 'info';
    }
}

// Extract meeting ID from link for display
$meeting_id = '';
if ($is_connected && preg_match('#zoom\.us/j/(\d+)#', $current_link, $m)) {
    $mid = $m[1];
    // Format as XXX XXXX XXXX
    $meeting_id = preg_replace('/(\d{3})(\d{4})(\d{4})/', '$1 $2 $3', $mid);
    if (!$meeting_id) $meeting_id = $mid;
}
?>
<style>
body { background: #F0F7FF; }
.zs-wrap  { max-width: 820px; margin: 0 auto; padding: 40px 24px 80px; }
.zs-hero  {
    background: linear-gradient(135deg, #1D4ED8 0%, #2563EB 50%, #3B82F6 100%);
    border-radius: 28px; padding: 48px 44px; color: white;
    display: flex; align-items: center; gap: 32px; margin-bottom: 32px;
    box-shadow: 0 16px 48px rgba(37,99,235,.28); position: relative; overflow: hidden;
}
.zs-hero::before {
    content: ''; position: absolute; top: -60px; right: -60px;
    width: 220px; height: 220px; border-radius: 50%;
    background: rgba(255,255,255,.08);
}
.zs-hero-icon {
    width: 84px; height: 84px; border-radius: 22px;
    background: rgba(255,255,255,.18); backdrop-filter: blur(8px);
    display: flex; align-items: center; justify-content: center; font-size: 2.6rem;
    border: 2px solid rgba(255,255,255,.25); flex-shrink: 0;
}
.zs-hero-text h1 { font-size: 1.9rem; font-weight: 900; margin: 0 0 8px; }
.zs-hero-text p  { opacity: .85; font-size: .95rem; margin: 0; line-height: 1.6; }

/* Status badge */
.zs-status-badge {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 8px 20px; border-radius: 50px; font-size: .82rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: .6px; margin-top: 14px;
}
.zs-status-badge.connected    { background: rgba(16,185,129,.25); color: #A7F3D0; }
.zs-status-badge.disconnected { background: rgba(251,191,36,.25);  color: #FDE68A; }

/* Cards */
.zs-card {
    background: white; border-radius: 24px; padding: 36px;
    border: 1px solid #BFDBFE; margin-bottom: 24px;
    box-shadow: 0 8px 28px rgba(37,99,235,.07);
}
.zs-card h2 {
    font-size: 1.15rem; font-weight: 800; color: #1e293b;
    margin: 0 0 22px; display: flex; align-items: center; gap: 12px;
}
.zs-card h2 .ic {
    width: 40px; height: 40px; border-radius: 12px; background: #EFF6FF;
    display: flex; align-items: center; justify-content: center;
    color: #2563EB; font-size: 1rem;
}

/* Current link display */
.zs-link-display {
    background: #F0FDF4; border: 1.5px solid #A7F3D0; border-radius: 14px;
    padding: 18px 22px; display: flex; align-items: center;
    justify-content: space-between; gap: 16px; margin-bottom: 18px;
}
.zs-link-url { font-size: .88rem; color: #065F46; font-weight: 700; word-break: break-all; }
.meeting-id-pill {
    display: inline-flex; align-items: center; gap: 8px;
    background: #EFF6FF; border: 1.5px solid #BFDBFE;
    border-radius: 50px; padding: 8px 18px; font-size: .85rem;
    font-weight: 800; color: #1D4ED8; margin-bottom: 18px;
}

/* Input group */
.zs-input-group {
    display: flex; gap: 12px; align-items: center;
}
.zs-input {
    flex: 1; padding: 14px 18px; border: 2px solid #BFDBFE; border-radius: 14px;
    font-size: .95rem; color: #1e293b; outline: none;
    transition: border-color .2s;
}
.zs-input:focus { border-color: #2563EB; }

/* Buttons */
.btn-zs-save {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 14px 26px; border-radius: 14px; font-size: .9rem; font-weight: 800;
    background: linear-gradient(135deg, #1D4ED8, #3B82F6); color: white;
    border: none; cursor: pointer; transition: all .22s; white-space: nowrap;
    box-shadow: 0 4px 16px rgba(37,99,235,.3);
}
.btn-zs-save:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(37,99,235,.35); }
.btn-zs-remove {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 11px 22px; border-radius: 50px; font-size: .82rem; font-weight: 700;
    background: white; color: #EF4444; border: 1.5px solid #FECACA; cursor: pointer;
    transition: all .2s;
}
.btn-zs-remove:hover { background: #FEF2F2; }

/* Steps */
.zs-steps { display: flex; flex-direction: column; gap: 16px; }
.zs-step {
    display: flex; gap: 16px; align-items: flex-start;
    padding: 18px 20px; background: #F8FAFC; border-radius: 14px;
    border: 1px solid #E2E8F0;
}
.zs-step-num {
    width: 36px; height: 36px; border-radius: 50%; background: #2563EB;
    color: white; font-weight: 900; font-size: 1rem;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.zs-step-body h4 { margin: 0 0 5px; font-size: .92rem; font-weight: 800; color: #1e293b; }
.zs-step-body p  { margin: 0; font-size: .85rem; color: #64748b; line-height: 1.5; }
.zs-step-body a  { color: #2563EB; font-weight: 700; }

/* Alert */
.zs-alert {
    border-radius: 14px; padding: 16px 22px; margin-bottom: 24px;
    display: flex; align-items: center; gap: 12px; font-size: .9rem; font-weight: 600;
}
.zs-alert.success { background: #F0FDF4; color: #065F46; border: 1px solid #A7F3D0; }
.zs-alert.error   { background: #FEF2F2; color: #991B1B; border: 1px solid #FECACA; }
.zs-alert.info    { background: #EFF6FF; color: #1E40AF; border: 1px solid #BFDBFE; }

/* Test button */
.btn-test-zoom {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 11px 22px; border-radius: 50px; font-size: .85rem; font-weight: 700;
    background: #F0FDF4; color: #059669; border: 1.5px solid #A7F3D0;
    text-decoration: none; transition: all .2s;
}
.btn-test-zoom:hover { background: #D1FAE5; color: #047857; }

.back-link {
    display: inline-flex; align-items: center; gap: 8px;
    color: #64748b; font-size: .88rem; font-weight: 600;
    text-decoration: none; margin-bottom: 28px;
    padding: 9px 18px; border-radius: 50px;
    border: 1.5px solid #E2E8F0; background: white; transition: all .2s;
}
.back-link:hover { color: #2563EB; border-color: #BFDBFE; }
</style>

<div class="zs-wrap">
    <a href="therapist_profile.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Back to Profile
    </a>

    <?php if ($message): ?>
    <div class="zs-alert <?php echo $msg_type; ?>">
        <i class="fas <?php echo $msg_type === 'success' ? 'fa-check-circle' : ($msg_type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'); ?>"></i>
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <!-- Hero -->
    <div class="zs-hero">
        <div class="zs-hero-icon">📹</div>
        <div class="zs-hero-text">
            <h1>Zoom Meeting Room</h1>
            <p>Connect your personal Zoom meeting room so clients can join your video sessions with one click — from the app or the browser.</p>
            <span class="zs-status-badge <?php echo $is_connected ? 'connected' : 'disconnected'; ?>">
                <i class="fas <?php echo $is_connected ? 'fa-circle-check' : 'fa-circle-xmark'; ?>"></i>
                <?php echo $is_connected ? 'Connected' : 'Not Connected'; ?>
            </span>
        </div>
    </div>

    <?php if ($is_connected): ?>
    <!-- Current connection info -->
    <div class="zs-card">
        <h2><span class="ic"><i class="fas fa-link"></i></span> Current Zoom Room</h2>

        <?php if ($meeting_id): ?>
        <div class="meeting-id-pill">
            <i class="fas fa-video"></i> Meeting ID: <?php echo htmlspecialchars($meeting_id); ?>
        </div>
        <?php endif; ?>

        <div class="zs-link-display">
            <span class="zs-link-url"><?php echo htmlspecialchars($current_link); ?></span>
            <a href="<?php echo htmlspecialchars($current_link); ?>" target="_blank" rel="noopener" class="btn-test-zoom">
                <i class="fas fa-external-link-alt"></i> Test Link
            </a>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="remove_link">
            <button type="submit" class="btn-zs-remove" onclick="return confirm('Remove your Zoom link? Clients will not be able to join video sessions until you add a new one.')">
                <i class="fas fa-unlink"></i> Remove Link
            </button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Update / Enter link -->
    <div class="zs-card">
        <h2><span class="ic"><i class="fas fa-<?php echo $is_connected ? 'pen' : 'plug'; ?>"></i></span>
            <?php echo $is_connected ? 'Update Meeting Link' : 'Add Your Zoom Meeting Link'; ?>
        </h2>
        <form method="POST">
            <input type="hidden" name="action" value="save_link">
            <p style="font-size:.88rem;color:#64748b;margin:0 0 18px;">
                Paste your permanent Zoom personal meeting link below. This is the link you share when starting a meeting from your Zoom account.
            </p>
            <div class="zs-input-group">
                <input type="url" name="zoom_link" class="zs-input"
                    value="<?php echo htmlspecialchars($current_link); ?>"
                    placeholder="https://zoom.us/j/1234567890?pwd=yourpassword"
                    required>
                <button type="submit" class="btn-zs-save">
                    <i class="fas fa-save"></i> Save
                </button>
            </div>
            <small style="display:block;margin-top:10px;color:#94a3b8;font-size:.78rem;">
                <i class="fas fa-shield-alt"></i> Your link is only shared with clients who have paid and confirmed a Video Session with you.
            </small>
        </form>
    </div>

    <!-- How to find your link -->
    <div class="zs-card">
        <h2><span class="ic"><i class="fas fa-question"></i></span> How to Get Your Zoom Link</h2>
        <div class="zs-steps">
            <div class="zs-step">
                <div class="zs-step-num">1</div>
                <div class="zs-step-body">
                    <h4>Open Zoom and sign in</h4>
                    <p>Download the <a href="https://zoom.us/download" target="_blank">Zoom desktop app</a> or go to <a href="https://zoom.us" target="_blank">zoom.us</a> in your browser and log in with your account.</p>
                </div>
            </div>
            <div class="zs-step">
                <div class="zs-step-num">2</div>
                <div class="zs-step-body">
                    <h4>Go to "Meetings" → "Personal Meeting Room"</h4>
                    <p>In the app: click <strong>Meetings</strong> in the sidebar, then select <strong>Personal Meeting Room</strong> tab.<br>On the web: My Account → Meetings → Personal Meeting Room.</p>
                </div>
            </div>
            <div class="zs-step">
                <div class="zs-step-num">3</div>
                <div class="zs-step-body">
                    <h4>Copy the invitation link</h4>
                    <p>Click <strong>"Copy Invitation"</strong> or <strong>"Copy Link"</strong>. The URL will look like:<br>
                    <code style="background:#F1F5F9;padding:3px 10px;border-radius:6px;font-size:.82rem;">https://zoom.us/j/1234567890?pwd=xxxxx</code></p>
                </div>
            </div>
            <div class="zs-step">
                <div class="zs-step-num">4</div>
                <div class="zs-step-body">
                    <h4>Paste it above and click Save</h4>
                    <p>Once saved, clients can join your Zoom room directly — the platform will try to open the Zoom app first, then fall back to the browser automatically.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
