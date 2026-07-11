<?php
// zoom_launch.php — Smart Zoom launcher
require_once 'includes/db_connect.php';
require_once 'includes/header.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$session_id = $_GET['session_id'] ?? null;
$direct_url = $_GET['url'] ?? null;

$zoom_url     = '';
$session_date = '';
$partner_name = '';

if ($session_id) {
    $stmt = $pdo->prepare("
        SELECT ps.private_session_id, ps.session_date, ps.status, ps.payment_status,
               ps.communication_method, ps.duration_minutes, ps.zoom_join_url as zoom_link,
               t.first_name as t_first, t.last_name as t_last, t.user_id as t_user_id,
               c.name as c_name, c.anonymous_id, c.user_id as c_user_id
        FROM private_sessions ps
        JOIN therapist t ON ps.therapist_id = t.therapist_id
        JOIN client c    ON ps.client_id = c.client_id
        WHERE ps.private_session_id = ?
    ");
    $stmt->execute([$session_id]);
    $sess = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($sess) {
        $zoom_url     = $sess['zoom_link'] ?? '';
        $session_date = date('l, F j · H:i', strtotime($sess['session_date']));
        $role         = $_SESSION['role'] ?? 'Client';
        $partner_name = $role === 'Therapist'
            ? ($sess['c_name'] ?: $sess['anonymous_id'])
            : 'Dr. ' . $sess['t_first'] . ' ' . $sess['t_last'];

        // Mark session active + record join
        $pdo->prepare("UPDATE private_sessions SET status='Active' WHERE private_session_id=?")->execute([$session_id]);
        
        $col = ($role === 'Therapist') ? 'therapist_joined_at' : 'client_joined_at';
        $pdo->prepare("UPDATE private_sessions SET {$col} = NOW() WHERE private_session_id = ?")->execute([$session_id]);
    }
}

if ($direct_url) $zoom_url = urldecode($direct_url);

$placeholder_urls = ['https://zoom.us/test', 'https://zoom.us/', '', '0', 'null'];
$is_valid_url = $zoom_url
    && !in_array(trim($zoom_url), $placeholder_urls)
    && filter_var($zoom_url, FILTER_VALIDATE_URL);

$role         = $_SESSION['role'] ?? 'Client';
$is_therapist = $role === 'Therapist';
$theme_color  = '#2D8CFF'; // Zoom Blue
$theme_bg     = $is_therapist ? '#F0F7FF' : '#FDF6EE';
$theme_border = $is_therapist ? '#BFDBFE' : '#EDC9AF';
$theme_btn    = 'linear-gradient(135deg, #2D8CFF, #1E66C8)';
$theme_shadow = 'rgba(45,140,255,.28)';
$back_url     = $is_therapist ? 'therapist_dashboard.php' : 'dashboard.php';
?>
<style>
body { background: <?php echo $theme_bg; ?>; }
.zl-wrap {
    max-width: 660px; margin: 0 auto; padding: 48px 24px 80px;
    display: flex; flex-direction: column; align-items: center; text-align: center;
}
.zl-icon {
    width: 110px; height: 110px; border-radius: 28px;
    background: white;
    margin: 0 auto 28px;
    display: flex; align-items: center; justify-content: center; font-size: 3.5rem;
    box-shadow: 0 8px 28px <?php echo $theme_shadow; ?>;
    border: 1px solid #E5E7EB;
}
.zl-title { font-size: 1.9rem; font-weight: 900; color: #1e293b; margin-bottom: 10px; }
.zl-sub   { color: #64748b; font-size: 1rem; margin-bottom: 36px; line-height: 1.6; }
.zl-card {
    background: white; border-radius: 24px; padding: 36px;
    border: 1px solid <?php echo $theme_border; ?>;
    box-shadow: 0 12px 40px <?php echo $theme_shadow; ?>;
    width: 100%; margin-bottom: 24px;
}
@keyframes zoomSpin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }
.zl-spinner {
    width: 56px; height: 56px; border-radius: 50%;
    border: 4px solid #E5E7EB;
    border-top-color: <?php echo $theme_color; ?>;
    animation: zoomSpin 0.9s linear infinite; margin: 0 auto 18px;
}
.zl-status { font-size: 1rem; font-weight: 700; color: <?php echo $theme_color; ?>; margin-bottom: 24px; }
.btn-zoom-join {
    display: flex; align-items: center; justify-content: center; gap: 10px;
    padding: 17px 28px; border-radius: 50px; font-size: 1rem; font-weight: 800;
    background: <?php echo $theme_btn; ?>; color: white; text-decoration: none;
    box-shadow: 0 8px 24px <?php echo $theme_shadow; ?>;
    transition: all .22s; width: 100%; margin-bottom: 12px;
}
.btn-zoom-join:hover { transform: translateY(-2px); color: white; box-shadow: 0 12px 32px <?php echo $theme_shadow; ?>; }
.btn-back {
    display: inline-flex; align-items: center; gap: 6px;
    color: #94a3b8; font-size: .85rem; font-weight: 600; text-decoration: none;
    padding: 8px 18px; border-radius: 50px; border: 1.5px solid #e2e8f0;
    background: white; transition: all .2s;
}
.btn-back:hover { color: <?php echo $theme_color; ?>; border-color: <?php echo $theme_border; ?>; }
.zl-info { display:flex; flex-direction:column; gap:10px; margin-bottom:28px; text-align:left; }
.zl-info-row {
    display:flex; justify-content:space-between; align-items:center;
    padding:12px 16px; background:#f8fafc; border-radius:12px;
    border:1px solid #f1f5f9; font-size:.88rem;
}
.zl-info-label { color:#64748b; font-weight:600; }
.zl-info-val   { font-weight:800; color:#1e293b; }
.status-dot {
    display:inline-block; width:10px; height:10px; border-radius:50%;
    background:#22c55e; margin-right:6px;
    animation: blink 1s ease-in-out infinite;
}
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:.3} }
</style>

<div class="zl-wrap">
    <a href="<?php echo $back_url; ?>" class="btn-back" style="align-self:flex-start; margin-bottom:30px;">
        <i class="fas fa-arrow-left"></i> Back
    </a>

    <?php if ($is_valid_url): ?>
    <!-- Valid Zoom link — launch -->
    <div class="zl-icon">
        <i class="fas fa-video" style="color: #2D8CFF;"></i>
    </div>
    <div class="zl-title">Joining Zoom Meeting</div>
    <div class="zl-sub">
        <?php if ($partner_name): ?>
        Session with <strong><?php echo htmlspecialchars($partner_name); ?></strong>
        <?php if ($session_date): ?> · <?php echo $session_date; ?><?php endif; ?><br>
        <?php endif; ?>
        Your meeting is ready. Click the button to join the session:
    </div>

    <div class="zl-card">
        <div class="zl-spinner" id="spinner"></div>
        <div class="zl-status" id="statusText">Opening Zoom…</div>

        <?php if ($session_date): ?>
        <div class="zl-info">
            <div class="zl-info-row">
                <span class="zl-info-label"><i class="far fa-calendar-alt"></i> Session</span>
                <span class="zl-info-val"><?php echo $session_date; ?></span>
            </div>
            <div class="zl-info-row">
                <span class="zl-info-label"><i class="fas fa-user"></i> With</span>
                <span class="zl-info-val"><?php echo htmlspecialchars($partner_name); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <a href="<?php echo htmlspecialchars($zoom_url); ?>"
           class="btn-zoom-join" id="btnZoom">
            <i class="fas fa-video"></i> Launch Zoom Meeting
        </a>
        <p style="font-size:.77rem; color:#94a3b8; margin-top:8px;">
            If you have the Zoom app installed, it should open automatically. Otherwise, you can join via your browser.
        </p>
    </div>

    <a href="<?php echo $back_url; ?>" class="btn-back" style="justify-content:center;">
        <i class="fas fa-arrow-left"></i> Return to Dashboard
    </a>

    <script>
    window.addEventListener('DOMContentLoaded', function() {
        const zoomUrl  = <?php echo json_encode($zoom_url); ?>;
        const spinner   = document.getElementById('spinner');
        const statusTxt = document.getElementById('statusText');

        setTimeout(function() {
            statusTxt.textContent = 'Redirecting to Zoom…';
            window.location.href = zoomUrl;
        }, 1200);

        setTimeout(function() {
            if (spinner) spinner.style.display = 'none';
            if (statusTxt) statusTxt.textContent = 'If Zoom didn\'t open, click the button above.';
        }, 5000);
    });
    </script>

    <?php else: ?>
    <!-- No valid link -->
    <div class="zl-icon" style="background:#FEF2F2;">
        <i class="fas fa-exclamation-triangle" style="color:#FCA5A5;"></i>
    </div>
    <div class="zl-title">Preparing Your Room</div>
    <div class="zl-sub">
        We are preparing your secure Zoom link. Please wait a moment.
    </div>
    <div class="zl-card">
        <div class="zl-setup" style="background:#FEF2F2;border-color:#FECACA;">
            <p style="color:#7F1D1D; margin:0;"><i class="fas fa-clock"></i> We're generating the meeting details. If this takes too long, please refresh or contact support.</p>
        </div>
        <button onclick="window.location.reload()" class="btn-zoom-join" style="margin-bottom:15px; background:linear-gradient(135deg, #10B981, #059669);">
            <i class="fas fa-sync-alt"></i> Refresh Page
        </button>
        <a href="<?php echo $back_url; ?>" class="btn-back" style="display:flex;justify-content:center;">
            <i class="fas fa-arrow-left"></i> Return to Dashboard
        </a>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
