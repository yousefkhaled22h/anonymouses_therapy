<?php
// group_management.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'includes/db_connect.php';

// Auto-close and delete expired group sessions globally
try {
    $stmt_expired = $pdo->prepare("
        SELECT group_session_id, topic 
        FROM group_sessions 
        WHERE DATE_ADD(session_date, INTERVAL duration_minutes MINUTE) < NOW()
    ");
    $stmt_expired->execute();
    $expired_sessions = $stmt_expired->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($expired_sessions)) {
        $expired_ids = array_column($expired_sessions, 'group_session_id');
        $in_ph = implode(',', array_fill(0, count($expired_ids), '?'));
        
        $stmt_del_m = $pdo->prepare("DELETE FROM group_session_messages WHERE group_session_id IN ($in_ph)");
        $stmt_del_m->execute($expired_ids);
        
        $stmt_close = $pdo->prepare("UPDATE group_sessions SET status = 'ended' WHERE group_session_id IN ($in_ph)");
        $stmt_close->execute($expired_ids);
        
        $req_id = $_GET['id'] ?? null;
        if (($_GET['action'] ?? '') === 'room' && $req_id) {
            foreach ($expired_sessions as $es) {
                if ($es['group_session_id'] === $req_id) {
                    header("Location: group_management.php?error=expired&topic=" . urlencode($es['topic']));
                    exit();
                }
            }
        }
    }
} catch (Exception $e) {}

$action = $_GET['action'] ?? 'list';

