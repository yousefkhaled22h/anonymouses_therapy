<?php
// session_room.php – Therapy Session Room with Zoom redirect & waiting screen
require_once 'includes/db_connect.php';
require_once 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$session_id = $_GET['id'] ?? null;
if (!$session_id) {
    header("Location: dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'Client';

// Fetch session details with payment check
$stmt = $pdo->prepare("
    SELECT ps.private_session_id, ps.session_date, ps.status, ps.payment_status,
           ps.communication_method, ps.amount, ps.duration_minutes,
           t.first_name as t_first, t.last_name as t_last, t.zoom_link, t.user_id as t_user_id,
           c.name as c_name, c.anonymous_id, c.user_id as c_user_id
    FROM private_sessions ps
    JOIN therapist t ON ps.therapist_id = t.therapist_id
    JOIN client c ON ps.client_id = c.client_id
    WHERE ps.private_session_id = ?
");
$stmt->execute([$session_id]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$session) {
    header("Location: dashboard.php");
    exit();
}

// Access control: only therapist or client of this session
$is_therapist = ($user_role === 'Therapist' && $session['t_user_id'] === $user_id);
$is_client = ($user_role === 'Client' && $session['c_user_id'] === $user_id);

if ($is_therapist) {
    // Check Verification Status
    $stmt_v = $pdo->prepare("SELECT verified FROM therapist WHERE user_id = ?");
    $stmt_v->execute([$user_id]);
    if (($stmt_v->fetchColumn() ?? 0) != 1) {
        header("Location: therapist_dashboard.php");
        exit();
    }
}

if (!$is_therapist && !$is_client) {
    header("Location: dashboard.php");
    exit();
}

// Payment must be confirmed
$payment_ok = in_array(strtolower($session['payment_status'] ?? ''), ['paid', 'completed']);

// Timing check
$session_ts = strtotime($session['session_date']);
$now_ts = time();
$mins_until = round(($session_ts - $now_ts) / 60);
$is_joinable = ($now_ts >= ($session_ts - 300)); // 5 min early allowed

$zoom_link = $session['zoom_link'] ?: 'https://zoom.us/test';
$partner = $is_therapist
    ? ($session['c_name'] ?: $session['anonymous_id'])
    : 'Dr. ' . $session['t_first'] . ' ' . $session['t_last'];

// Theme: blue for therapist, beige for client
$theme_primary = $is_therapist ? '#2563EB' : '#7C5A3A';
$theme_light = $is_therapist ? '#EFF6FF' : '#FDF6EE';
$theme_border = $is_therapist ? '#BFDBFE' : '#EDC9AF';
$theme_btn = $is_therapist ? 'linear-gradient(135deg,#1D4ED8,#3B82F6)' : 'linear-gradient(135deg,#7C5A3A,#A8784F)';
$theme_shadow = $is_therapist ? 'rgba(37,99,235,.28)' : 'rgba(124,90,58,.28)';
?>
<style>
    :root {
        --t-primary:
            <?php echo $theme_primary; ?>
        ;
        --t-light:
            <?php echo $theme_light; ?>
        ;
        --t-border:
            <?php echo $theme_border; ?>
        ;
        --t-shadow:
            <?php echo $theme_shadow; ?>
        ;
    }

    body {
        background: var(--t-light);
    }

    .room-wrap {
        max-width: 760px;
        margin: 0 auto;
        padding: 48px 24px 80px;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .room-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--t-light);
        border: 1.5px solid var(--t-border);
        color: var(--t-primary);
        font-size: .82rem;
        font-weight: 700;
        padding: 7px 18px;
        border-radius: 50px;
        margin-bottom: 24px;
        text-transform: uppercase;
        letter-spacing: .6px;
    }

    .room-card {
        background: white;
        border-radius: 28px;
        padding: 52px 48px;
        border: 1px solid var(--t-border);
        width: 100%;
        box-shadow: 0 12px 40px var(--t-shadow);
    }

    .room-icon {
        width: 88px;
        height: 88px;
        border-radius: 50%;
        margin: 0 auto 24px;
        background: var(--t-light);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.8rem;
        border: 3px solid var(--t-border);
    }

    .room-title {
        font-size: 1.8rem;
        font-weight: 900;
        color: #1e293b;
        margin-bottom: 10px;
    }

    .room-sub {
        color: #64748b;
        font-size: 1rem;
        margin-bottom: 30px;
        line-height: 1.6;
    }

    /* Timer countdown */
    .room-timer {
        background: var(--t-light);
        border-radius: 18px;
        padding: 20px 32px;
        border: 1px dashed var(--t-border);
        margin-bottom: 30px;
        display: inline-block;
    }

    .room-timer-label {
        font-size: .78rem;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        margin-bottom: 4px;
    }

    .room-timer-val {
        font-size: 2.4rem;
        font-weight: 900;
        color: var(--t-primary);
        font-variant-numeric: tabular-nums;
    }

    /* Pulse animation for waiting */
    @keyframes roomPulse {

        0%,
        100% {
            box-shadow: 0 0 0 0 color-mix(in srgb, var(--t-primary) 40%, transparent);
        }

        50% {
            box-shadow: 0 0 0 16px transparent;
        }
    }

    .room-icon.pulsing {
        animation: roomPulse 2s ease-in-out infinite;
    }

    /* Info rows */
    .room-info {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-bottom: 34px;
        text-align: left;
    }

    .room-info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 18px;
        background: #f8fafc;
        border-radius: 12px;
        border: 1px solid #f1f5f9;
        font-size: .92rem;
    }

    .room-info-label {
        color: #64748b;
        font-weight: 600;
    }

    .room-info-val {
        font-weight: 800;
        color: #1e293b;
    }

    /* Buttons */
    .btn-room-join {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 18px 44px;
        border-radius: 50px;
        font-size: 1.1rem;
        font-weight: 800;
        background:
            <?php echo $theme_btn; ?>
        ;
        color: white;
        border: none;
        cursor: pointer;
        box-shadow: 0 8px 24px var(--t-shadow);
        transition: all .25s;
        text-decoration: none;
        width: 100%;
        margin-bottom: 14px;
    }

    .btn-room-join:hover {
        transform: translateY(-3px);
        box-shadow: 0 14px 32px var(--t-shadow);
        color: white;
    }

    .btn-room-join:disabled {
        opacity: .4;
        cursor: not-allowed;
        transform: none;
        pointer-events: none;
    }

    .btn-room-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #64748b;
        font-size: .88rem;
        font-weight: 600;
        text-decoration: none;
        padding: 10px 20px;
        border-radius: 50px;
        border: 1.5px solid #e2e8f0;
        transition: all .2s;
        background: white;
    }

    .btn-room-back:hover {
        color: var(--t-primary);
        border-color: var(--t-border);
    }

    /* Payment lock banner */
    .payment-lock {
        background: #FEF9C3;
        border: 1px solid #FDE047;
        border-radius: 14px;
        padding: 16px 22px;
        margin-bottom: 22px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: .9rem;
        color: #854D0E;
        font-weight: 600;
        text-align: left;
    }

    /* Session live timer */
    .live-timer {
        font-size: 3rem;
        font-weight: 900;
        color: var(--t-primary);
        font-variant-numeric: tabular-nums;
        letter-spacing: -1px;
    }

    .status-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #22c55e;
        margin-right: 6px;
        animation: blink 1s ease-in-out infinite;
    }

    @keyframes blink {

        0%,
        100% {
            opacity: 1
        }

        50% {
            opacity: .3
        }
    }

    /* Fullscreen Active Session Styles */
    .active-session-ui {
        display: none;
        position: fixed;
        inset: 0;
        background: #FAF9F6;
        z-index: 10000;
        flex-direction: column;
        font-family: 'Inter', system-ui, sans-serif;
    }

    .active-header {
        height: 72px;
        padding: 0 24px;
        background: white;
        border-bottom: 1px solid #E5E7EB;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .partner-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .partner-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: #E5E7EB;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }

    .partner-name {
        font-weight: 700;
        color: #111827;
        margin: 0;
        font-size: 1.05rem;
    }

    .partner-status {
        color: #10B981;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 5px;
        margin: 0;
    }

    .partner-status::before {
        content: '';
        width: 8px;
        height: 8px;
        background: currentColor;
        border-radius: 50%;
    }

    .header-controls {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .ctrl-btn {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        border: 1px solid #E5E7EB;
        background: white;
        color: #4B5563;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 1.1rem;
    }

    .ctrl-btn:hover {
        background: #F3F4F6;
        color: #111827;
    }

    .ctrl-btn.active {
        background: #FDE68A;
        border-color: #FBBF24;
        color: #92400E;
    }

    .ctrl-btn.end-call {
        background: #FEE2E2;
        color: #B91C1C;
        border-color: #FECACA;
        width: auto;
        padding: 0 20px;
        gap: 8px;
        font-weight: 700;
        font-size: 0.95rem;
    }

    .ctrl-btn.end-call:hover {
        background: #FECACA;
    }

    .session-body {
        flex: 1;
        display: flex;
        overflow: hidden;
    }

    .session-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: #FAF9F6;
        position: relative;
    }

    .voice-call-card {
        text-align: center;
    }

    .voice-ring {
        width: 180px;
        height: 180px;
        border-radius: 50%;
        border: 8px solid #EADDD7;
        background: #EADDD7;
        margin: 0 auto 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 4rem;
        color: #5C4332;
        box-shadow: 0 0 0 15px rgba(234, 221, 215, 0.3);
    }

    .voice-call-title {
        font-size: 2rem;
        font-weight: 900;
        color: #1e293b;
        margin-bottom: 8px;
    }

    .voice-call-status {
        color: #64748b;
        font-size: 1.1rem;
        margin-bottom: 24px;
    }

    .indicators {
        display: flex;
        flex-direction: column;
        gap: 10px;
        align-items: center;
    }

    .ind-item {
        font-size: 0.95rem;
        color: #4B5563;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
    }

    .ind-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #10B981;
    }

    .session-aside {
        width: 380px;
        background: white;
        border-left: 1px solid #E5E7EB;
        display: flex;
        flex-direction: column;
    }

    .chat-header {
        padding: 20px 24px;
        border-bottom: 1px solid #F3F4F6;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .chat-header h3 {
        font-size: 1.1rem;
        font-weight: 800;
        margin: 0;
        color: #111827;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 24px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .msg {
        max-width: 85%;
        padding: 12px 16px;
        border-radius: 18px;
        font-size: 0.95rem;
        line-height: 1.5;
        position: relative;
    }

    .msg.the-reply {
        align-self: flex-start;
        background: #F3F4F6;
        color: #1F2937;
        border-bottom-left-radius: 4px;
    }

    .msg.my-send {
        align-self: flex-end;
        background: #EADDD7;
        color: #5C4332;
        border-bottom-right-radius: 4px;
    }

    .msg-time {
        display: block;
        font-size: 0.75rem;
        opacity: 0.6;
        margin-top: 5px;
    }

    .chat-input-area {
        padding: 20px;
        border-top: 1px solid #F3F4F6;
    }

    .chat-input-wrap {
        background: #F9FAFB;
        border: 1.5px solid #E5E7EB;
        border-radius: 14px;
        padding: 8px 12px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .chat-input-wrap input {
        flex: 1;
        border: none;
        background: transparent;
        outline: none;
        font-size: 0.95rem;
        color: #111827;
    }

    .btn-send {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: #5C4332;
        color: white;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Report Modal Center Positioning */
    .report-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.4);
        backdrop-filter: blur(4px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 20000;
        padding: 20px;
    }

    .report-modal {
        background: white;
        border-radius: 28px;
        width: 100%;
        max-width: 480px;
        padding: 40px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        border: 1px solid #E5E7EB;
        position: relative;
        text-align: left;
        animation: modalScale .3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes modalScale {
        from {
            transform: scale(0.9);
            opacity: 0;
        }

        to {
            transform: scale(1);
            opacity: 1;
        }
    }

    .report-close {
        position: absolute;
        top: 24px;
        right: 24px;
        cursor: pointer;
        font-size: 1.5rem;
        color: #94a3b8;
        transition: color .2s;
    }

    .report-close:hover {
        color: #1e293b;
    }

    .report-title {
        font-size: 1.5rem;
        font-weight: 900;
        color: #1e293b;
        margin-bottom: 8px;
    }

    .report-sub {
        color: #64748b;
        font-size: .95rem;
        margin-bottom: 28px;
    }

    .report-form-group {
        margin-bottom: 20px;
    }

    .report-label {
        display: block;
        font-size: .88rem;
        font-weight: 700;
        color: #334155;
        margin-bottom: 8px;
    }

    .report-select,
    .report-textarea {
        width: 100%;
        padding: 14px 16px;
        border-radius: 14px;
        border: 1.5px solid #E2E8F0;
        font-size: .95rem;
        color: #1e293b;
        outline: none;
        transition: border-color .2s;
    }

    .report-select:focus,
    .report-textarea:focus {
        border-color: #EF4444;
    }

    .btn-report-submit {
        width: 100%;
        padding: 16px;
        border-radius: 50px;
        background: #EF4444;
        color: white;
        font-weight: 800;
        font-size: 1rem;
        border: none;
        cursor: pointer;
        box-shadow: 0 8px 24px rgba(239, 68, 68, .3);
        transition: all .2s;
        margin-top: 10px;
    }

    .btn-report-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 32px rgba(239, 68, 68, .4);
    }

    .btn-report-session {
        background: transparent;
        border: 1.5px solid #FCA5A5;
        color: #EF4444;
        padding: 8px 16px;
        border-radius: 50px;
        font-weight: 700;
        font-size: .8rem;
        cursor: pointer;
        transition: all .2s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
        margin-left: 10px;
    }

    .btn-report-session:hover {
        background: #FEF2F2;
        transform: translateY(-1px);
    }
    /* Star Rating */
    .star-rating {
        display: flex;
        flex-direction: row-reverse;
        justify-content: center;
        gap: 10px;
        margin-bottom: 20px;
    }
    .star-rating input { display: none; }
    .star-rating label {
        font-size: 2.5rem;
        color: #cbd5e1;
        cursor: pointer;
        transition: color 0.2s;
    }
    .star-rating label:hover,
    .star-rating label:hover ~ label,
    .star-rating input:checked ~ label {
        color: #f59e0b;
    }
</style>

<div class="room-wrap">
    <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; margin-bottom: 28px;">
        <a href="<?php echo $back; ?>" class="btn-room-back">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        <?php if ($is_client): ?>
            <button onclick="openReportModal('<?php echo $session['t_user_id']; ?>')" class="btn-report-session">
                <i class="fas fa-flag"></i> Report Therapist
            </button>
        <?php endif; ?>
    </div>

    <?php if (!$payment_ok): ?>
        <!-- Payment not confirmed -->
        <div class="room-badge"><i class="fas fa-lock"></i> Payment Pending</div>
        <div class="room-card">
            <div class="room-icon" style="background:#FEF2F2; border-color:#FECACA;"><i class="fas fa-lock"
                    style="color:#EF4444; font-size:2rem;"></i></div>
            <div class="room-title">Session Locked</div>
            <div class="room-sub">Payment has not been confirmed yet. The session will unlock once payment is received.
            </div>
            <div class="payment-lock">
                <i class="fas fa-exclamation-triangle" style="font-size:1.3rem; color:#F59E0B;"></i>
                <span>Status: <strong>Awaiting Payment Confirmation</strong> — This page will auto-refresh every 15
                    seconds.</span>
            </div>
            <script>setTimeout(() => location.reload(), 15000);</script>
        </div>

    <?php elseif (!$is_joinable): ?>
        <!-- Too early — countdown -->
        <div class="room-badge"><i class="fas fa-clock"></i> Session Waiting Room</div>
        <div class="room-card">
            <div class="room-icon pulsing" id="roomIcon">
                <?php echo $is_therapist ? '🩺' : '🌿'; ?>
            </div>
            <div class="room-title"><?php echo htmlspecialchars($partner); ?></div>
            <div class="room-sub">Your session starts in:</div>

            <div class="room-timer">
                <div class="room-timer-label">Time Remaining</div>
                <div class="room-timer-val" id="countdown">--:--:--</div>
            </div>

            <div class="room-info">
                <div class="room-info-row">
                    <span class="room-info-label"><i class="fas fa-calendar-alt"></i> Scheduled</span>
                    <span class="room-info-val"><?php echo date('l, F j · H:i', $session_ts); ?></span>
                </div>
                <div class="room-info-row">
                    <span class="room-info-label"><i class="fas fa-video"></i> Method</span>
                    <span class="room-info-val"><?php echo htmlspecialchars($session['communication_method']); ?></span>
                </div>
                <div class="room-info-row">
                    <span class="room-info-label"><i class="fas fa-clock"></i> Duration</span>
                    <span class="room-info-val"><?php echo $session['duration_minutes']; ?> minutes</span>
                </div>
            </div>

            <button class="btn-room-join" disabled id="joinBtn">
                <i class="fas fa-clock"></i> Opens 5 minutes before session
            </button>
            <a href="<?php echo $back; ?>" class="btn-room-back" style="display:flex; justify-content:center;">
                <i class="fas fa-arrow-left"></i> Return to Dashboard
            </a>
        </div>

        <script>
            const target = <?php echo $session_ts; ?> * 1000 - 5 * 60 * 1000; // 5 min early
            function tick() {
                const diff = target - Date.now();
                if (diff <= 0) {
                    location.reload(); return;
                }
                const h = String(Math.floor(diff / 3600000)).padStart(2, '0');
                const m = String(Math.floor((diff % 3600000) / 60000)).padStart(2, '0');
                const s = String(Math.floor((diff % 60000) / 1000)).padStart(2, '0');
                document.getElementById('countdown').textContent = h + ':' + m + ':' + s;
            }
            tick(); setInterval(tick, 1000);
        </script>

    <?php else: ?>
        <!-- Session is live/joinable -->
        <div class="room-badge">
            <span class="status-dot"></span>
            Session <?php echo strtolower($session['status']) === 'active' ? 'In Progress' : 'Ready'; ?>
        </div>
        <div class="room-card">
            <div class="room-icon" style="border-color:var(--t-primary); background:var(--t-light);">
                <?php echo $is_therapist ? '🩺' : '🌿'; ?>
            </div>
            <div class="room-title">
                <?php echo htmlspecialchars($session['communication_method'] === 'Video Session' ? 'Video Session' : 'Therapy Session'); ?>
            </div>
            <div class="room-sub">You're connected with <strong><?php echo htmlspecialchars($partner); ?></strong>.<br>Both
                participants must press Start to begin the timer.</div>

            <div class="room-info">
                <div class="room-info-row">
                    <span class="room-info-label"><i class="far fa-calendar-alt"></i> Date</span>
                    <span class="room-info-val"><?php echo date('l, F j · H:i', $session_ts); ?></span>
                </div>
                <div class="room-info-row">
                    <span class="room-info-label"><i class="fas fa-user"></i> Partner</span>
                    <span class="room-info-val"><?php echo htmlspecialchars($partner); ?></span>
                </div>
                <div class="room-info-row">
                    <span class="room-info-label"><i class="fas fa-video"></i> Method</span>
                    <span class="room-info-val"><?php echo htmlspecialchars($session['communication_method']); ?></span>
                </div>
                <div class="room-info-row">
                    <span class="room-info-label"><i class="fas fa-hourglass-half"></i> Duration</span>
                    <span class="room-info-val"><?php echo $session['duration_minutes']; ?> min</span>
                </div>
            </div>

            <!-- Participant presence indicator -->
            <div id="presenceBar"
                style="background:white; border:1px solid #EADDD7; border-radius:50px; padding:12px 24px; margin-bottom:28px; font-size:.9rem; color:#5C4332; display:inline-flex; align-items:center; gap:10px; font-weight:700;">
                <i class="fas fa-spinner fa-spin" style="color:#A8784F;"></i> Checking participant status…
            </div>

            <div id="communicationPlaceholder"
                style="background:#FAFBFD; border:1px solid #E5E7EB; border-radius:24px; padding:40px; margin-bottom:30px; text-align:center;">
                <h4 style="margin:0 0 10px; color:#1e293b; font-weight:800;">Internal Communication Module <i
                        class="fas fa-signal" style="color:#22c55e"></i></h4>
                <p style="font-size: 0.95rem; color: #64748b; margin-bottom:30px;">WebRTC / WebSocket connection
                    established.</p>
                <div
                    style="background:white; border:1px solid #E5E7EB; border-radius:20px; padding:60px 20px; color:#94a3b8; font-weight:600; font-size:1.1rem; border:2px dashed #CBD5E1;">
                    <?php echo htmlspecialchars($session['communication_method']); ?> Window
                </div>
            </div>

            <div id="zoomWebMeetingSDKBox"
                style="display:none; width: 100%; height: 400px; background: #1a1a1a; border-radius: 20px; margin-bottom: 24px; text-align: center; color: white;">
                <div style="padding-top: 150px;">
                    <i class="fas fa-video" style="font-size: 3rem; color: #2D8CFF; margin-bottom: 15px;"></i>
                    <h4>Zoom Web Meeting SDK Active</h4>
                    <p style="font-size: 0.85rem; color: #aaa;">Meeting ID:
                        <?php echo htmlspecialchars($session['zoom_meeting_id'] ?? 'Pending'); ?></p>
                </div>
            </div>

            <button class="btn-room-join" id="joinBtn" onclick="startSession()">
                <i
                    class="fas <?php echo $session['communication_method'] === 'Video Session' ? 'fa-video' : ($session['communication_method'] === 'Voice Call' ? 'fa-microphone' : 'fa-comments'); ?>"></i>
                Start <?php echo htmlspecialchars($session['communication_method']); ?>
            </button>

            <a href="<?php echo $back; ?>" class="btn-room-back" style="display:flex; justify-content:center;">
                <i class="fas fa-arrow-left"></i> Return to Dashboard
            </a>
        </div>

    <?php endif; ?>
</div>

<!-- Premium Active Session UI -->
<div class="active-session-ui" id="activeSessionUI">
    <header class="active-header">
        <div class="partner-info">
            <div class="partner-avatar">
                <?php echo $is_therapist ? '🌿' : '🩺'; ?>
            </div>
            <div>
                <p class="partner-name"><?php echo htmlspecialchars($partner); ?></p>
                <p class="partner-status">Active now</p>
            </div>
        </div>
        <div class="header-controls">
            <button class="ctrl-btn" title="Mute Microphone" onclick="toggleMic(this)"><i
                    class="fas fa-microphone"></i></button>
            <button class="ctrl-btn" title="Deafen Audio" onclick="toggleAudio(this)"><i
                    class="fas fa-volume-up"></i></button>
            <button class="ctrl-btn active" title="Chat" onclick="toggleChat()"><i
                    class="fas fa-comment-alt"></i></button>
            <button class="ctrl-btn end-call" onclick="endSession()"><i class="fas fa-phone-slash"></i> End
                Session</button>
        </div>
    </header>
    <div class="session-body">
        <main class="session-main">
            <!-- Dynamic Module Container -->
            <div id="moduleContent"
                style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                <!-- Voice Call Layout (Default for Voice) -->
                <div class="voice-call-card" id="voiceLayout">
                    <div class="voice-ring">
                        <i class="fas fa-microphone"></i>
                    </div>
                    <h2 class="voice-call-title">Voice Call Active</h2>
                    <p class="voice-call-status">Session in progress...</p>
                    <div class="indicators">
                        <div class="ind-item"><span class="ind-dot"></span> Microphone on</div>
                        <div class="ind-item"><span class="ind-dot"></span> Audio on</div>
                    </div>
                </div>
                <!-- Zoom Container (will be moved here if video) -->
                <div id="zoomContainer" style="display:none; width: 100%; height: 100%;"></div>
            </div>
        </main>
        <aside class="session-aside" id="chatSidebar">
            <header class="chat-header">
                <h3><i class="far fa-comment-alt"></i> Chat</h3>
                <div style="display:flex; gap:10px; color:#64748b; font-size:0.9rem;">
                    <i class="fas fa-expand-arrows-alt" style="cursor:pointer;"></i>
                    <i class="fas fa-times" style="cursor:pointer;" onclick="toggleChat()"></i>
                </div>
            </header>
            <div class="chat-messages" id="chatStream">
                <div class="msg the-reply">
                    Hello! I'm glad you're here. How are you feeling today?
                    <span class="msg-time">12:10 PM</span>
                </div>
            </div>
            <div class="chat-input-area">
                <div class="chat-input-wrap">
                    <input type="text" id="chatInput" placeholder="Message <?php echo explode(' ', $partner)[0]; ?>...">
                    <button class="btn-send" onclick="sendMessage()"><i class="fas fa-paper-plane"></i></button>
                </div>
            </div>
        </aside>
    </div>
</div>

<script>
    const SESSION_ID = '<?php echo $session_id; ?>';
    const MY_USER_ID = '<?php echo $user_id; ?>';
    const IS_VIDEO = <?php echo ($session['communication_method'] === 'Video Session') ? 'true' : 'false'; ?>;
    const IS_VOICE = <?php echo (strpos(strtolower($session['communication_method']), 'voice') !== false) ? 'true' : 'false'; ?>;
    let lastMessageId = 0;
    let isMicOn = true;
    let isAudioOn = true;
    
    // WebRTC variables
    let localStream = null;
    let peerConnection = null;
    let signalingQueue = [];
    const configuration = {
        iceServers: [
            { urls: 'stun:stun.l.google.com:19302' },
            { urls: 'stun:stun1.l.google.com:19302' },
            { urls: 'stun:stun2.l.google.com:19302' }
        ]
    };

    // Mark user as joined
    fetch('api/session/join.php?id=' + SESSION_ID, { method: 'POST' }).catch(() => { });

    function checkPresence() {
        fetch('api/session/status.php?id=' + SESSION_ID)
            .then(r => r.json())
            .then(data => {
                // Check if session was ended by the other person
                const sessStatus = (data.session_status || '').toLowerCase();
                if (sessStatus === 'completed') {
                    if (<?php echo $is_client ? 'true' : 'false'; ?>) {
                        openFeedbackModal();
                    } else {
                        window.location.href = 'dashboard.php';
                    }
                    return;
                }
                if (sessStatus === 'cancelled') {
                    alert("The session has been cancelled.");
                    window.location.href = 'dashboard.php';
                    return;
                }

                const bar = document.getElementById('presenceBar');
                const btns = [document.getElementById('joinBtn')];
                const count = data.joined_count || 0;
                const both = count >= 2;
                if (bar) {
                    bar.innerHTML = both
                        ? '<i class="fas fa-check-circle" style="color:#22c55e;"></i> Both participants are present — session is live!'
                        : '<i class="fas fa-spinner fa-spin" style="color:#A8784F;"></i> Waiting for other participant… (' + count + '/2)';
                    if (both) bar.style.borderColor = "#22c55e";
                }
            }).catch(() => { });
    }
    checkPresence();
    setInterval(checkPresence, 4000);

    function startSession() {
        fetch('api/session/join.php?id=' + SESSION_ID + '&start=1', { method: 'POST' }).catch(() => { });

        if (IS_VIDEO) {
            document.getElementById('activeSessionUI').style.display = 'flex';
            document.querySelector('.room-wrap').style.display = 'none';
            document.getElementById('chatSidebar').style.display = 'none';
            
            const stream = document.getElementById('chatStream');
            const inputArea = document.querySelector('.chat-input-area');
            if (inputArea) inputArea.style.display = 'none';
            
            stream.innerHTML = `
                <div style="text-align: center; padding: 60px 20px; font-family: 'Inter', sans-serif;">
                    <div style="background: #eff6ff; width: 100px; height: 100px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px;">
                        <i class="fas fa-video" style="font-size: 3rem; color: #2563eb;"></i>
                    </div>
                    <h2 style="color: #1e293b; font-weight: 700; margin-bottom: 15px; font-size: 1.8rem;">Zoom Waiting Room</h2>
                    <p style="color: #64748b; font-size: 1.1rem; margin-bottom: 35px; max-width: 500px; margin-left: auto; margin-right: auto;">
                        Your session is ready. Please click below to open Zoom. You will be placed in a waiting room until the host admits you.
                    </p>
                    <a href="zoom_launch.php?session_id=${SESSION_ID}" target="_blank" class="btn btn-primary" style="background: #2563eb; color: white; border: none; padding: 15px 40px; font-size: 1.1rem; font-weight: 600; border-radius: 50px; text-decoration: none; display: inline-block; box-shadow: 0 4px 14px rgba(37,99,235,0.3); transition: all 0.3s;">
                        <i class="fas fa-external-link-alt" style="margin-right: 8px;"></i> Join Main Session
                    </a>
                </div>
            `;
            return;
        }

        document.getElementById('activeSessionUI').style.display = 'flex';
        document.querySelector('.room-wrap').style.display = 'none';

        // Start fetching messages
        setInterval(fetchMessages, 3000);

        if (IS_VOICE) {
            initializeVoiceCall();
        }
    }

    async function initializeVoiceCall() {
        const statusEl = document.querySelector('.voice-call-status');
        
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            if (statusEl) statusEl.textContent = 'Voice calling requires a secure HTTPS connection.';
            alert('Voice calling requires a secure connection (HTTPS). Please reload the page using https://');
            return;
        }

        if (statusEl) statusEl.textContent = 'Requesting microphone access...';
        
        try {
            localStream = await navigator.mediaDevices.getUserMedia({ audio: true });
            if (statusEl) statusEl.textContent = 'Microphone active. Connecting to peer...';
            
            const micInd = document.querySelector('.indicators .ind-item:first-child');
            if (micInd) {
                micInd.innerHTML = '<span class="ind-dot"></span> Microphone active';
            }
        } catch (err) {
            console.error('Error getting user media:', err);
            if (statusEl) statusEl.textContent = 'Microphone permission denied.';
            alert('Could not access microphone. Please ensure your browser microphone permissions are enabled.');
            return;
        }

        peerConnection = new RTCPeerConnection(configuration);

        // Add local tracks
        localStream.getTracks().forEach(track => {
            peerConnection.addTrack(track, localStream);
        });

        // Remote stream track handler
        peerConnection.ontrack = (event) => {
            const remoteStream = event.streams[0];
            let remoteAudio = document.getElementById('remoteAudio');
            if (!remoteAudio) {
                remoteAudio = document.createElement('audio');
                remoteAudio.id = 'remoteAudio';
                remoteAudio.autoplay = true;
                document.body.appendChild(remoteAudio);
            }
            remoteAudio.srcObject = remoteStream;
            if (statusEl) statusEl.textContent = 'Connected! Voice call active.';
        };

        // ICE candidate handler
        peerConnection.onicecandidate = (event) => {
            if (event.candidate) {
                sendSignal({
                    type: 'candidate',
                    candidate: event.candidate
                });
            }
        };

        // Caller vs Callee roles: Client generates offer
        const IS_CLIENT_ROLE = <?php echo $is_client ? 'true' : 'false'; ?>;
        if (IS_CLIENT_ROLE) {
            try {
                if (statusEl) statusEl.textContent = 'Generating call invitation...';
                const offer = await peerConnection.createOffer();
                await peerConnection.setLocalDescription(offer);
                sendSignal({
                    type: 'offer',
                    sdp: offer.sdp
                });
            } catch (err) {
                console.error('Error creating WebRTC offer:', err);
            }
        }
    }

    function sendSignal(data) {
        const formData = new FormData();
        formData.append('session_id', SESSION_ID);
        formData.append('message', '__SIGNAL__:' + JSON.stringify(data));
        
        fetch('api/session/send_message.php', {
            method: 'POST',
            body: formData
        }).catch(err => console.error('Signal transmission failed:', err));
    }

    async function handleIncomingSignal(signal) {
        if (!peerConnection) {
            signalingQueue.push(signal);
            return;
        }

        try {
            if (signal.type === 'offer') {
                await peerConnection.setRemoteDescription(new RTCSessionDescription({
                    type: 'offer',
                    sdp: signal.sdp
                }));

                const answer = await peerConnection.createAnswer();
                await peerConnection.setLocalDescription(answer);
                sendSignal({
                    type: 'answer',
                    sdp: answer.sdp
                });

                // Drain candidate queue
                while (signalingQueue.length > 0) {
                    const qSignal = signalingQueue.shift();
                    if (qSignal.type === 'candidate') {
                        await peerConnection.addIceCandidate(new RTCIceCandidate(qSignal.candidate));
                    }
                }
            } else if (signal.type === 'answer') {
                await peerConnection.setRemoteDescription(new RTCSessionDescription({
                    type: 'answer',
                    sdp: signal.sdp
                }));
            } else if (signal.type === 'candidate') {
                if (peerConnection.remoteDescription && peerConnection.remoteDescription.type) {
                    await peerConnection.addIceCandidate(new RTCIceCandidate(signal.candidate));
                } else {
                    signalingQueue.push(signal);
                }
            }
        } catch (err) {
            console.error('WebRTC signaling application failed:', err);
        }
    }

    function fetchMessages() {
        fetch('api/session/get_messages.php?id=' + SESSION_ID + '&last_id=' + lastMessageId)
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success' && data.messages.length > 0) {
                    const stream = document.getElementById('chatStream');
                    data.messages.forEach(m => {
                        if (m.message_id <= lastMessageId) return;

                        // Parse WebRTC signaling signals
                        if (m.message.startsWith('__SIGNAL__:')) {
                            try {
                                const signalData = JSON.parse(m.message.substring(11));
                                if (m.user_id !== MY_USER_ID) {
                                    handleIncomingSignal(signalData);
                                }
                            } catch (e) {
                                console.error('Signaling payload parse error:', e);
                            }
                            lastMessageId = m.message_id;
                            return;
                        }

                        const isMe = m.user_id === MY_USER_ID;
                        const div = document.createElement('div');
                        div.className = 'msg ' + (isMe ? 'my-send' : 'the-reply');

                        const time = new Date(m.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                        div.innerHTML = m.message + '<span class="msg-time">' + time + '</span>';

                        stream.appendChild(div);
                        lastMessageId = m.message_id;
                    });
                    stream.scrollTop = stream.scrollHeight;
                }
            });
    }

    function toggleChat() {
        const sidebar = document.getElementById('chatSidebar');
        sidebar.style.display = sidebar.style.display === 'none' ? 'flex' : 'none';
    }

    function sendMessage() {
        const input = document.getElementById('chatInput');
        const msgText = input.value.trim();
        if (!msgText) return;

        const formData = new FormData();
        formData.append('session_id', SESSION_ID);
        formData.append('message', msgText);

        fetch('api/session/send_message.php', {
            method: 'POST',
            body: formData
        })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    input.value = '';
                    fetchMessages(); // Immediately poll
                }
            });
    }

    function toggleMic(btn) {
        isMicOn = !isMicOn;
        btn.classList.toggle('active', !isMicOn);
        btn.title = isMicOn ? 'Mute Microphone' : 'Unmute Microphone';
        btn.innerHTML = isMicOn ? '<i class="fas fa-microphone"></i>' : '<i class="fas fa-microphone-slash"></i>';
        
        // Mute actual local stream tracks
        if (localStream) {
            localStream.getAudioTracks().forEach(track => {
                track.enabled = isMicOn;
            });
        }
        
        const micInd = document.querySelector('.indicators .ind-item:first-child');
        if (micInd) {
            micInd.innerHTML = isMicOn 
                ? '<span class="ind-dot"></span> Microphone active' 
                : '<span class="ind-dot" style="background:#ef4444"></span> Microphone muted';
        }
    }

    function toggleAudio(btn) {
        isAudioOn = !isAudioOn;
        btn.classList.toggle('active', !isAudioOn);
        btn.title = isAudioOn ? 'Deafen Audio' : 'Undeafen Audio';
        btn.innerHTML = isAudioOn ? '<i class="fas fa-volume-up"></i>' : '<i class="fas fa-volume-mute"></i>';
        
        // Mute remote audio playback element
        const remoteAudio = document.getElementById('remoteAudio');
        if (remoteAudio) {
            remoteAudio.muted = !isAudioOn;
        }
        
        const audioInd = document.querySelector('.indicators .ind-item:last-child');
        if (audioInd) {
            audioInd.innerHTML = isAudioOn 
                ? '<span class="ind-dot"></span> Audio on' 
                : '<span class="ind-dot" style="background:#ef4444"></span> Audio deafened';
        }
    }

    function endSession() {
        const confirmMsg = "Are you sure you want to end this session? It will be marked as completed.";
        if (confirm(confirmMsg)) {
            fetch('api/session/finish.php?id=' + SESSION_ID, { method: 'POST' })
                .then(() => {
                    if (<?php echo $is_client ? 'true' : 'false'; ?>) {
                        openFeedbackModal();
                    } else {
                        window.location.href = 'dashboard.php';
                    }
                });
        }
    }

    function openFeedbackModal() {
        document.getElementById('feedbackModalOverlay').style.display = 'flex';
    }

    function submitFeedback() {
        const rating = document.querySelector('input[name="rating"]:checked')?.value;
        const therapistRating = document.querySelector('input[name="therapist_rating"]:checked')?.value;
        const feedback = document.getElementById('feedbackText').value;
        
        if (!rating) {
            alert("Please select a rating for the session.");
            return;
        }
        if (!therapistRating) {
            alert("Please select a rating for the therapist.");
            return;
        }

        const formData = new FormData();
        formData.append('session_id', SESSION_ID);
        formData.append('rating', rating);
        formData.append('therapist_rating', therapistRating);
        formData.append('feedback', feedback);

        const btn = document.getElementById('feedbackSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

        fetch('api/session/submit_feedback.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.href = 'dashboard.php';
            } else {
                alert(data.message);
                btn.disabled = false;
                btn.innerHTML = 'Submit Feedback';
            }
        })
        .catch(() => {
            alert('An error occurred.');
            btn.disabled = false;
            btn.innerHTML = 'Submit Feedback';
        });
    }

    // Handle Enter key in chat
    document.getElementById('chatInput').addEventListener('keypress', function (e) {
        if (e.key === 'Enter') sendMessage();
    });
    function openReportModal(userId) {
        document.getElementById('reportedUserId').value = userId;
        document.getElementById('reportModalOverlay').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeReportModal() {
        document.getElementById('reportModalOverlay').style.display = 'none';
        document.body.style.overflow = '';
    }

    function handleReportSubmit(e) {
        e.preventDefault();
        const btn = document.getElementById('reportSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

        const formData = new FormData(e.target);

        fetch('api/report/submit_report.php', {
            method: 'POST',
            body: formData
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    closeReportModal();
                } else {
                    alert(data.message);
                }
            })
            .catch(() => alert('An error occurred.'))
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = 'Submit Report';
            });
    }
