<?php
// api/notifications/get.php
require_once '../../includes/db_connect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'notifications' => [], 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'Client'; // Client, Therapist, Volunteer, Admin
$role_lower = strtolower($role);

$notifications = [];
$read_notifs = $_SESSION['read_notifications'] ?? [];
$cookie_name = 'sh_read_notifs_' . $user_id;
if (isset($_COOKIE[$cookie_name])) {
    $cookie_decoded = json_decode($_COOKIE[$cookie_name], true);
    if (is_array($cookie_decoded)) {
        $read_notifs = array_unique(array_merge($read_notifs, $cookie_decoded));
    }
}

// Helper caching for user profiles to avoid database query overhead in loops
$user_cache = [];
function getCachedUserDisplayNameAndAvatar($pdo, $userId) {
    global $user_cache;
    if (isset($user_cache[$userId])) {
        return $user_cache[$userId];
    }
    
    // 1. Check client
    $stmt = $pdo->prepare("SELECT name, anonymous_id, avatar_path FROM client WHERE user_id = ?");
    $stmt->execute([$userId]);
    $client = $stmt->fetch();
    if ($client) {
        $user_cache[$userId] = [
            'name' => $client['name'] ?: ($client['anonymous_id'] ?: 'Anonymous Client'),
            'avatar' => $client['avatar_path'],
            'role_theme' => 'client'
        ];
        return $user_cache[$userId];
    }
    
    // 2. Check therapist
    $stmt = $pdo->prepare("SELECT first_name, last_name, profile_image FROM therapist WHERE user_id = ?");
    $stmt->execute([$userId]);
    $therapist = $stmt->fetch();
    if ($therapist) {
        $user_cache[$userId] = [
            'name' => 'Dr. ' . $therapist['first_name'] . ' ' . $therapist['last_name'],
            'avatar' => $therapist['profile_image'],
            'role_theme' => 'therapist'
        ];
        return $user_cache[$userId];
    }
    
    // 3. Check volunteer
    $stmt = $pdo->prepare("SELECT first_name, last_name FROM volunteer WHERE user_id = ?");
    $stmt->execute([$userId]);
    $volunteer = $stmt->fetch();
    if ($volunteer) {
        $user_cache[$userId] = [
            'name' => $volunteer['first_name'] . ' ' . $volunteer['last_name'],
            'avatar' => null,
            'role_theme' => 'volunteer'
        ];
        return $user_cache[$userId];
    }
    
    // 4. Check admin
    $stmt = $pdo->prepare("SELECT email FROM admin WHERE admin_id = ?");
    $stmt->execute([$userId]);
    $admin = $stmt->fetch();
    if ($admin) {
        $user_cache[$userId] = [
            'name' => 'Admin (' . explode('@', $admin['email'])[0] . ')',
            'avatar' => null,
            'role_theme' => 'admin'
        ];
        return $user_cache[$userId];
    }
    
    $user_cache[$userId] = [
        'name' => 'Anonymous User',
        'avatar' => null,
        'role_theme' => 'system'
    ];
    return $user_cache[$userId];
}