if ($action === 'room') {
    $session_id = $_GET['id'] ?? null;
    if (!$session_id) {
        header("Location: group_management.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role'] ?? 'client';
    $therapist_id = $_SESSION['therapist_id'] ?? null;

    $role_lc = strtolower($user_role);
    $leave_url = 'group_management.php';
    if ($role_lc === 'therapist') {
        $leave_url = 'therapist_dashboard.php?view=groups';
    } elseif ($role_lc === 'volunteer') {
        $leave_url = 'volunteer/dashboard.php?view=groups';
    }

    if (strtolower($user_role) === 'therapist') {
        // Check Verification Status
        $stmt_v = $pdo->prepare("SELECT verified FROM therapist WHERE user_id = ?");
        $stmt_v->execute([$user_id]);
        if (($stmt_v->fetchColumn() ?? 0) != 1) {
            header("Location: therapist_dashboard.php");
            exit();
        }
    } elseif (strtolower($user_role) === 'volunteer') {
        // Check Verification Status
        $stmt_v = $pdo->prepare("SELECT verification_status FROM volunteer WHERE user_id = ?");
        $stmt_v->execute([$user_id]);
        if (strtolower($stmt_v->fetchColumn() ?? '') === 'pending') {
            header("Location: volunteer/dashboard.php");
            exit();
        }
    }

    // Fetch session details
    try {
        $stmt = $pdo->prepare("
            SELECT gs.*, t.first_name, t.last_name, t.user_id as therapist_user_id
            FROM group_sessions gs
            LEFT JOIN therapist t ON gs.therapist_id = t.therapist_id
            WHERE gs.group_session_id = ?
        ");
        $stmt->execute([$session_id]);
        $session = $stmt->fetch();

        if (!$session) {
            header("Location: group_management.php");
            exit();
        }

        $is_therapist_owner = (strtolower($user_role) === 'therapist' && $session['therapist_user_id'] == $user_id);

        // Lock enforcement checks
        $session_time = strtotime($session['session_date']);
        $current_time = time();
        $diff = $session_time - $current_time;
        
        if ($is_therapist_owner) {
            // Therapist owner can only enter if the session is active, or if it is within 3 minutes of starting
            if (strtolower($session['status']) !== 'active' && $diff > 180) {
                $wait_min = ceil(($diff - 180) / 60);
                header("Location: group_management.php?error=therapist_early&wait=" . $wait_min . "&topic=" . urlencode($session['topic']));
                exit();
            }
        } else {
            // Client/Volunteer can only enter if status is 'active'
            $is_active = (strtolower($session['status']) === 'active');
            if (!$is_active) {
                header("Location: group_management.php?error=room_locked&topic=" . urlencode($session['topic']) . "&time=" . urlencode($session['session_date']));
                exit();
            }
        }

        // Check if the user is already in the session
        $stmt_check_joined = $pdo->prepare("SELECT COUNT(*) FROM group_session_participants WHERE group_session_id = ? AND user_id = ?");
        $stmt_check_joined->execute([$session_id, $user_id]);
        $already_joined = ($stmt_check_joined->fetchColumn() > 0);

        if (!$already_joined && !$is_therapist_owner) {
            $stmt_p_count = $pdo->prepare("SELECT COUNT(*) FROM group_session_participants WHERE group_session_id = ?");
            $stmt_p_count->execute([$session_id]);
            $p_count = $stmt_p_count->fetchColumn() ?: 0;

            $max_p = $session['max_participants'] ?: 15;
            if ($p_count >= $max_p) {
                header("Location: group_management.php?error=room_full&topic=" . urlencode($session['topic']));
                exit();
            }
        }

        // Join the session automatically if not already joined
        if (!$already_joined) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO group_session_participants (group_session_id, user_id) VALUES (?, ?)");
            $stmt->execute([$session_id, $user_id]);

            if (strtolower($user_role) === 'volunteer') {
                $stmt_update_v = $pdo->prepare("UPDATE volunteer SET total_sessions = total_sessions + 1 WHERE user_id = ?");
                $stmt_update_v->execute([$user_id]);
            }
        }

        // Check if muted (muted IDs stored as JSON in admin_comments)
        $muted_users = json_decode($session['admin_comments'] ?? '[]', true);
        if (!is_array($muted_users))
            $muted_users = [];
        $is_muted = in_array($user_id, $muted_users);

        // Fetch active/scheduled rooms for the sidebar
        $stmt_rooms = $pdo->prepare("SELECT group_session_id, topic, status FROM group_sessions WHERE status IN ('scheduled', 'active') ORDER BY session_date ASC");
        $stmt_rooms->execute();
        $active_rooms = $stmt_rooms->fetchAll();

    } catch (PDOException $e) {
        die("Database error.");
    }

    $body_class = 'group-session-room';

    $is_therapist_role = (strtolower($user_role) === 'therapist');
    $is_volunteer_role = (strtolower($user_role) === 'volunteer');
    if ($is_therapist_role) {
        $room_bg = '#F4F9FD';
        $send_btn_bg = '#337AB7';
        $send_btn_hover = '#286090';
    } elseif ($is_volunteer_role) {
        $room_bg = '#F0FDF4';
        $send_btn_bg = '#059669';
        $send_btn_hover = '#047857';
    } else {
        $room_bg = '#FAF6F0';
        $send_btn_bg = '#A68A6C';
        $send_btn_hover = '#937659';
    }

    require_once 'includes/header.php';
    ?>
    <style>
        html, body {
            overflow: hidden !important;
            margin: 0 !important;
            padding: 0 !important;
            height: 100vh !important;
            width: 100vw !important;
        }

        /* Fix: Ensure navbar container stays centered and not shifted */
        header#main-header .container,
        header .container {
            max-width: 1200px !important;
            margin-left: auto !important;
            margin-right: auto !important;
            padding-left: 20px !important;
            padding-right: 20px !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }

        header#main-header {
            width: 100vw !important;
            left: 0 !important;
            right: 0 !important;
            box-sizing: border-box !important;
        }

        .minimal-footer {
            display: none !important;
        }

        .chat-outer-wrapper {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 81px);
            padding: 24px 30px;
            background: <?php echo $room_bg; ?>;
            box-sizing: border-box;
            gap: 20px;
        }

        .chat-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 50px;
            flex-shrink: 0;
        }

        .chat-header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .back-btn-circle {
            border-radius: 50%;
            width: 44px;
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            border: 1px solid #e5e7eb;
            background: white;
            text-decoration: none;
            transition: all 0.2s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }

        .back-btn-circle:hover {
            transform: scale(1.05);
            color: #111827;
            background: #f9fafb;
        }

        .chat-header-text {
            display: flex;
            flex-direction: column;
        }

        .chat-title {
            color: #1f2937;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin: 0;
            line-height: 1.2;
        }

        .chat-subtitle {
            color: #6b7280;
            font-size: 0.85rem;
            margin-top: 2px;
        }

        .chat-header-right {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .btn-toggle-sidebar {
            background: white !important;
            border: 1px solid #e5e7eb !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04) !important;
            width: 44px;
            height: 44px;
            border-radius: 12px !important;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4b5563 !important;
            padding: 0 !important;
            transition: all 0.2s !important;
        }

        .btn-toggle-sidebar:hover {
            background: #f9fafb !important;
            color: #111827 !important;
        }

        .btn-end-session {
            background-color: #b95c50 !important;
            color: white !important;
            border-radius: 12px !important;
            font-weight: 700 !important;
            padding: 10px 25px !important;
            border: none !important;
            box-shadow: 0 2px 8px rgba(185, 92, 80, 0.2) !important;
            transition: all 0.2s !important;
        }

        .btn-end-session:hover {
            background-color: #a34c41 !important;
            transform: translateY(-1px);
        }

        .btn-start-session {
            background-color: #22c55e !important;
            color: white !important;
            border-radius: 12px !important;
            font-weight: 700 !important;
            padding: 10px 25px !important;
            border: none !important;
            box-shadow: 0 2px 8px rgba(34, 197, 94, 0.2) !important;
            transition: all 0.2s !important;
        }

        .btn-start-session:hover {
            background-color: #1aa84f !important;
            transform: translateY(-1px);
        }

        .chat-cards-container {
            display: flex;
            flex: 1;
            min-height: 0;
            gap: 24px;
            align-items: stretch;
        }

        .chat-card-left {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #EDE8E1;
            box-shadow: 0 4px 24px rgba(0,0,0,0.02);
            overflow: hidden;
        }

        #chat-box {
            background-color: #FAF8F5 !important;
            background-image: radial-gradient(#e5e7eb 1.2px, transparent 1.2px);
            background-size: 24px 24px;
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            min-height: 0;
            scroll-behavior: smooth;
            padding: 24px 30px;
        }

        #chat-box::-webkit-scrollbar { width: 6px; }
        #chat-box::-webkit-scrollbar-track { background: transparent; }
        #chat-box::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }

        .chat-input-area {
            background: #ffffff;
            border-top: 1px solid #EDE8E1;
            padding: 16px 24px;
            position: relative;
        }

        #message-form {
            display: flex;
            flex-direction: row;
            flex-wrap: nowrap;
            align-items: center;
            gap: 12px;
            width: 100%;
            margin: 0;
            padding: 0;
        }

        #message-input {
            flex-grow: 1;
            padding: 14px 20px;
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            font-size: 1rem;
            border-radius: 12px;
            min-width: 0;
            color: #1f2937;
            box-shadow: none;
            transition: border-color 0.2s;
        }

        #message-input:focus {
            border-color: #A68A6C;
            outline: none;
        }

        .btn-emoji {
            flex-shrink: 0;
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #EBF2F7 !important;
            border: none !important;
            color: #4B7396 !important;
            box-shadow: none !important;
            transition: background-color 0.2s !important;
        }

        .btn-emoji:hover {
            background-color: #deebf5 !important;
        }

        .btn-send {
            flex-shrink: 0;
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: <?php echo $send_btn_bg; ?> !important;
            border: none !important;
            color: white !important;
            box-shadow: none !important;
            transition: all 0.2s !important;
        }

        .btn-send:hover {
            background-color: <?php echo $send_btn_hover; ?> !important;
            transform: scale(1.02);
        }

        #emoji-picker-wrapper {
            display: none;
            position: absolute;
            bottom: 80px;
            right: 24px;
            z-index: 1000;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-radius: 12px;
            background: white;
            overflow: hidden;
        }

        .chat-row-incoming {
            display: flex;
            justify-content: flex-start;
            margin-bottom: 24px;
            width: 100%;
            direction: ltr !important;
        }

        .chat-row-outgoing {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 24px;
            width: 100%;
            align-items: flex-end;
            direction: ltr !important;
        }

        .msg-bubble-incoming, .msg-bubble-outgoing {
            direction: <?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>;
            text-align: <?php echo $lang === 'ar' ? 'right' : 'left'; ?>;
        }

        #customParticipantsSidebar.hidden {
            display: none !important;
        }

        /* Participants Sidebar */
        #customParticipantsSidebar {
            width: 340px;
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #EDE8E1;
            box-shadow: 0 4px 24px rgba(0,0,0,0.02);
            padding: 24px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .participants-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            flex-shrink: 0;
        }

        #participants-list {
            flex: 1;
            overflow-y: auto;
            min-height: 0;
        }

        #participants-list::-webkit-scrollbar { width: 5px; }
        #participants-list::-webkit-scrollbar-track { background: transparent; }
        #participants-list::-webkit-scrollbar-thumb { background: #f3f4f6; border-radius: 10px; }

        /* Themed Avatar CSS */
        .p-avatar-wrapper {
            position: relative;
            width: 40px;
            height: 40px;
            flex-shrink: 0;
        }

        .p-avatar-circle-themed {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.1rem;
        }

        .online-dot {
            position: absolute;
            bottom: -1px;
            right: -1px;
            width: 12px;
            height: 12px;
            background-color: #22c55e;
            border: 2px solid #ffffff;
            border-radius: 50%;
        }

        /* Participant Card Styles */
        .participant-card-new {
            background: #ffffff;
            border: 1px solid #f3f4f6;
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: box-shadow 0.2s ease;
            position: relative;
        }

        .participant-card-new:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        }

        .p-name-new {
            font-size: 0.9rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 2px;
            line-height: 1.2;
        }

        .p-role-new {
            font-size: 0.78rem;
            color: #6b7280;
            margin: 0;
        }

        .p-status-pill-new {
            background-color: #f0fdf4;
            color: #16a34a;
            font-size: 0.72rem;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 9999px;
            margin-left: auto;
            flex-shrink: 0;
        }

        .p-actions-dropdown {
            position: relative;
            margin-left: 6px;
            flex-shrink: 0;
        }

        .p-actions-btn-new {
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            color: #9ca3af;
            font-size: 1.1rem;
            border-radius: 50%;
            width: 26px;
            height: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s, color 0.2s;
        }

        .p-actions-btn-new:hover {
            background-color: #f3f4f6;
            color: #374151;
        }

        .dropdown-menu-custom {
            display: none;
            position: absolute;
            right: 0;
            top: 28px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
            border-radius: 8px;
            padding: 4px 0;
            min-width: 130px;
            z-index: 1000;
        }

        .dropdown-menu-custom.show {
            display: block;
        }

        .dropdown-item-custom {
            display: block;
            width: 100%;
            padding: 8px 12px;
            font-size: 0.82rem;
            text-align: left;
            color: #374151;
            background: none;
            border: none;
            cursor: pointer;
            transition: background-color 0.15s;
        }

        .dropdown-item-custom:hover {
            background-color: #f3f4f6;
            color: #111827;
        }

        .dropdown-item-custom.text-danger:hover {
            background-color: #fee2e2;
        }

        /* Role Badges in Header */
        .role-badge {
            background: #f1f0ee;
            border: 1px solid #e2e1df;
            color: #78716c;
            padding: 8px 15px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        /* Responsive sidebar settings */
        @media (max-width: 992px) {
            #customParticipantsSidebar {
                position: fixed;
                right: 0;
                top: 81px;
                height: calc(100vh - 81px);
                z-index: 999;
                transform: translateX(100%);
                box-shadow: -10px 0 30px rgba(0,0,0,0.08);
                border-radius: 0;
            }
            #customParticipantsSidebar.active {
                transform: translateX(0);
            }
        }
    </style>
    <div class="chat-outer-wrapper">
        <!-- Header row -->
        <div class="chat-header-row">
            <div class="chat-header-left">
                <!-- Back Button -->
                <a href="<?php echo $leave_url; ?>" class="back-btn-circle">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div class="chat-header-text">
                    <h2 class="chat-title"><?php echo htmlspecialchars($session['topic']); ?></h2>
                    <div class="chat-subtitle">
                        <i class="fas fa-user-friends me-1"></i> <span id="participant-count-header">0</span> Participants
                    </div>
                </div>
            </div>
            <div class="chat-header-right">
                <?php if($is_therapist_owner): ?>
                    <div class="role-badge">
                        <i class="fas fa-shield-alt"></i> Moderator
                    </div>
                <?php endif; ?>
                
                <button id="toggleParticipantsBtn" class="btn btn-toggle-sidebar">
                    <i class="fas fa-user-friends"></i>
                </button>

                <?php if ($is_therapist_owner): ?>
                    <?php if ($session['status'] === 'active'): ?>
                        <button class="btn btn-end-session" onclick="updateSessionStatus('ended');">End Session</button>
                    <?php elseif ($session['status'] === 'scheduled'): ?>
                        <button class="btn btn-start-session" onclick="updateSessionStatus('active');">Start Session</button>
                    <?php endif; ?>
                <?php else: ?>
                    <button class="btn btn-end-session" style="background-color: #78716c !important; box-shadow: 0 2px 8px rgba(120, 113, 108, 0.2) !important;" onclick="leaveSession();">Leave Session</button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Cards container -->
        <div class="chat-cards-container">
            <div class="chat-card-left">
                <div id="chat-box">
                    <div class="text-center text-muted py-5">
                        <div class="spinner-border spinner-border-sm me-2"></div>
                        Connecting to chat...
                    </div>
                </div>

                <div class="chat-input-area">
                    <form id="message-form">
                        <input type="text" id="message-input" class="form-control" placeholder="Type a message..."
                            <?php echo (($session['status'] !== 'active' && !$is_therapist_owner) || $is_muted) ? 'disabled' : ''; ?>
                            autocomplete="off">
                        
                        <button type="button" id="emoji-button" class="btn btn-emoji" 
                            <?php echo (($session['status'] !== 'active' && !$is_therapist_owner) || $is_muted) ? 'disabled' : ''; ?>>
                            <i class="fas fa-smile"></i>
                        </button>
                        
                        <button type="submit" class="btn btn-send" <?php echo (($session['status'] !== 'active' && !$is_therapist_owner) || $is_muted) ? 'disabled' : ''; ?>>
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                    <div id="emoji-picker-wrapper">
                        <emoji-picker></emoji-picker>
                    </div>
                    <?php if ($is_muted): ?>
                        <small class="text-danger d-block mt-2"><i class="fas fa-microphone-slash me-1"></i>You are currently muted by the therapist.</small>
                    <?php elseif ($session['status'] === 'scheduled'): ?>
                        <small class="text-muted d-block mt-2"><i class="fas fa-info-circle me-1"></i>Waiting for therapist to start the session...</small>
                    <?php elseif ($session['status'] === 'ended'): ?>
                        <small class="text-muted d-block mt-2"><i class="fas fa-lock me-1"></i>Chat is closed as the session has ended.</small>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Participants Sidebar (Right Card) -->
            <div id="customParticipantsSidebar">
                <div class="participants-header">
                    <i class="fas fa-user-friends me-2" style="color: #444; font-size: 1.1rem;"></i>
                    <h4 class="fw-bold m-0" style="color: #444; font-size: 1.1rem; display: inline-block;">Participants (<span id="participant-count">0</span>)</h4>
                </div>
                <div id="participants-list">
                    <!-- Participants loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <!-- Report User Modal -->
    <div id="reportModal" class="modal" style="display:none; position:fixed; z-index:1050; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.4); align-items:center; justify-content:center;">
        <div class="modal-content" style="background-color:#fefefe; padding:30px; border:none; width:90%; max-width:500px; border-radius:12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3 style="margin:0;">Report User</h3>
                <span class="close-btn" onclick="closeReportModal()" style="cursor:pointer; font-size:1.5rem;">&times;</span>
            </div>
            <form id="reportForm" onsubmit="submitReport(event)">
                <input type="hidden" id="report_reported_user_id" name="reported_user_id">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display:block; margin-bottom:8px; font-weight:bold;">Reason for Report</label>
                    <select name="report_type" id="report_type" class="form-control" style="width:100%; padding:10px; border-radius:8px; border:1px solid #ccc;" required>
                        <option value="">Select a reason...</option>
                        <option value="Harassment">Harassment</option>
                        <option value="Spam">Spam</option>
                        <option value="Hate Speech">Hate Speech</option>
                        <option value="Inappropriate Content">Inappropriate Content</option>
                        <option value="Bad Words">Bad Words</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display:block; margin-bottom:8px; font-weight:bold;">Additional Details</label>
                    <textarea name="description" id="report_description" rows="4" class="form-control" style="width:100%; padding:10px; border-radius:8px; border:1px solid #ccc; resize:vertical;"></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 1.1rem; border-radius: 12px; font-weight: 600; background: #0d6efd; color: white; border: none; cursor: pointer;">Submit Report</button>
            </form>
        </div>
    </div>

    <script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@1/index.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const sessionId = "<?php echo $session_id; ?>";
        const currentUserId = "<?php echo $user_id; ?>";
        const currentUserRole = "<?php echo strtolower($user_role); ?>";
        const isTherapist = <?php echo $is_therapist_owner ? 'true' : 'false'; ?>;

        function canReport(viewerRole, authorRole) {
            if (!viewerRole || !authorRole) return false;
            viewerRole = viewerRole.toLowerCase();
            authorRole = authorRole.toLowerCase();
            
            if (authorRole === 'client') return true;
            if (authorRole === 'therapist') {
                if (viewerRole === 'therapist' || viewerRole === 'volunteer' || viewerRole === 'client') return false;
                return true;
            }
            if (authorRole === 'volunteer') return true;
            return false;
        }

        function getAvatarInfo(username, role) {
            const name = (username || '').toLowerCase();
            let color = '#f97316'; // orange default
            let icon = 'fa-user';
            let roleText = 'Volunteer';

            if (role && role.toLowerCase() === 'therapist') {
                roleText = 'Moderator';
            } else if (role && role.toLowerCase() === 'volunteer') {
                roleText = 'Volunteer';
            } else if (role && role.toLowerCase() === 'client') {
                roleText = 'Client';
            }

            if (name.includes('silentleaf')) {
                color = '#0d9488'; // teal-green
                icon = 'fa-leaf';
            } else if (name.includes('blueriver')) {
                color = '#2563eb'; // blue
                icon = 'fa-water';
            } else if (name.includes('impartialleaf')) {
                color = '#6d28d9'; // purple
                icon = 'fa-leaf';
            } else if (name.includes('hana')) {
                color = '#dc2626'; // red
                icon = 'fa-gear';
            } else if (name.includes('yousef')) {
                color = '#f97316'; // orange
                icon = 'fa-user';
            } else {
                // Fallback deterministic colors/icons
                let hash = 0;
                for (let i = 0; i < name.length; i++) {
                    hash = name.charCodeAt(i) + ((hash << 5) - hash);
                }
                const themes = [
                    { color: '#f97316', icon: 'fa-user' },
                    { color: '#0d9488', icon: 'fa-leaf' },
                    { color: '#2563eb', icon: 'fa-water' },
                    { color: '#6d28d9', icon: 'fa-leaf' },
                    { color: '#dc2626', icon: 'fa-gear' }
                ];
                const index = Math.abs(hash) % themes.length;
                color = themes[index].color;
                icon = themes[index].icon;
            }

            return { color, icon, roleText };
        }

        function toggleParticipantDropdown(event, userId) {
            event.stopPropagation();
            $('.dropdown-menu-custom').not('#dropdown-' + userId).removeClass('show');
            $('#dropdown-' + userId).toggleClass('show');
        }

        function toggleMuteParticipant(targetUserId, muteState) {
            $.post('api/group/update_session.php', {
                session_id: sessionId,
                action: 'mute',
                user_id: targetUserId,
                mute: muteState ? 'true' : 'false'
            }, function(res) {
                if (res.status === 'success') {
                    fetchParticipants();
                    fetchMessages();
                } else {
                    alert(res.message || 'Error updating mute status.');
                }
            }, 'json');
        }

        function removeParticipant(targetUserId) {
            if (!confirm('Are you sure you want to remove this participant from the session?')) return;
            $.post('api/group/update_session.php', {
                session_id: sessionId,
                action: 'remove',
                user_id: targetUserId
            }, function(res) {
                if (res.status === 'success') {
                    fetchParticipants();
                } else {
                    alert(res.message || 'Error removing participant.');
                }
            }, 'json');
        }

        function fetchMessages() {
            $.getJSON('api/group/fetch_messages.php', { session_id: sessionId }, function (data) {
                if (data.session_ended) {
                    window.location.href = '<?php echo $leave_url; ?>';
                    return;
                }
                if (data.status === 'success') {
                    const box = $('#chat-box');
                    const atBottom = box.scrollTop() + box.innerHeight() >= box[0].scrollHeight - 20;

                    if (data.messages.length > 0) {
                        if (box.find('.text-muted:not(.typing)').length) box.empty();

                        const renderedIds = box.find('[data-msg-id]').map(function () { return $(this).data('msg-id'); }).get();

                        data.messages.forEach(msg => {
                            if (renderedIds.includes(msg.message_id)) return;

                            const isMine = String(msg.sender_user_id) === String(currentUserId);
                            const isModerator = (isMine && isTherapist) || msg.author_name.includes('Dr.');
                            const avatarInfo = getAvatarInfo(msg.author_name, msg.role);

                            console.log("msg.sender_user_id:", msg.sender_user_id, "currentUserId:", currentUserId, "isMine:", isMine);

                            let msgHtml = '';
                            if (isMine) {
                                // Outgoing message (Right-aligned, soft beige bubble, timestamp and double checkmarks to the left)
                                msgHtml = `
                                <div class="chat-row-outgoing animate-fade-in" data-msg-id="${msg.message_id}">
                                    <div class="d-flex align-items-center me-2" style="font-size: 0.78rem; color: #a8a29e; margin-bottom: 2px;">
                                        <span class="me-1">${msg.sent_at}</span>
                                        <span style="color: #8c827a; font-weight: bold;">✓✓</span>
                                    </div>
                                    <div class="msg-bubble-outgoing" style="background-color: #F5EBE6; border-radius: 12px; padding: 10px 16px; color: #444; max-width: 70%; font-size: 0.95rem; line-height: 1.5; box-shadow: 0 2px 5px rgba(0,0,0,0.01);">
                                        ${msg.message_text}
                                    </div>
                                </div>`;
                            } else {
                                // Incoming message (Left-aligned, crisp white bubble, themed avatar, inline username and timestamp above)
                                msgHtml = `
                                <div class="chat-row-incoming animate-fade-in" data-msg-id="${msg.message_id}">
                                    <div class="p-avatar-wrapper me-3">
                                        <div class="p-avatar-circle-themed" style="background-color: ${avatarInfo.color};">
                                            <i class="fa-solid ${avatarInfo.icon}"></i>
                                        </div>
                                        <div class="online-dot"></div>
                                    </div>
                                    <div class="flex-grow-1" style="max-width: 70%;">
                                        <div class="d-flex align-items-center mb-1">
                                            <span class="fw-bold" style="color: #444; font-size: 0.9rem;">${msg.author_name}</span>
                                            <span style="color: #a8a29e; font-size: 0.8rem; margin-left: 8px;">${msg.sent_at}</span>
                                            ${(canReport(currentUserRole, msg.role)) ? `<button type="button" onclick="openReportModal('${msg.sender_user_id}')" style="background: none; border: none; cursor: pointer; color: #94a3b8; margin-left: 8px; padding: 0;" title="Report User"><i class="fas fa-flag" style="font-size: 0.75rem;"></i></button>` : ''}
                                        </div>
                                        <div class="msg-bubble-incoming" style="background-color: #ffffff; border: 1px solid #EDE8E1; border-radius: 12px; padding: 10px 16px; color: #444; font-size: 0.95rem; line-height: 1.5; display: inline-block; box-shadow: 0 2px 5px rgba(0,0,0,0.02);">
                                            ${msg.message_text}
                                        </div>
                                    </div>
                                </div>`;
                            }

                            box.append(msgHtml);
                        });

                        if (atBottom) box.scrollTop(box[0].scrollHeight);
                    }

                    const serverMsgIds = data.messages.map(m => m.message_id);
                    box.find('[data-msg-id]').each(function () {
                        const id = $(this).data('msg-id');
                        if (!serverMsgIds.includes(id)) $(this).fadeOut(300, function () { $(this).remove(); });
                    });

                    if (data.messages.length === 0 && box.children().length === 0) {
                        box.html('<div class="text-center text-muted py-5"><i class="fas fa-comments fa-2x d-block mb-3 opacity-25"></i>No messages yet. Say hi!</div>');
                    }
                }
            });
        }

        function fetchParticipants() {
            $.getJSON('api/group/fetch_participants.php', { session_id: sessionId }, function (data) {
                if (data.session_ended) {
                    window.location.href = '<?php echo $leave_url; ?>';
                    return;
                }
                if (data.status === 'success') {
                    const list = $('#participants-list');
                    list.empty();
                    $('#participant-count, #participant-count-header').text(data.participants.length);
                    data.participants.forEach(p => {
                        const isMuted = data.muted_users.includes(p.user_id);
                        const isYou = String(p.user_id) === String(currentUserId);
                        const avatarInfo = getAvatarInfo(p.name, p.role);
                        
                        let dropdownItemsHtml = '';
                        if (!isYou) {
                            if (isTherapist) {
                                dropdownItemsHtml += `
                                    <button class="dropdown-item-custom" onclick="toggleMuteParticipant('${p.user_id}', ${!isMuted})">
                                        <i class="fas fa-${isMuted ? 'microphone' : 'microphone-slash'} me-2"></i>${isMuted ? 'Unmute' : 'Mute'}
                                    </button>
                                    <button class="dropdown-item-custom text-danger" onclick="removeParticipant('${p.user_id}')">
                                        <i class="fas fa-user-minus me-2"></i>Remove
                                    </button>
                                `;
                            }
                            if (canReport(currentUserRole, p.role)) {
                                dropdownItemsHtml += `
                                    <button class="dropdown-item-custom" onclick="openReportModal('${p.user_id}')">
                                        <i class="fas fa-flag me-2"></i>Report
                                    </button>
                                `;
                            }
                        }
                        
                        const actionsMenuHtml = dropdownItemsHtml ? `
                            <div class="p-actions-dropdown">
                                <button type="button" class="p-actions-btn-new" onclick="toggleParticipantDropdown(event, '${p.user_id}')">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <div id="dropdown-${p.user_id}" class="dropdown-menu-custom">
                                    ${dropdownItemsHtml}
                                </div>
                            </div>
                        ` : `
                            <div class="p-actions-dropdown" style="width: 26px; height: 26px;"></div>
                        `;

                        list.append(`
                            <div class="participant-card-new animate-fade-in">
                                <div class="p-avatar-wrapper">
                                    <div class="p-avatar-circle-themed" style="background-color: ${avatarInfo.color};">
                                        <i class="fa-solid ${avatarInfo.icon}"></i>
                                    </div>
                                    <div class="online-dot"></div>
                                </div>
                                <div class="flex-grow-1" style="min-width: 0;">
                                    <div class="p-name-new text-truncate">${p.name}${isYou ? ' (You)' : ''}</div>
                                    <div class="p-role-new">${avatarInfo.roleText} ${isMuted ? '<i class="fas fa-microphone-slash text-danger ms-1" title="Muted"></i>' : ''}</div>
                                </div>
                                <span class="p-status-pill-new">Online</span>
                                ${actionsMenuHtml}
                            </div>
                        `);
                    });
                }
            });
        }

        function updateSessionStatus(status) {
            let msg = `Are you sure you want to set this session as ${status}?`;
            if (status === 'ended') {
                msg = `Are you sure you want to completely delete this session? This action cannot be undone.`;
            }
            
            if (!confirm(msg)) return;
            
            window.bypassUnload = true;
            
            $.post('api/group/update_session.php', { session_id: sessionId, action: 'status', status: status }, function (res) {
                if (res.status === 'success') {
                    location.reload();
                } else {
                    window.bypassUnload = false;
                    alert(res.message);
                }
            }, 'json');
        }

        function leaveSession() {
            if (!confirm('Are you sure you want to leave this session?')) return;
            window.bypassUnload = true;
            $.post('api/group/leave_session.php', { session_id: sessionId }, function (res) {
                if (res.status === 'success') {
                    window.location.href = '<?php echo $leave_url; ?>';
                } else {
                    window.bypassUnload = false;
                    alert('Error leaving session.');
                }
            }, 'json');
        }

        function openReportModal(reportedUserId) {
            document.getElementById('report_reported_user_id').value = reportedUserId;
            document.getElementById('report_type').value = '';
            document.getElementById('report_description').value = '';
            document.getElementById('reportModal').style.display = 'flex';
        }

        function closeReportModal() {
            document.getElementById('reportModal').style.display = 'none';
        }

        function submitReport(e) {
            e.preventDefault();
            const form = document.getElementById('reportForm');
            const formData = new FormData(form);

            fetch('api/report/submit_report.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Report submitted successfully.');
                    closeReportModal();
                } else {
                    alert(data.message || 'Error submitting report.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while submitting the report.');
            });
        }

        $('#message-form').submit(function (e) {
            e.preventDefault();
            const text = $('#message-input').val().trim();
            if (!text) return;

            $('#message-input').val('');
            $.post('api/group/send_message.php', { session_id: sessionId, message: text }, function (res) {
                if (res.status === 'success') {
                    fetchMessages();
                } else {
                    alert(res.message);
                }
            }, 'json');
        });

        $(document).ready(function () {
            // End session automatically if therapist owner leaves the page
            window.bypassUnload = false;
            
            // Listen for reload key commands to prevent ending session on manual refresh
            window.addEventListener('keydown', function (e) {
                if (e.key === 'F5' || (e.ctrlKey && e.key === 'r') || (e.metaKey && e.key === 'r')) {
                    window.bypassUnload = true;
                }
            });

            // End session automatically if therapist owner leaves the page
            if (isTherapist) {
                window.addEventListener('beforeunload', function () {
                    if (window.bypassUnload) return;
                    
                    const url = 'api/group/update_session.php';
                    const data = new FormData();
                    data.append('session_id', sessionId);
                    data.append('action', 'status');
                    data.append('status', 'ended');
                    navigator.sendBeacon(url, data);
                });
            }

            $('#chat-box').empty();
            fetchMessages();
            fetchParticipants();
            setInterval(fetchMessages, 3000);
            setInterval(fetchParticipants, 5000);

            const emojiButton = document.getElementById('emoji-button');
            const emojiPickerWrapper = document.getElementById('emoji-picker-wrapper');
            const messageInput = document.getElementById('message-input');
            
            if (emojiButton && emojiPickerWrapper) {
                emojiButton.addEventListener('click', () => {
                    const isVisible = emojiPickerWrapper.style.display === 'block';
                    emojiPickerWrapper.style.display = isVisible ? 'none' : 'block';
                });

                document.addEventListener('click', (event) => {
                    if (!emojiButton.contains(event.target) && !emojiPickerWrapper.contains(event.target)) {
                        emojiPickerWrapper.style.display = 'none';
                    }
                    if (!$(event.target).closest('.p-actions-dropdown').length) {
                        $('.dropdown-menu-custom').removeClass('show');
                    }
                });

                document.querySelector('emoji-picker').addEventListener('emoji-click', event => {
                    const cursorPosition = messageInput.selectionStart;
                    const textBefore = messageInput.value.substring(0, cursorPosition);
                    const textAfter = messageInput.value.substring(cursorPosition, messageInput.value.length);
                    messageInput.value = textBefore + event.detail.unicode + textAfter;
                    messageInput.selectionStart = messageInput.selectionEnd = cursorPosition + event.detail.unicode.length;
                    messageInput.focus();
                });
            }

            // Toggle participants sidebar
            const toggleSidebarBtn = document.getElementById('toggleParticipantsBtn');
            const participantsSidebar = document.getElementById('customParticipantsSidebar');
            if (toggleSidebarBtn && participantsSidebar) {
                toggleSidebarBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    if (window.innerWidth <= 992) {
                        participantsSidebar.classList.toggle('active');
                    } else {
                        participantsSidebar.classList.toggle('hidden');
                    }
                });
                document.addEventListener('click', (event) => {
                    if (window.innerWidth <= 992) {
                        if (!participantsSidebar.contains(event.target) && !toggleSidebarBtn.contains(event.target)) {
                            participantsSidebar.classList.remove('active');
                        }
                    }
                });
            }
        });
    </script>
    <style>
        /* Extra custom overriding styles (ensuring compatibility) */
    </style>
    <?php
    require_once 'includes/footer.php';

} else {
    // Default or 'list'
    $__group_embedded = !empty($__embedded_mode);
    
    // Redirect standalone list page for therapists and volunteers to dashboard
    if (!$__group_embedded && !empty($_SESSION['role'])) {
        $user_role_check = strtolower($_SESSION['role']);
        if ($user_role_check === 'therapist') {
            header("Location: therapist_dashboard.php?view=groups");
            exit();
        } elseif ($user_role_check === 'volunteer') {
            header("Location: volunteer/dashboard.php?view=groups");
            exit();
        }
    }

    $pagination_base = 'group_management.php?';
    if ($__group_embedded) {
        $pagination_base = (strtolower($user_role) === 'therapist') ? 'therapist_dashboard.php?view=groups&' : 'dashboard.php?view=groups&';
    }
    $body_class = 'group-sessions-page';
    if (!empty($_SESSION['role'])) {
        $role_lc = strtolower($_SESSION['role']);
        if ($role_lc === 'therapist') $body_class = 'role-therapist';
        elseif ($role_lc === 'volunteer') $body_class = 'role-volunteer';
        else $body_class = 'role-client';
    }
    if (!$__group_embedded) {
        require_once 'includes/header.php';
    }
    ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php
    $user_id = $_SESSION['user_id'];
    $user_role = strtolower($_SESSION['role'] ?? 'client');
    $is_client = ($user_role === 'client');
    $is_therapist = ($user_role === 'therapist');
    $is_volunteer = ($user_role === 'volunteer');
    
    if (!$__group_embedded) {
        if ($is_therapist) {
            include 'includes/therapist_sidebar.php';
        } elseif ($is_client) {
            require_once 'includes/dashboard_components.php';
            echo '<link rel="stylesheet" href="assets/css/dashboard-style.css">';
            echo '<div class="dashboard-wrapper">';
            render_sidebar('groups');
            echo '<div class="dashboard-main">';
            echo '<div class="container-fluid">';
            ?>
            <!-- Sidebar toggle button for mobile/desktop -->
            <div class="dashboard-header-row" style="display: flex; gap: 20px; align-items: stretch; margin-bottom: 30px;">
                <button id="sidebarToggle" class="sidebar-toggle-inline" aria-label="Toggle Sidebar" style="align-self: flex-start; margin-top: 10px; cursor: pointer;">
                    <i id="toggleIcon" class="fa-solid fa-bars-staggered"></i>
                </button>
            </div>
            <?php
        }
    } else {
        echo '<div style="width:100%;">';
        echo '<div class="container-fluid">';
    }

    $is_verified = true;
    if ($is_therapist) {
        try {
            $stmt_v = $pdo->prepare("SELECT verified FROM therapist WHERE user_id = ?");
            $stmt_v->execute([$user_id]);
            $is_verified = ($stmt_v->fetchColumn() ?? 0) == 1;
        } catch (PDOException $e) {
            $is_verified = false;
        }
    } elseif ($is_volunteer) {
        try {
            $stmt_v = $pdo->prepare("SELECT verification_status FROM volunteer WHERE user_id = ?");
            $stmt_v->execute([$user_id]);
            $is_verified = strtolower($stmt_v->fetchColumn() ?? '') !== 'pending';
        } catch (PDOException $e) {
            $is_verified = false;
        }
    }

    if ($is_therapist) {
        $c_bg = '#F4F9FD';
        $c_text_main = '#1A4D80';
        $c_text_muted = '#4A7AAB';
        $c_pill_bg = 'rgba(26, 77, 128, 0.1)';
    } elseif ($is_volunteer) {
        $c_bg = '#F0FDF4';
        $c_text_main = '#064E3B';
        $c_text_muted = '#059669';
        $c_pill_bg = 'rgba(5, 150, 105, 0.1)';
    } else {
        $c_bg = '#f5f0e6';
        $c_text_main = '#4f4438';
        $c_text_muted = '#887d72';
        $c_pill_bg = '#e6dfd1';
    }

    if ($is_therapist) {
        $t_bg = '#F4F9FD';
        $t_primary = '#337AB7';
        $t_primaryHover = '#286090';
        $t_iconBg = '#d1e5f7';
        $t_pillBg = '#e2eff9';
        $t_textDark = '#1A4D80';
        $t_textMuted = '#4A7AAB';
        $t_border = '#d1e5f7';
    } elseif ($is_volunteer) {
        $t_bg = '#F0FDF4';
        $t_primary = '#059669';
        $t_primaryHover = '#047857';
        $t_iconBg = '#D1FAE5';
        $t_pillBg = '#A7F3D0';
        $t_textDark = '#064E3B';
        $t_textMuted = '#059669';
        $t_border = '#6EE7B7';
    } else {
        $t_bg = '#F9F5F0';
        $t_primary = '#5d4f40';
        $t_primaryHover = '#4a3f33';
        $t_iconBg = '#ebdcd0';
        $t_pillBg = '#e2d3c5';
        $t_textDark = '#332a22';
        $t_textMuted = '#8b8076';
        $t_border = '#ede6dd';
    }
    $t_card = '#ffffff';
    ?>
    <script>
        tailwind.config = {
            corePlugins: {
                preflight: false,
            },
            theme: {
                extend: {
                    colors: {
                        brand: {
                            bg: '<?php echo $t_bg; ?>',
                            card: '<?php echo $t_card; ?>',
                            primary: '<?php echo $t_primary; ?>',
                            primaryHover: '<?php echo $t_primaryHover; ?>',
                            iconBg: '<?php echo $t_iconBg; ?>',
                            pillBg: '<?php echo $t_pillBg; ?>',
                            textDark: '<?php echo $t_textDark; ?>',
                            textMuted: '<?php echo $t_textMuted; ?>',
                            border: '<?php echo $t_border; ?>'
                        }
                    },
                    borderRadius: {
                        '3xl': '1.75rem',
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php
    $joined_session_ids = [];
    try {
        if (!empty($_SESSION['user_id'])) {
            $stmt_joined = $pdo->prepare("SELECT group_session_id FROM group_session_participants WHERE user_id = ?");
            $stmt_joined->execute([$_SESSION['user_id']]);
            $joined_session_ids = $stmt_joined->fetchAll(PDO::FETCH_COLUMN) ?: [];
        }
    } catch (Exception $e) {}

    try {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 6;
        if ($page < 1) $page = 1;
        if ($limit < 1) $limit = 6;

        $total_stmt = $pdo->query("SELECT COUNT(*) FROM group_sessions WHERE status IN ('scheduled', 'active')");
        $total_sessions = (int)$total_stmt->fetchColumn();
        $total_pages = ceil($total_sessions / $limit);
        if ($total_pages < 1) $total_pages = 1;
        if ($page > $total_pages) $page = $total_pages;
        $offset = ($page - 1) * $limit;

        $stmt = $pdo->prepare("
            SELECT gs.*, t.first_name, t.last_name, t.user_id as therapist_user_id, t.profile_image,
                   COALESCE(gsu.p_count, 0) as current_participants,
                   COALESCE(gs.max_participants, 15) as max_participants
            FROM group_sessions gs
            LEFT JOIN therapist t ON gs.therapist_id = t.therapist_id
            LEFT JOIN (
                SELECT group_session_id, COUNT(*) as p_count 
                FROM group_session_participants 
                GROUP BY group_session_id
            ) gsu ON gs.group_session_id = gsu.group_session_id
            WHERE gs.status IN ('scheduled', 'active')
            ORDER BY gs.session_date ASC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        $sessions = $stmt->fetchAll();
    } catch (PDOException $e) {
        $error = "Error loading sessions.";
        $sessions = [];
        $total_pages = 1;
        $page = 1;
        $limit = 6;
    }
    ?>
    <style>
        /* ── Fix: Tailwind CDN's .container class conflicts with the site's header
           centering. These overrides restore the correct navbar layout. ── */
        header#main-header .container,
        header .container {
            max-width: 1200px !important;
            margin-left: auto !important;
            margin-right: auto !important;
            padding-left: 20px !important;
            padding-right: 20px !important;
            width: 100% !important;
        }

        header#main-header {
            width: 100% !important;
            left: 0 !important;
            right: 0 !important;
        }

        .back-btn-circle {
            border-radius: 50%;
            width: 44px;
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            border: 1px solid #e5e7eb;
            background: white;
            text-decoration: none;
            transition: all 0.2s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }

        .back-btn-circle:hover {
            transform: scale(1.05);
            color: #111827;
            background: #f9fafb;
        }

        .feature-icon-box {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: <?php echo $t_iconBg; ?>;
            display: flex;
            align-items: center;
            justify-content: center;
            color: <?php echo $t_primary; ?>;
            font-size: 1.25rem;
            flex-shrink: 0;
        }
    </style>

    <?php
    // --- Notification & Redirect Error Banners ---
    echo '<div class="container mt-4 mb-2 max-w-7xl mx-auto px-6">';

    // 1. Redirect Errors
    if (isset($_GET['error'])) {
        $err = $_GET['error'];
        $topic = $_GET['topic'] ?? '';
        $msg = '';
        if ($err === 'expired') {
            $msg = "⌛ <strong>Session Expired</strong>: The session <strong>\"" . htmlspecialchars($topic) . "\"</strong> has ended and is closed.";
        } elseif ($err === 'room_full') {
            $msg = "🚫 <strong>Room Full</strong>: The group session <strong>\"" . htmlspecialchars($topic) . "\"</strong> has reached its maximum participant limit. You cannot join until someone leaves.";
        } elseif ($err === 'therapist_early') {
            $wait = $_GET['wait'] ?? '';
            $msg = "⏳ <strong>Too Early</strong>: The session <strong>\"" . htmlspecialchars($topic) . "\"</strong> is scheduled to start later. You can enter the room 3 minutes prior to the start time. Please wait approximately <strong>" . htmlspecialchars($wait) . " minute(s)</strong>.";
        } elseif ($err === 'room_locked') {
            $time = $_GET['time'] ?? '';
            $msg = "🔒 <strong>Room Locked</strong>: The session <strong>\"" . htmlspecialchars($topic) . "\"</strong> scheduled for <strong>" . htmlspecialchars($time) . "</strong> has not been started by the therapist yet. Please wait until the therapist starts the session.";
        }
        if ($msg) {
            echo '<div class="alert alert-warning mb-3" role="alert" style="border-radius:12px; font-family:\'Outfit\',sans-serif; background:#fffbeb; border:1px solid #fef3c7; color:#78350f;">' . $msg . '</div>';
        }
    }
    echo '</div>';
    ?>
    <?php if ($__group_embedded): ?>
        <div class="sessions-header-embedded max-w-7xl mx-auto px-6 mt-6 mb-8 text-left" style="font-family: 'Outfit', sans-serif;">
            <div style="display: inline-block; background: <?php echo $t_pillBg; ?>; border: 1px solid <?php echo $t_border; ?>; padding: 4px 12px; border-radius: 20px; font-weight: 600; font-size: 0.75rem; color: <?php echo $t_primary; ?>; margin-bottom: 12px;">
                Community Support
            </div>
            <h1 class="fw-bold mb-2" style="font-size: 2rem; color: <?php echo $t_textDark; ?>; letter-spacing: -0.5px; margin: 0;"><?php echo __('Group Therapy Sessions'); ?></h1>
            <p style="font-size: 0.95rem; color: <?php echo $t_textMuted; ?>; margin: 0;"><?php echo __('Connect with others in a supportive environment'); ?></p>
        </div>
    <?php else: ?>
        <div class="sessions-hero text-center" style="background-color: <?php echo $t_bg; ?>; padding: 60px 0 40px 0; border-bottom: 1px solid rgba(0,0,0,0.03); position: relative;">
            <div class="container d-flex flex-column align-items-center">
                <div style="text-align: center; position: relative; width: 100%;">
                    <?php if (strtolower($user_role) !== 'client' && !$__group_embedded): ?>
                        <a href="<?php echo (strtolower($user_role) === 'therapist') ? 'therapist_dashboard.php' : 'dashboard.php'; ?>" class="back-btn-circle" style="position: absolute; left: 0; top: 0;"><i class="fas fa-arrow-left"></i></a>
                    <?php endif; ?>
                    <div style="display: inline-block; background: <?php echo $t_pillBg; ?>; border: 1px solid <?php echo $t_border; ?>; padding: 6px 16px; border-radius: 20px; font-weight: 600; font-size: 0.85rem; color: <?php echo $t_primary; ?>; margin-bottom: 20px;">
                        Community Support
                    </div>
                    <h1 class="fw-bold mb-3" style="font-size: 3rem; color: <?php echo $t_textDark; ?>; letter-spacing: -0.5px; font-family: 'Outfit', sans-serif; margin: 0 0 10px 0;">Group Therapy Sessions</h1>
                    <p style="max-width: 650px; font-size: 1.1rem; line-height: 1.6; color: <?php echo $t_textMuted; ?>; text-align: center; margin: 0 auto 40px auto;">Connect with others in a supportive environment</p>

                    <!-- Features Row -->
                    <div class="row justify-content-center" style="max-width: 1000px; margin: 0 auto; display: flex; gap: 20px; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 250px; background: white; border: 1px solid #f1f5f9; border-radius: 16px; padding: 16px; display: flex; align-items: center; gap: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.01); text-align: left;">
                            <div class="feature-icon-box">
                                <i class="fa-solid fa-handshake"></i>
                            </div>
                            <div>
                                <h4 style="font-size: 0.95rem; font-weight: 700; color: <?php echo $t_textDark; ?>; margin: 0 0 3px 0; font-family: 'Outfit', sans-serif;">Peer Support</h4>
                                <p style="font-size: 0.8rem; color: <?php echo $t_textMuted; ?>; margin: 0; line-height: 1.4;">Connect with others facing similar challenges</p>
                            </div>
                        </div>
                        <div style="flex: 1; min-width: 250px; background: white; border: 1px solid #f1f5f9; border-radius: 16px; padding: 16px; display: flex; align-items: center; gap: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.01); text-align: left;">
                            <div class="feature-icon-box">
                                <i class="fa-solid fa-star"></i>
                            </div>
                            <div>
                                <h4 style="font-size: 0.95rem; font-weight: 700; color: <?php echo $t_textDark; ?>; margin: 0 0 3px 0; font-family: 'Outfit', sans-serif;">Professional Guidance</h4>
                                <p style="font-size: 0.8rem; color: <?php echo $t_textMuted; ?>; margin: 0; line-height: 1.4;">Led by licensed therapists who moderate discussions</p>
                            </div>
                        </div>
                        <div style="flex: 1; min-width: 250px; background: white; border: 1px solid #f1f5f9; border-radius: 16px; padding: 16px; display: flex; align-items: center; gap: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.01); text-align: left;">
                            <div class="feature-icon-box">
                                <i class="fa-solid fa-shield-halved"></i>
                            </div>
                            <div>
                                <h4 style="font-size: 0.95rem; font-weight: 700; color: <?php echo $t_textDark; ?>; margin: 0 0 3px 0; font-family: 'Outfit', sans-serif;">Free Access</h4>
                                <p style="font-size: 0.8rem; color: <?php echo $t_textMuted; ?>; margin: 0; line-height: 1.4;">All group sessions are completely free to join</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Sessions Grid -->
    <div class="container pb-5 mt-5" style="min-height: 60vh;">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-7xl mx-auto" style="font-family: 'Outfit', sans-serif;">
            <?php if (empty($sessions)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 50px 0;">
                    <div class="card glass-card p-5 border-0 rounded-4" style="background: white; border: 1px solid #e2e8f0; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.02);">
                        <i class="fas fa-couch fa-4x text-muted mb-4"></i>
                        <h2 class="text-secondary">All quiet in the rooms...</h2>
                        <p class="text-muted">Check back soon for new sessions scheduled by our therapists.</p>
                    </div>
                </div>
            <?php else:
                $themes = [
                    [
                        'name' => 'blue',
                        'color' => '#2563eb',
                        'bg_light' => '#eff6ff',
                        'icon_bg' => '#dbeafe',
                        'text' => '#1e40af',
                        'border' => '#bfdbfe',
                        'free_bg' => '#eff6ff'
                    ],
                    [
                        'name' => 'green',
                        'color' => '#16a34a',
                        'bg_light' => '#f0fdf4',
                        'icon_bg' => '#dcfce7',
                        'text' => '#166534',
                        'border' => '#bbf7d0',
                        'free_bg' => '#f0fdf4'
                    ],
                    [
                        'name' => 'purple',
                        'color' => '#7c3aed',
                        'bg_light' => '#f5f3ff',
                        'icon_bg' => '#ede9fe',
                        'text' => '#5b21b6',
                        'border' => '#ddd6fe',
                        'free_bg' => '#f5f3ff'
                    ],
                    [
                        'name' => 'orange',
                        'color' => '#ea580c',
                        'bg_light' => '#fff7ed',
                        'icon_bg' => '#ffedd5',
                        'text' => '#9a3412',
                        'border' => '#fed7aa',
                        'free_bg' => '#fff7ed'
                    ],
                    [
                        'name' => 'teal',
                        'color' => '#0d9488',
                        'bg_light' => '#f0fdfa',
                        'icon_bg' => '#ccfbf1',
                        'text' => '#115e59',
                        'border' => '#99f6e4',
                        'free_bg' => '#f0fdfa'
                    ],
                    [
                        'name' => 'pink',
                        'color' => '#db2777',
                        'bg_light' => '#fdf2f8',
                        'icon_bg' => '#fce7f3',
                        'text' => '#9d174d',
                        'border' => '#fbcfe8',
                        'free_bg' => '#fdf2f8'
                    ]
                ];

                foreach ($sessions as $index => $session):
                    $is_mine = ($user_role === 'therapist' && $session['therapist_user_id'] == $user_id);
                    $t = $themes[$index % count($themes)];
                    
                    $session_time = strtotime($session['session_date']);
                    $current_time = time();
                    $diff = $session_time - $current_time;
                    
                    $is_active = (strtolower($session['status']) === 'active');
                    $is_full = ($session['current_participants'] >= $session['max_participants']);
                    $user_already_joined = in_array($session['group_session_id'], $joined_session_ids);

                    if ($is_mine) {
                        // Therapist owner: Manage is unlocked starting 3 minutes before starting or if active
                        $is_unlocked = ($is_active || $diff <= 180);
                        $button_text = 'Manage';
                        $can_click = $is_unlocked;
                    } else {
                        // Clients/Volunteers: Join Session is unlocked only if session is active
                        $is_unlocked = $is_active;
                        $button_text = 'Join Session';
                        if ($is_full && !$user_already_joined) {
                            $can_click = false;
                            $button_text = 'Full';
                        } else {
                            $can_click = $is_unlocked;
                        }
                    }

                    // Dynamic Status badge styling
                    if ($is_full) {
                        $badge_label = 'Full';
                        $badge_bg = '#fee2e2';
                        $badge_color = '#ef4444';
                    } elseif ($is_active) {
                        $badge_label = 'Open';
                        $badge_bg = '#f0fdf4';
                        $badge_color = '#16a34a';
                    } else {
                        $badge_label = 'Waiting';
                        $badge_bg = '#fff7ed';
                        $badge_color = '#ea580c';
                    }
                    ?>
                    <!-- Individual Session Card -->
                    <div class="relative bg-white rounded-3xl border border-gray-100 shadow-sm flex flex-col justify-between transition-all duration-300 hover:-translate-y-1.5 hover:shadow-md animate-up" style="min-height: 400px; text-align: left;">
                        <!-- Top Card Body -->
                        <div class="p-6 pb-4">
                            <div class="flex justify-between items-center mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0" style="background-color: <?php echo $t['icon_bg']; ?>; color: <?php echo $t['color']; ?>;">
                                        <i class="fa-solid fa-users text-sm"></i>
                                    </div>
                                    <h3 class="text-base font-bold text-gray-800 m-0 leading-snug">
                                        <?php echo htmlspecialchars(!empty($session['room_name']) ? $session['room_name'] : $session['topic']); ?>
                                    </h3>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold flex items-center gap-1.5" style="background-color: <?php echo $badge_bg; ?>; color: <?php echo $badge_color; ?>;">
                                    <span class="w-1.5 h-1.5 rounded-full" style="background-color: <?php echo $badge_color; ?>;"></span> <?php echo $badge_label; ?>
                                </span>
                            </div>

                            <p class="text-gray-500 text-xs line-clamp-3 leading-relaxed mb-5" style="min-height: 54px;">
                                <?php echo htmlspecialchars(!empty($session['room_name']) ? $session['topic'] : (!empty($session['admin_comments']) ? $session['admin_comments'] : 'Connect with others on their journey to overcome challenges and find hope in this supportive group space.')); ?>
                            </p>

                            <hr class="border-gray-100/80 mb-5">

                            <div class="space-y-3">
                                <div class="flex items-center text-gray-500 text-xs">
                                    <i class="fa-regular fa-calendar-alt w-6 text-left text-sm" style="color: <?php echo $t['color']; ?>;"></i>
                                    <span><?php echo date('l, F j', strtotime($session['session_date'])); ?></span>
                                </div>
                                <div class="flex items-center text-gray-500 text-xs">
                                    <i class="fa-regular fa-clock w-6 text-left text-sm" style="color: <?php echo $t['color']; ?>;"></i>
                                    <span><?php echo date('H:i', strtotime($session['session_date'])); ?> <span class="text-gray-400 font-light">(60 minutes)</span></span>
                                </div>
                                <div class="flex items-center text-gray-500 text-xs">
                                    <i class="fa-solid fa-user-group w-6 text-left text-xs" style="color: <?php echo $t['color']; ?>;"></i>
                                    <span><?php echo $session['current_participants']; ?>/<?php echo $session['max_participants']; ?> participants</span>
                                </div>
                            </div>
                        </div>

                        <!-- Bottom Card Footer -->
                        <div class="relative p-6 pt-5 pb-7 mt-auto flex flex-col gap-4 rounded-b-[24px] border-t border-gray-100" style="background-color: <?php echo $t['bg_light']; ?>;">
                            <div class="flex justify-between items-center">
                                <div class="flex items-center">
                                    <img src="<?php echo !empty($session['profile_image']) ? $root . $session['profile_image'] : 'https://ui-avatars.com/api/?name=' . urlencode($session['first_name'] . ' ' . $session['last_name']) . '&background=random&color=fff'; ?>" class="w-9 h-9 rounded-full object-cover mr-3 border border-gray-200/80">
                                    <div>
                                        <p class="text-[10px] text-gray-400 font-light mb-0 uppercase tracking-wider">Moderator</p>
                                        <p class="font-semibold text-gray-700 text-xs mb-0">Dr. <?php echo htmlspecialchars($session['first_name'] . ' ' . $session['last_name']); ?></p>
                                    </div>
                                </div>
                                <?php if (($is_therapist || $is_volunteer) && !$is_verified): ?>
                                    <button onclick="alert('Access restricted until account is approved.')" class="bg-gray-300 text-gray-500 px-4 py-2 rounded-lg text-xs font-semibold shadow-sm cursor-not-allowed">
                                        <i class="fas fa-lock me-1"></i> Locked
                                    </button>
                                <?php elseif ($button_text === 'Full'): ?>
                                    <button class="bg-red-100 text-red-500 px-5 py-2.5 rounded-xl text-xs font-semibold shadow-sm cursor-not-allowed border-0" style="background-color: #fee2e2 !important; color: #ef4444 !important;" disabled>
                                        <i class="fas fa-users-slash me-1"></i> Full
                                    </button>
                                <?php elseif (!$can_click): ?>
                                    <button class="bg-gray-300 text-gray-500 px-5 py-2.5 rounded-xl text-xs font-semibold shadow-sm cursor-not-allowed border-0" style="background-color: #cbd5e1 !important; color: #64748b !important;" disabled>
                                        <i class="fas fa-lock me-1"></i> Locked
                                    </button>
                                <?php else: ?>
                                    <a href="<?php echo $root; ?>group_management.php?action=room&id=<?php echo $session['group_session_id']; ?>" class="bg-brand-primary hover:bg-brand-primaryHover text-white px-5 py-2.5 rounded-xl text-xs font-semibold transition-colors shadow-sm text-decoration-none d-inline-block hover:text-white">
                                        <?php echo $button_text; ?>
                                    </a>
                                <?php endif; ?>
                            </div>

                            <!-- Overlapping Free Pill -->
                            <div class="absolute left-1/2 -bottom-3.5 -translate-x-1/2">
                                <div class="text-xs font-semibold px-5 py-1.5 rounded-full flex items-center gap-1.5 shadow-sm border whitespace-nowrap m-0" style="background-color: <?php echo $t['free_bg']; ?>; color: <?php echo $t['color']; ?>; border-color: <?php echo $t['border']; ?>;">
                                    <i class="fa-solid fa-tag text-[10px]"></i> Free
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination Bar -->
        <?php if ($total_sessions > 0): ?>
            <div class="flex justify-center items-center max-w-7xl mx-auto mt-12 flex-wrap gap-4" style="font-family: 'Outfit', sans-serif;">
                <!-- Page numbers -->
                <div class="flex items-center gap-2">
                    <!-- Previous page ‹ -->
                    <a href="<?php echo $pagination_base; ?>page=<?php echo max(1, $page - 1); ?>&limit=<?php echo $limit; ?>" class="w-9 h-9 rounded-full border border-gray-200 bg-white flex items-center justify-center text-gray-500 text-sm hover:bg-gray-50 text-decoration-none transition-colors <?php echo $page === 1 ? 'pointer-events-none opacity-50' : ''; ?>">
                        ‹
                    </a>
                    
                    <!-- Page numbers list -->
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="<?php echo $pagination_base; ?>page=<?php echo $i; ?>&limit=<?php echo $limit; ?>" class="w-9 h-9 rounded-full border flex items-center justify-center text-sm font-semibold text-decoration-none transition-colors <?php echo $i === $page ? 'bg-brand-primary border-brand-primary text-white hover:text-white' : 'border-gray-200 bg-white text-gray-500 hover:bg-gray-50'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <!-- Next page › -->
                    <a href="<?php echo $pagination_base; ?>page=<?php echo min($total_pages, $page + 1); ?>&limit=<?php echo $limit; ?>" class="w-9 h-9 rounded-full border border-gray-200 bg-white flex items-center justify-center text-gray-500 text-sm hover:bg-gray-50 text-decoration-none transition-colors <?php echo $page === $total_pages ? 'pointer-events-none opacity-50' : ''; ?>">
                        ›
                    </a>
                </div>

                <!-- Per page limit dropdown selector -->
                <div class="flex items-center gap-2">
                    <select onchange="location = this.value;" class="bg-white border border-gray-200 rounded-lg px-3 py-1.5 text-xs text-gray-600 font-medium shadow-sm outline-none cursor-pointer">
                        <?php foreach ([3, 6, 9, 12] as $val): ?>
                            <option value="<?php echo $pagination_base; ?>page=1&limit=<?php echo $val; ?>" <?php echo $val === $limit ? 'selected' : ''; ?>>
                                <?php echo $val; ?> per page
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Group Guidelines Section -->
    <div style="background-color: <?php echo $t_bg; ?>; padding: 80px 0; border-top: 1px solid rgba(0,0,0,0.05); margin-top: 40px;">
        <div class="container animate-up">
            <div class="text-center mb-5">
                <h2 class="fw-bold mb-2" style="color: <?php echo $c_text_main; ?>; font-family: var(--font-heading); font-size: 2.25rem;">Group Guidelines</h2>
                <p style="color: <?php echo $c_text_muted; ?>; font-size: 1.1rem;">To ensure a safe and supportive environment for everyone</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card bg-white position-relative shadow-sm rounded-4 mb-3 p-4 guideline-card" style="border: 1px solid rgba(0,0,0,0.05);">
                        <h5 class="fw-bold mb-2" style="color: <?php echo $c_text_main; ?>; font-size: 1.15rem;">Respect Privacy</h5>
                        <p class="mb-0" style="color: <?php echo $c_text_muted; ?>; font-size: 0.95rem;">What's shared in the group stays in the group. Confidentiality is crucial.</p>
                    </div>
                    <div class="card bg-white position-relative shadow-sm rounded-4 mb-3 p-4 guideline-card" style="border: 1px solid rgba(0,0,0,0.05);">
                        <h5 class="fw-bold mb-2" style="color: <?php echo $c_text_main; ?>; font-size: 1.15rem;">Be Supportive</h5>
                        <p class="mb-0" style="color: <?php echo $c_text_muted; ?>; font-size: 0.95rem;">Listen actively and offer support without judgment.</p>
                    </div>
                    <div class="card bg-white position-relative shadow-sm rounded-4 mb-3 p-4 guideline-card" style="border: 1px solid rgba(0,0,0,0.05);">
                        <h5 class="fw-bold mb-2" style="color: <?php echo $c_text_main; ?>; font-size: 1.15rem;">Follow Moderator Guidance</h5>
                        <p class="mb-0" style="color: <?php echo $c_text_muted; ?>; font-size: 0.95rem;">The therapist moderator may mute participants or remove disruptive messages.</p>
                    </div>
                    <div class="card bg-white position-relative shadow-sm rounded-4 mb-3 p-4 guideline-card" style="border: 1px solid rgba(0,0,0,0.05);">
                        <h5 class="fw-bold mb-2" style="color: <?php echo $c_text_main; ?>; font-size: 1.15rem;">Stay On Topic</h5>
                        <p class="mb-0" style="color: <?php echo $c_text_muted; ?>; font-size: 0.95rem;">Keep discussions relevant to the session's focus.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        :root {
            --primary-soft: #eef2ff;
            --secondary-soft: #f1f5f9;
            --card-radius: 24px;
        }
        body {
            background-color: <?php echo $t_bg; ?> !important;
        }
        .guideline-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .guideline-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.04) !important;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .animate-up {
            animation: fadeInUp 0.6s ease-out forwards;
        }
    </style>

    <?php if ($user_role === 'therapist' && $is_verified): ?>
        <a href="#" id="btnCreateRoomModal" class="btn btn-lg rounded-pill shadow-lg d-flex align-items-center justify-content-center action-btn-float" style="position: fixed; bottom: 40px; right: 40px; background-color: #2b5c8f; color: white; border: none; font-weight: 700; padding: 15px 30px; font-size: 1.15rem; z-index: 9999; text-transform: uppercase; letter-spacing: 0.5px; text-decoration: none;">
            <i class="fas fa-plus me-2" style="font-size: 1.3rem;"></i> Create New Room
        </a>
        <style>
            .action-btn-float {
                transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            }
            .action-btn-float:hover {
                transform: translateY(-5px) scale(1.02);
                box-shadow: 0 20px 40px rgba(43, 92, 143, 0.3) !important;
                background-color: #1c446f !important;
                color: white;
            }
            /* Fallback Modal Styles */
            .modal { display: none; position: fixed; z-index: 1055; left: 0; top: 0; width: 100%; height: 100%; overflow-x: hidden; overflow-y: auto; outline: 0; background-color: rgba(0,0,0,0.55); backdrop-filter: blur(2px); align-items: center; justify-content: center; }
            .modal.show { display: flex; }
            .modal-dialog { position: relative; width: 100%; max-width: 500px; pointer-events: none; margin: 1.75rem auto; }
            .modal-content { position: relative; display: flex; flex-direction: column; width: 100%; pointer-events: auto; background-color: #fff; background-clip: padding-box; border: 1px solid rgba(0,0,0,.2); border-radius: 16px; outline: 0; box-shadow: 0 10px 30px rgba(0,0,0,0.1); overflow: hidden; }
            .modal-header { display: flex; flex-shrink: 0; align-items: center; justify-content: space-between; padding: 20px 25px; border-bottom: 1px solid #e2e8f0; }
            .modal-title { margin: 0; line-height: 1.5; font-size: 1.25rem; font-weight: 700; color: #1e293b; }
        </style>

        <!-- Create Group Therapy Room Modal -->
        <div class="modal fade" id="createRoomModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" style="font-family: 'Outfit', sans-serif; font-weight: 700; color: #0f172a; font-size: 1.5rem;">Create Group Therapy Room</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #94a3b8;"></button>
                    </div>
                    <form action="api/group/create_session.php" method="POST">
                        <div class="modal-body" style="padding: 25px; display: flex; flex-direction: column; gap: 20px; font-family: 'Outfit', sans-serif; text-align: left;">
                            
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <label class="form-label" style="font-weight: 600; color: #1e3a8a; font-size: 0.95rem;">Room Topic</label>
                                <input type="text" name="topic" placeholder="e.g. Anxiety & Stress Support" required
                                    style="width: 100%; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 0.95rem; outline: none; background: #f8fafc; color: #334155; box-sizing: border-box; transition: border-color 0.2s;"
                                    onfocus="this.style.borderColor='#2563eb'; this.style.background='#ffffff';"
                                    onblur="this.style.borderColor='#e2e8f0'; this.style.background='#f8fafc';">
                            </div>

                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <label class="form-label" style="font-weight: 600; color: #1e3a8a; font-size: 0.95rem;">Room Name (Optional)</label>
                                <input type="text" name="room_name" placeholder="e.g. Quiet Haven"
                                    style="width: 100%; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 0.95rem; outline: none; background: #f8fafc; color: #334155; box-sizing: border-box; transition: border-color 0.2s;"
                                    onfocus="this.style.borderColor='#2563eb'; this.style.background='#ffffff';"
                                    onblur="this.style.borderColor='#e2e8f0'; this.style.background='#f8fafc';">
                            </div>

                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <label class="form-label" style="font-weight: 600; color: #1e3a8a; font-size: 0.95rem;">Session Date & Time</label>
                                <input type="datetime-local" name="session_date" required
                                    style="width: 100%; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 0.95rem; outline: none; background: #f8fafc; color: #334155; box-sizing: border-box; transition: border-color 0.2s;"
                                    onfocus="this.style.borderColor='#2563eb'; this.style.background='#ffffff';"
                                    onblur="this.style.borderColor='#e2e8f0'; this.style.background='#f8fafc';">
                            </div>

                            <div style="display: flex; gap: 20px; width: 100%;">
                                <div style="flex: 1; display: flex; flex-direction: column; gap: 8px;">
                                    <label class="form-label" style="font-weight: 600; color: #1e3a8a; font-size: 0.95rem;">Max Enrolled Participants</label>
                                    <input type="number" name="max_participants" value="15" min="1" max="100" required
                                        style="width: 100%; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 0.95rem; outline: none; background: #f8fafc; color: #334155; box-sizing: border-box; transition: border-color 0.2s;"
                                        onfocus="this.style.borderColor='#2563eb'; this.style.background='#ffffff';"
                                        onblur="this.style.borderColor='#e2e8f0'; this.style.background='#f8fafc';">
                                </div>
                                <div style="flex: 1; display: flex; flex-direction: column; gap: 8px;">
                                    <label class="form-label" style="font-weight: 600; color: #1e3a8a; font-size: 0.95rem;">Duration (Minutes)</label>
                                    <input type="number" name="duration_minutes" value="60" min="5" max="300" required
                                        style="width: 100%; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 0.95rem; outline: none; background: #f8fafc; color: #334155; box-sizing: border-box; transition: border-color 0.2s;"
                                        onfocus="this.style.borderColor='#2563eb'; this.style.background='#ffffff';"
                                        onblur="this.style.borderColor='#e2e8f0'; this.style.background='#f8fafc';">
                                </div>
                            </div>

                        </div>
                        <div class="modal-footer" style="padding: 15px 25px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 12px; background: #f8fafc; border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="padding: 10px 20px; border-radius: 10px; font-weight: 600; font-size: 0.9rem; border: 1px solid #cbd5e1; background: #ffffff; color: #475569; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#ffffff'">Cancel</button>
                            <button type="submit" class="btn btn-primary" style="padding: 10px 20px; border-radius: 10px; font-weight: 600; font-size: 0.9rem; border: none; background: #2563eb; color: #ffffff; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#1d4ed8'" onmouseout="this.style.background='#2563eb'">Create Room</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const createBtn = document.getElementById('btnCreateRoomModal');
            const modal = document.getElementById('createRoomModal');
            if (createBtn && modal) {
                createBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    modal.style.display = 'flex';
                    modal.classList.add('show');
                });
            }

            // Modal Close
            if (modal) {
                modal.querySelectorAll('[data-bs-dismiss="modal"]').forEach(btn => {
                    btn.addEventListener('click', function() {
                        modal.style.display = 'none';
                        modal.classList.remove('show');
                    });
                });
                
                // Close on clicking overlay
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        modal.style.display = 'none';
                        modal.classList.remove('show');
                    }
                });
            }
        });
        </script>
    <?php endif; ?>
    <?php
    if ($__group_embedded) {
        echo '</div>'; // closes container-fluid
        echo '</div>'; // closes width:100%
    } elseif ($is_client) {
        echo '</div>'; // closes container-fluid
        require_once 'includes/footer.php';
        echo '</div>'; // closes dashboard-main
        echo '</div>'; // closes dashboard-wrapper
        require_once 'includes/sidebar_toggle_script.php';
    } else {
        require_once 'includes/footer.php';
    }
}
?>
