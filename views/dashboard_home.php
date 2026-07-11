<?php
if (!defined('IN_DASHBOARD')) {
    header("Location: ../therapist_dashboard.php");
    exit();
}

// Ensure variables from therapist_dashboard.php are available:
// $pdo, $therapist_id, $is_verified, $name, $email, $profile_image

// 1. Fetch Stats
$stats = ['total' => 0, 'pending' => 0, 'completed_today' => 0, 'month_earnings' => 0];

if ($therapist_id) {
    // Total Sessions
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM private_sessions WHERE therapist_id = ? AND LOWER(status) = 'completed'");
    $stmt->execute([$therapist_id]);
    $stats['total'] = $stmt->fetchColumn();

    // Pending Requests
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM private_sessions WHERE therapist_id = ? AND LOWER(status) IN ('pending', 'pending_reschedule')");
    $stmt->execute([$therapist_id]);
    $stats['pending'] = $stmt->fetchColumn();

    // Completed Today
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM private_sessions WHERE therapist_id = ? AND DATE(session_date) = CURDATE() AND LOWER(status) = 'completed'");
    $stmt->execute([$therapist_id]);
    $stats['completed_today'] = $stmt->fetchColumn();

    // Monthly Earnings
    $stmt = $pdo->prepare("SELECT SUM(amount) FROM private_sessions WHERE therapist_id = ? AND LOWER(status) = 'completed' AND MONTH(session_date) = MONTH(CURDATE()) AND YEAR(session_date) = YEAR(CURDATE())");
    $stmt->execute([$therapist_id]);
    $earnings = $stmt->fetchColumn();
    $stats['month_earnings'] = number_format($earnings ?: 0, 0);

    // Next Session Reminder (LIMIT 1)
    $stmt = $pdo->prepare("
        SELECT ps.private_session_id as id, ps.session_date, ps.status, ps.amount,
        ps.communication_method, ps.duration_minutes, c.name as client_name, c.anonymous_id
        FROM private_sessions ps 
        JOIN client c ON ps.client_id = c.client_id 
        WHERE ps.therapist_id = ? AND DATE_ADD(ps.session_date, INTERVAL ps.duration_minutes MINUTE) >= NOW() AND LOWER(ps.status) IN ('reserved', 'active')
        ORDER BY ps.session_date ASC
        LIMIT 1
    ");
    $stmt->execute([$therapist_id]);
    $next_session = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!-- Profile Header Card Revamp -->
<div class="profile-header-card" style="padding: 30px 40px; margin-bottom: 25px;">
    <div class="profile-avatar-large" style="width: 100px; height: 100px; font-size: 2.5rem; flex-shrink: 0; border-radius: 50%; overflow: hidden;">
        <?php if ($profile_image && !empty($profile_image) && strpos($profile_image, 'default_avatar') === false): ?>
            <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
        <?php else: ?>
            <?php 
            $ui_avatar_url = "https://ui-avatars.com/api/?name=" . urlencode($name) . "&background=random&color=fff&size=150";
            ?>
            <img src="<?php echo $ui_avatar_url; ?>" alt="Default Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
        <?php endif; ?>
    </div>
    <div class="profile-info">
        <h1 style="font-size: 2.2rem;">
            <span>Dr. <?php echo htmlspecialchars($name); ?></span>
            <?php if ($is_verified): ?>
                <span class="badge-verified-inline" style="margin-left: 15px;">
                    <i class="fa-solid fa-circle-check"></i> Verified
                </span>
            <?php endif; ?>
        </h1>
        <span class="profile-email-sub" style="margin: 5px 0 10px;"><?php echo htmlspecialchars($email); ?></span>
        <div class="badge-licensed" style="padding: 5px 15px; font-size: 0.85rem;">Licensed Therapist</div>
    </div>
</div>

<!-- Compact KPI Row -->
<div class="kpi-grid">
    <div class="stat-mini-card">
        <div>
            <h4><?php echo __('Total Sessions'); ?></h4>
            <div class="val"><?php echo $stats['total']; ?></div>
            <div style="margin-top: 8px; font-size: 0.85rem; color: #10b981; font-weight: 700;"><i class="fa-solid fa-arrow-trend-up"></i> +12% vs last month</div>
        </div>
        <div class="stat-icon-s stat-icon-total">
            <i class="fa-solid fa-calendar-check"></i>
        </div>
    </div>
    <div class="stat-mini-card">
        <div>
            <h4><?php echo __('Pending Requests'); ?></h4>
            <div class="val"><?php echo $stats['pending']; ?></div>
            <div style="margin-top: 8px; font-size: 0.85rem; color: #f97316; font-weight: 700;"><i class="fa-solid fa-circle-exclamation"></i> <?php echo __('Requires attention'); ?></div>
        </div>
        <div class="stat-icon-s stat-icon-pending">
            <i class="fa-solid fa-file-invoice"></i>
        </div>
    </div>
    <div class="stat-mini-card">
        <div>
            <h4><?php echo __('Completed Today'); ?></h4>
            <div class="val"><?php echo $stats['completed_today']; ?></div>
            <div style="margin-top: 8px; font-size: 0.85rem; color: #22c55e; font-weight: 700;"><i class="fa-solid fa-circle-check"></i> <?php echo __('Great work!'); ?></div>
        </div>
        <div class="stat-icon-s stat-icon-completed">
            <i class="fa-solid fa-user-check"></i>
        </div>
    </div>
    <div class="stat-mini-card">
        <div>
            <h4><?php echo __('Monthly Earnings'); ?></h4>
            <div class="val"><?php echo $stats['month_earnings']; ?> <span style="font-size: 1.2rem; color: #64748b; font-weight: 600;">EGP</span></div>
            <div style="margin-top: 8px; font-size: 0.85rem; color: #10b981; font-weight: 700;"><i class="fa-solid fa-arrow-trend-up"></i> +5% vs last month</div>
        </div>
        <div class="stat-icon-s stat-icon-earnings">
            <i class="fa-solid fa-wallet"></i>
        </div>
    </div>
</div>

<!-- Main Split Workspace Row -->
<div style="width: 100%;">
    <!-- Full-Width: Next Session Reminder -->
    <div class="section-card" style="padding: 30px;">
        <!-- Header with circular bell icon -->
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px; border-bottom: 1px solid #f1f5f9; padding-bottom: 16px;">
            <div style="width: 42px; height: 42px; border-radius: 50%; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                <i class="fa-solid fa-bell"></i>
            </div>
            <h3 style="margin: 0; color: #1e293b; font-size: 1.35rem; font-weight: 800;"><?php echo __('Next Session Reminder'); ?></h3>
        </div>
        
        <?php if (!empty($next_session)): 
            $client_label = $next_session['client_name'] ?: ($next_session['anonymous_id'] ?: 'Client');
            $initials = strtoupper(substr($client_label, 0, 2));
            $session_time = strtotime($next_session['session_date']);
            $time_str = date('D, M j, Y h:i A', $session_time);
            $current_time = time();
            $is_joinable = ($current_time >= ($session_time - 300));
            $is_video = strtolower($next_session['communication_method']) === 'video session';
            $join_link = 'session_room.php?id=' . $next_session['id'];
            
            // Format Day:Hour:Min Countdown
            $total_sec = max(0, $session_time - $current_time);
            $d = floor($total_sec / 86400);
            $h = floor(($total_sec % 86400) / 3600);
            $m = floor(($total_sec % 3600) / 60);
            $countdown_str = sprintf('%02dd : %02dh : %02dm', $d, $h, $m);
            
            // Badge style
            $badge_bg = '#eff6ff';
            $badge_color = '#2563eb';
            $badge_icon = $is_video ? 'fa-video' : 'fa-message';
            
            $btn_style = $is_joinable ? 'background:#2563eb;color:white;border:none;' : 'background:#f1f5f9;color:#94a3b8;border:none;cursor:not-allowed;';
            $btn_text = $is_joinable ? ($is_video ? '<i class="fas fa-video me-1"></i> Join Zoom' : '<i class="fas fa-comments me-1"></i> Join Chat') : sprintf(__('Opens in %s'), $countdown_str);
            $href = $is_joinable ? $join_link : '#';
        ?>
            <!-- Session Row Container styled like photo -->
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.01), 0 2px 4px -1px rgba(0,0,0,0.01); position: relative; width: 100%; box-sizing: border-box;">
                <!-- Left Details Block -->
                <div style="display: flex; align-items: flex-start; gap: 16px; flex: 1; min-width: 280px;">
                    <!-- Avatar circle with initials -->
                    <div style="width: 48px; height: 48px; background: #e0f2fe; color: #0369a1; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.15rem; flex-shrink: 0;">
                        <?php echo htmlspecialchars($initials); ?>
                    </div>
                    <!-- Info Block -->
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <strong style="color: #1e293b; font-size: 1.1rem; font-weight: 800;"><?php echo htmlspecialchars($client_label); ?></strong>
                        
                        <!-- Badges Row -->
                        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                            <span style="background: <?php echo $badge_bg; ?>; color: <?php echo $badge_color; ?>; padding: 6px 12px; font-weight: 700; border-radius: 8px; font-size: 0.80rem; display: inline-flex; align-items: center; gap: 6px;">
                                <i class="fa-solid <?php echo $badge_icon; ?>"></i> <?php echo htmlspecialchars($next_session['communication_method']); ?> Session
                            </span>
                            <span style="color: #64748b; font-weight: 600; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 6px;">
                                <i class="fa-regular fa-calendar" style="color: #94a3b8;"></i> <?php echo $time_str; ?>
                            </span>
                        </div>
                        
                        <!-- Opens in badge -->
                        <div style="margin-top: 4px;">
                            <span class="session-countdown" data-time="<?php echo $session_time; ?>" style="background: #f1f5f9; color: #64748b; font-weight: 700; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem;">
                                <?php if ($is_joinable): ?>
                                    <span style="color: #22c55e;"><i class="fa-solid fa-circle-play me-1"></i> Session is Open</span>
                                <?php else: ?>
                                    <?php echo sprintf(__('Opens in %s'), $countdown_str); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                     </div>
                 </div>
                 
                 <!-- Right Action Buttons Block -->
                 <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                     <?php if ($is_verified): ?>
                         <a class="session-join-btn" data-time="<?php echo $session_time; ?>" data-is-video="<?php echo $is_video ? 1 : 0; ?>" data-join-link="<?php echo $join_link; ?>" href="<?php echo $href; ?>" class="btn btn-sm" style="font-weight:700; padding:10px 20px; border-radius:12px; transition: all 0.2s; <?php echo $btn_style; ?>">
                             <?php echo $btn_text; ?>
                         </a>
                        <button onclick="rescheduleSession('<?php echo $next_session['id']; ?>', <?php echo $next_session['duration_minutes'] ?: 60; ?>)" style="background: #ffffff; border: 1px solid #cbd5e1; color: #334155; font-weight: 700; padding: 10px 20px; border-radius: 12px; cursor: pointer; transition: all 0.2s; font-size: 0.85rem;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#ffffff'">
                            <?php echo __('Reschedule'); ?>
                        </button>
                        <button onclick="requestCancelFlow('<?php echo $next_session['id']; ?>')" style="background: #ffffff; border: 1px solid #fca5a5; color: #ef4444; font-weight: 700; padding: 10px 20px; border-radius: 12px; cursor: pointer; transition: all 0.2s; font-size: 0.85rem;" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='#ffffff'">
                            <?php echo __('Request Cancellation'); ?>
                        </button>
                    <?php else: ?>
                        <span style="font-size: 0.88rem; color: #94a3b8; font-weight: 600;"><i class="fas fa-lock me-1"></i> Pending Approval</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div style="padding: 40px; text-align: center; background: #f8fafc; border-radius: 16px; border: 2px dashed #cbd5e1;">
                <i class="fa-regular fa-calendar-xmark" style="font-size: 3rem; color: #94a3b8; margin-bottom: 15px;"></i>
                <p style="color: #64748b; font-size: 1.1rem; margin: 0; font-weight: 600;"><?php echo __('No upcoming sessions scheduled.'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>