</script>

<!-- Report Modal -->
<div id="reportModalOverlay" class="report-modal-overlay">
    <div class="report-modal">
        <span class="report-close" onclick="closeReportModal()">&times;</span>
        <h3 class="report-title">Report Content</h3>
        <p class="report-sub">Please describe the issue with this session or the therapist.</p>

        <form id="reportForm" onsubmit="handleReportSubmit(event)">
            <input type="hidden" id="reportedUserId" name="reported_user_id">

            <div class="report-form-group">
                <label class="report-label">Reason for Report</label>
                <select class="report-select" name="report_type" required>
                    <option value="" disabled selected>Select a reason...</option>
                    <option value="Professionalism">Lack of Professionalism</option>
                    <option value="Harassment">Harassment or Abuse</option>
                    <option value="Inappropriate Content">Inappropriate Content</option>
                    <option value="Technical Issues">Technical Issues</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="report-form-group">
                <label class="report-label">Details</label>
                <textarea class="report-textarea" name="description" rows="4" placeholder="Provide more context here..."
                    required></textarea>
            </div>

            <button type="submit" class="btn-report-submit" id="reportSubmitBtn">Submit Report</button>
        </form>
    </div>
</div>

<!-- Feedback Modal (for clients) -->
<div id="feedbackModalOverlay" class="report-modal-overlay">
    <div class="report-modal" style="text-align: center;">
        <h3 class="report-title">How was your session?</h3>

        <p class="report-sub" style="font-weight: 600; margin-bottom: 5px; margin-top: 10px;">Rate the Session</p>
        <div class="star-rating">
            <input type="radio" name="rating" id="star5" value="5"><label for="star5">★</label>
            <input type="radio" name="rating" id="star4" value="4"><label for="star4">★</label>
            <input type="radio" name="rating" id="star3" value="3"><label for="star3">★</label>
            <input type="radio" name="rating" id="star2" value="2"><label for="star2">★</label>
            <input type="radio" name="rating" id="star1" value="1"><label for="star1">★</label>
        </div>

        <p class="report-sub" style="font-weight: 600; margin-bottom: 5px; margin-top: 10px;">Rate the Therapist</p>
        <div class="star-rating">
            <input type="radio" name="therapist_rating" id="t_star5" value="5"><label for="t_star5">★</label>
            <input type="radio" name="therapist_rating" id="t_star4" value="4"><label for="t_star4">★</label>
            <input type="radio" name="therapist_rating" id="t_star3" value="3"><label for="t_star3">★</label>
            <input type="radio" name="therapist_rating" id="t_star2" value="2"><label for="t_star2">★</label>
            <input type="radio" name="therapist_rating" id="t_star1" value="1"><label for="t_star1">★</label>
        </div>

        <div class="report-form-group">
            <textarea class="report-textarea" id="feedbackText" rows="3" placeholder="Share your experience (optional)..." style="margin-top: 10px;"></textarea>
        </div>

        <button type="button" class="btn-report-submit" id="feedbackSubmitBtn" onclick="submitFeedback()" style="background: var(--t-primary); box-shadow: 0 8px 24px var(--t-shadow); margin-top: 10px;">Submit Feedback</button>
        <button type="button" class="btn-room-back" onclick="window.location.href='dashboard.php'" style="margin-top: 10px; width: 100%; justify-content: center;">Skip</button>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>