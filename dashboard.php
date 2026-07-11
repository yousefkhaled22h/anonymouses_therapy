<?php
// dashboard.php
require_once 'includes/header.php';
require_once 'includes/db_connect.php';
require_once 'includes/dashboard_components.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'Client';

if ($user_role !== 'Client') {
    if ($user_role === 'Therapist') {
        header("Location: therapist_profile.php");
    } else {
        header("Location: index.php");
    }
    exit();
}
$display_name = $_SESSION['name'] ?? 'User';

$sessions = [];

try {
    // 2. Paid Sessions (One-on-One)
    // Table: private_sessions (private_session_id, client_id, therapist_id, session_date, amount, status...)
    // Join with clients/therapists tables for names.

    if ($user_role == 'Client') {
        // We need client_id for querying private_sessions, not user_id.
        // I stored client_id in session during login, but if old session, fetch it.
        $client_id = $_SESSION['client_id'] ?? null;
        if (!$client_id) {
            $stmt = $pdo->prepare("SELECT client_id FROM client WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $client_id = $stmt->fetchColumn();
            $_SESSION['client_id'] = $client_id;
        }

        // Fetch today's logged moods
        $today_moods = [];
        try {
            $stmt_m = $pdo->prepare("SELECT mood FROM client_mood_history WHERE client_id = ? AND recorded_date = ?");
            $stmt_m->execute([$client_id, date('Y-m-d')]);
            $today_moods = $stmt_m->fetchAll(PDO::FETCH_COLUMN) ?: [];
        } catch (Exception $e) {
            // Table might not exist yet
        }

        $stmt = $pdo->prepare("
            SELECT ps.private_session_id as id, ps.session_date, 'One-on-One' as type, 
            CONCAT(t.first_name, ' ', t.last_name) as partner_name, ps.status as session_status,
            ps.communication_method, IFNULL(ps.early_start_requested, 0) as early_start_requested, 
            ps.proposed_datetime, IFNULL(ps.reschedule_requested, 0) as reschedule_requested, ps.therapist_id,
            ps.amount, ps.payment_status, ps.early_start_to,
            ps.cancel_reason, IFNULL(ps.cancellation_acknowledged, 0) as cancellation_acknowledged
            FROM private_sessions ps 
            JOIN therapist t ON ps.therapist_id = t.therapist_id 
            WHERE ps.client_id = ? 
              AND (
                  (LOWER(ps.status) IN ('active', 'scheduled', 'reserved', 'pending', 'confirmed', 'pending_reschedule') AND DATE_ADD(ps.session_date, INTERVAL ps.duration_minutes MINUTE) >= DATE_SUB(NOW(), INTERVAL 30 MINUTE))
                  OR (LOWER(ps.status) = 'cancelled' AND IFNULL(ps.cancellation_acknowledged, 0) = 0 AND (ps.cancel_reason IS NULL OR ps.cancel_reason NOT LIKE 'Cancelled by Client%'))
              )
        ");
        $stmt->execute([$client_id]);
        $sessions = array_merge($sessions, $stmt->fetchAll(PDO::FETCH_ASSOC));

    } elseif ($user_role == 'Therapist') {
        $therapist_id = $_SESSION['therapist_id'] ?? null;
        if (!$therapist_id) {
            $stmt = $pdo->prepare("SELECT therapist_id FROM therapist WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $therapist_id = $stmt->fetchColumn();
            $_SESSION['therapist_id'] = $therapist_id;
        }

        $stmt = $pdo->prepare("
            SELECT ps.private_session_id as id, ps.session_date, 'One-on-One' as type, 
            c.anonymous_id as partner_name 
            FROM private_sessions ps 
            JOIN client c ON ps.client_id = c.client_id 
            WHERE ps.therapist_id = ? AND ps.status = 'Active'
              AND DATE_ADD(ps.session_date, INTERVAL ps.duration_minutes MINUTE) >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
        ");
        $stmt->execute([$therapist_id]);
        $sessions = array_merge($sessions, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // 3. Group Sessions
    // Schema: group_sessions (group_session_id, topic, session_date...)
    // Schema: group_session_participants (group_session_id, user_id). 
    // Participant table has user_id (not client/therapist id) according to my last read of schema (Step 80 view line 126: user_id VARCHAR).

    $stmt = $pdo->prepare("
        SELECT gs.group_session_id as id, gs.session_date, 'Group Session' as type, 
        gs.topic as partner_name, gs.status as session_status
        FROM group_sessions gs 
        JOIN group_session_participants gsp ON gs.group_session_id = gsp.group_session_id 
        WHERE gsp.user_id = ? AND LOWER(gs.status) IN ('active', 'scheduled')
          AND gs.session_date >= (NOW() - INTERVAL 2 HOUR)
    ");
    $stmt->execute([$user_id]);
    $upcoming_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // $sessions = [];
}
?>

<div class="dashboard-wrapper">
    <?php render_sidebar('dashboard'); ?>

    <!-- Main Content -->
    <div class="dashboard-main">
        <div class="container-fluid">
            <div class="dashboard-header-row" style="display: flex; gap: 20px; align-items: stretch; margin-bottom: 30px;">
                <button id="sidebarToggle" class="sidebar-toggle-inline" aria-label="Toggle Sidebar" style="align-self: flex-start; margin-top: 10px;">
                    <i id="toggleIcon" class="fa-solid fa-bars-staggered"></i>
                </button>
                <div class="dashboard-banner-card">
                    <h2 style="font-family: 'Lora', serif; font-size: 2.2rem; color: #333; margin-bottom: 15px; font-weight: 500;"><?php echo __('Welcome back,'); ?> <?php echo htmlspecialchars($display_name); ?>! 👋</h2>
                    <p style="font-size: 1.05rem; color: #555;"><?php echo __('Here\'s your wellness overview for today,'); ?><br>
                    <strong style="color: #8D6E63; font-weight: 700; font-size: 1.1rem;"><?php echo date('l, F jS'); ?>.</strong></p>
                </div>
            </div>

            <div class="dashboard-grid">
                <!-- Group Therapy Hub -->
                <div class="card animate-up delay-100 card-group-hub" style="position: relative; overflow: hidden; min-height: 220px; display: flex; flex-direction: column;">
                    <h3 class="card-title" style="border: none; font-size: 1.15rem; margin-bottom: 15px;">
                        <span class="card-icon" style="background: #F4EBE1; color: #8D6E63; border-radius: 50%; padding: 12px; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;"><i class="fa-solid fa-users"></i></span> 
                        <?php echo __('Group Therapy Hub'); ?>
                    </h3>
                    <div style="flex: 1; z-index: 2; max-width: 70%; display: flex; flex-direction: column; justify-content: space-between;">
                        <p style="color: #555; font-size: 0.95rem; margin-bottom: 20px; line-height: 1.6;"><?php echo __('Explore therapeutic group sessions led by professionals.'); ?></p>
                        <div>
                            <a href="group_management.php" class="btn btn-primary" style="background: #A68A6C; border-color: #A68A6C; border-radius: 12px; font-size: 0.9rem; padding: 10px 20px; font-weight: 600;"><?php echo __('Browse All Rooms'); ?> <i class="fa-solid fa-chevron-right" style="font-size: 0.75rem; margin-left: 5px;"></i></a>
                        </div>
                    </div>
                    <img src="assets/images/group_therapy_card_clean.png" style="position: absolute; bottom: 10px; right: 10px; height: 75px; width: auto; max-width: 30%; object-fit: contain; z-index: 1; mix-blend-mode: multiply;">
                </div>

                <!-- Daily Challenges Summary -->
                <div class="card animate-up delay-200 card-challenges" style="position: relative; overflow: hidden; min-height: 220px; display: flex; flex-direction: column;">
                    <h3 class="card-title" style="border: none; display: flex; justify-content: space-between; align-items: center; font-size: 1.15rem; margin-bottom: 15px;">
                        <span style="display: flex; align-items: center; gap: 10px;">
                            <span class="card-icon" style="background: #F4EBE1; color: #8D6E63; border-radius: 50%; padding: 12px; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;"><i class="fa-solid fa-trophy"></i></span> 
                            <?php echo __('Challenges'); ?>
                        </span>
                        <?php
                            $stmt_p = $pdo->prepare("SELECT points FROM client WHERE client_id = ?");
                            $stmt_p->execute([$client_id]);
                            $pts = $stmt_p->fetchColumn() ?: 0;
                        ?>
                        <span style="font-size: 0.85rem; background: #F4EBE1; color: #8D6E63; padding: 6px 14px; border-radius: 20px; font-weight: 700;">
                            <?php echo number_format($pts); ?> pts
                        </span>
                    </h3>
                    <div style="flex: 1; z-index: 2; max-width: 70%; display: flex; flex-direction: column; justify-content: space-between;">
                        <p style="color: #555; font-size: 0.95rem; margin-bottom: 20px; line-height: 1.6;"><?php echo __('Complete daily tasks to earn points and improve your wellness journey!'); ?></p>
                        <div>
                            <a href="challenges.php" class="btn btn-primary" style="background: #A68A6C; border-color: #A68A6C; border-radius: 12px; font-size: 0.9rem; padding: 10px 20px; font-weight: 600;"><?php echo __('View All Challenges'); ?> <i class="fa-solid fa-chevron-right" style="font-size: 0.75rem; margin-left: 5px;"></i></a>
                        </div>
                    </div>
                    <img src="assets/images/challenges_card.png" style="position: absolute; bottom: 10px; right: 10px; height: 75px; width: auto; max-width: 30%; object-fit: contain; z-index: 1; mix-blend-mode: multiply;">
                </div>


                <!-- Mood Tracker -->
                <div class="card animate-up delay-300 card-mood">
                    <h3 class="card-title" style="border: none; margin-bottom: 10px; font-size: 1.15rem;">
                        <span class="card-icon" style="background: #F4EBE1; color: #8D6E63; border-radius: 50%; padding: 12px; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;"><i class="fas fa-heart-pulse"></i></span> 
                        <?php echo __('Mood Tracker'); ?>
                    </h3>
                    <p class="mood-prompt" style="color: #333; font-size: 0.95rem; margin-bottom: 20px;"><?php echo __('How do you feel right now?'); ?></p>
                    <div class="mood-options-new">
                        <div class="mood-btn-new m-happy <?= in_array('Happy', $today_moods) ? 'selected' : ''; ?>" onclick="logMood('Happy', this)" title="<?php echo __('Happy'); ?>">
                            <div class="mood-icon">😄</div>
                            <span class="mood-label"><?php echo __('Happy'); ?></span>
                        </div>
                        <div class="mood-btn-new m-calm <?= in_array('Calm', $today_moods) ? 'selected' : ''; ?>" onclick="logMood('Calm', this)" title="<?php echo __('Calm'); ?>">
                            <div class="mood-icon">😌</div>
                            <span class="mood-label"><?php echo __('Calm'); ?></span>
                        </div>
                        <div class="mood-btn-new m-neutral <?= in_array('Neutral', $today_moods) ? 'selected' : ''; ?>" onclick="logMood('Neutral', this)" title="<?php echo __('Neutral'); ?>">
                            <div class="mood-icon">😐</div>
                            <span class="mood-label"><?php echo __('Neutral'); ?></span>
                        </div>
                        <div class="mood-btn-new m-sad" <?= in_array('Sad', $today_moods) ? 'selected' : ''; ?> onclick="logMood('Sad', this)" title="<?php echo __('Sad'); ?>">
                            <div class="mood-icon">😢</div>
                            <span class="mood-label"><?php echo __('Sad'); ?></span>
                        </div>
                        <div class="mood-btn-new m-stressed" <?= in_array('Stressed', $today_moods) ? 'selected' : ''; ?> onclick="logMood('Stressed', this)" title="<?php echo __('Stressed'); ?>">
                            <div class="mood-icon">😫</div>
                            <span class="mood-label"><?php echo __('Stressed'); ?></span>
                        </div>
                    </div>
                    <p id="moodStatus" style="text-align: center; color: #A68A6C; font-weight: bold; margin-top: 15px; min-height: 20px;"></p>
                </div>
            </div>

            <!-- Upcoming Sessions (Wide Card) -->
            <div class="card animate-up delay-100" style="margin-top: 30px; margin-bottom: 30px; width: 100%; max-width: 1750px;">
                <h3 class="card-title">
                    <span class="card-icon">📅</span> <?php echo __('Upcoming Sessions'); ?>
                </h3>

                <div id="upcoming-sessions-container">
                <?php if (count($sessions) > 0): ?>
                    <div class="session-list" id="upcoming-sessions-list">
                        <?php foreach ($sessions as $session): ?>
                            <?php 
                                $session_time = strtotime($session['session_date']);
                                $current_time = time();
                                $is_joinable = ($current_time >= ($session_time - 300)) || ($session['session_status'] === 'Active'); 
                                $m = $session['communication_method'] ?? 'Chat';
                                $is_video = (stripos($m, 'video') !== false);
                                // Route ALL sessions to session_room
                                $join_link = 'session_room.php?id=' . $session['id'];
                                $status = $session['session_status'] ?? 'Scheduled';
                                $partner_name = $session['partner_name'] ?? 'Therapist';
                                $parts = explode(' ', str_replace('Dr. ', '', $partner_name));
                                $initials = strtoupper(substr($parts[0] ?? 'T', 0, 1) . substr($parts[count($parts)-1] ?? '', 0, 1));
                                // Role colour: client = beige, therapist = blue
                                $btn_color  = ($user_role === 'Therapist') ? '#337AB7' : '#A68A6C';
                                $btn_hover  = ($user_role === 'Therapist') ? '#286090' : '#8D735B';
                                $left_bar   = ($user_role === 'Therapist') ? '#A68A6C' : '#337AB7';
                                $m_class = 'method-text'; $m_icon = 'fa-comments';
                                if ($is_video) { $m_class = 'method-video'; $m_icon = 'fa-video'; }
                                elseif (stripos($m, 'voice') !== false) { $m_class = 'method-voice'; $m_icon = 'fa-phone'; }
                            ?>
                            <div class="session-item-row" data-session-id="<?php echo $session['id']; ?>" style="<?php echo $is_joinable ? 'border-left: 5px solid '.$left_bar.';' : ''; ?>">
                                <div class="session-partner-info">
                                    <div class="partner-avatar-label" style="<?php echo ($user_role==='Therapist') ? 'background:#EFF6FF;color:#337AB7;border-color:#BFDBFE;' : ''; ?>"><?php echo $initials; ?></div>
                                    <div class="session-details">
                                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                                            <strong style="font-size: 1.1rem; color: #1e293b;"><?php echo htmlspecialchars($partner_name); ?></strong>
                                            <span class="status-badge status-<?php echo strtolower($status); ?>"><?php echo htmlspecialchars(ucfirst($status)); ?></span>
                                        </div>
                                        <div class="session-meta-tags">
                                            <span class="method-tag <?php echo $m_class; ?>"><i class="fas <?php echo $m_icon; ?>"></i> <?php echo htmlspecialchars($m); ?></span>
                                            <span class="meta-tag"><i class="far fa-calendar-alt"></i> <?php echo date('D, M d, H:i', $session_time); ?></span>
                                            <?php if (!empty($session['amount'])): ?>
                                            <span class="meta-tag amount-tag"><i class="fas fa-receipt"></i> <?php echo number_format($session['amount'], 0); ?> EGP</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="session-actions">
                                    <?php if (!empty($session['reschedule_requested']) && !empty($session['proposed_datetime'])): ?>
                                        <div class="alert-mini alert-info"><i class="fas fa-calendar-alt"></i> 
                                            <strong><?php echo __('Therapist proposed a new time:'); ?></strong><br>
                                            <span style="font-size: 0.9rem; color: #1e40af; font-weight: 600;">
                                                <?php echo date('M d, Y', strtotime($session['proposed_datetime'])); ?> | 
                                                <?php echo date('h:i A', strtotime($session['proposed_datetime'])); ?>
                                                <?php if (!empty($session['early_start_to'])) echo ' - ' . date('h:i A', strtotime($session['early_start_to'])); ?>
                                            </span>
                                            <div style="display:flex;gap:4px;margin-top:6px;align-items:center;">
                                                <?php 
                                                    $start_ts = strtotime($session['proposed_datetime']);
                                                    $end_ts = !empty($session['early_start_to']) ? strtotime(date('Y-m-d', $start_ts) . ' ' . $session['early_start_to']) : $start_ts;
                                                    if ($end_ts > $start_ts):
                                                ?>
                                                    <select id="slot_<?php echo $session['id']; ?>" class="form-select form-select-sm" style="display:inline-block; width:auto; border-radius: 6px; border: 1px solid #bfdbfe; font-size: 0.8rem; padding: 2px 24px 2px 8px; color: #1e40af; font-weight: 600;">
                                                        <?php for ($t = $start_ts; $t <= $end_ts; $t += 1800): ?>
                                                            <option value="<?php echo date('Y-m-d H:i:00', $t); ?>"><?php echo date('h:i A', $t); ?></option>
                                                        <?php endfor; ?>
                                                    </select>
                                                <?php endif; ?>
                                                <button onclick="acceptProposedTime('<?php echo $session['id']; ?>')" class="btn btn-xs btn-success"><?php echo __('Accept'); ?></button>
                                                <button onclick="declineProposedTime('<?php echo $session['id']; ?>')" class="btn btn-xs btn-outline-danger"><?php echo __('Decline'); ?></button>
                                            </div>
                                        </div>
                                    <?php elseif (!empty($session['early_start_requested']) && !empty($session['proposed_datetime'])): ?>
                                        <div class="alert-mini alert-info"><i class="fas fa-calendar-plus"></i> <?php echo __('New time proposed:'); ?> <?php echo date('h:i A', strtotime($session['proposed_datetime'])); ?>
                                            <div style="display:flex;gap:4px;margin-top:4px;">
                                                <button onclick="acceptProposedTime('<?php echo $session['id']; ?>')" class="btn btn-xs btn-success"><?php echo __('Accept'); ?></button>
                                                <button onclick="declineProposedTime('<?php echo $session['id']; ?>')" class="btn btn-xs btn-outline"><?php echo __('Decline'); ?></button>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <div style="display:flex;gap:8px;align-items:center;justify-content:flex-end;">
                                        <a href="<?php echo $is_joinable ? $join_link : '#'; ?>"
                                           class="btn btn-sm join-btn <?php echo $is_joinable ? 'active' : 'disabled'; ?>"
                                           style="<?php echo $is_joinable ? 'background:'.$btn_color.';border-color:'.$btn_color.';' : 'cursor:not-allowed;'; ?>">
                                            <?php 
                                            if ($is_joinable) {
                                                echo $is_video ? '<i class="fas fa-video"></i> ' . __('Join Zoom') : '<i class="fas fa-comments"></i> ' . __('Join Chat');
                                            } else {
                                                $total_sec = max(0, $session_time - $current_time);
                                                $d = floor($total_sec / 86400);
                                                $h = floor(($total_sec % 86400) / 3600);
                                                $m = floor(($total_sec % 3600) / 60);
                                                $countdown_str = sprintf('%02dd : %02dh : %02dm', $d, $h, $m);
                                                echo sprintf(__('Opens in %s'), $countdown_str);
                                            }
                                            ?>
                                        </a>
                                        <button onclick="cancelSession('<?php echo $session['id']; ?>')" class="btn btn-sm btn-outline-danger" style="border-radius:20px;font-size:0.75rem;padding:4px 12px;"><?php echo __('Cancel'); ?></button>
                                    </div>
                                </div>
                            </div>
                            <?php 
                            if (strtolower($status) === 'cancelled' && empty($session['cancellation_acknowledged']) && strpos($session['cancel_reason'] ?? '', 'Cancelled by Client') === false) {
                                echo "<script>document.addEventListener('DOMContentLoaded', function() { showCancellationNotice('{$session['id']}', '" . addslashes($session['cancel_reason'] ?? 'No reason provided') . "', '" . addslashes($partner_name) . "'); });</script>";
                            }
                            ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state" id="upcoming-sessions-empty">
                        <p><?php echo __('No upcoming sessions.'); ?></p>
                        <?php if ($user_role === 'Client'): ?>
                            <a href="therapists.php" class="btn btn-primary"><?php echo __('Book a Session'); ?></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                </div>
            </div>
        </div>
        <?php require_once 'includes/footer.php'; ?>
    </div>
</div>

<!-- Cancellation Notice Modal -->
<div id="cancellationModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div style="background: white; border-radius: 24px; padding: 40px; max-width: 500px; width: 90%; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.2); animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
        <div style="width: 80px; height: 80px; background: #FEF2F2; color: #EF4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; margin: 0 auto 20px;">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h3 style="font-size: 1.5rem; color: #1e293b; margin-bottom: 10px; font-weight: 800;">Session Cancelled</h3>
        <p style="color: #64748b; margin-bottom: 25px; line-height: 1.6;">
            Your upcoming session with <strong id="cancelPartnerName" style="color: #1e293b;"></strong> has been cancelled by the therapist.
        </p>
        <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 16px; padding: 20px; text-align: left; margin-bottom: 30px;">
            <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; color: #94A3B8; font-weight: 700; margin-bottom: 8px;">Reason for Cancellation</div>
            <div id="cancelReasonText" style="color: #334155; font-size: 0.95rem; line-height: 1.5; font-style: italic;"></div>
        </div>
        <p style="font-size: 0.85rem; color: #10B981; font-weight: 600; margin-bottom: 25px;">
            <i class="fas fa-check-circle"></i> The full session amount has been refunded to your wallet.
        </p>
        <input type="hidden" id="cancelSessionId">
        <button onclick="acknowledgeCancellation()" id="btnAckCancel" style="width: 100%; background: #EF4444; color: white; border: none; padding: 16px; border-radius: 50px; font-size: 1.1rem; font-weight: 700; cursor: pointer; transition: all 0.2s;">
            Acknowledge Cancellation
        </button>
    </div>
</div>
<style>
@keyframes popIn { 0% { transform: scale(0.8); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
</style>

<link rel="stylesheet" href="assets/css/dashboard-style.css">

<style>
    /* Add any page-specific overrides here if needed */
    /* Sidebar Overrides for Client */
    body[data-role="client"] .sidebar-menu a .icon {
        -webkit-text-stroke: 1.2px #A68A6C;
        color: transparent !important;
        transition: all 0.2s;
    }
    body[data-role="client"] .sidebar-menu a.active .icon,
    body[data-role="client"] .sidebar-menu a:hover .icon {
        -webkit-text-stroke: 0;
        color: #8D6E63 !important;
    }
    body[data-role="client"] .sidebar-menu a.active {
        background-color: #F4EBE1 !important;
        color: #8D6E63 !important;
        border-radius: 12px;
    }
    body[data-role="client"] .sidebar-menu a {
        border-radius: 12px;
        margin: 5px 15px;
        padding: 12px 15px;
    }
    body[data-role="client"] .dashboard-sidebar {
        border-right: 1px solid #fdfdfd;
    }

    .dashboard-banner-card {
        flex: 1;
        background-color: #F4EBE1;
        background-image: linear-gradient(to right, rgba(244,235,225, 0.95) 0%, rgba(244,235,225, 0.8) 45%, rgba(244,235,225, 0) 70%), url('assets/images/dashboard_banner.png');
        background-size: cover;
        background-position: right center;
        background-repeat: no-repeat;
        border-radius: 20px;
        padding: 40px 50px;
        position: relative;
        overflow: hidden;
    }

    .mood-options-new {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
    }
    .mood-btn-new {
        background: #F9F6F0;
        border-radius: 16px;
        padding: 15px 5px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        width: calc(33.33% - 10px);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .mood-btn-new:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .mood-btn-new.selected {
        background: #F4EBE1;
        box-shadow: 0 0 0 2px #8D6E63;
    }
    .mood-btn-new .mood-icon {
        font-size: 2rem;
        margin-bottom: 5px;
    }
    .mood-btn-new .mood-label {
        font-size: 0.75rem;
        color: #555;
        font-weight: 500;
    }

    .dashboard-header-row {
        margin-bottom: 30px;
        width: 100%;
        max-width: 1750px;
    }
    
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 30px;
        width: 100%;
        max-width: 1750px;
    }

    .container-fluid {
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 100%;
        padding: 0 20px;
    }

    /* Quick fixes for cards to match new theme if needed */
    .card {
        background: white;
        padding: 25px;
        border-radius: 16px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        margin-bottom: 0;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        display: flex;
        flex-direction: column;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 20px -5px rgba(0, 0, 0, 0.1);
    }

    .card-title {
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 15px;
        margin-bottom: 20px;
        font-size: 1.2rem;
        color: #1e293b;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .session-item,
    .challenge-item {
        border-bottom: 1px solid #f1f5f9;
        padding: 15px 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .session-item:last-child,
    .challenge-item:last-child {
        border-bottom: none;
    }

    .challenge-text {
        font-weight: 500;
        color: #334155;
    }

    .mood-options {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(75px, 1fr));
        gap: 12px;
        margin-top: 20px;
    }

    .mood-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 15px 5px;
        border-radius: 16px; /* Soft square shape */
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 2px solid transparent;
        background: #f8fafc; /* Neutral background */
    }

    .mood-btn:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 15px rgba(0,0,0,0.05);
    }

    .mood-btn.selected {
        transform: scale(1.05) translateY(-2px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }

    .mood-icon {
        font-size: 2.5rem;
        transition: all 0.3s ease;
        line-height: 1;
    }

    .mood-label {
        font-size: 0.75rem;
        font-weight: 700;
        color: #64748b; /* Default text color */
        transition: color 0.3s ease;
    }

    /* Specific Colors on Hover or Selected */
    .mood-btn.m-happy:hover, .mood-btn.m-happy.selected { background: #fef9c3; border-color: #fde047; }
    .mood-btn.m-happy:hover .mood-label, .mood-btn.m-happy.selected .mood-label { color: #854d0e; }

    .mood-btn.m-calm:hover, .mood-btn.m-calm.selected { background: #dcfce7; border-color: #86efac; }
    .mood-btn.m-calm:hover .mood-label, .mood-btn.m-calm.selected .mood-label { color: #14532d; }

    .mood-btn.m-neutral:hover, .mood-btn.m-neutral.selected { background: #e0f2fe; border-color: #7dd3fc; }
    .mood-btn.m-neutral:hover .mood-label, .mood-btn.m-neutral.selected .mood-label { color: #0c4a6e; }

    .mood-btn.m-sad:hover, .mood-btn.m-sad.selected { background: #ede9fe; border-color: #c4b5fd; }
    .mood-btn.m-sad:hover .mood-label, .mood-btn.m-sad.selected .mood-label { color: #4c1d95; }

    .mood-btn.m-stressed:hover, .mood-btn.m-stressed.selected { background: #fee2e2; border-color: #fca5a5; }
    .mood-btn.m-stressed:hover .mood-label, .mood-btn.m-stressed.selected .mood-label { color: #7f1d1d; }

    .session-item-row {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.3s ease;
        box-shadow: 0 4px 6px rgba(0,0,0,0.02);
    }
    .session-item-row:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px rgba(0,0,0,0.05);
    }
    .session-partner-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .partner-avatar-label {
        width: 50px;
        height: 50px;
        background: #fdf6ee;
        color: #A68A6C;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.2rem;
        border: 1px solid #eaddd7;
    }
    .session-meta-tags {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 5px;
    }
    .method-tag {
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .method-video { background: #dcfce7; color: #166534; }
    .method-voice { background: #fef9c3; color: #854d0e; }
    .method-text { background: #e0f2fe; color: #075985; }
    
    .meta-tag {
        font-size: 0.85rem;
        color: #64748b;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .amount-tag {
        color: #10b981;
        font-weight: 700;
    }

    .status-badge {
        font-size: 0.7rem;
        padding: 2px 8px;
        border-radius: 50px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .status-scheduled { background: #e0f2fe; color: #0369a1; }
    .status-active { background: #dcfce7; color: #166534; }
    .status-pending { background: #fef3c7; color: #92400e; }
    .status-reserved { background: #f3e8ff; color: #6b21a8; }
    .status-confirmed { background: #dcfce7; color: #166534; }

    .alert-mini {
        padding: 8px 12px;
        border-radius: 12px;
        font-size: 0.8rem;
        width: 100%;
        margin-bottom: 8px;
        border: 1px solid transparent;
    }
    .alert-mini.alert-danger { background: #fef2f2; color: #991b1b; border-color: #fca5a5; }
    .alert-mini.alert-info { background: #eff6ff; color: #1e40af; border-color: #bfdbfe; }
    
    .btn-xs { padding: 2px 8px; font-size: 0.75rem; border-radius: 4px; border: none; cursor: pointer; }
    .btn-xs.btn-danger { background: #ef4444; color: white; }
    .btn-xs.btn-success { background: #22c55e; color: white; }
    .btn-xs.btn-outline { background: transparent; border: 1px solid currentColor; }

    .join-btn.active { background-color: #A68A6C; color: white; border: 1px solid #A68A6C; }
    .join-btn.disabled { background-color: #F5F5F5; color: #A0A0A0; border: 1px solid #E0E0E0; }
    .join-btn { padding: 8px 20px; font-size: 0.9em; border-radius: 10px; text-decoration: none; transition: all 0.2s; font-weight: 700; }
    .join-btn.active:hover { background-color: #8D735B; transform: translateY(-1px); }

    @media (max-width: 768px) {
        .session-item-row { flex-direction: column; align-items: flex-start; gap: 15px; }
        .session-actions { width: 100%; }
        .session-actions > div { justify-content: flex-start !important; }
    }
</style>

<script>


    function completeChallenge(btn) {
        btn.textContent = "✓";
        btn.style.backgroundColor = "#22c55e"; // Green
        btn.style.color = "white";
        btn.disabled = true;
    }

    function logMood(mood, el) {
        if (!el) return;
        
        const isSelected = el.classList.contains('selected');
        const action = isSelected ? 'delete' : 'add';
        
        if (action === 'add') {
            const currentSelected = document.querySelector('.mood-btn-new.selected');
            if (currentSelected) {
                alert("You can only log one mood. Please unselect the current mood first.");
                return;
            }
        }
        
        // Toggle selected class locally
        el.classList.toggle('selected');
        
        fetch('api/mood/log.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mood: mood, action: action })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                if (action === 'add') {
                    document.getElementById('moodStatus').textContent = "Logged: " + mood;
                } else {
                    document.getElementById('moodStatus').textContent = "Removed: " + mood;
                }
            } else {
                // Revert class on failure
                el.classList.toggle('selected');
                document.getElementById('moodStatus').textContent = "Error: " + data.message;
            }
        })
        .catch(err => {
            console.error(err);
            // Revert class on failure
            el.classList.toggle('selected');
            document.getElementById('moodStatus').textContent = "Failed to log mood.";
        });
    }

    function cancelSession(id) {
        if(!confirm("Cancel this session and refund amount to your wallet?")) return;
        fetch('api/booking/cancel.php?id=' + id)
            .then(r => r.json())
            .then(data => {
                if(data.status === 'success') {
                    location.reload();
                } else {
                    alert(data.message);
                }
            });
    }

    function acceptProposedTime(id) {
        let slotEl = document.getElementById('slot_' + id);
        let selectedTime = slotEl ? slotEl.value : '';
        let label = slotEl && slotEl.options[slotEl.selectedIndex] ? slotEl.options[slotEl.selectedIndex].text : '';
        let msg = label ? 'Accept the new proposed time (' + label + ')?' : 'Accept the new proposed time?';
        
        if(!confirm(msg)) return;
        
        fetch('api/booking/accept_proposed_time.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + id + '&selected_time=' + encodeURIComponent(selectedTime)
        })
            .then(r => r.json())
            .then(data => {
                if(data.status === 'success') location.reload();
                else alert(data.message);
            });
    }

    function declineProposedTime(id) {
        if(!confirm('Decline the proposed time? The session will remain at its original reserved time.')) return;
        
        fetch('api/booking/decline_proposed_time.php?id=' + id)
            .then(r => r.json())
            .then(data => {
                if(data.status === 'success') location.reload();
                else alert(data.message);
            });
    }

    function rePickTime(session_id, therapist_id) {
        if(!confirm('This will cancel the current session, refund your wallet, and take you to pick a new time. Proceed?')) return;
        fetch('api/booking/cancel.php?id=' + session_id)
            .then(r => r.json())
            .then(data => {
                if(data.status === 'success') window.location.href = 'book_session.php?therapist_id=' + therapist_id;
                else alert(data.message);
            });
    }

    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('dashboardSidebar');
        const toggleBtn = document.getElementById('sidebarToggle');
        
        // Toggle Sidebar state
        toggleBtn.addEventListener('click', () => {
            const isMobile = window.innerWidth <= 992;
            const icon = document.getElementById('toggleIcon');
            
            if (isMobile) {
                sidebar.classList.toggle('active');
                document.getElementById('sidebarOverlay').classList.toggle('active');
            } else {
                sidebar.classList.toggle('is-closed');
                document.querySelector('.dashboard-wrapper').classList.toggle('sidebar-closed');
                
                // Change icon
                if (sidebar.classList.contains('is-closed')) {
                    icon.className = 'fa-solid fa-arrow-right-long';
                } else {
                    icon.className = 'fa-solid fa-bars-staggered';
                }
                
                // Save preference
                const isClosed = sidebar.classList.contains('is-closed');
                localStorage.setItem('sidebarClosed', isClosed);
            }
        });

        // Close mobile sidebar on overlay click
        document.getElementById('sidebarOverlay').addEventListener('click', () => {
            sidebar.classList.remove('active');
            document.getElementById('sidebarOverlay').classList.remove('active');
        });

        // Load preference (Desktop only)
        if (window.innerWidth > 992 && localStorage.getItem('sidebarClosed') === 'true') {
            sidebar.classList.add('is-closed');
            document.querySelector('.dashboard-wrapper').classList.add('sidebar-closed');
            document.getElementById('toggleIcon').className = 'fa-solid fa-arrow-right-long';
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.animate-up').forEach(el => {
            observer.observe(el);
        });

        // Real-time updates for upcoming sessions
        function refreshUpcomingSessions() {
            fetch('api/session/fetch_upcoming.php')
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        updateSessionsUI(res.data);
                    }
                })
                .catch(err => console.error('Error refreshing sessions:', err));
        }

        // Role vars injected from PHP
        const USER_ROLE  = '<?php echo $user_role; ?>';
        const IS_CLIENT  = USER_ROLE === 'Client';
        const BTN_COLOR  = IS_CLIENT ? '#A68A6C' : '#337AB7';
        const BTN_HOVER  = IS_CLIENT ? '#8D735B' : '#286090';
        const LEFT_BAR   = IS_CLIENT ? '#A68A6C' : '#337AB7';
        const AVT_STYLE  = IS_CLIENT
            ? 'background:#fdf6ee;color:#A68A6C;border-color:#eaddd7;'
            : 'background:#EFF6FF;color:#337AB7;border-color:#BFDBFE;';

        const TRANSLATIONS = {
            'No upcoming sessions.': '<?php echo __('No upcoming sessions.'); ?>',
            'Book a Session': '<?php echo __('Book a Session'); ?>',
            'Join Zoom': '<?php echo __('Join Zoom'); ?>',
            'Join Chat': '<?php echo __('Join Chat'); ?>',
            'Opens in': '<?php echo __('Opens in %s'); ?>',
            'Therapist requested reschedule': '<?php echo __('Therapist requested a reschedule'); ?>',
            'Pick New Time': '<?php echo __('Pick New Time'); ?>',
            'New time:': '<?php echo __('New time proposed:'); ?>',
            'Accept': '<?php echo __('Accept'); ?>',
            'Decline': '<?php echo __('Decline'); ?>',
            'Cancel': '<?php echo __('Cancel'); ?>'
        };

        function formatCountdown(minutes) {
            const totalSec = Math.max(0, minutes * 60);
            const d = Math.floor(totalSec / 86400);
            const h = Math.floor((totalSec % 86400) / 3600);
            const m = Math.floor((totalSec % 3600) / 60);
            return (d < 10 ? '0' + d : d) + 'd : ' + (h < 10 ? '0' + h : h) + 'h : ' + (m < 10 ? '0' + m : m) + 'm';
        }

        function t(key) {
            return TRANSLATIONS[key] || key;
        }

        function updateSessionsUI(sessions) {
            const container = document.getElementById('upcoming-sessions-container');
            if (!container) return;

            // ── Clean slate every refresh ──
            container.innerHTML = '';

            if (!sessions || sessions.length === 0) {
                container.innerHTML = `<div class="empty-state"><p>${t('No upcoming sessions.')}</p>${IS_CLIENT ? '<a href="therapists.php" class="btn btn-primary" style="background:'+BTN_COLOR+';border-color:'+BTN_COLOR+';">'+t('Book a Session')+'</a>' : ''}</div>`;
                return;
            }

            let html = '<div class="session-list">';
            sessions.forEach(s => {
                const isJoinable   = s.is_joinable;
                const status       = s.session_status || 'Scheduled';
                const statusClass  = status.toLowerCase();
                const m            = s.communication_method || 'Chat';
                const isGroup      = s.is_group_session;
                const isVideo      = m.toLowerCase().includes('video');

                // If it's a cancelled session that hasn't been acknowledged, pop the modal and skip rendering
                if (statusClass === 'cancelled' && !s.cancellation_acknowledged) {
                    showCancellationNotice(s.id, s.cancel_reason || 'No reason provided', s.partner_name);
                    return;
                }

                // ── Functional redirect: Video → Zoom, Voice/Chat → session_room ──
                let joinHref = isJoinable 
                    ? (isGroup ? `group_management.php?action=room&id=${s.id}` : `session_room.php?id=${s.id}`)
                    : '#';
                const joinLabel = isJoinable
                    ? (isVideo ? `<i class="fas fa-video"></i> ${t('Join Zoom')}` : `<i class="fas fa-comments"></i> ${t('Join Chat')}`)
                    : t('Opens in').replace('%s', formatCountdown(s.countdown));

                let mClass = 'method-text', mIcon = 'fa-comments';
                if (isVideo) { mClass = 'method-video'; mIcon = 'fa-video'; }
                else if (m.toLowerCase().includes('voice')) { mClass = 'method-voice'; mIcon = 'fa-phone'; }

                const parts    = (s.partner_name || 'T').replace('Dr. ', '').split(' ');
                const initials = ((parts[0]?.charAt(0) || 'T') + (parts[parts.length-1]?.charAt(0) || '')).toUpperCase();
                const amountStr = s.amount ? `<span class="meta-tag amount-tag"><i class="fas fa-receipt"></i> ${parseInt(s.amount).toLocaleString()} EGP</span>` : '';

                html += `
                <div class="session-item-row" data-session-id="${s.id}" style="${isJoinable ? 'border-left:5px solid '+LEFT_BAR+';' : ''}">
                    <div class="session-partner-info">
                        <div class="partner-avatar-label" style="${AVT_STYLE}">${initials}</div>
                        <div class="session-details">
                            <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
                                <strong style="font-size:1.1rem;color:#1e293b;">${s.partner_name || 'Session'}</strong>
                                <span class="status-badge status-${statusClass}">${status.charAt(0).toUpperCase()+status.slice(1)}</span>
                            </div>
                            <div class="session-meta-tags">
                                <span class="method-tag ${mClass}"><i class="fas ${mIcon}"></i> ${m}</span>
                                <span class="meta-tag"><i class="far fa-calendar-alt"></i> ${s.formatted_date}</span>
                                ${amountStr}
                            </div>
                        </div>
                    </div>
                    <div class="session-actions">
                        ${(s.reschedule_requested && s.proposed_datetime) ? `
                            <div class="alert-mini alert-info"><i class="fas fa-calendar-alt"></i> 
                                <strong>${t('Therapist proposed a new time:')}</strong><br>
                                <span style="font-size: 0.9rem; color: #1e40af; font-weight: 600;">
                                    ${new Date(s.proposed_datetime).toLocaleDateString('en-US', {month:'short', day:'numeric', year:'numeric'})} | 
                                    ${new Date(s.proposed_datetime).toLocaleTimeString('en-US', {hour: '2-digit', minute:'2-digit'})}
                                    ${s.early_start_to ? ' - ' + new Date(s.proposed_datetime.substring(0,10) + 'T' + s.early_start_to).toLocaleTimeString('en-US', {hour: '2-digit', minute:'2-digit'}) : ''}
                                </span>
                                <div style="display:flex;gap:4px;margin-top:6px;align-items:center;">
                                    ${(function(){
                                        let sTs = new Date(s.proposed_datetime).getTime();
                                        let eTs = s.early_start_to ? new Date(s.proposed_datetime.substring(0,10) + 'T' + s.early_start_to).getTime() : sTs;
                                        if (eTs > sTs) {
                                            let opts = '';
                                            for (let t = sTs; t <= eTs; t += 1800000) {
                                                let d = new Date(t);
                                                let val = d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0') + ' ' + String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0') + ':00';
                                                let ampm = d.getHours() >= 12 ? 'PM' : 'AM';
                                                let hr = d.getHours() % 12 || 12;
                                                let lbl = String(hr).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0') + ' ' + ampm;
                                                opts += `<option value="${val}">${lbl}</option>`;
                                            }
                                            return `<select id="slot_${s.id}" class="form-select form-select-sm" style="display:inline-block; width:auto; border-radius: 6px; border: 1px solid #bfdbfe; font-size: 0.8rem; padding: 2px 24px 2px 8px; color: #1e40af; font-weight: 600;">${opts}</select>`;
                                        }
                                        return '';
                                    })()}
                                    <button onclick="acceptProposedTime('${s.id}')" class="btn btn-xs btn-success">${t('Accept')}</button>
                                    <button onclick="declineProposedTime('${s.id}')" class="btn btn-xs btn-outline-danger">${t('Decline')}</button>
                                </div>
                            </div>` : ''}
                        ${(s.early_start_requested && s.proposed_datetime && !s.reschedule_requested) ? `<div class="alert-mini alert-info"><i class="fas fa-calendar-plus"></i> ${t('New time:')} ${new Date(s.proposed_datetime).toLocaleTimeString('en-US', {hour: '2-digit', minute:'2-digit'})}<div style="display:flex;gap:4px;margin-top:4px;"><button onclick="acceptProposedTime('${s.id}')" class="btn btn-xs btn-success">${t('Accept')}</button><button onclick="declineProposedTime('${s.id}')" class="btn btn-xs btn-outline">${t('Decline')}</button></div></div>` : ''}
                        <div style="display:flex;gap:8px;align-items:center;justify-content:flex-end;">
                            <a href="${joinHref}"
                               class="btn btn-sm join-btn ${isJoinable ? 'active' : 'disabled'}"
                               style="${isJoinable ? 'background:'+BTN_COLOR+';border-color:'+BTN_COLOR+';color:#fff;' : 'cursor:not-allowed;'}">
                                ${joinLabel}
                            </a>
                            <button onclick="cancelSession('${s.id}')" class="btn btn-sm btn-outline-danger" style="border-radius:20px;font-size:.75rem;padding:4px 12px;">${t('Cancel')}</button>
                        </div>
                    </div>
                </div>`;
            });
            html += '</div>';
            container.innerHTML = html;
        }

        // Poll every 15s + fire immediately on load
        refreshUpcomingSessions();
        setInterval(refreshUpcomingSessions, 15000);
    });

    let isCancellationModalOpen = false;
    function showCancellationNotice(id, reason, partner) {
        if (isCancellationModalOpen) return; // Only show one at a time
        isCancellationModalOpen = true;
        document.getElementById('cancelSessionId').value = id;
        document.getElementById('cancelPartnerName').textContent = partner;
        document.getElementById('cancelReasonText').textContent = '"' + reason + '"';
        const modal = document.getElementById('cancellationModal');
        modal.style.display = 'flex';
    }

    function acknowledgeCancellation() {
        const id = document.getElementById('cancelSessionId').value;
        const btn = document.getElementById('btnAckCancel');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        btn.disabled = true;

        fetch('api/booking/acknowledge_cancellation.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ session_id: id })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                document.getElementById('cancellationModal').style.display = 'none';
                isCancellationModalOpen = false;
                window.location.reload();
            } else {
                alert(res.message);
                btn.innerHTML = 'Acknowledge Cancellation';
                btn.disabled = false;
            }
        })
        .catch(err => {
            alert('Error acknowledging cancellation.');
            btn.innerHTML = 'Acknowledge Cancellation';
            btn.disabled = false;
        });
    }
</script>