try {
    // ----------------------------------------------------
    // A. Fetch Admin Announcements
    // ----------------------------------------------------
    $stmt = $pdo->query("SELECT notifications_json FROM admin");
    while ($row = $stmt->fetch()) {
        if (!empty($row['notifications_json'])) {
            $announcements = json_decode($row['notifications_json'], true);
            if (is_array($announcements)) {
                foreach ($announcements as $ann) {
                    $target = strtolower($ann['target_role'] ?? 'all');
                    // Check if targeted to user's role or all
                    if ($target === 'all' || $target === $role_lower) {
                        // Check if announcement is scheduled for now or past
                        $sched_time = strtotime($ann['scheduled_at'] ?? $ann['created_at']);
                        if ($sched_time <= time()) {
                            $id = 'admin_broadcast_' . ($ann['notification_id'] ?? uniqid());
                            $notifications[] = [
                                'id'          => $id,
                                'type'        => 'admin_broadcast',
                                'icon'        => 'fa-bullhorn',
                                'role_theme'  => 'admin',
                                'title'       => $ann['title'] ?? 'Administrative Announcement',
                                'body'        => $ann['message'] ?? '',
                                'link'        => ($role_lower === 'therapist') ? 'therapist_dashboard.php' : (($role_lower === 'volunteer') ? 'volunteer/dashboard.php' : 'dashboard.php'),
                                'created_at'  => $ann['created_at'] ?? date('Y-m-d H:i:s'),
                                'raw_time'    => strtotime($ann['created_at'] ?? 'now'),
                                'read'        => in_array($id, $read_notifs) ? 1 : 0
                            ];
                        }
                    }
                }
            }
        }
    }

    // Get specific role identifiers
    $client_id = null;
    $therapist_id = null;
    $volunteer_id = null;

    if ($role_lower === 'client') {
        $stmt_cli = $pdo->prepare("SELECT client_id FROM client WHERE user_id = ?");
        $stmt_cli->execute([$user_id]);
        $client_id = $stmt_cli->fetchColumn();
    } elseif ($role_lower === 'therapist') {
        $stmt_ther = $pdo->prepare("SELECT therapist_id FROM therapist WHERE user_id = ?");
        $stmt_ther->execute([$user_id]);
        $therapist_id = $stmt_ther->fetchColumn();
    } elseif ($role_lower === 'volunteer') {
        $stmt_vol = $pdo->prepare("SELECT volunteer_id FROM volunteer WHERE user_id = ?");
        $stmt_vol->execute([$user_id]);
        $volunteer_id = $stmt_vol->fetchColumn();
    }

    // ----------------------------------------------------
    // B. Session Notifications
    // ----------------------------------------------------
    if ($client_id) {
        // 1. Session Cancellations
        $stmt_c = $pdo->prepare("
            SELECT ps.private_session_id, ps.session_date, ps.amount, ps.cancel_reason,
                   CONCAT(t.first_name, ' ', t.last_name) as therapist_name
            FROM private_sessions ps
            JOIN therapist t ON ps.therapist_id = t.therapist_id
            WHERE ps.client_id = ? 
              AND ps.status = 'cancelled'
              AND (ps.cancellation_acknowledged = 0 OR ps.cancellation_acknowledged IS NULL)
              AND (ps.cancel_reason IS NULL OR ps.cancel_reason NOT LIKE 'Cancelled by Client%')
        ");
        $stmt_c->execute([$client_id]);
        $cancelled = $stmt_c->fetchAll();
        foreach ($cancelled as $c) {
            $id = 'session_cancelled_' . $c['private_session_id'];
            $refund_txt = $c['amount'] > 0 ? " A refund of $" . number_format($c['amount'], 2) . " has been credited to your wallet." : "";
            $notifications[] = [
                'id'          => $id,
                'type'        => 'session_cancelled',
                'icon'        => 'fa-calendar-times',
                'role_theme'  => 'therapist', // Action from therapist
                'title'       => 'Session Cancelled',
                'body'        => "Your session with Dr. " . $c['therapist_name'] . " scheduled for " . date('M d, g:i A', strtotime($c['session_date'])) . " was cancelled." . $refund_txt,
                'link'        => 'dashboard.php',
                'created_at'  => date('Y-m-d H:i:s'),
                'raw_time'    => time(), // Urgent immediate attention
                'read'        => in_array($id, $read_notifs) ? 1 : 0
            ];
        }

        // 2. Reschedules & Proposed Early Starts
        $stmt_r = $pdo->prepare("
            SELECT ps.private_session_id, ps.session_date, ps.proposed_datetime, ps.early_start_requested, ps.status,
                   CONCAT(t.first_name, ' ', t.last_name) as therapist_name
            FROM private_sessions ps
            JOIN therapist t ON ps.therapist_id = t.therapist_id
            WHERE ps.client_id = ? 
              AND (ps.status = 'pending_reschedule' OR ps.early_start_requested = 1)
        ");
        $stmt_r->execute([$client_id]);
        $resched = $stmt_r->fetchAll();
        foreach ($resched as $r) {
            if ($r['status'] === 'pending_reschedule' && !empty($r['session_date'])) {
                $id = 'session_reschedule_' . $r['private_session_id'];
                $notifications[] = [
                    'id'          => $id,
                    'type'        => 'session_reschedule',
                    'icon'        => 'fa-calendar-alt',
                    'role_theme'  => 'therapist',
                    'title'       => 'Reschedule Proposed',
                    'body'        => "Dr. " . $r['therapist_name'] . " proposed to reschedule your session to " . date('M d, g:i A', strtotime($r['session_date'])) . ". Please review it on your dashboard.",
                    'link'        => 'dashboard.php',
                    'created_at'  => date('Y-m-d H:i:s'),
                    'raw_time'    => time(),
                    'read'        => in_array($id, $read_notifs) ? 1 : 0
                ];
            }
            if ($r['early_start_requested'] == 1 && !empty($r['proposed_datetime'])) {
                $id = 'early_start_' . $r['private_session_id'];
                $notifications[] = [
                    'id'          => $id,
                    'type'        => 'early_start',
                    'icon'        => 'fa-bolt',
                    'role_theme'  => 'therapist',
                    'title'       => 'Early Start Offered',
                    'body'        => "Dr. " . $r['therapist_name'] . " offered to start early at " . date('g:i A', strtotime($r['proposed_datetime'])) . ". Confirmed availability on dashboard.",
                    'link'        => 'dashboard.php',
                    'created_at'  => date('Y-m-d H:i:s'),
                    'raw_time'    => time(),
                    'read'        => in_array($id, $read_notifs) ? 1 : 0
                ];
            }
        }
    }

    if ($therapist_id) {
        // Query scheduled group sessions starting in <= 3 minutes for this therapist owner
        try {
            $stmt_g_notif = $pdo->prepare("
                SELECT gs.group_session_id, gs.topic, gs.session_date
                FROM group_sessions gs
                WHERE gs.therapist_id = ? 
                  AND gs.status = 'scheduled'
                  AND gs.session_date <= DATE_ADD(NOW(), INTERVAL 3 MINUTE)
                  AND gs.session_date >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
            ");
            $stmt_g_notif->execute([$therapist_id]);
            $group_notifs = $stmt_g_notif->fetchAll() ?: [];
            foreach ($group_notifs as $gn) {
                $gn_diff = strtotime($gn['session_date']) - time();
                $gn_min = ceil($gn_diff / 60);
                $gn_id = 'group_session_start_' . $gn['group_session_id'];
                
                $notifications[] = [
                    'id'          => $gn_id,
                    'type'        => 'group_session_start',
                    'icon'        => 'fa-bell',
                    'role_theme'  => 'therapist',
                    'title'       => 'Group Session Starting Soon!',
                    'body'        => "Your group session on \"" . htmlspecialchars($gn['topic']) . "\" starts " . ($gn_min > 0 ? "in " . $gn_min . " min(s)" : "now") . ". Go to the room to start it.",
                    'link'        => 'group_management.php?action=room&id=' . $gn['group_session_id'],
                    'created_at'  => date('Y-m-d H:i:s'),
                    'raw_time'    => time() + 7200, // Very high priority, keep at top
                    'read'        => in_array($gn_id, $read_notifs) ? 1 : 0
                ];
            }
        } catch (Exception $e) {}

        // Reschedule request from Client to Therapist
        $stmt_tr = $pdo->prepare("
            SELECT ps.private_session_id, ps.session_date,
                   COALESCE(c.name, c.anonymous_id) as client_name
            FROM private_sessions ps
            JOIN client c ON ps.client_id = c.client_id
            WHERE ps.therapist_id = ? 
              AND ps.reschedule_requested = 1
              AND ps.status IN ('Active', 'scheduled', 'reserved', 'RESERVED')
        ");
        $stmt_tr->execute([$therapist_id]);
        $client_resched = $stmt_tr->fetchAll();
        foreach ($client_resched as $cr) {
            $id = 'reschedule_requested_' . $cr['private_session_id'];
            $notifications[] = [
                'id'          => $id,
                'type'        => 'reschedule_requested',
                'icon'        => 'fa-calendar-plus',
                'role_theme'  => 'client',
                'title'       => 'Reschedule Requested',
                'body'        => "Client " . $cr['client_name'] . " has requested a reschedule for your session on " . date('M d, g:i A', strtotime($cr['session_date'])) . ".",
                'link'        => 'therapist_dashboard.php',
                'created_at'  => date('Y-m-d H:i:s'),
                'raw_time'    => time(),
                'read'        => in_array($id, $read_notifs) ? 1 : 0
            ];
        }

        // Client session cancellations notification
        $stmt_tc = $pdo->prepare("
            SELECT ps.private_session_id, ps.session_date, ps.cancel_reason,
                   COALESCE(c.name, c.anonymous_id) as client_name
            FROM private_sessions ps
            JOIN client c ON ps.client_id = c.client_id
            WHERE ps.therapist_id = ? 
              AND ps.status = 'cancelled'
              AND ps.cancel_reason LIKE 'Cancelled by Client%'
              AND (ps.cancellation_acknowledged = 0 OR ps.cancellation_acknowledged IS NULL)
        ");
        $stmt_tc->execute([$therapist_id]);
        $cancelled_by_client = $stmt_tc->fetchAll();
        foreach ($cancelled_by_client as $cc) {
            $id = 'client_cancelled_' . $cc['private_session_id'];
            $notifications[] = [
                'id'          => $id,
                'type'        => 'client_cancelled',
                'icon'        => 'fa-calendar-times',
                'role_theme'  => 'client',
                'title'       => 'Session Cancelled by Client',
                'body'        => "Your session with Client " . $cc['client_name'] . " scheduled for " . date('M d, g:i A', strtotime($cc['session_date'])) . " was cancelled by the client.",
                'link'        => 'therapist_dashboard.php',
                'created_at'  => date('Y-m-d H:i:s'),
                'raw_time'    => time(),
                'read'        => in_array($id, $read_notifs) ? 1 : 0
            ];
        }
    }

    // Urgency Alert: Session starts soon (<= 60 mins) for both Client and Therapist
    if ($client_id || $therapist_id) {
        $cond = $client_id ? "ps.client_id = ?" : "ps.therapist_id = ?";
        $param = $client_id ? $client_id : $therapist_id;

        $stmt_soon = $pdo->prepare("
            SELECT ps.private_session_id, ps.session_date, ps.communication_method,
                   CONCAT(t.first_name, ' ', t.last_name) as therapist_name,
                   COALESCE(c.name, c.anonymous_id) as client_name
            FROM private_sessions ps
            LEFT JOIN therapist t ON ps.therapist_id = t.therapist_id
            LEFT JOIN client c ON ps.client_id = c.client_id
            WHERE $cond 
              AND ps.status IN ('Active', 'scheduled', 'reserved', 'RESERVED')
              AND ps.session_date >= NOW()
              AND ps.session_date <= DATE_ADD(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt_soon->execute([$param]);
        $soon_sessions = $stmt_soon->fetchAll();
        foreach ($soon_sessions as $ss) {
            $diff_mins = round((strtotime($ss['session_date']) - time()) / 60);
            if ($diff_mins >= 0 && $diff_mins <= 60) {
                $id = 'session_soon_' . $ss['private_session_id'] . '_' . ($diff_mins <= 15 ? 'urgent' : 'warning');
                $is_urgent = $diff_mins <= 15;
                
                $title = $is_urgent ? '⚠️ Session Starting Soon!' : '📅 Session Reminder';
                $partner = ($role_lower === 'client') ? "Dr. " . $ss['therapist_name'] : $ss['client_name'];
                $body = $is_urgent 
                    ? "Your session with " . $partner . " starts in " . max(0, $diff_mins) . " mins. Get ready and join now!"
                    : "You have an upcoming session with " . $partner . " in " . $diff_mins . " mins.";
                
                $link = ($role_lower === 'client') 
                    ? (($ss['communication_method'] === 'Video Session') ? "booking_success.php?session_id=" . $ss['private_session_id'] : "session_room.php?id=" . $ss['private_session_id'])
                    : "therapist_dashboard.php";

                $notifications[] = [
                    'id'          => $id,
                    'type'        => $is_urgent ? 'session_soon_urgent' : 'session_soon',
                    'icon'        => 'fa-clock',
                    'role_theme'  => ($role_lower === 'client') ? 'therapist' : 'client',
                    'title'       => $title,
                    'body'        => $body,
                    'link'        => $link,
                    'created_at'  => date('Y-m-d H:i:s'),
                    'raw_time'    => time() + 3600, // Boost priority so it stays at the top
                    'read'        => in_array($id, $read_notifs) ? 1 : 0
                ];
            }
        }
    }

    // ----------------------------------------------------
    // C. Social Q&A Likes and Comments
    // ----------------------------------------------------
    // 1. Likes/Helpful Votes on user's posts
    $stmt_l = $pdo->prepare("
        SELECT hv.vote_id, hv.created_at, hv.user_id as voter_id, q.post_id, q.post_type, q.content
        FROM helpful_votes hv
        JOIN community_qna q ON hv.item_id = q.post_id
        WHERE q.user_id = ? 
          AND hv.user_id != ?
        ORDER BY hv.created_at DESC 
        LIMIT 10
    ");
    $stmt_l->execute([$user_id, $user_id]);
    $likes = $stmt_l->fetchAll();
    foreach ($likes as $l) {
        $id = 'vote_' . $l['vote_id'];
        $voter = getCachedUserDisplayNameAndAvatar($pdo, $l['voter_id']);
        $post_preview = mb_strimwidth(strip_tags($l['content']), 0, 45, '...');
        
        $notifications[] = [
            'id'          => $id,
            'type'        => 'post_liked',
            'icon'        => 'fa-thumbs-up',
            'role_theme'  => $voter['role_theme'],
            'title'       => 'Helpful Vote Received',
            'body'        => $voter['name'] . " marked your " . ($l['post_type'] === 'reply' ? 'comment' : 'post') . " as helpful: \"" . $post_preview . "\"",
            'link'        => ($role_lower === 'volunteer') ? 'volunteer/community_qna.php' : 'community.php',
            'created_at'  => $l['created_at'],
            'raw_time'    => strtotime($l['created_at']),
            'read'        => in_array($id, $read_notifs) ? 1 : 0
        ];
    }

    // 2. Replies to user's posts
    $stmt_rep = $pdo->prepare("
        SELECT r.post_id as reply_id, r.created_at, r.user_id as replyer_id, p.post_id as parent_id, p.post_type, r.content as reply_content
        FROM community_qna r
        JOIN community_qna p ON r.parent_id = p.post_id
        WHERE p.user_id = ? 
          AND r.user_id != ? 
          AND r.post_type = 'reply'
        ORDER BY r.created_at DESC 
        LIMIT 10
    ");
    $stmt_rep->execute([$user_id, $user_id]);
    $replies = $stmt_rep->fetchAll();
    foreach ($replies as $rep) {
        $id = 'reply_' . $rep['reply_id'];
        $replyer = getCachedUserDisplayNameAndAvatar($pdo, $rep['replyer_id']);
        $reply_preview = mb_strimwidth(strip_tags($rep['reply_content']), 0, 50, '...');

        $notifications[] = [
            'id'          => $id,
            'type'        => 'post_reply',
            'icon'        => 'fa-comment',
            'role_theme'  => $replyer['role_theme'],
            'title'       => 'New Reply on Post',
            'body'        => $replyer['name'] . " replied to your post: \"" . $reply_preview . "\"",
            'link'        => ($role_lower === 'volunteer') ? 'volunteer/community_qna.php' : 'community.php',
            'created_at'  => $rep['created_at'],
            'raw_time'    => strtotime($rep['created_at']),
            'read'        => in_array($id, $read_notifs) ? 1 : 0
        ];
    }

    // ----------------------------------------------------
    // D. Content updates: Resource library additions (latest 5 resources)
    // ----------------------------------------------------
    $stmt_res = $pdo->query("
        SELECT resource_id, title, type, category, created_at, added_by_admin_id, added_by_therapist_id
        FROM resource
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    while ($res = $stmt_res->fetch()) {
        $id = 'resource_' . $res['resource_id'];
        $sender_theme = 'admin';
        if (!empty($res['added_by_therapist_id'])) {
            $sender_theme = 'therapist';
        }
        
        $notifications[] = [
            'id'          => $id,
            'type'        => 'new_resource',
            'icon'        => 'fa-book-open',
            'role_theme'  => $sender_theme,
            'title'       => 'New Wellness Resource',
            'body'        => "A new " . htmlspecialchars($res['type'] ?? 'article') . " \"" . htmlspecialchars($res['title']) . "\" is now available in the Resource Library.",
            'link'        => ($role_lower === 'volunteer') ? 'volunteer/resource_library.php' : 'resources.php',
            'created_at'  => $res['created_at'],
            'raw_time'    => strtotime($res['created_at']),
            'read'        => in_array($id, $read_notifs) ? 1 : 0
        ];
    }

} catch (PDOException $e) {
    // Return error if queries fail
    echo json_encode(['status' => 'error', 'notifications' => [], 'message' => $e->getMessage()]);
    exit();
}

// Sort notifications by timestamp descending (latest first)
usort($notifications, function($a, $b) {
    return $b['raw_time'] - $a['raw_time'];
});

// Limit total notifications to 30 to keep UI clean and performing well
$notifications = array_slice($notifications, 0, 30);

echo json_encode(['status' => 'success', 'notifications' => $notifications]);
