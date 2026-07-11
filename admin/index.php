<?php
// admin/index.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db_connect.php';
require_once '../includes/i18n.php';
ob_start('translate_html_buffer');

// Verify Admin Session
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle Community Q&A Actions (Ask, Comment, Edit, Delete) for Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id']) && strtolower($_SESSION['role'] ?? '') === 'admin') {


    // 3. Handle Community QA Edit and Delete Actions
    if (isset($_POST['community_qa_action'])) {
        $qa_action = $_POST['community_qa_action'];

        if ($qa_action === 'delete_post' && isset($_POST['post_id'])) {
            $post_id = $_POST['post_id'];
            $del = $pdo->prepare("DELETE FROM community_qna WHERE post_id = ?");
            $del->execute([$post_id]);
            header("Location: index.php?tab=community_qa&search=" . urlencode($_GET['search'] ?? ''));
            exit();
        }
        
        if ($qa_action === 'delete_comment' && isset($_POST['comment_id'])) {
            $comment_id = $_POST['comment_id'];
            $del = $pdo->prepare("DELETE FROM community_qna WHERE post_id = ?");
            $del->execute([$comment_id]);
            header("Location: index.php?tab=community_qa&search=" . urlencode($_GET['search'] ?? ''));
            exit();
        }
        
        if ($qa_action === 'edit_post' && isset($_POST['post_id']) && isset($_POST['new_content'])) {
            $post_id = $_POST['post_id'];
            $new_content = trim($_POST['new_content']);
            if (!empty($new_content)) {
                $upd = $pdo->prepare("UPDATE community_qna SET content = ? WHERE post_id = ?");
                $upd->execute([$new_content, $post_id]);
            }
            header("Location: index.php?tab=community_qa&search=" . urlencode($_GET['search'] ?? ''));
            exit();
        }

        if ($qa_action === 'edit_comment' && isset($_POST['comment_id']) && isset($_POST['new_content'])) {
            $comment_id = $_POST['comment_id'];
            $new_content = trim($_POST['new_content']);
            if (!empty($new_content)) {
                $upd = $pdo->prepare("UPDATE community_qna SET content = ? WHERE post_id = ?");
                $upd->execute([$new_content, $comment_id]);
            }
            header("Location: index.php?tab=community_qa&search=" . urlencode($_GET['search'] ?? ''));
            exit();
        }
    }
}

$admin_id = $_SESSION['user_id'];
$admin_role = $_SESSION['admin_role'] ?? 'Support Staff';
$display_name = $_SESSION['name'] ?? 'Admin';

// Permission Checking Helper
function hasAccess($category) {
    global $admin_role;
    if ($admin_role === 'Super Admin') return true;

    switch ($category) {
        case 'user':
            return false; // Super Admin only
        case 'therapist':
        case 'volunteer':
            return in_array($admin_role, ['Therapist Manager']);
        case 'session':
            return in_array($admin_role, ['Therapist Manager', 'Support Staff']);
        case 'report':
            return in_array($admin_role, ['Support Staff']);
        case 'challenge':
        case 'qa':
        case 'resource':
            return in_array($admin_role, ['Content Manager']);
        case 'notification':
            return in_array($admin_role, ['Support Staff']);
        case 'audit':
            return false; // Super Admin only
        default:
    }
}

// AI recommendation helper function (runs in O(1) query cost from pre-cached lists)
function getClientAIRecInMemory($client_id, $current_mood, $client_mood_logs, $therapists, $challenges, $resources, $group_sessions) {
    $history = $client_mood_logs ?? [];
    
    $negative_moods = ['sad', 'stressed', 'anxious', 'angry', 'depressed', 'fearful', 'lonely'];
    $positive_moods = ['happy', 'calm', 'excited', 'peaceful', 'content', 'grateful'];

    $neg_count = 0;
    $pos_count = 0;
    foreach ($history as $h) {
        $m = strtolower($h['mood']);
        if (in_array($m, $negative_moods)) $neg_count++;
        if (in_array($m, $positive_moods)) $pos_count++;
    }

    $total = count($history);
    $deterioration = false;
    $trend = "Stable Pattern";
    if ($total >= 3) {
        $recent = array_slice($history, 0, 3);
        $older = array_slice($history, 3, 4);
        
        $recent_neg = 0;
        foreach ($recent as $r) {
            if (in_array(strtolower($r['mood']), $negative_moods)) $recent_neg++;
        }
        $older_neg = 0;
        foreach ($older as $o) {
            if (in_array(strtolower($o['mood']), $negative_moods)) $older_neg++;
        }

        if ($recent_neg > $older_neg) {
            $trend = "Deteriorating (Emotional Decline)";
            $deterioration = true;
        } elseif ($recent_neg < $older_neg) {
            $trend = "Improving (Positive Trajectory)";
        } else {
            $trend = "Stable Pattern";
        }
    }

    $mood_lower = strtolower($current_mood);

    // Recommend clinical facilitators (Therapists)
    $specialty_keyword = 'depression';
    if (in_array($mood_lower, ['stressed', 'anxious'])) {
        $specialty_keyword = 'anxiety';
    } elseif (in_array($mood_lower, ['angry'])) {
        $specialty_keyword = 'anger';
    }

    $rec_therapists = [];
    foreach ($therapists as $t) {
        if ($t['verified'] == 1 && strpos(strtolower($t['specialties'] ?? ''), $specialty_keyword) !== false) {
            $rec_therapists[] = [
                'therapist_id' => $t['therapist_id'],
                'first_name' => $t['first_name'],
                'last_name' => $t['last_name'],
                'specialties' => $t['specialties'],
                'rating' => $t['avg_rating']
            ];
            if (count($rec_therapists) >= 2) break;
        }
    }

    // fallback if no matching specialty
    if (empty($rec_therapists)) {
        foreach ($therapists as $t) {
            if ($t['verified'] == 1) {
                $rec_therapists[] = [
                    'therapist_id' => $t['therapist_id'],
                    'first_name' => $t['first_name'],
                    'last_name' => $t['last_name'],
                    'specialties' => $t['specialties'],
                    'rating' => $t['avg_rating']
                ];
                if (count($rec_therapists) >= 2) break;
            }
        }
    }

    // Recommend daily challenges
    $target_diff = 'Easy';
    if ($mood_lower === 'happy' || $mood_lower === 'calm') {
        $target_diff = 'Medium';
    }

    $rec_challenges = [];
    foreach ($challenges as $c) {
        if (strtolower($c['difficulty'] ?? '') === strtolower($target_diff)) {
            $rec_challenges[] = [
                'challenge_id' => $c['challenge_id'],
                'title' => $c['title'],
                'description' => $c['description'],
                'difficulty' => $c['difficulty'],
                'points' => $c['points']
            ];
            if (count($rec_challenges) >= 2) break;
        }
    }
    if (empty($rec_challenges)) {
        foreach (array_slice($challenges, 0, 2) as $c) {
            $rec_challenges[] = [
                'challenge_id' => $c['challenge_id'],
                'title' => $c['title'],
                'description' => $c['description'],
                'difficulty' => $c['difficulty'],
                'points' => $c['points']
            ];
        }
    }

    // Recommend resources
    $resource_cat = 'mental health';
    if (in_array($mood_lower, ['sad', 'depressed'])) {
        $resource_cat = 'depression';
    } elseif (in_array($mood_lower, ['stressed', 'anxious'])) {
        $resource_cat = 'anxiety';
    }

    $rec_resources = [];
    foreach ($resources as $r) {
        if (strpos(strtolower($r['category'] ?? ''), $resource_cat) !== false || strpos(strtolower($r['title'] ?? ''), $resource_cat) !== false) {
            $rec_resources[] = [
                'resource_id' => $r['resource_id'],
                'title' => $r['title'],
                'type' => $r['type'],
                'category' => $r['category'],
                'url' => $r['url']
            ];
            if (count($rec_resources) >= 2) break;
        }
    }
    if (empty($rec_resources)) {
        foreach (array_slice($resources, 0, 2) as $r) {
            $rec_resources[] = [
                'resource_id' => $r['resource_id'],
                'title' => $r['title'],
                'type' => $r['type'],
                'category' => $r['category'],
                'url' => $r['url']
            ];
        }
    }

    // Recommend group sessions
    $rec_sessions = [];
    foreach ($group_sessions as $gs) {
        if (in_array(strtolower($gs['status'] ?? ''), ['active', 'scheduled'])) {
            $rec_sessions[] = [
                'group_session_id' => $gs['group_session_id'],
                'room_name' => $gs['room_name'],
                'topic' => $gs['topic'],
                'session_date' => $gs['session_date']
            ];
            if (count($rec_sessions) >= 2) break;
        }
    }

    return [
        'trend' => $trend,
        'neg_count' => $neg_count,
        'pos_count' => $pos_count,
        'total' => $total,
        'at_risk' => ($neg_count >= 3 || $deterioration),
        'therapists' => $rec_therapists,
        'challenges' => $rec_challenges,
        'resources' => $rec_resources,
        'sessions' => $rec_sessions
    ];
}

// Fetch stats and lists for the dashboard modules
try {
    // 1. Stats Cards
    $total_users = (int)$pdo->query("SELECT COUNT(*) FROM user")->fetchColumn();
    $total_therapists = (int)$pdo->query("SELECT COUNT(*) FROM therapist")->fetchColumn();
    $active_users = (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM user WHERE last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
    $new_users_month = (int)$pdo->query("SELECT COUNT(*) FROM user WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())")->fetchColumn();
    $total_ind_sessions = (int)$pdo->query("SELECT COUNT(*) FROM private_sessions")->fetchColumn();
    $total_grp_sessions = (int)$pdo->query("SELECT COUNT(*) FROM group_sessions")->fetchColumn();
    $total_journal_entries = (int)$pdo->query("SELECT COUNT(*) FROM journal_entry")->fetchColumn();
    $total_mood_records = (int)$pdo->query("SELECT COUNT(*) FROM client_mood_history")->fetchColumn();
    $total_challenges = (int)$pdo->query("SELECT COUNT(*) FROM daily_challenges")->fetchColumn();
    $total_reports = (int)$pdo->query("SELECT COUNT(*) FROM report")->fetchColumn();
    $pending_approvals = (int)$pdo->query("SELECT COUNT(*) FROM therapist WHERE verified = 0 OR verified IS NULL")->fetchColumn();
    $pending_volunteers_count = (int)$pdo->query("SELECT COUNT(*) FROM volunteer WHERE verification_status = 'Pending'")->fetchColumn();

    // 2. Lists for tables
    // Users List
    $users_stmt = $pdo->query("SELECT u.user_id, u.email, u.role, u.created_at, u.status, u.activity_score, u.last_login, c.name as client_name FROM user u LEFT JOIN client c ON u.user_id = c.user_id ORDER BY u.created_at DESC");
    $all_users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Therapists List
    $therapists_stmt = $pdo->query("SELECT t.therapist_id, t.first_name, t.last_name, u.email, t.specialties, t.hourly_rate, t.years_experience, t.rating as avg_rating, t.verified, u.status as user_status, tv.license_file_path FROM therapist t JOIN user u ON t.user_id = u.user_id LEFT JOIN therapist_verification tv ON t.therapist_id = tv.therapist_id ORDER BY t.created_at DESC");
    $therapists = $therapists_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Volunteers List
    $volunteers_stmt = $pdo->query("SELECT v.volunteer_id, v.first_name, v.last_name, u.email, v.bio, v.languages, v.skills, v.total_sessions, v.rating, v.certificates, v.verification_status, u.status as user_status FROM volunteer v JOIN user u ON v.user_id = u.user_id ORDER BY v.created_at DESC");
    $volunteers = $volunteers_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch individual sessions for indexing
    $sessions_by_therapist = [];
    foreach ($therapists as $t) {
        $sessions_by_therapist[$t['therapist_id']] = [];
    }
    $all_therapist_sessions_stmt = $pdo->query("SELECT ps.*, c.name as client_name FROM private_sessions ps JOIN client c ON ps.client_id = c.client_id ORDER BY ps.session_date DESC");
    while ($row = $all_therapist_sessions_stmt->fetch(PDO::FETCH_ASSOC)) {
        if (isset($sessions_by_therapist[$row['therapist_id']])) {
            $sessions_by_therapist[$row['therapist_id']][] = $row;
        }
    }

    // Individual Bookings List
    $bookings_stmt = $pdo->query("SELECT ps.private_session_id, ps.session_date, ps.communication_method, ps.amount, ps.status, ps.payment_status, ps.rating, ps.feedback, ps.therapist_id, ps.client_id, c.name as client_name, CONCAT(t.first_name, ' ', t.last_name) as therapist_name FROM private_sessions ps JOIN client c ON ps.client_id = c.client_id JOIN therapist t ON ps.therapist_id = t.therapist_id ORDER BY ps.session_date DESC");
    $all_bookings = $bookings_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group Sessions List
    $group_stmt = $pdo->query("SELECT gs.*, CONCAT(t.first_name, ' ', t.last_name) as therapist_name, (SELECT COUNT(*) FROM group_session_participants gsp WHERE gsp.group_session_id = gs.group_session_id) as participant_count FROM group_sessions gs LEFT JOIN therapist t ON gs.therapist_id = t.therapist_id ORDER BY gs.session_date DESC");
    $group_sessions = $group_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Reports Moderation List
    $reports_stmt = $pdo->query("SELECT r.*, u1.email as reporter_email, u2.email as reported_email FROM report r JOIN user u1 ON r.reporter_user_id = u1.user_id LEFT JOIN user u2 ON r.reported_user_id = u2.user_id ORDER BY r.created_at DESC");
    $reports = $reports_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Q&A List
    $qa_stmt = $pdo->query("SELECT q.*, u.email as author_email, u.role as author_role FROM community_qna q LEFT JOIN user u ON q.user_id = u.user_id ORDER BY q.created_at DESC");
    $qa_items = $qa_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Resource Library List
    $resources_stmt = $pdo->query("SELECT r.*, CASE WHEN r.added_by_admin_id IS NOT NULL THEN 'Admin' ELSE CONCAT(t.first_name, ' ', t.last_name) END as author_name FROM resource r LEFT JOIN therapist t ON r.added_by_therapist_id = t.therapist_id ORDER BY r.created_at DESC");
    $resources = $resources_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Audit Logs — merged from admin.audit_logs JSON column
    $audit_logs = [];
    $admin_rows = $pdo->query("SELECT admin_id, email, audit_logs FROM admin")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($admin_rows as $ar) {
        if (!empty($ar['audit_logs'])) {
            $entries = json_decode($ar['audit_logs'], true);
            if (is_array($entries)) {
                $audit_logs = array_merge($audit_logs, $entries);
            }
        }
    }
    usort($audit_logs, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
    $audit_logs = array_slice($audit_logs, 0, 100);

    // Challenges List
    $challenges_stmt = $pdo->query("SELECT * FROM daily_challenges ORDER BY created_at DESC");
    $challenges = $challenges_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Notifications — merged from admin.notifications_json JSON column
    $notifications = [];
    foreach ($admin_rows as $ar) {
        if (!empty($ar['notifications_json'])) {
            $notif_entries = json_decode($ar['notifications_json'], true);
            if (is_array($notif_entries)) {
                foreach ($notif_entries as &$ne) {
                    $ne['read_count'] = isset($ne['read_by']) ? count($ne['read_by']) : 0;
                }
                unset($ne);
                $notifications = array_merge($notifications, $notif_entries);
            }
        }
    }
    usort($notifications, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));

    // Mood/Journal placeholders — kept for AI rec helper even though tabs are removed
    $moods = [];
    $journals = [];

    // Chart Data
    // User Growth Trend (Last 6 Months)
    $chart_users = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count FROM user GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY month ASC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
    // Booking Trend
    $chart_bookings = $pdo->query("SELECT DATE_FORMAT(session_date, '%b') as month, COUNT(*) as count FROM private_sessions GROUP BY DATE_FORMAT(session_date, '%Y-%m') ORDER BY session_date ASC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
    // Mood Distribution
    $chart_moods = $pdo->query("SELECT mood, COUNT(*) as count FROM client_mood_history GROUP BY mood")->fetchAll(PDO::FETCH_ASSOC);
    // Challenge Completions
    $chart_challenges = $pdo->query("SELECT dc.title, (SELECT COUNT(*) FROM completed_challenges cc WHERE cc.challenge_id = dc.challenge_id) as completions FROM daily_challenges dc LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

    // --- Community Q&A Fetch Block ---
    // Predefined topics
    $comm_all_topics = [
        'ADHD' => 0,
        'Adoption/Foster Care' => 0,
        'Alcohol/Drug Abuse' => 0,
        'Anxiety' => 0,
        'Autism Spectrum' => 0,
        'Bipolar Disorder' => 0,
        'Borderline (BPD)' => 0,
        'Breakups' => 0,
        'Depression' => 0,
        'Relationship' => 0,
        'Trauma' => 0,
        'Other' => 0
    ];

    $comm_posts = [];
    $comm_search_query = $_GET['search'] ?? '';

    $comm_where_clause = " WHERE p.parent_id IS NULL AND (p.report_status IS NULL OR p.report_status != 'DeletedByTherapist')";
    $comm_params = [];
    if (!empty($comm_search_query)) {
        $comm_where_clause .= " AND (p.content LIKE ? OR p.category LIKE ? OR p.post_id = ?)";
        $comm_params[] = "%$comm_search_query%";
        $comm_params[] = "%$comm_search_query%";
        $comm_params[] = $comm_search_query;
    }

    $comm_query = "
        SELECT 
            p.post_id, p.content, p.created_at as timestamp, p.category as topic_type, p.post_type, p.user_id as post_user_id, p.helpful_count,
            COALESCE(
                CASE WHEN u.role = 'admin' THEN CONCAT(adm.email, ' (Admin)') END,
                c.anonymous_id, 
                CONCAT(t.first_name, ' ', t.last_name), 
                CONCAT(v.first_name, ' ', v.last_name), 
                'Unknown'
            ) as author_name,
            u.role, t.profile_image, c.avatar_path,
            (SELECT COUNT(*) FROM community_qna cc WHERE cc.parent_id = p.post_id) as answer_count
        FROM community_qna p
        LEFT JOIN client c ON p.user_id = c.user_id
        LEFT JOIN therapist t ON p.user_id = t.user_id
        LEFT JOIN volunteer v ON p.user_id = v.user_id
        LEFT JOIN user u ON p.user_id = u.user_id
        LEFT JOIN admin adm ON p.user_id = adm.admin_id
        $comm_where_clause
        ORDER BY p.created_at DESC
        LIMIT 20
    ";

    $comm_stmt = $pdo->prepare($comm_query);
    $comm_stmt->execute($comm_params);
    $comm_posts = $comm_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch comments for all fetched posts
    $comm_comments = [];
    if (!empty($comm_posts)) {
        $comm_post_ids = array_column($comm_posts, 'post_id');
        $comm_in_clause = str_repeat('?,', count($comm_post_ids) - 1) . '?';
        $comm_comment_query = "
            SELECT 
                cc.post_id as comment_id, cc.parent_id as post_id, cc.content, cc.created_at as timestamp, cc.user_id as comment_user_id,
                COALESCE(
                    CASE WHEN u.role = 'admin' THEN CONCAT(adm.email, ' (Admin)') END,
                    c.anonymous_id, 
                    CONCAT(t.first_name, ' ', t.last_name), 
                    CONCAT(v.first_name, ' ', v.last_name), 
                    'Unknown'
                ) as author_name,
                u.role, t.profile_image, c.avatar_path
            FROM community_qna cc
            LEFT JOIN client c ON cc.user_id = c.user_id
            LEFT JOIN therapist t ON cc.user_id = t.user_id
            LEFT JOIN volunteer v ON cc.user_id = v.user_id
            LEFT JOIN user u ON cc.user_id = u.user_id
            LEFT JOIN admin adm ON cc.user_id = adm.admin_id
            WHERE cc.parent_id IN ($comm_in_clause) AND (cc.report_status IS NULL OR cc.report_status != 'DeletedByTherapist')
            ORDER BY cc.created_at ASC
        ";
        $comm_comment_stmt = $pdo->prepare($comm_comment_query);
        $comm_comment_stmt->execute($comm_post_ids);
        $comm_all_comments = $comm_comment_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Group by post_id
        foreach ($comm_all_comments as $c) {
            $comm_comments[$c['post_id']][] = $c;
        }
    }

    // Fetch user's helpful vote status
    $comm_helpful_counts = [];  // [post_id => ['count'=>N, 'voted'=>bool]]
    if (!empty($comm_posts)) {
        foreach ($comm_posts as $p) {
            $comm_helpful_counts[$p['post_id']] = ['count' => (int)$p['helpful_count'], 'voted' => false];
        }

        if ($admin_id) {
            $comm_post_ids = array_column($comm_posts, 'post_id');
            $comm_in_ph    = implode(',', array_fill(0, count($comm_post_ids), '?'));
            try {
                $vstmt = $pdo->prepare("SELECT item_id FROM helpful_votes WHERE user_id=? AND item_id IN ($comm_in_ph) AND item_type='post'");
                $vstmt->execute(array_merge([$admin_id], $comm_post_ids));
                foreach ($vstmt->fetchAll(PDO::FETCH_COLUMN) as $vid) {
                    if (isset($comm_helpful_counts[$vid])) {
                        $comm_helpful_counts[$vid]['voted'] = true;
                    }
                }
            } catch (PDOException $e) { /* ignore */ }
        }
    }

    // Fetch topic counts
    $comm_topic_stmt = $pdo->query("SELECT category as post_type, COUNT(*) as count FROM community_qna WHERE parent_id IS NULL GROUP BY category");
    $comm_db_topics = $comm_topic_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    foreach ($comm_db_topics as $topic => $count) {
        $comm_all_topics[$topic] = $count;
    }

} catch (PDOException $e) {
    error_log('[ADMIN DASHBOARD] Initialization error: ' . $e->getMessage());
    $db_error = $e->getMessage();
}

$current_url = $_SERVER['REQUEST_URI'] ?? '';
$url_parts = parse_url($current_url);
$path = $url_parts['path'] ?? '';
if (isset($url_parts['query'])) {
    parse_str($url_parts['query'], $params);
    $url_en = $path . '?' . http_build_query(array_merge($params, ['lang' => 'en']));
    $url_ar = $path . '?' . http_build_query(array_merge($params, ['lang' => 'ar']));
} else {
    $url_en = $path . '?lang=en';
    $url_ar = $path . '?lang=ar';
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Safe Haven Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin-style.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="admin-mode">

    <!-- Admin Sidebar Overlay backdrop -->
    <div class="admin-sidebar-overlay" id="adminSidebarOverlay"></div>

    <!-- Left Fixed Navigation Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-logo">
            <img src="../assets/images/logo.png" alt="Safe Haven">
            <h1>Safe Haven Admin</h1>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-menu-item">
                <button class="sidebar-link active" data-tab="overview"><i class="fas fa-chart-line"></i><span>Overview</span></button>
            </li>
            <li class="sidebar-menu-item">
                <button class="sidebar-link" data-tab="users"><i class="fas fa-user-group"></i><span>User Management</span></button>
            </li>
            <li class="sidebar-menu-item">
                <button class="sidebar-link" data-tab="therapists"><i class="fas fa-user-md"></i><span>Therapists</span></button>
            </li>
            <li class="sidebar-menu-item">
                <button class="sidebar-link" data-tab="volunteers"><i class="fas fa-handshake"></i><span>Volunteers</span></button>
            </li>
            <li class="sidebar-menu-item">
                <button class="sidebar-link" data-tab="sessions"><i class="fas fa-clock"></i><span>Sessions</span></button>
            </li>
            <li class="sidebar-menu-item">
                <button class="sidebar-link" data-tab="reports"><i class="fas fa-flag"></i><span>Moderation Reports</span></button>
            </li>
            <li class="sidebar-menu-item">
                <button class="sidebar-link" data-tab="qa"><i class="fas fa-comments"></i><span>Q&A Forum</span></button>
            </li>
            <li class="sidebar-menu-item">
                <button class="sidebar-link" data-tab="community_qa"><i class="fas fa-people-group"></i><span>Community Q&A</span></button>
            </li>
            <li class="sidebar-menu-item">
                <button class="sidebar-link" data-tab="resources"><i class="fas fa-file-pdf"></i><span>Resource CMS</span></button>
            </li>
            <li class="sidebar-menu-item">
                <button class="sidebar-link" data-tab="notifications"><i class="fas fa-bullhorn"></i><span>Notifications</span></button>
            </li>
            <li class="sidebar-menu-item">
                <button class="sidebar-link" data-tab="audit"><i class="fas fa-shield-halved"></i><span>Audit Security Logs</span></button>
            </li>
        </ul>
        <div class="sidebar-footer">
            <button class="sidebar-link text-danger" onclick="window.location.href='../api/auth/logout.php'"><i class="fas fa-sign-out-alt"></i><span>Logout</span></button>
        </div>
    </aside>

    <!-- Main Content Panel Wrapper -->
    <main class="admin-main">
        <header class="admin-header-nav">
            <button class="admin-sidebar-toggle" id="adminSidebarToggle" aria-label="Toggle Sidebar">
                <i class="fas fa-bars"></i>
            </button>
            <div>
                <h2 style="font-size: 1.35rem; font-weight: 800;">Safe Haven Management Portal</h2>
                <p style="font-size: 0.825rem; color: var(--text-secondary);">Role Access Level: <strong><?php echo htmlspecialchars($admin_role); ?></strong></p>
            </div>
            <div class="admin-header-actions">
                <a href="<?php echo htmlspecialchars($lang === 'en' ? $url_ar : $url_en); ?>" class="theme-toggle-btn" title="<?php echo $lang === 'en' ? 'Switch to Arabic' : 'التحويل إلى الإنجليزية'; ?>" style="text-decoration: none; font-weight: bold; display: flex; align-items: center; justify-content: center; font-size: 0.85rem;">
                    <?php echo $lang === 'en' ? 'العربية' : 'English'; ?>
                </a>
                <button class="theme-toggle-btn" id="themeToggleBtn" title="Toggle Light/Dark Theme">
                    <i class="fas fa-moon"></i>
                </button>
                <div class="admin-user-profile">
                    <div class="admin-user-avatar">
                        <?php echo strtoupper(substr($display_name, 0, 1)); ?>
                    </div>
                    <div class="admin-user-info">
                        <h4><?php echo htmlspecialchars($display_name); ?></h4>
                        <span><?php echo htmlspecialchars($admin_role); ?></span>
                    </div>
                </div>
            </div>
        </header>

        <section class="admin-content-body">
            
            <!-- OVERVIEW DASHBOARD TAB -->
            <div id="overview" class="tab-content active">
                <div class="stats-cards-grid">
                    <div class="metric-card" onclick="switchTab('users')">
                        <div class="metric-details">
                            <h3>Total Users</h3>
                            <div class="metric-val"><?php echo number_format($total_users); ?></div>
                        </div>
                        <div class="metric-icon-box purple"><i class="fas fa-users"></i></div>
                    </div>
                    <div class="metric-card" onclick="switchTab('therapists')">
                        <div class="metric-details">
                            <h3>Total Therapists</h3>
                            <div class="metric-val"><?php echo number_format($total_therapists); ?></div>
                        </div>
                        <div class="metric-icon-box blue"><i class="fas fa-user-md"></i></div>
                    </div>
                    <div class="metric-card" onclick="switchTab('users')">
                        <div class="metric-details">
                            <h3>Active Users</h3>
                            <div class="metric-val"><?php echo number_format($active_users); ?></div>
                        </div>
                        <div class="metric-icon-box emerald"><i class="fas fa-user-check"></i></div>
                    </div>
                    <div class="metric-card" onclick="switchTab('users')">
                        <div class="metric-details">
                            <h3>New Users This Month</h3>
                            <div class="metric-val"><?php echo number_format($new_users_month); ?></div>
                        </div>
                        <div class="metric-icon-box info"><i class="fas fa-user-plus"></i></div>
                    </div>
                    <div class="metric-card" onclick="switchTab('sessions')">
                        <div class="metric-details">
                            <h3>Ind. Sessions</h3>
                            <div class="metric-val"><?php echo number_format($total_ind_sessions); ?></div>
                        </div>
                        <div class="metric-icon-box purple"><i class="fas fa-video"></i></div>
                    </div>
                    <div class="metric-card" onclick="switchTab('sessions')">
                        <div class="metric-details">
                            <h3>Group Sessions</h3>
                            <div class="metric-val"><?php echo number_format($total_grp_sessions); ?></div>
                        </div>
                        <div class="metric-icon-box info"><i class="fas fa-users-rectangle"></i></div>
                    </div>
                    <div class="metric-card" onclick="switchTab('reports')">
                        <div class="metric-details">
                            <h3>Total Reports</h3>
                            <div class="metric-val"><?php echo number_format($total_reports); ?></div>
                        </div>
                        <div class="metric-icon-box rose"><i class="fas fa-flag"></i></div>
                    </div>
                    <div class="metric-card" onclick="switchTab('therapists')">
                        <div class="metric-details">
                            <h3>Pending Therapists</h3>
                            <div class="metric-val"><?php echo number_format($pending_approvals); ?></div>
                        </div>
                        <div class="metric-icon-box warning"><i class="fas fa-clock"></i></div>
                    </div>
                    <div class="metric-card" onclick="switchTab('volunteers')">
                        <div class="metric-details">
                            <h3>Pending Volunteers</h3>
                            <div class="metric-val"><?php echo number_format($pending_volunteers_count); ?></div>
                        </div>
                        <div class="metric-icon-box warning"><i class="fas fa-handshake-angle"></i></div>
                    </div>
                </div>

                <div class="charts-grid">
                    <div class="dashboard-card">
                        <div class="card-title-bar">
                            <div>
                                <h2>User Growth Trend</h2>
                                <p>Platform expansion over recent months</p>
                            </div>
                        </div>
                        <div style="height: 300px; position: relative;">
                            <canvas id="userGrowthChart"></canvas>
                        </div>
                    </div>

                    <div class="dashboard-card">
                        <div class="card-title-bar">
                            <div>
                                <h2>Session Bookings</h2>
                                <p>Monthly volume of 1-on-1 sessions booked</p>
                            </div>
                        </div>
                        <div style="height: 300px; position: relative;">
                            <canvas id="sessionBookingChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- USER MANAGEMENT TAB -->
            <div id="users" class="tab-content">
                <?php if (!hasAccess('user')): ?>
                    <div class="access-denied-container">
                        <div class="access-denied-icon"><i class="fas fa-lock"></i></div>
                        <h2>Access Denied</h2>
                        <p>Only Super Admins can manage system users, suspend accounts, and view audit history logs.</p>
                    </div>
                <?php else: ?>
                    <div class="dashboard-card">
                        <div class="card-title-bar">
                            <div>
                                <h2>Platform User Management</h2>
                                <p>Browse, edit profiles, suspend, or reactivate user accounts</p>
                            </div>
                            <button class="btn-admin primary" onclick="openModal('addAdminModal')"><i class="fas fa-user-plus"></i> Add Administrator</button>
                        </div>
                        
                        <div class="table-control-bar">
                            <div class="search-container">
                                <i class="fas fa-search search-icon"></i>
                                <input type="text" id="userListSearch" class="search-input" placeholder="Search by email, name, or role...">
                            </div>
                            <div class="filter-inputs-group">
                                <select id="userRoleFilter" class="filter-select">
                                    <option value="all">All Roles</option>
                                    <option value="client">Client</option>
                                    <option value="therapist">Therapist</option>
                                    <option value="volunteer">Volunteer</option>
                                    <option value="admin">Admin</option>
                                </select>
                                <select id="userStatusFilter" class="filter-select">
                                    <option value="all">All Statuses</option>
                                    <option value="Active">Active</option>
                                    <option value="Suspended">Suspended</option>
                                    <option value="Deleted">Deleted</option>
                                </select>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="modern-table" id="userListTable">
                                <thead>
                                    <tr>
                                        <th>User ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Registration Date</th>
                                        <th>Last Login</th>
                                        <th>Status</th>
                                        <th>Activity Score</th>
                                        <th style="text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_users as $u): ?>
                                        <tr data-role="<?php echo htmlspecialchars($u['role']); ?>" data-status="<?php echo htmlspecialchars($u['status']); ?>">
                                            <td><code style="font-size:0.75rem;"><?php echo htmlspecialchars($u['user_id']); ?></code></td>
                                            <td>
                                                <div class="user-avatar-group">
                                                    <div class="user-avatar-circle">
                                                        <?php echo strtoupper(substr($u['client_name'] ?: ($u['role'] === 'admin' ? 'Admin' : 'User'), 0, 1)); ?>
                                                    </div>
                                                    <div class="user-name-subtitle">
                                                        <strong><?php echo htmlspecialchars($u['client_name'] ?: ($u['role'] === 'admin' ? 'System Administrator' : 'Anonymous')); ?></strong>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                                            <td><span class="badge badge-<?php echo ($u['role'] === 'client') ? 'primary' : (($u['role'] === 'therapist') ? 'warning' : 'success'); ?>"><?php echo htmlspecialchars($u['role']); ?></span></td>
                                            <td><?php echo htmlspecialchars(date('M d, Y', strtotime($u['created_at']))); ?></td>
                                            <td><?php echo $u['last_login'] ? htmlspecialchars(date('M d H:i', strtotime($u['last_login']))) : '<span style="color:#cbd5e1;">Never</span>'; ?></td>
                                            <td>
                                                <span class="status-badge <?php echo ($u['status'] === 'Active') ? 'status-active' : (($u['status'] === 'Suspended') ? 'status-pending' : 'status-suspended'); ?>">
                                                    <?php echo htmlspecialchars($u['status']); ?>
                                                </span>
                                            </td>
                                            <td><strong><?php echo (int)($u['activity_score']); ?></strong></td>
                                            <td style="text-align: right;">
                                                <div class="action-btns" style="justify-content: flex-end;">
                                                    <button type="button" class="btn-icon" title="Edit Profile Details" onclick="openEditUserModal(<?php echo htmlspecialchars(json_encode($u), ENT_QUOTES, 'UTF-8'); ?>)"><i class="fas fa-edit"></i></button>
                                                    <?php if ($u['status'] === 'Active'): ?>
                                                        <button type="button" class="btn-icon delete" title="Suspend Account" onclick="changeUserStatus('suspend_user', '<?php echo $u['user_id']; ?>')"><i class="fas fa-ban"></i></button>
                                                    <?php else: ?>
                                                        <button type="button" class="btn-icon" title="Reactivate Account" style="color:var(--success);" onclick="changeUserStatus('reactivate_user', '<?php echo $u['user_id']; ?>')"><i class="fas fa-rotate-left"></i></button>
                                                    <?php endif; ?>
                                                    <?php if ($u['status'] !== 'Deleted'): ?>
                                                        <button type="button" class="btn-icon delete" title="Soft Delete Account" onclick="changeUserStatus('delete_user', '<?php echo $u['user_id']; ?>')"><i class="fas fa-trash-can"></i></button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- THERAPIST TAB -->
            <div id="therapists" class="tab-content">
                <?php if (!hasAccess('therapist')): ?>
                    <div class="access-denied-container">
                        <div class="access-denied-icon"><i class="fas fa-lock"></i></div>
                        <h2>Access Denied</h2>
                        <p>Only the Therapist Manager or Super Admins can verify clinical profiles, reject applications, and view therapist performance stats.</p>
                    </div>
                <?php else: ?>
                    <!-- Pending approvals segment -->
                    <div class="dashboard-card">
                        <div class="card-title-bar">
                            <div>
                                <h2>Pending Approvals</h2>
                                <p>Licensed therapists awaiting verification check</p>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Specialty</th>
                                        <th>Experience</th>
                                        <th>Hourly Rate</th>
                                        <th>Certifications</th>
                                        <th style="text-align: right;">Approval Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $pending_list = array_filter($therapists, function($t) { return !$t['verified']; });
                                    if (empty($pending_list)):
                                    ?>
                                        <tr><td colspan="6" style="text-align:center; color: var(--text-secondary); padding:2rem;">All therapist applications verified.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($pending_list as $pt): ?>
                                            <tr>
                                                <td><strong>Dr. <?php echo htmlspecialchars($pt['first_name'] . ' ' . $pt['last_name']); ?></strong><br><small style="color:var(--text-muted);"><?php echo htmlspecialchars($pt['email']); ?></small></td>
                                                <td><span class="badge badge-primary"><?php echo htmlspecialchars($pt['specialties'] ?: 'General'); ?></span></td>
                                                <td><?php echo (int)($pt['years_experience']); ?> years</td>
                                                <td><strong>$<?php echo number_format($pt['hourly_rate'] ?? 0, 2); ?></strong></td>
                                                <td>
                                                    <?php if (!empty($pt['license_file_path'])): ?>
                                                        <span onclick="showCertificate('<?php echo htmlspecialchars(addslashes($pt['license_file_path'])); ?>', '<?php echo htmlspecialchars(addslashes('Dr. ' . $pt['first_name'] . ' ' . $pt['last_name'])); ?>')" style="color:var(--primary); font-weight:700; cursor:pointer;"><i class="fas fa-file-pdf"></i> View Certificate</span>
                                                    <?php else: ?>
                                                        <span style="color:var(--text-muted);">No certificate uploaded</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="text-align: right;">
                                                    <div class="action-btns" style="justify-content: flex-end;">
                                                        <button class="btn-admin primary" style="padding: 0.35rem 0.75rem; font-size: 0.75rem;" onclick="approveTherapist('<?php echo $pt['therapist_id']; ?>')"><i class="fas fa-check"></i> Approve</button>
                                                        <button class="btn-admin danger" style="padding: 0.35rem 0.75rem; font-size: 0.75rem;" onclick="rejectTherapist('<?php echo $pt['therapist_id']; ?>')"><i class="fas fa-times"></i> Reject</button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- All therapists list -->
                    <div class="dashboard-card">
                        <div class="card-title-bar">
                            <div>
                                <h2>Active Therapists & Performance</h2>
                                <p>Manage clinical profiles and view session success analytics</p>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Therapist Name</th>
                                        <th>Specialty</th>
                                        <th>Rate</th>
                                        <th>Verified</th>
                                        <th>Avg Rating</th>
                                        <th>Total Sessions</th>
                                        <th>Active Clients</th>
                                        <th>Completion Rate</th>
                                        <th style="text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($therapists as $t): ?>
                                        <?php
                                        // Performance calculations
                                        $t_sessions = $sessions_by_therapist[$t['therapist_id']] ?? [];
                                        $completed_s = array_filter($t_sessions, function($s) { return $s['status'] === 'Completed'; });
                                        $completion_pct = count($t_sessions) > 0 ? (count($completed_s) / count($t_sessions)) * 100 : 100;
                                        $distinct_clients = count(array_unique(array_column($t_sessions, 'client_id')));
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="user-avatar-group">
                                                    <div class="user-avatar-circle" style="background:var(--warning-light); color:var(--warning);">
                                                        <?php echo strtoupper(substr($t['first_name'] ?: 'T', 0, 1)); ?>
                                                    </div>
                                                    <div class="user-name-subtitle">
                                                        <strong>Dr. <?php echo htmlspecialchars($t['first_name'] . ' ' . $t['last_name']); ?></strong>
                                                        <span><?php echo htmlspecialchars($t['email']); ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($t['specialties'] ?: 'General'); ?></td>
                                            <td><strong>$<?php echo number_format($t['hourly_rate'] ?? 0, 2); ?></strong></td>
                                            <td>
                                                <span class="badge <?php echo $t['verified'] ? 'badge-success' : 'badge-danger'; ?>">
                                                    <?php echo $t['verified'] ? 'Verified' : 'Unverified'; ?>
                                                </span>
                                            </td>
                                            <td><span style="color:#f59e0b; font-weight:700;"><i class="fas fa-star"></i> <?php echo $t['avg_rating'] ? number_format($t['avg_rating'], 2) : 'N/A'; ?></span></td>
                                            <td><strong><?php echo count($t_sessions); ?></strong></td>
                                            <td><?php echo $distinct_clients; ?></td>
                                            <td>
                                                <div style="display:flex; align-items:center; gap:0.5rem;">
                                                    <div style="flex:1; width:50px; height:6px; background:#f1f5f9; border-radius:4px; overflow:hidden;">
                                                        <div style="width:<?php echo $completion_pct; ?>%; height:100%; background:var(--success);"></div>
                                                    </div>
                                                    <small style="font-weight:700; font-size:0.75rem;"><?php echo round($completion_pct); ?>%</small>
                                                </div>
                                            </td>
                                            <td style="text-align: right;">
                                                <div class="action-btns" style="justify-content: flex-end;">
                                                    <?php if (!empty($t['license_file_path'])): ?>
                                                        <button class="btn-icon" style="color:var(--primary);" title="View License/Certificate" onclick="showCertificate('<?php echo htmlspecialchars(addslashes($t['license_file_path'])); ?>', '<?php echo htmlspecialchars(addslashes('Dr. ' . $t['first_name'] . ' ' . $t['last_name'])); ?>')"><i class="fas fa-file-pdf"></i></button>
                                                    <?php endif; ?>
                                                    <button class="btn-admin primary" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;" onclick="viewTherapistSessions(<?php echo htmlspecialchars(json_encode($t), ENT_QUOTES, 'UTF-8'); ?>, <?php echo htmlspecialchars(json_encode($t_sessions), ENT_QUOTES, 'UTF-8'); ?>)"><i class="fas fa-comments"></i> Sessions</button>
                                                    <button class="btn-icon" title="Edit Profile Details" onclick="openEditTherapistModal(<?php echo htmlspecialchars(json_encode($t), ENT_QUOTES, 'UTF-8'); ?>)"><i class="fas fa-edit"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- VOLUNTEERS TAB -->
            <div id="volunteers" class="tab-content">
                <?php if (!hasAccess('volunteer')): ?>
                    <div class="access-denied-container">
                        <div class="access-denied-icon"><i class="fas fa-lock"></i></div>
                        <h2>Access Denied</h2>
                        <p>Only the Therapist Manager or Super Admins can verify volunteer profiles, approve/reject applications, and view volunteer stats.</p>
                    </div>
                <?php else: ?>
                    <!-- Pending approvals segment -->
                    <div class="dashboard-card">
                        <div class="card-title-bar">
                            <div>
                                <h2>Pending Approvals</h2>
                                <p>Volunteers awaiting certificate verification check</p>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Languages</th>
                                        <th>Skills</th>
                                        <th>Certificates</th>
                                        <th style="text-align: right;">Approval Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $pending_vol_list = array_filter($volunteers, function($v) { return strtolower($v['verification_status'] ?? '') === 'pending'; });
                                    if (empty($pending_vol_list)):
                                    ?>
                                        <tr><td colspan="5" style="text-align:center; color: var(--text-secondary); padding:2rem;">All volunteer applications verified.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($pending_vol_list as $pv): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($pv['first_name'] . ' ' . $pv['last_name']); ?></strong><br><small style="color:var(--text-muted);"><?php echo htmlspecialchars($pv['email']); ?></small></td>
                                                <td><?php echo htmlspecialchars($pv['languages'] ?: 'Not specified'); ?></td>
                                                <td><?php echo htmlspecialchars($pv['skills'] ?: 'Not specified'); ?></td>
                                                <td>
                                                    <?php if (!empty($pv['certificates'])): ?>
                                                        <span onclick="showCertificate('<?php echo htmlspecialchars(addslashes($pv['certificates'])); ?>', '<?php echo htmlspecialchars(addslashes($pv['first_name'] . ' ' . $pv['last_name'])); ?>')" style="color:var(--primary); font-weight:700; cursor:pointer;"><i class="fas fa-file-image"></i> View Certificate</span>
                                                    <?php else: ?>
                                                        <span style="color:var(--text-muted);">No certificate uploaded</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="text-align: right;">
                                                    <div class="action-btns" style="justify-content: flex-end;">
                                                        <button class="btn-admin primary" style="padding: 0.35rem 0.75rem; font-size: 0.75rem;" onclick="approveVolunteer('<?php echo $pv['volunteer_id']; ?>')"><i class="fas fa-check"></i> Approve</button>
                                                        <button class="btn-admin danger" style="padding: 0.35rem 0.75rem; font-size: 0.75rem;" onclick="rejectVolunteer('<?php echo $pv['volunteer_id']; ?>')"><i class="fas fa-times"></i> Reject</button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- All volunteers list -->
                    <div class="dashboard-card">
                        <div class="card-title-bar">
                            <div>
                                <h2>Active & Registered Volunteers</h2>
                                <p>Manage volunteer profiles and verify status</p>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Volunteer Name</th>
                                        <th>Languages</th>
                                        <th>Skills</th>
                                        <th>Total Sessions</th>
                                        <th>Status</th>
                                        <th>User Status</th>
                                        <th>Certificates</th>
                                        <th style="text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($volunteers as $v): ?>
                                        <tr>
                                            <td>
                                                <div class="user-avatar-group">
                                                    <div class="user-avatar-circle" style="background:var(--success-light); color:var(--success);">
                                                        <?php echo strtoupper(substr($v['first_name'] ?: 'V', 0, 1)); ?>
                                                    </div>
                                                    <div class="user-name-subtitle">
                                                        <strong><?php echo htmlspecialchars($v['first_name'] . ' ' . $v['last_name']); ?></strong>
                                                        <span><?php echo htmlspecialchars($v['email']); ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($v['languages'] ?: 'Not specified'); ?></td>
                                            <td><?php echo htmlspecialchars($v['skills'] ?: 'Not specified'); ?></td>
                                            <td><strong><?php echo (int)$v['total_sessions']; ?></strong></td>
                                            <td>
                                                <span class="badge <?php echo (strtolower($v['verification_status'] ?? '') === 'approved' || strtolower($v['verification_status'] ?? '') === 'active') ? 'badge-success' : ((strtolower($v['verification_status'] ?? '') === 'pending') ? 'badge-warning' : 'badge-danger'); ?>">
                                                    <?php echo htmlspecialchars(ucfirst($v['verification_status'] ?? 'Pending')); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-badge <?php echo ($v['user_status'] === 'Active') ? 'status-active' : (($v['user_status'] === 'Suspended') ? 'status-pending' : 'status-suspended'); ?>">
                                                    <?php echo htmlspecialchars($v['user_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($v['certificates'])): ?>
                                                    <span onclick="showCertificate('<?php echo htmlspecialchars(addslashes($v['certificates'])); ?>', '<?php echo htmlspecialchars(addslashes($v['first_name'] . ' ' . $v['last_name'])); ?>')" style="color:var(--primary); font-weight:700; cursor:pointer;"><i class="fas fa-file-image"></i> View Certificate</span>
                                                <?php else: ?>
                                                    <span style="color:var(--text-muted);">No certificate uploaded</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align: right;">
                                                <div class="action-btns" style="justify-content: flex-end;">
                                                    <?php if (strtolower($v['verification_status'] ?? '') === 'pending' || strtolower($v['verification_status'] ?? '') === 'rejected'): ?>
                                                        <button class="btn-icon" style="color:var(--success);" title="Approve Certificate" onclick="approveVolunteer('<?php echo $v['volunteer_id']; ?>')"><i class="fas fa-check"></i></button>
                                                    <?php endif; ?>
                                                    <?php if (strtolower($v['verification_status'] ?? '') !== 'rejected'): ?>
                                                        <button class="btn-icon delete" title="Reject Application" onclick="rejectVolunteer('<?php echo $v['volunteer_id']; ?>')"><i class="fas fa-times"></i></button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- SESSION TAB -->
            <div id="sessions" class="tab-content">
                <?php if (!hasAccess('session')): ?>
                    <div class="access-denied-container">
                        <div class="access-denied-icon"><i class="fas fa-lock"></i></div>
                        <h2>Access Denied</h2>
                        <p>Only Support Staff, Therapist Managers, or Super Admins can manage individual session bookings, create/cancel group rooms, and reassign facilitators.</p>
                    </div>
                <?php else: ?>
                    <div class="dashboard-card">
                        <div class="card-title-bar">
                            <div>
                                <h2>1-on-1 Sessions Booking Directory</h2>
                                <p>Monitor bookings, cancel sessions, or reassign clinicians</p>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Session ID</th>
                                        <th>Client</th>
                                        <th>Therapist</th>
                                        <th>Date & Time</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                        <th>Feedback</th>
                                        <th style="text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_bookings as $b): ?>
                                        <tr>
                                            <td><code style="font-size:0.75rem;"><?php echo htmlspecialchars($b['private_session_id']); ?></code></td>
                                            <td><strong><?php echo htmlspecialchars($b['client_name']); ?></strong></td>
                                            <td>Dr. <?php echo htmlspecialchars($b['therapist_name']); ?></td>
                                            <td style="font-size:0.85rem;"><?php echo htmlspecialchars($b['session_date']); ?></td>
                                            <td><span class="badge badge-info"><?php echo htmlspecialchars($b['communication_method']); ?></span></td>
                                            <td>
                                                <span class="status-badge <?php echo ($b['status'] === 'Completed') ? 'status-active' : (($b['status'] === 'cancelled') ? 'status-suspended' : 'status-pending'); ?>">
                                                    <?php echo htmlspecialchars($b['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo ($b['payment_status'] === 'paid' || $b['payment_status'] === 'completed') ? 'badge-success' : 'badge-danger'; ?>">
                                                    $<?php echo number_format($b['amount'], 2); ?> (<?php echo strtoupper($b['payment_status']); ?>)
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($b['rating']): ?>
                                                    <span style="color:#f59e0b; font-weight:700; font-size:0.8rem;"><i class="fas fa-star"></i> <?php echo $b['rating']; ?></span>
                                                    <span style="color:var(--text-secondary); font-size:0.75rem; display:block; max-width:120px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?php echo htmlspecialchars($b['feedback']); ?>"><?php echo htmlspecialchars($b['feedback']); ?></span>
                                                <?php else: ?>
                                                    <span style="color:var(--text-muted); font-size:0.75rem;">No Feedback</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align: right;">
                                                <div class="action-btns" style="justify-content: flex-end;">
                                                    <button class="btn-icon" title="Reschedule Session" onclick="openRescheduleModal('<?php echo $b['private_session_id']; ?>')"><i class="fas fa-calendar-alt"></i></button>
                                                    <button class="btn-icon" title="Reassign Therapist" onclick="openReassignModal('<?php echo $b['private_session_id']; ?>')"><i class="fas fa-exchange-alt"></i></button>
                                                    <?php if ($b['status'] !== 'cancelled'): ?>
                                                        <button class="btn-icon delete" title="Cancel Booking" onclick="cancelBooking('<?php echo $b['private_session_id']; ?>')"><i class="fas fa-circle-xmark"></i></button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- GROUP SESSIONS CMS -->
                    <div class="dashboard-card">
                        <div class="card-title-bar">
                            <div>
                                <h2>Group Therapy Rooms</h2>
                                <p>Create sessions, add facilitators, close active rooms, or eject participants</p>
                            </div>
                            <button class="btn-admin primary" onclick="openCreateGroupModal()"><i class="fas fa-plus"></i> Create Group Session</button>
                        </div>

                        <div class="table-responsive">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Room ID</th>
                                        <th>Room Name</th>
                                        <th>Topic</th>
                                        <th>Date & Time</th>
                                        <th>Facilitator</th>
                                        <th>Participants</th>
                                        <th>Status</th>
                                        <th style="text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($group_sessions as $gs): ?>
                                        <tr>
                                            <td><code style="font-size:0.75rem;"><?php echo htmlspecialchars($gs['group_session_id']); ?></code></td>
                                            <td><strong><?php echo htmlspecialchars($gs['room_name'] ?: 'General Room'); ?></strong></td>
                                            <td><?php echo htmlspecialchars($gs['topic']); ?></td>
                                            <td style="font-size:0.85rem;"><?php echo htmlspecialchars($gs['session_date']); ?></td>
                                            <td><?php echo $gs['therapist_name'] ? 'Dr. ' . htmlspecialchars($gs['therapist_name']) : '<span style="color:var(--text-muted);">Unassigned</span>'; ?></td>
                                            <td><span class="badge badge-info"><?php echo $gs['participant_count']; ?> / <?php echo $gs['max_participants']; ?> Enrolled</span></td>
                                            <td>
                                                <span class="status-badge <?php echo ($gs['status'] === 'active') ? 'status-active' : (($gs['status'] === 'cancelled') ? 'status-suspended' : 'status-pending'); ?>">
                                                    <?php echo htmlspecialchars($gs['status']); ?>
                                                </span>
                                            </td>
                                            <td style="text-align: right;">
                                                <div class="action-btns" style="justify-content: flex-end;">
                                                    <button class="btn-icon" title="Edit Details" onclick="openEditGroupModal(<?php echo htmlspecialchars(json_encode($gs), ENT_QUOTES, 'UTF-8'); ?>)"><i class="fas fa-edit"></i></button>
                                                    <?php if ($gs['status'] === 'active'): ?>
                                                        <button class="btn-icon" title="Close Room Session" style="color:var(--success);" onclick="closeGroupSession('<?php echo $gs['group_session_id']; ?>')"><i class="fas fa-circle-check"></i></button>
                                                    <?php endif; ?>
                                                    <?php if ($gs['status'] !== 'cancelled'): ?>
                                                        <button class="btn-icon delete" title="Cancel Room Session" onclick="cancelGroupSession('<?php echo $gs['group_session_id']; ?>')"><i class="fas fa-trash"></i></button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- MODERATION REPORTS TAB -->
            <div id="reports" class="tab-content">
                <?php if (!hasAccess('report')): ?>
                    <div class="access-denied-container">
                        <div class="access-denied-icon"><i class="fas fa-lock"></i></div>
                        <h2>Access Denied</h2>
                        <p>Only Support Staff or Super Admins can access moderation reports, add action notes, and dismiss/resolve infractions.</p>
                    </div>
                <?php else: ?>
                    <div class="dashboard-card">
                        <div class="card-title-bar">
                            <div>
                                <h2>Moderation Center & User Violations</h2>
                                <p>Investigate claims, verify status, and mark resolution outcomes</p>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Report ID</th>
                                        <th>Reported User</th>
                                        <th>Reporter</th>
                                        <th>Violation Type</th>
                                        <th>Description</th>
                                        <th>Severity</th>
                                        <th>Status</th>
                                        <th>Date Submitted</th>
                                        <th>Notes / Action</th>
                                        <th style="text-align: right;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reports as $r): ?>
                                        <?php 
                                        $severity = 'Medium';
                                        $sev_class = 'status-pending';
                                        if (in_array($r['report_type'], ['Harassment', 'Abuse', 'Fake Therapist'])) {
                                            $severity = 'High';
                                            $sev_class = 'status-suspended';
                                        }
                                        ?>
                                        <tr>
                                            <td><code style="font-size:0.75rem;"><?php echo htmlspecialchars($r['report_id']); ?></code></td>
                                            <td><strong><?php echo htmlspecialchars($r['reported_email'] ?: 'N/A'); ?></strong></td>
                                            <td><?php echo htmlspecialchars($r['reporter_email']); ?></td>
                                            <td><span class="badge badge-danger"><?php echo htmlspecialchars($r['report_type']); ?></span></td>
                                            <td style="font-size:0.85rem; max-width:180px;"><?php echo htmlspecialchars($r['description']); ?></td>
                                            <td><span class="status-badge <?php echo $sev_class; ?>"><?php echo $severity; ?></span></td>
                                            <td>
                                                <span class="badge <?php echo ($r['status'] === 'Resolved' || $r['status'] === 'Dismissed') ? 'badge-success' : 'badge-warning'; ?>">
                                                    <?php echo htmlspecialchars($r['status'] ?: 'Pending'); ?>
                                                </span>
                                            </td>
                                            <td style="font-size:0.85rem;"><?php echo htmlspecialchars($r['created_at']); ?></td>
                                            <td style="font-size:0.85rem; font-style:italic;"><?php echo htmlspecialchars($r['action_taken'] ?: 'No notes added'); ?></td>
                                            <td style="text-align: right;">
                                                <div class="action-btns" style="justify-content: flex-end;">
                                                    <button class="btn-admin primary" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;" onclick="openResolveReportModal('<?php echo $r['report_id']; ?>', '<?php echo $r['reported_user_id']; ?>')"><i class="fas fa-gavel"></i> Moderate</button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Q&A MODERATION TAB -->
            <div id="qa" class="tab-content">
                <?php if (!hasAccess('qa')): ?>
                    <div class="access-denied-container">
                        <div class="access-denied-icon"><i class="fas fa-lock"></i></div>
                        <h2>Access Denied</h2>
                        <p>Only the Content Manager or Super Admins can moderate community questions and replies.</p>
                    </div>
                <?php else: ?>
                    <div class="dashboard-card">
                        <div class="card-title-bar">
                            <div>
                                <h2>Q&A Forum Moderation Center</h2>
                                <p>Review community discussion questions, replies, and hide/show posts</p>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Post ID</th>
                                        <th>Author</th>
                                        <th>Category</th>
                                        <th>Post Type</th>
                                        <th>Content</th>
                                        <th>Status</th>
                                        <th style="text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($qa_items as $q): ?>
                                        <tr>
                                            <td><code style="font-size:0.75rem;"><?php echo htmlspecialchars($q['post_id']); ?></code></td>
                                            <td><strong><?php echo htmlspecialchars($q['author_email'] ?: 'Anonymous'); ?></strong><br><small style="color:var(--text-muted);"><?php echo htmlspecialchars($q['author_role'] ?: 'client'); ?></small></td>
                                            <td><span class="badge badge-info"><?php echo htmlspecialchars($q['category']); ?></span></td>
                                            <td><span class="badge badge-primary"><?php echo htmlspecialchars($q['post_type']); ?></span></td>
                                            <td style="font-size:0.85rem; max-width:280px;"><?php echo htmlspecialchars($q['content']); ?></td>
                                            <td>
                                                <span class="status-badge <?php echo ($q['is_public'] ?? true) ? 'status-active' : 'status-suspended'; ?>">
                                                    <?php echo ($q['is_public'] ?? true) ? 'Public' : 'Hidden'; ?>
                                                </span>
                                            </td>
                                            <td style="text-align: right;">
                                                <div class="action-btns" style="justify-content: flex-end;">
                                                    <?php if (!empty($q['parent_id'])): ?>
                                                        <button type="button" class="btn-icon" title="View Parent Post" onclick="window.location.href='index.php?tab=community_qa&search=<?php echo urlencode($q['parent_id']); ?>'"><i class="fas fa-up-right-from-square"></i></button>
                                                    <?php endif; ?>
                                                    <?php if ($q['is_public'] ?? true): ?>
                                                        <button class="btn-icon" title="Hide Post" onclick="moderateQa('hide', '<?php echo $q['post_id']; ?>')"><i class="fas fa-eye-slash"></i></button>
                                                    <?php else: ?>
                                                        <button class="btn-icon" title="Unhide Post" onclick="moderateQa('show', '<?php echo $q['post_id']; ?>')"><i class="fas fa-eye"></i></button>
                                                    <?php endif; ?>
                                                    <button class="btn-icon delete" title="Delete Post" onclick="moderateQa('delete', '<?php echo $q['post_id']; ?>')"><i class="fas fa-trash"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- COMMUNITY Q&A VIEW TAB -->
            <div id="community_qa" class="tab-content">
                <style>
                    /* Theme aligned variables for Community Q&A */
                    #community_qa {
                        --primary-color: var(--primary);
                        --secondary-color: var(--primary-hover);
                        --bg-color: var(--bg-primary);
                        --light-bg: var(--bg-secondary);
                        --border-color: var(--border-color);
                        --text-color: var(--text-primary);
                        --text-sec: var(--text-secondary);
                    }

                    #community_qa .qna-layout {
                        display: flex;
                        gap: 25px;
                        max-width: 1750px;
                        margin: 0 auto;
                        align-items: flex-start;
                        width: 100%;
                    }

                    #community_qa .topics-sidebar {
                        flex: 0 0 260px;
                        background: var(--light-bg);
                        border-radius: var(--radius-md);
                        box-shadow: var(--shadow-sm);
                        border: 1px solid var(--border-color);
                        overflow: hidden;
                    }

                    #community_qa .topics-header {
                        padding: 20px;
                        border-bottom: 1px solid var(--border-color);
                    }

                    #community_qa .topics-header h3 {
                        margin: 0;
                        color: var(--text-color);
                        font-size: 1.15rem;
                        font-weight: 700;
                    }

                    #community_qa .topics-list {
                        list-style: none;
                        padding: 0;
                        margin: 0;
                        max-height: 500px;
                        overflow-y: auto;
                    }

                    #community_qa .topics-list li {
                        border-bottom: 1px solid var(--border-color);
                    }

                    #community_qa .topics-list li a {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        padding: 12px 20px;
                        color: var(--text-sec);
                        text-decoration: none;
                        transition: background-color 0.2s;
                        font-size: 0.9rem;
                        font-weight: 600;
                    }

                    #community_qa .topics-list li a:hover {
                        background-color: var(--bg-color);
                        color: var(--primary-color);
                    }

                    #community_qa .topic-count {
                        background: var(--border-color);
                        color: var(--text-sec);
                        padding: 2px 8px;
                        border-radius: 12px;
                        font-size: 0.75rem;
                        font-weight: 700;
                    }

                    #community_qa .main-qna-content {
                        flex: 1;
                        min-width: 0;
                    }

                    #community_qa .search-bar {
                        display: flex;
                        background: var(--light-bg);
                        border-radius: var(--radius-sm);
                        overflow: hidden;
                        box-shadow: var(--shadow-sm);
                        border: 1px solid var(--border-color);
                    }

                    #community_qa .search-input {
                        flex: 1;
                        padding: 12px 18px;
                        border: none;
                        font-size: 0.95rem;
                        color: var(--text-color);
                        outline: none;
                        background: transparent;
                    }

                    #community_qa .search-btn {
                        background: var(--primary-color);
                        color: white;
                        border: none;
                        padding: 10px 20px;
                        cursor: pointer;
                        font-size: 0.95rem;
                        transition: all 0.3s ease;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }

                    #community_qa .search-btn:hover {
                        background: var(--secondary-color);
                    }

                    #community_qa .btn-ask {
                        background: var(--primary-color);
                        color: white;
                        border: none;
                        padding: 10px 18px;
                        border-radius: var(--radius-sm);
                        font-weight: 700;
                        font-size: 0.9rem;
                        cursor: pointer;
                        display: inline-flex;
                        align-items: center;
                        gap: 8px;
                        transition: opacity 0.2s;
                        text-decoration: none;
                        white-space: nowrap;
                    }

                    #community_qa .btn-ask:hover {
                        background: var(--secondary-color);
                    }

                    #community_qa .questions-container {
                        display: flex;
                        flex-direction: column;
                        gap: 20px;
                        margin-top: 15px;
                    }

                    #community_qa .questions-header {
                        background: var(--light-bg);
                        border-radius: var(--radius-md);
                        border: 1px solid var(--border-color);
                        padding: 15px 20px;
                        display: flex;
                        align-items: center;
                        gap: 10px;
                    }

                    #community_qa .questions-header h3 {
                        margin: 0;
                        color: var(--text-color);
                        font-size: 1.1rem;
                        font-weight: 700;
                    }

                    #community_qa .questions-header .clock-icon {
                        color: var(--primary-color);
                        font-size: 1.1rem;
                    }

                    #community_qa .question-item {
                        background: var(--light-bg);
                        border-radius: var(--radius-md);
                        border: 1px solid var(--border-color);
                        padding: 24px;
                        transition: transform 0.2s, box-shadow 0.2s;
                    }

                    #community_qa .question-item:hover {
                        transform: translateY(-2px);
                        box-shadow: var(--shadow-md);
                    }

                    #community_qa .card-header-row {
                        display: flex;
                        align-items: flex-start;
                        gap: 16px;
                        margin-bottom: 15px;
                    }

                    #community_qa .card-avatar {
                        width: 44px;
                        height: 44px;
                        border-radius: 50%;
                        background-color: var(--primary-light);
                        color: var(--primary-color);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 1.1rem;
                        font-weight: bold;
                        flex-shrink: 0;
                        overflow: hidden;
                    }

                    #community_qa .card-avatar img {
                        width: 100%;
                        height: 100%;
                        object-fit: cover;
                    }

                    #community_qa .card-title-area {
                        flex: 1;
                    }

                    #community_qa .card-title-text {
                        font-size: 1.05rem;
                        font-weight: 700;
                        color: var(--text-color);
                        margin: 0 0 6px 0;
                        line-height: 1.4;
                    }

                    #community_qa .card-meta-text {
                        font-size: 0.85rem;
                        color: var(--text-sec);
                        font-weight: 500;
                    }

                    #community_qa .card-tags {
                        display: flex;
                        gap: 10px;
                        margin-bottom: 18px;
                        flex-wrap: wrap;
                    }

                    #community_qa .card-tag {
                        padding: 4px 12px;
                        border: 1px solid var(--border-color);
                        border-radius: 9999px;
                        font-size: 0.8rem;
                        color: var(--text-sec);
                        font-weight: 600;
                        background: var(--bg-color);
                    }

                    #community_qa .card-footer-row {
                        display: flex;
                        align-items: center;
                        gap: 20px;
                        color: var(--text-sec);
                        font-weight: 600;
                        font-size: 0.88rem;
                    }

                    #community_qa .card-footer-item {
                        display: flex;
                        align-items: center;
                        gap: 6px;
                        cursor: pointer;
                        background: none;
                        border: none;
                        color: inherit;
                        font: inherit;
                        padding: 0;
                        transition: opacity 0.2s;
                    }
                    
                    #community_qa .card-footer-item:hover {
                        opacity: 0.8;
                        color: var(--primary-color);
                    }

                    #community_qa .helpful-btn {
                        display: inline-flex;
                        align-items: center;
                        gap: 6px;
                        padding: 5px 12px;
                        border-radius: 50px;
                        font-size: 0.82rem;
                        font-weight: 700;
                        border: 1px solid var(--border-color);
                        background: var(--light-bg);
                        color: var(--text-sec);
                        cursor: pointer;
                        transition: all .2s;
                        user-select: none;
                    }

                    #community_qa .helpful-btn:hover {
                        border-color: var(--success);
                        color: var(--success);
                        background: var(--success-light);
                        transform: translateY(-1px);
                    }

                    #community_qa .helpful-btn.voted {
                        border-color: var(--success);
                        color: var(--success);
                        background: var(--success-light);
                    }

                    #community_qa .comments-section {
                        margin-top: 15px;
                        padding-top: 15px;
                        border-top: 1px dashed var(--border-color);
                    }

                    #community_qa .comment-item {
                        background: var(--bg-color);
                        padding: 12px 15px;
                        border-radius: var(--radius-sm);
                        margin-bottom: 10px;
                        border: 1px solid var(--border-color);
                    }

                    #community_qa .reply-form input {
                        flex: 1;
                        padding: 8px 14px;
                        border: 1px solid var(--border-color);
                        border-radius: 20px;
                        outline: none;
                        background: var(--light-bg);
                        color: var(--text-color);
                        font-size: 0.9rem;
                    }
                    
                    #community_qa .reply-form button {
                        background: var(--primary-color);
                        color: white;
                        border: none;
                        padding: 8px 18px;
                        border-radius: 20px;
                        cursor: pointer;
                        font-weight: 700;
                        font-size: 0.85rem;
                    }
                    #community_qa .reply-form button:hover {
                        background: var(--secondary-color);
                    }

                    #community_qa .topic-select-dropdown {
                        display: none;
                        border: none;
                        background: transparent;
                        padding: 0 15px;
                        font-weight: 600;
                        color: var(--primary-color);
                        border-inline-end: 1px solid var(--border-color);
                        outline: none;
                        cursor: pointer;
                        height: 100%;
                        font-size: 0.95rem;
                    }

                    @media (max-width: 992px) {
                        #community_qa .topics-sidebar {
                            display: none !important;
                        }
                        #community_qa .topic-select-dropdown {
                            display: inline-block;
                        }
                        #community_qa .qna-layout {
                            flex-direction: column;
                        }
                    }

                    @media (max-width: 768px) {
                        #community_qa .card-header-row {
                            flex-direction: column;
                            align-items: stretch;
                            gap: 12px;
                        }
                        #community_qa .card-footer-row {
                            flex-wrap: wrap;
                            gap: 12px;
                        }
                        #community_qa .qna-top-row {
                            flex-direction: column !important;
                            align-items: stretch !important;
                            gap: 12px !important;
                        }
                        #community_qa .topic-select-dropdown {
                            border-inline-end: none;
                            border-bottom: 1px solid var(--border-color);
                            padding: 10px 15px;
                            width: 100%;
                            height: auto;
                        }
                    }
                </style>

                <div class="qna-layout">
                    <!-- Left Topics Card -->
                    <div class="topics-sidebar">
                        <div class="topics-header">
                            <h3><?php echo __('Q & A Topics'); ?></h3>
                        </div>
                        <ul class="topics-list">
                            <li>
                                <a href="?tab=community_qa">
                                    All Topics
                                    <span class="topic-count"><?php echo array_sum($comm_all_topics); ?></span>
                                </a>
                            </li>
                            <?php foreach ($comm_all_topics as $topic => $count): ?>
                                <li>
                                    <a href="?tab=community_qa&search=<?php echo urlencode($topic); ?>">
                                        <?php echo htmlspecialchars(__($topic)); ?>
                                        <span class="topic-count"><?php echo $count; ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Right Content -->
                    <div class="main-qna-content">
                        <!-- Header Action Row -->
                        <div class="qna-top-row" style="display: flex; gap: 15px; margin-bottom: 25px; align-items: stretch; width: 100%;">
                            <!-- Search Bar -->
                            <form action="index.php" method="GET" class="search-bar" style="flex: 1; margin-bottom: 0; display: flex; align-items: center;">
                                <input type="hidden" name="tab" value="community_qa">
                                <select class="topic-select-dropdown" onchange="const txt = this.form.querySelector('.search-input'); txt.value = this.value; this.form.submit();">
                                    <option value=""><?php echo __('All Topics'); ?></option>
                                    <?php foreach ($comm_all_topics as $topic_name => $count): ?>
                                        <option value="<?php echo htmlspecialchars($topic_name); ?>" <?php echo ($comm_search_query === $topic_name) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars(__($topic_name)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" name="search" class="search-input" placeholder="<?php echo __('Search Questions'); ?>"
                                    value="<?php echo htmlspecialchars($comm_search_query); ?>">
                                <button type="submit" class="search-btn">
                                    <i class="fas fa-search"></i>
                                </button>
                            </form>
                        </div>

                        <!-- Questions List -->
                        <div class="questions-container">
                            <div class="questions-header">
                                <i class="far fa-clock clock-icon"></i>
                                <h3><?php echo __('Recent Questions'); ?></h3>
                            </div>

                            <div class="questions-list">
                                <?php if (empty($comm_posts)): ?>
                                    <div style="text-align: center; color: var(--text-muted); padding: 40px; background: var(--bg-secondary); border-radius: var(--radius-md); border: 1px solid var(--border-color);"><?php echo __('No questions found. Be the first to ask!'); ?></div>
                                <?php else: ?>
                                    <?php foreach ($comm_posts as $post): ?>
                                        <?php $post_comments = $comm_comments[$post['post_id']] ?? []; ?>
                                        <div class="question-item">
                                            <div class="card-header-row">
                                                <div class="card-avatar" style="<?php echo (!empty($post['profile_image']) || !empty($post['avatar_path'])) ? 'padding: 0; background: transparent;' : ''; ?>">
                                                    <?php if (strtolower($post['role'] ?? '') === 'therapist'): ?>
                                                        <?php if (!empty($post['profile_image'])): ?>
                                                            <img src="<?php echo htmlspecialchars('../' . $post['profile_image']); ?>" alt="Therapist" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                            <div style="display:none; align-items:center; justify-content:center; width: 100%; height: 100%; font-weight: bold; font-size: 1.1rem;">
                                                                <?php echo htmlspecialchars(strtoupper(substr($post['author_name'], 0, 1))); ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <span style="font-weight: bold;"><?php echo htmlspecialchars(strtoupper(substr($post['author_name'], 0, 1))); ?></span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <?php if (!empty($post['avatar_path'])): ?>
                                                            <img src="<?php echo htmlspecialchars('../' . $post['avatar_path']); ?>" alt="User">
                                                        <?php else: ?>
                                                            <span style="font-weight: bold;"><?php echo htmlspecialchars(strtoupper(substr($post['author_name'], 0, 1))); ?></span>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="card-title-area">
                                                    <h4 class="card-title-text"><?php echo htmlspecialchars($post['content']); ?></h4>
                                                    <div class="card-meta-text">
                                                        <?php echo htmlspecialchars($post['author_name']); ?> &bull; 
                                                        <?php 
                                                        $time_diff = time() - strtotime($post['timestamp']);
                                                        if ($time_diff < 0) $time_diff = 0;
                                                        if ($time_diff < 60) echo 'Just now';
                                                        elseif ($time_diff < 3600) echo floor($time_diff/60) . ' mins ago';
                                                        elseif ($time_diff < 86400) echo floor($time_diff/3600) . ' hours ago';
                                                        else echo date('M jS, Y', strtotime($post['timestamp']));
                                                        ?>
                                                    </div>
                                                </div>
                                                <div class="card-actions-menu" style="display: flex; gap: 10px; align-items: center;">
                                                    <button type="button" onclick="commEditPost('<?php echo $post['post_id']; ?>', '<?php echo htmlspecialchars(addslashes($post['content'])); ?>')" style="background: none; border: none; cursor: pointer; color: var(--primary);" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form action="index.php" method="POST" style="margin:0; padding:0; display:inline;" onsubmit="return confirm('Delete this post?');">
                                                        <input type="hidden" name="community_qa_action" value="delete_post">
                                                        <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                                        <button type="submit" style="background: none; border: none; cursor: pointer; color: var(--danger);" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>

                                            <div class="card-tags">
                                                <span class="card-tag"><?php echo htmlspecialchars(__($post['topic_type'] ?? 'General')); ?></span>
                                            </div>

                                            <?php
                                                $hdata  = $comm_helpful_counts[$post['post_id']] ?? ['count'=>0,'voted'=>false];
                                                $hcount = $hdata['count'];
                                                $hvoted = $hdata['voted'];
                                            ?>
                                            <div class="card-footer-row">
                                                <button type="button"
                                                    class="helpful-btn <?php echo $hvoted ? 'voted' : ''; ?>"
                                                    id="comm_hbtn_<?php echo $post['post_id']; ?>"
                                                    data-id="<?php echo $post['post_id']; ?>"
                                                    data-type="post"
                                                    data-voted="<?php echo $hvoted ? '1' : '0'; ?>"
                                                    onclick="commToggleHelpful(this)">
                                                    <i class="<?php echo $hvoted ? 'fas' : 'far'; ?> fa-thumbs-up hb-icon"></i>
                                                    <span class="hb-count"><?php echo $hcount; ?></span>
                                                    <?php echo __('Helpful'); ?>
                                                </button>
                                                <button type="button" class="card-footer-item" onclick="toggleComments('comm_comments_<?php echo $post['post_id']; ?>')">
                                                    <i class="far fa-comment-alt"></i> <?php echo $post['answer_count']; ?> <?php echo $post['answer_count'] == 1 ? __('reply') : __('replies'); ?>
                                                </button>
                                            </div>

                                            <!-- Comments Section (Hidden by default) -->
                                            <div id="comm_comments_<?php echo $post['post_id']; ?>" class="comments-section"
                                                style="display: none;">

                                                <?php if (!empty($post_comments)): ?>
                                                    <div class="comments-list" style="margin-bottom: 20px;">
                                                        <?php foreach ($post_comments as $cmt): ?>
                                                            <div class="comment-item">
                                                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                                                    <div style="display: flex; align-items: center; gap: 8px;">
                                                                        <div class="card-avatar" style="width: 28px; height: 28px; font-size: 0.8rem; <?php echo (!empty($cmt['profile_image']) || !empty($cmt['avatar_path'])) ? 'padding: 0; background: transparent;' : ''; ?>">
                                                                            <?php if (strtolower($cmt['role'] ?? '') === 'therapist'): ?>
                                                                                <?php if (!empty($cmt['profile_image'])): ?>
                                                                                    <img src="<?php echo htmlspecialchars('../' . $cmt['profile_image']); ?>" alt="Therapist">
                                                                                <?php else: ?>
                                                                                    <span><?php echo htmlspecialchars(strtoupper(substr($cmt['author_name'], 0, 1))); ?></span>
                                                                                <?php endif; ?>
                                                                            <?php else: ?>
                                                                                <?php if (!empty($cmt['avatar_path'])): ?>
                                                                                    <img src="<?php echo htmlspecialchars('../' . $cmt['avatar_path']); ?>" alt="User">
                                                                                <?php else: ?>
                                                                                    <span><?php echo htmlspecialchars(strtoupper(substr($cmt['author_name'], 0, 1))); ?></span>
                                                                                <?php endif; ?>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                        <strong style="color: var(--text-color); font-size: 0.95rem;">
                                                                            <?php echo htmlspecialchars($cmt['author_name']); ?>
                                                                            <?php if (strtolower($cmt['role'] ?? '') === 'therapist'): ?>
                                                                                <span style="color: #22c55e; font-size: 0.8em; margin-left: 5px;"><i class="fas fa-check-circle"></i> Pro</span>
                                                                            <?php elseif (strtolower($cmt['role'] ?? '') === 'admin'): ?>
                                                                                <span style="color: var(--primary); font-size: 0.8em; margin-left: 5px;"><i class="fas fa-shield-halved"></i> Staff</span>
                                                                            <?php endif; ?>
                                                                        </strong>
                                                                    </div>
                                                                    <span style="color: var(--text-muted); font-size: 0.8rem; display: flex; align-items: center;">
                                                                        <?php echo date('M d, H:i', strtotime($cmt['timestamp'])); ?>
                                                                        <button type="button" onclick="commEditComment('<?php echo $cmt['comment_id']; ?>', '<?php echo htmlspecialchars(addslashes($cmt['content'])); ?>')" style="background:none; border:none; cursor:pointer; color:var(--text-sec); margin-left:10px;"><i class="fas fa-edit"></i></button>
                                                                        <form action="index.php" method="POST" style="display:inline; margin:0;" onsubmit="return confirm('Delete this comment?');">
                                                                            <input type="hidden" name="community_qa_action" value="delete_comment">
                                                                            <input type="hidden" name="comment_id" value="<?php echo $cmt['comment_id']; ?>">
                                                                            <button type="submit" style="background:none; border:none; cursor:pointer; color:var(--danger); margin-left:5px;"><i class="fas fa-trash"></i></button>
                                                                        </form>
                                                                    </span>
                                                                </div>
                                                                <p style="margin: 0; color: var(--text-sec); font-size: 0.95rem; line-height: 1.5;">
                                                                    <?php echo nl2br(htmlspecialchars($cmt['content'])); ?>
                                                                </p>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>


                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RESOURCE CMS TAB -->
            <div id="resources" class="tab-content">
                <?php if (!hasAccess('resource')): ?>
                    <div class="access-denied-container">
                        <div class="access-denied-icon"><i class="fas fa-lock"></i></div>
                        <h2>Access Denied</h2>
                        <p>Only the Content Manager or Super Admins can upload resources, add PDFs/Videos, and manage resource details.</p>
                    </div>
                <?php else: ?>
                    <div class="dashboard-card">
                        <div class="card-title-bar">
                            <div>
                                <h2>Resource Library CMS</h2>
                                <p>Manage Articles, Videos, crisis PDFs, and awareness guides</p>
                            </div>
                            <button class="btn-admin primary" onclick="openCreateResourceModal()"><i class="fas fa-plus"></i> Add New Resource</button>
                        </div>

                        <div class="table-responsive">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Resource Title</th>
                                        <th>Type</th>
                                        <th>Category</th>
                                        <th>Author</th>
                                        <th>URL/Link</th>
                                        <th style="text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($resources as $res): ?>
                                        <tr>
                                            <td><code style="font-size:0.75rem;"><?php echo htmlspecialchars($res['resource_id']); ?></code></td>
                                            <td><strong><?php echo htmlspecialchars($res['title']); ?></strong></td>
                                            <td><span class="badge badge-primary"><?php echo htmlspecialchars($res['type']); ?></span></td>
                                            <td><span class="badge badge-info"><?php echo htmlspecialchars($res['category']); ?></span></td>
                                            <td><?php echo htmlspecialchars($res['author_name']); ?></td>
                                            <td style="font-size:0.85rem; color:var(--primary);"><?php echo htmlspecialchars($res['url'] ?: 'No URL'); ?></td>
                                            <td style="text-align: right;">
                                                <div class="action-btns" style="justify-content: flex-end;">
                                                    <button class="btn-icon" title="Edit Resource" onclick="openEditResourceModal(<?php echo htmlspecialchars(json_encode($res), ENT_QUOTES, 'UTF-8'); ?>)"><i class="fas fa-edit"></i></button>
                                                    <button class="btn-icon delete" title="Delete Resource" onclick="deleteResource('<?php echo $res['resource_id']; ?>')"><i class="fas fa-trash"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- NOTIFICATION CENTER TAB -->
            <div id="notifications" class="tab-content">
                <?php if (!hasAccess('notification')): ?>
                    <div class="access-denied-container">
                        <div class="access-denied-icon"><i class="fas fa-lock"></i></div>
                        <h2>Access Denied</h2>
                        <p>Only Support Staff or Super Admins can schedule and send global announcements or target user role notifications.</p>
                    </div>
                <?php else: ?>
                    <div class="dashboard-card">
                        <div class="card-title-bar">
                            <div>
                                <h2>Notification Management Center</h2>
                                <p>Send user alerts, awareness campaigns, and scheduled announcements</p>
                            </div>
                            <button class="btn-admin primary" onclick="openCreateNotificationModal()"><i class="fas fa-bullhorn"></i> Send Announcement</button>
                        </div>

                        <div class="table-responsive">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Notification Title</th>
                                        <th>Target Role</th>
                                        <th>Message</th>
                                        <th>Type</th>
                                        <th>Scheduled At</th>
                                        <th>Read Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($notifications as $n): ?>
                                        <tr>
                                            <td><code style="font-size:0.75rem;"><?php echo htmlspecialchars($n['notification_id']); ?></code></td>
                                            <td><strong><?php echo htmlspecialchars($n['title']); ?></strong></td>
                                            <td><span class="badge badge-info"><?php echo htmlspecialchars($n['target_role']); ?></span></td>
                                            <td style="font-size:0.85rem; max-width:250px;"><?php echo htmlspecialchars($n['message']); ?></td>
                                            <td><span class="badge badge-primary"><?php echo htmlspecialchars($n['type']); ?></span></td>
                                            <td style="font-size:0.85rem;"><?php echo htmlspecialchars($n['scheduled_at']); ?></td>
                                            <td><strong><?php echo $n['read_count']; ?> Reads</strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- AUDIT Logs TAB -->
            <div id="audit" class="tab-content">
                <?php if (!hasAccess('audit')): ?>
                    <div class="access-denied-container">
                        <div class="access-denied-icon"><i class="fas fa-lock"></i></div>
                        <h2>Access Denied</h2>
                        <p>Only Super Admins can inspect the system security audit trails and log change history.</p>
                    </div>
                <?php else: ?>
                    <div class="dashboard-card">
                        <div class="card-title-bar">
                            <div>
                                <h2>Security Audit Logs</h2>
                                <p>Review detailed logs of login attempts, profile changes, and administrative actions</p>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Log ID</th>
                                        <th>Admin ID</th>
                                        <th>Admin Email</th>
                                        <th>Action Taken</th>
                                        <th>Details</th>
                                        <th>IP Address</th>
                                        <th>Timestamp</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($audit_logs as $log): ?>
                                        <tr>
                                            <td><code>#<?php echo (int)($log['log_id']); ?></code></td>
                                            <td><code style="font-size:0.75rem;"><?php echo htmlspecialchars($log['user_id'] ?: 'System'); ?></code></td>
                                            <td><?php echo htmlspecialchars($log['email'] ?: 'N/A'); ?></td>
                                            <td><span class="badge badge-primary"><?php echo htmlspecialchars($log['action']); ?></span></td>
                                            <td style="font-size:0.85rem; max-width:300px;"><?php echo htmlspecialchars($log['details']); ?></td>
                                            <td><code><?php echo htmlspecialchars($log['ip_address'] ?: 'Local'); ?></code></td>
                                            <td style="font-size:0.85rem;"><?php echo htmlspecialchars($log['created_at']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </section>
    </main>

    <!-- TOAST MESSAGES -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- MODALS -->

    <!-- Edit User Modal -->
    <div class="modal-overlay" id="editUserModal">
        <div class="admin-modal">
            <div class="modal-header">
                <h2>Modify User Information</h2>
                <button type="button" class="modal-close" onclick="closeModal('editUserModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editUserForm" class="admin-form">
                    <input type="hidden" name="action" value="edit_user">
                    <input type="hidden" name="target_user_id" id="editUserId">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" id="editUserEmail" required>
                    </div>
                    <div class="form-group">
                        <label>Account Status</label>
                        <select name="status" id="editUserStatus">
                            <option value="Active">Active</option>
                            <option value="Suspended">Suspended</option>
                            <option value="Deleted">Deleted</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Admin Access Role (Optional)</label>
                        <select name="admin_role" id="editUserAdminRole">
                            <option value="">None (Standard User)</option>
                            <option value="Super Admin">Super Admin</option>
                            <option value="Therapist Manager">Therapist Manager</option>
                            <option value="Content Manager">Content Manager</option>
                            <option value="Support Staff">Support Staff</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Activity Score</label>
                        <input type="number" name="activity_score" id="editUserActivityScore" required min="0">
                    </div>
                    <div style="display:flex; justify-content:flex-end; gap:0.5rem; margin-top:1.5rem;">
                        <button type="button" class="btn-admin" onclick="closeModal('editUserModal')">Cancel</button>
                        <button type="submit" class="btn-admin primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Therapist Profile Modal -->
    <div class="modal-overlay" id="editTherapistModal">
        <div class="admin-modal">
            <div class="modal-header">
                <h2>Edit Therapist Profile</h2>
                <button class="modal-close" onclick="closeModal('editTherapistModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editTherapistForm" class="admin-form">
                    <input type="hidden" name="action" value="edit_therapist_profile">
                    <input type="hidden" name="therapist_id" id="editTherapistId">
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="first_name" id="editTherapistFirstName" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="last_name" id="editTherapistLastName" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Specialties</label>
                        <input type="text" name="specialties" id="editTherapistSpecialties" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Hourly Rate ($)</label>
                            <input type="number" step="0.01" name="hourly_rate" id="editTherapistHourlyRate" required>
                        </div>
                        <div class="form-group">
                            <label>Years of Experience</label>
                            <input type="number" name="years_experience" id="editTherapistExperience" required>
                        </div>
                    </div>
                    <div style="display:flex; justify-content:flex-end; gap:0.5rem; margin-top:1.5rem;">
                        <button type="button" class="btn-admin" onclick="closeModal('editTherapistModal')">Cancel</button>
                        <button type="submit" class="btn-admin primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reschedule Session Modal -->
    <div class="modal-overlay" id="rescheduleModal">
        <div class="admin-modal" style="max-width:400px;">
            <div class="modal-header">
                <h2>Reschedule Session</h2>
                <button class="modal-close" onclick="closeModal('rescheduleModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="rescheduleForm" class="admin-form">
                    <input type="hidden" name="action" value="reschedule_session">
                    <input type="hidden" name="session_id" id="rescheduleSessionId">
                    <div class="form-group">
                        <label>New Session Date & Time</label>
                        <input type="datetime-local" name="new_date" required>
                    </div>
                    <div style="display:flex; justify-content:flex-end; gap:0.5rem; margin-top:1.5rem;">
                        <button type="button" class="btn-admin" onclick="closeModal('rescheduleModal')">Cancel</button>
                        <button type="submit" class="btn-admin primary">Reschedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reassign Therapist Modal -->
    <div class="modal-overlay" id="reassignModal">
        <div class="admin-modal" style="max-width:450px;">
            <div class="modal-header">
                <h2>Reassign Therapist</h2>
                <button class="modal-close" onclick="closeModal('reassignModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="reassignForm" class="admin-form">
                    <input type="hidden" name="action" value="reassign_therapist">
                    <input type="hidden" name="session_id" id="reassignSessionId">
                    <div class="form-group">
                        <label>Select Therapist</label>
                        <select name="therapist_id" required>
                            <?php foreach ($therapists as $t): ?>
                                <option value="<?php echo $t['therapist_id']; ?>">Dr. <?php echo htmlspecialchars($t['first_name'] . ' ' . $t['last_name']); ?> (<?php echo htmlspecialchars($t['specialties']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display:flex; justify-content:flex-end; gap:0.5rem; margin-top:1.5rem;">
                        <button type="button" class="btn-admin" onclick="closeModal('reassignModal')">Cancel</button>
                        <button type="submit" class="btn-admin primary">Reassign</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Create Group Modal -->
    <div class="modal-overlay" id="createGroupModal">
        <div class="admin-modal">
            <div class="modal-header">
                <h2>Create Group Therapy Room</h2>
                <button class="modal-close" onclick="closeModal('createGroupModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="createGroupForm" class="admin-form">
                    <input type="hidden" name="action" value="create_group_session">
                    <div class="form-group">
                        <label>Room Topic</label>
                        <input type="text" name="topic" required placeholder="e.g. Anxiety & Stress Support">
                    </div>
                    <div class="form-group">
                        <label>Room Name (Optional)</label>
                        <input type="text" name="room_name" placeholder="e.g. Quiet Haven">
                    </div>
                    <div class="form-group">
                        <label>Session Date & Time</label>
                        <input type="datetime-local" name="session_date" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Max Enrolled Participants</label>
                            <input type="number" name="max_participants" value="15" min="2" max="50" required>
                        </div>
                        <div class="form-group">
                            <label>Duration (Minutes)</label>
                            <input type="number" name="duration_minutes" value="60" min="15" max="180" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Assign Facilitating Therapist</label>
                        <select name="therapist_id">
                            <option value="">Leave Unassigned</option>
                            <?php foreach ($therapists as $t): ?>
                                <option value="<?php echo $t['therapist_id']; ?>">Dr. <?php echo htmlspecialchars($t['first_name'] . ' ' . $t['last_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display:flex; justify-content:flex-end; gap:0.5rem; margin-top:1.5rem;">
                        <button type="button" class="btn-admin" onclick="closeModal('createGroupModal')">Cancel</button>
                        <button type="submit" class="btn-admin primary">Create Room</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Group Modal -->
    <div class="modal-overlay" id="editGroupModal">
        <div class="admin-modal">
            <div class="modal-header">
                <h2>Edit Group Therapy Room</h2>
                <button class="modal-close" onclick="closeModal('editGroupModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editGroupForm" class="admin-form">
                    <input type="hidden" name="action" value="edit_group_session">
                    <input type="hidden" name="group_session_id" id="editGroupSessionId">
                    <div class="form-group">
                        <label>Room Topic</label>
                        <input type="text" name="topic" id="editGroupTopic" required>
                    </div>
                    <div class="form-group">
                        <label>Room Name (Optional)</label>
                        <input type="text" name="room_name" id="editGroupRoomName">
                    </div>
                    <div class="form-group">
                        <label>Session Date & Time</label>
                        <input type="datetime-local" name="session_date" id="editGroupDate" required>
                    </div>
                    <div class="form-group">
                        <label>Max Enrolled Participants</label>
                        <input type="number" name="max_participants" id="editGroupMaxPart" required>
                    </div>
                    <div class="form-group">
                        <label>Assign Facilitating Therapist</label>
                        <select name="therapist_id" id="editGroupTherapistId">
                            <option value="">Leave Unassigned</option>
                            <?php foreach ($therapists as $t): ?>
                                <option value="<?php echo $t['therapist_id']; ?>">Dr. <?php echo htmlspecialchars($t['first_name'] . ' ' . $t['last_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display:flex; justify-content:flex-end; gap:0.5rem; margin-top:1.5rem;">
                        <button type="button" class="btn-admin" onclick="closeModal('editGroupModal')">Cancel</button>
                        <button type="submit" class="btn-admin primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Resolve Report Modal -->
    <div class="modal-overlay" id="resolveReportModal">
        <div class="admin-modal" style="max-width:450px;">
            <div class="modal-header">
                <h2>Resolve Report Infraciton</h2>
                <button class="modal-close" onclick="closeModal('resolveReportModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="resolveReportForm" class="admin-form">
                    <input type="hidden" name="action" value="update_report_status">
                    <input type="hidden" name="report_id" id="resolveReportId">
                    <input type="hidden" name="reported_user_id" id="resolveReportUserId">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Action on Reported User</label>
                            <select name="user_action" id="resolveReportUserAction" required>
                                <option value="none">Keep User Active / No Action</option>
                                <option value="suspend">Suspend User Account</option>
                                <option value="ban">Ban / Delete User Account</option>
                                <option value="ignore">Ignore Report (Dismiss)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Moderation Status</label>
                            <select name="status" id="resolveReportStatus" required>
                                <option value="Under Review">Under Review</option>
                                <option value="Resolved">Resolved</option>
                                <option value="Escalated">Escalated</option>
                                <option value="Dismissed">Dismissed</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Moderator Action Notes</label>
                        <textarea name="action_taken" rows="4" placeholder="Describe the action taken (e.g. Warning issued, User suspended, Claim rejected)..." required></textarea>
                    </div>
                    <div style="display:flex; justify-content:flex-end; gap:0.5rem; margin-top:1.5rem;">
                        <button type="button" class="btn-admin" onclick="closeModal('resolveReportModal')">Cancel</button>
                        <button type="submit" class="btn-admin primary">Resolve</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Resource Modal -->
    <div class="modal-overlay" id="resourceModal">
        <div class="admin-modal">
            <div class="modal-header">
                <h2 id="resourceModalTitle">Add Library Resource</h2>
                <button class="modal-close" onclick="closeModal('resourceModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="resourceForm" class="admin-form" action="index.php" method="POST">
                    <input type="hidden" name="action" value="add_resource" id="resourceAction">
                    <input type="hidden" name="resource_id" id="resourceId">
                    <div class="form-group">
                        <label>Resource Title</label>
                        <input type="text" name="title" id="resourceTitle" required placeholder="e.g. Grounding Techniques Guide">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Resource Type</label>
                            <select name="type" id="resourceType">
                                <option value="PDF">PDF Guide</option>
                                <option value="Video">Video Course</option>
                                <option value="Article">Article</option>
                                <option value="Guide">Crisis Guide</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Mental Health Category</label>
                            <select name="category" id="resourceCategory">
                                <option value="Anxiety">Anxiety</option>
                                <option value="Stress">Stress</option>
                                <option value="Depression">Depression</option>
                                <option value="Relationships">Relationships</option>
                                <option value="Self-Confidence">Self-Confidence</option>
                                <option value="Mindfulness">Mindfulness</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Short Content Abstract / Description</label>
                        <textarea name="description" id="resourceDescription" rows="4" placeholder="Briefly describe this resource content..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Resource URL / Download Link</label>
                        <input type="url" name="url" id="resourceUrl" placeholder="https://safehaven.com/resource.pdf">
                    </div>
                    <div style="display:flex; justify-content:flex-end; gap:0.5rem; margin-top:1.5rem;">
                        <button type="button" class="btn-admin" onclick="closeModal('resourceModal')">Cancel</button>
                        <button type="submit" class="btn-admin primary" id="resourceSubmitBtn">Add Resource</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Create Notification Modal -->
    <div class="modal-overlay" id="notificationModal">
        <div class="admin-modal">
            <div class="modal-header">
                <h2>Create System Announcement</h2>
                <button class="modal-close" onclick="closeModal('notificationModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="notificationForm" class="admin-form">
                    <input type="hidden" name="action" value="send_notification">
                    <div class="form-group">
                        <label>Announcement Title</label>
                        <input type="text" name="title" required placeholder="e.g. Scheduled Maintenance">
                    </div>
                    <div class="form-group">
                        <label>Target User Role Selection</label>
                        <select name="target_role" required>
                            <option value="all">Global (All Users)</option>
                            <option value="client">Clients Only</option>
                            <option value="therapist">Therapists Only</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Announcement Type</label>
                        <select name="type" required>
                            <option value="system">System Notification</option>
                            <option value="awareness">Awareness Campaign</option>
                            <option value="announcement">Important announcement</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Detailed Message Content</label>
                        <textarea name="message" rows="4" required placeholder="Enter message details..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Schedule Delivery Time (Leave blank for immediate delivery)</label>
                        <input type="datetime-local" name="scheduled_at">
                    </div>
                    <div style="display:flex; justify-content:flex-end; gap:0.5rem; margin-top:1.5rem;">
                        <button type="button" class="btn-admin" onclick="closeModal('notificationModal')">Cancel</button>
                        <button type="submit" class="btn-admin primary">Broadcast Announcement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Therapist Sessions Modal -->
    <div class="modal-overlay" id="therapistSessionsModal">
        <div class="admin-modal" style="max-width: 800px; padding: 0;">
            <div class="modal-header" style="padding: 2rem 2rem 1.5rem 2rem; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 1.25rem;">
                    <div id="ts-avatar" style="width: 65px; height: 65px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700; flex-shrink: 0;">T</div>
                    <div style="overflow: hidden;">
                        <h2 id="ts-name" style="margin:0; font-size: 1.25rem;">Therapist Name</h2>
                        <p id="ts-specialties" style="color: var(--primary); margin:0.25rem 0 0 0; font-size: 0.875rem;">Specialties</p>
                    </div>
                </div>
                <button type="button" class="modal-close" onclick="closeModal('therapistSessionsModal')">&times;</button>
            </div>
            
            <div class="modal-body" style="padding: 1.5rem 2rem; max-height: 60vh; overflow-y: auto;">
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.5rem;">
                    <div style="background: var(--bg-primary); padding: 1rem; border-radius: var(--radius-md); text-align: center; border: 1px solid var(--border-color);">
                        <div style="font-size: 0.75rem; color: var(--text-secondary); font-weight: 700; text-transform: uppercase;">Total Sessions</div>
                        <div id="ts-stat-total" style="font-size: 1.5rem; font-weight: 800; margin-top: 0.25rem;">0</div>
                    </div>
                    <div style="background: var(--bg-primary); padding: 1rem; border-radius: var(--radius-md); text-align: center; border: 1px solid var(--border-color);">
                        <div style="font-size: 0.75rem; color: var(--text-secondary); font-weight: 700; text-transform: uppercase;">Avg Rating</div>
                        <div id="ts-stat-rating" style="font-size: 1.5rem; font-weight: 800; color: #f59e0b; margin-top: 0.25rem;">0.00 ★</div>
                    </div>
                    <div style="background: var(--bg-primary); padding: 1rem; border-radius: var(--radius-md); text-align: center; border: 1px solid var(--border-color);">
                        <div style="font-size: 0.75rem; color: var(--text-secondary); font-weight: 700; text-transform: uppercase;">Session Fee</div>
                        <div id="ts-stat-rate" style="font-size: 1.5rem; font-weight: 800; color: var(--success); margin-top: 0.25rem;">$0.00</div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="modern-table" id="ts-sessions-table">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Date</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Rating</th>
                                <th>Feedback</th>
                            </tr>
                        </thead>
                        <tbody id="ts-sessions-body"></tbody>
                    </table>
                </div>
                <div id="ts-no-sessions" style="display: none; text-align: center; color: var(--text-secondary); padding: 3rem;">
                    <i class="fas fa-calendar-times" style="font-size: 2.5rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                    <p style="font-size: 0.95rem; margin: 0; font-weight: 700;">No sessions found for this therapist</p>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-admin" onclick="closeModal('therapistSessionsModal')">Close Details</button>
            </div>
        </div>
    </div>



    <!-- Add Admin Modal -->
    <div class="modal-overlay" id="addAdminModal">
        <div class="admin-modal" style="max-width: 450px;">
            <div class="modal-header">
                <h2>Add New Administrator</h2>
                <button class="modal-close" onclick="closeModal('addAdminModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addAdminForm" class="admin-form">
                    <input type="hidden" name="action" value="add_admin">
                    <div class="form-group">
                        <label>Admin Email Address</label>
                        <input type="email" name="email" required placeholder="e.g. admin.new@safehaven.com">
                    </div>
                    <div class="form-group">
                        <label>Login Password</label>
                        <input type="password" name="password" required placeholder="At least 6 characters" minlength="6">
                    </div>
                    <div class="form-group">
                        <label>Admin Role Level</label>
                        <select name="admin_role" required>
                            <option value="Support Staff">Support Staff</option>
                            <option value="Content Manager">Content Manager</option>
                            <option value="Therapist Manager">Therapist Manager</option>
                            <option value="Super Admin">Super Admin</option>
                        </select>
                    </div>
                    <div style="display:flex; justify-content:flex-end; gap:0.5rem; margin-top:1.5rem;">
                        <button type="button" class="btn-admin" onclick="closeModal('addAdminModal')">Cancel</button>
                        <button type="submit" class="btn-admin primary">Create Administrator</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Certificate Modal -->
    <div class="modal-overlay" id="viewCertificateModal">
        <div class="admin-modal" style="max-width: 800px; width: 90%;">
            <div class="modal-header">
                <h2 id="certModalTitle">Volunteer Certificate</h2>
                <button class="modal-close" onclick="closeModal('viewCertificateModal')">&times;</button>
            </div>
            <div class="modal-body" style="text-align: center; background: #f8fafc; padding: 1.5rem;">
                <div id="certModalContent" style="display: flex; justify-content: center; align-items: center; min-height: 300px;">
                    <!-- Content will be dynamically inserted here via JS -->
                </div>
            </div>
            <div class="modal-footer">
                <a id="certModalDownload" href="#" target="_blank" class="btn-admin primary"><i class="fas fa-external-link-alt"></i> Open in New Tab</a>
                <button type="button" class="btn-admin" onclick="closeModal('viewCertificateModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- MAIN JAVASCRIPT HANDLERS -->
    <script>
        // Tab switching routing
        const tabs = document.querySelectorAll('.sidebar-link[data-tab]');
        const contents = document.querySelectorAll('.tab-content');

        function switchTab(tabId) {
            tabs.forEach(t => {
                if (t.dataset.tab === tabId) t.classList.add('active');
                else t.classList.remove('active');
            });
            contents.forEach(c => {
                if (c.id === tabId) c.classList.add('active');
                else c.classList.remove('active');
            });
            window.scrollTo({top: 0, behavior: 'smooth'});
        }

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                switchTab(tab.dataset.tab);
            });
        });

        // Initialize active tab from URL query parameters if present (unless it is a page reload)
        document.addEventListener('DOMContentLoaded', () => {
            const navigationEntries = performance.getEntriesByType('navigation');
            const isReload = (navigationEntries.length > 0 && navigationEntries[0].type === 'reload') || (window.performance && window.performance.navigation && window.performance.navigation.type === 1);
            
            if (isReload) {
                switchTab('overview');
                const url = new URL(window.location);
                url.searchParams.delete('tab');
                window.history.replaceState({}, '', url);
            } else {
                const urlParams = new URLSearchParams(window.location.search);
                const tabParam = urlParams.get('tab');
                if (tabParam) {
                    switchTab(tabParam);
                }
            }
        });

        // Community Q&A Actions JS Handlers
        function commToggleHelpful(btn) {
            if (btn.classList.contains('loading')) return;
            btn.classList.add('loading');

            const itemId   = btn.dataset.id;
            const itemType = btn.dataset.type;

            fetch('../api/qa/helpful.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ item_id: itemId, type: itemType })
            })
            .then(r => r.json())
            .then(data => {
                btn.classList.remove('loading');
                if (!data.success) { alert(data.message || 'Error'); return; }

                const icon  = btn.querySelector('.hb-icon');
                const count = btn.querySelector('.hb-count');

                if (data.voted) {
                    btn.classList.add('voted');
                    btn.dataset.voted = '1';
                    icon.className = 'fas fa-thumbs-up hb-icon';
                    icon.style.transform = 'scale(1.4)';
                    setTimeout(() => icon.style.transform = '', 200);
                } else {
                    btn.classList.remove('voted');
                    btn.dataset.voted = '0';
                    icon.className = 'far fa-thumbs-up hb-icon';
                }
                count.textContent = data.count;
            })
            .catch(() => btn.classList.remove('loading'));
        }

        // Toggle Comments Section
        function toggleComments(id) {
            const el = document.getElementById(id);
            if (el.style.display === 'none') {
                el.style.display = 'block';
                el.style.opacity = '0';
                setTimeout(() => { el.style.opacity = '1'; }, 10);
            } else {
                el.style.display = 'none';
            }
        }

        function commEditPost(id, oldContent) {
            let newContent = prompt("Edit post content:", oldContent);
            if (newContent !== null && newContent.trim() !== '') {
                let form = document.createElement('form');
                form.method = 'POST';
                form.action = 'index.php?tab=community_qa';
                
                let act = document.createElement('input'); act.type = 'hidden'; act.name = 'community_qa_action'; act.value = 'edit_post';
                let pid = document.createElement('input'); pid.type = 'hidden'; pid.name = 'post_id'; pid.value = id;
                let val = document.createElement('input'); val.type = 'hidden'; val.name = 'new_content'; val.value = newContent;
                
                form.appendChild(act); form.appendChild(pid); form.appendChild(val);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function commEditComment(id, oldContent) {
            let newContent = prompt("Edit comment content:", oldContent);
            if (newContent !== null && newContent.trim() !== '') {
                let form = document.createElement('form');
                form.method = 'POST';
                form.action = 'index.php?tab=community_qa';
                
                let act = document.createElement('input'); act.type = 'hidden'; act.name = 'community_qa_action'; act.value = 'edit_comment';
                let cid = document.createElement('input'); cid.type = 'hidden'; cid.name = 'comment_id'; cid.value = id;
                let val = document.createElement('input'); val.type = 'hidden'; val.name = 'new_content'; val.value = newContent;
                
                form.appendChild(act); form.appendChild(cid); form.appendChild(val);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Theme Toggle (Dark/Light mode)
        const themeToggleBtn = document.getElementById('themeToggleBtn');
        const themeIcon = themeToggleBtn.querySelector('i');
        
        themeToggleBtn.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            themeIcon.className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            updateChartsTheme(newTheme);
            showToast(`Theme switched to ${newTheme} mode!`, 'success');
        });

        // Update Chart Grid and Text Colors dynamically based on the current theme
        function updateChartsTheme(theme) {
            const textColor = theme === 'dark' ? '#cbd5e1' : '#475569';
            const gridColor = theme === 'dark' ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.04)';

            if (!window.myCharts) return;

            for (const key in window.myCharts) {
                const chart = window.myCharts[key];
                if (!chart) continue;

                // Update scales colors if present
                if (chart.options.scales) {
                    if (chart.options.scales.x) {
                        chart.options.scales.x.ticks = chart.options.scales.x.ticks || {};
                        chart.options.scales.x.ticks.color = textColor;
                        chart.options.scales.x.grid = chart.options.scales.x.grid || {};
                        chart.options.scales.x.grid.color = gridColor;
                    }
                    if (chart.options.scales.y) {
                        chart.options.scales.y.ticks = chart.options.scales.y.ticks || {};
                        chart.options.scales.y.ticks.color = textColor;
                        chart.options.scales.y.grid = chart.options.scales.y.grid || {};
                        chart.options.scales.y.grid.color = gridColor;
                    }
                }

                // Update legend colors if present
                if (chart.options.plugins && chart.options.plugins.legend) {
                    chart.options.plugins.legend.labels = chart.options.plugins.legend.labels || {};
                    chart.options.plugins.legend.labels.color = textColor;
                }

                chart.update();
            }
        }

        // Toast Helper
        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'}"></i> <span>${message}</span>`;
            container.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(15px)';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Modals Management
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        // Show Certificate Modal
        function showCertificate(certPath, volunteerName) {
            const contentDiv = document.getElementById('certModalContent');
            const titleHeader = document.getElementById('certModalTitle');
            const downloadBtn = document.getElementById('certModalDownload');

            titleHeader.textContent = `${volunteerName}'s Certificate`;
            
            // For volunteer certificates, paths are saved starting with 'uploads/' relative to the 'volunteer' folder.
            // Adjust path to point to 'volunteer/uploads/' relative to root.
            let adjustedPath = certPath;
            if (certPath.startsWith('uploads/')) {
                adjustedPath = 'volunteer/' + certPath;
            }

            downloadBtn.href = '../' + adjustedPath;
            contentDiv.innerHTML = '';

            const isPdf = adjustedPath.toLowerCase().endsWith('.pdf');
            if (isPdf) {
                // For PDF, use an iframe or embed
                const iframe = document.createElement('iframe');
                iframe.src = '../' + adjustedPath;
                iframe.style.width = '100%';
                iframe.style.height = '500px';
                iframe.style.border = 'none';
                contentDiv.appendChild(iframe);
            } else {
                // Assume it is an image
                const img = document.createElement('img');
                img.src = '../' + adjustedPath;
                img.style.maxWidth = '100%';
                img.style.maxHeight = '500px';
                img.style.objectFit = 'contain';
                img.style.borderRadius = '8px';
                img.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
                contentDiv.appendChild(img);
            }

            openModal('viewCertificateModal');
        }

        // Click outside modal to close
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.remove('active');
                }
            });
        });

        // Users Filtering & Search
        const userSearch = document.getElementById('userListSearch');
        const roleFilter = document.getElementById('userRoleFilter');
        const statusFilter = document.getElementById('userStatusFilter');
        
        function filterUsersTable() {
            const query = userSearch.value.toLowerCase();
            const role = roleFilter.value;
            const status = statusFilter.value;

            document.querySelectorAll('#userListTable tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                const matchesSearch = text.includes(query);
                const matchesRole = role === 'all' || row.dataset.role === role;
                const matchesStatus = status === 'all' || row.dataset.status === status;

                if (matchesSearch && matchesRole && matchesStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        if (userSearch) {
            userSearch.addEventListener('input', filterUsersTable);
            roleFilter.addEventListener('change', filterUsersTable);
            statusFilter.addEventListener('change', filterUsersTable);
        }

        // Ajax controller for administrative posts
        async function submitAdminAction(formData, modalToClose = null) {
            try {
                const response = await fetch('../api/admin/admin_action.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    showToast(result.message || 'Action executed successfully.', 'success');
                    if (modalToClose) closeModal(modalToClose);
                    setTimeout(() => location.reload(), 1200);
                } else {
                    showToast(result.message || 'Action failed.', 'danger');
                }
            } catch (err) {
                showToast('A network error occurred. Please try again.', 'danger');
            }
        }

        // --- Administrative Forms & Actions Bindings ---

        // Edit User
        function openEditUserModal(user) {
            document.getElementById('editUserId').value = user.user_id;
            document.getElementById('editUserEmail').value = user.email;
            document.getElementById('editUserStatus').value = user.status;
            document.getElementById('editUserAdminRole').value = user.admin_role || '';
            document.getElementById('editUserActivityScore').value = user.activity_score;
            openModal('editUserModal');
        }

        document.getElementById('editUserForm').addEventListener('submit', function(e) {
            e.preventDefault();
            submitAdminAction(new FormData(this), 'editUserModal');
        });

        // Suspend/Reactivate user status change
        function changeUserStatus(action, targetUserId) {
            const conf = confirm(`Are you sure you want to perform this status change on user ID: ${targetUserId}?`);
            if (!conf) return;

            const fd = new FormData();
            fd.append('action', action);
            fd.append('target_user_id', targetUserId);
            submitAdminAction(fd);
        }

        // Approve Therapist
        function approveTherapist(therapistId) {
            const conf = confirm('Confirm approval status verification for this therapist?');
            if (!conf) return;
            const fd = new FormData();
            fd.append('action', 'approve_therapist');
            fd.append('therapist_id', therapistId);
            submitAdminAction(fd);
        }

        // Reject Therapist
        function rejectTherapist(therapistId) {
            const comments = prompt('Enter rejection feedback details for the therapist:');
            if (comments === null) return; // cancelled
            const fd = new FormData();
            fd.append('action', 'reject_therapist');
            fd.append('therapist_id', therapistId);
            fd.append('comments', comments);
            submitAdminAction(fd);
        }

        // Approve Volunteer
        function approveVolunteer(volunteerId) {
            const conf = confirm('Confirm approval status verification for this volunteer?');
            if (!conf) return;
            const fd = new FormData();
            fd.append('action', 'approve_volunteer');
            fd.append('volunteer_id', volunteerId);
            submitAdminAction(fd);
        }

        // Reject Volunteer
        function rejectVolunteer(volunteerId) {
            const conf = confirm('Confirm rejection status for this volunteer?');
            if (!conf) return;
            const fd = new FormData();
            fd.append('action', 'reject_volunteer');
            fd.append('volunteer_id', volunteerId);
            submitAdminAction(fd);
        }

        // Edit Therapist Profile
        function openEditTherapistModal(t) {
            document.getElementById('editTherapistId').value = t.therapist_id;
            document.getElementById('editTherapistFirstName').value = t.first_name || '';
            document.getElementById('editTherapistLastName').value = t.last_name || '';
            document.getElementById('editTherapistSpecialties').value = t.specialties || '';
            document.getElementById('editTherapistHourlyRate').value = t.hourly_rate || 0;
            document.getElementById('editTherapistExperience').value = t.years_experience || 0;
            openModal('editTherapistModal');
        }

        document.getElementById('editTherapistForm').addEventListener('submit', function(e) {
            e.preventDefault();
            submitAdminAction(new FormData(this), 'editTherapistModal');
        });

        // View Therapist Sessions
        function viewTherapistSessions(therapist, sessions) {
            const name = `Dr. ${therapist.first_name} ${therapist.last_name}`;
            document.getElementById('ts-name').textContent = name;
            document.getElementById('ts-avatar').textContent = therapist.first_name.charAt(0).toUpperCase();
            document.getElementById('ts-specialties').textContent = therapist.specialties || 'General Psychology';
            
            document.getElementById('ts-stat-total').textContent = sessions.length;
            
            let totalRating = 0, ratedCnt = 0;
            sessions.forEach(s => {
                if (s.rating) { totalRating += parseFloat(s.rating); ratedCnt++; }
            });
            const avg = ratedCnt > 0 ? (totalRating / ratedCnt).toFixed(2) : 'N/A';
            document.getElementById('ts-stat-rating').textContent = avg + (ratedCnt > 0 ? ' ★' : '');
            document.getElementById('ts-stat-rate').textContent = '$' + parseFloat(therapist.hourly_rate || 0).toFixed(2);

            const tbody = document.getElementById('ts-sessions-body');
            const noSessions = document.getElementById('ts-no-sessions');
            const table = document.getElementById('ts-sessions-table');

            tbody.innerHTML = '';
            if (sessions.length === 0) {
                table.style.display = 'none';
                noSessions.style.display = 'block';
            } else {
                table.style.display = '';
                noSessions.style.display = 'none';
                sessions.forEach(s => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td><strong>${s.client_name || 'Anonymous'}</strong></td>
                        <td style="font-size:0.85rem;">${s.session_date}</td>
                        <td><span class="badge badge-info">${s.communication_method}</span></td>
                        <td><span class="status-badge ${s.status === 'Completed' ? 'status-active' : 'status-pending'}">${s.status}</span></td>
                        <td><span style="color:#f59e0b; font-weight:700;">${s.rating ? '★ '.repeat(s.rating) : 'None'}</span></td>
                        <td style="font-size:0.85rem; color:var(--text-secondary); max-width:200px; overflow:hidden; text-overflow:ellipsis;">${s.feedback || 'No comments'}</td>
                    `;
                    tbody.appendChild(tr);
                });
            }
            openModal('therapistSessionsModal');
        }

        // Cancel Session Booking
        function cancelBooking(sessionId) {
            const reason = prompt('Specify cancellation reason:');
            if (reason === null) return;
            const fd = new FormData();
            fd.append('action', 'cancel_session');
            fd.append('session_id', sessionId);
            fd.append('reason', reason);
            submitAdminAction(fd);
        }

        // Reschedule
        function openRescheduleModal(sid) {
            document.getElementById('rescheduleSessionId').value = sid;
            openModal('rescheduleModal');
        }
        document.getElementById('rescheduleForm').addEventListener('submit', function(e) {
            e.preventDefault();
            submitAdminAction(new FormData(this), 'rescheduleModal');
        });

        // Reassign
        function openReassignModal(sid) {
            document.getElementById('reassignSessionId').value = sid;
            openModal('reassignModal');
        }
        document.getElementById('reassignForm').addEventListener('submit', function(e) {
            e.preventDefault();
            submitAdminAction(new FormData(this), 'reassignModal');
        });

        // Create Group
        function openCreateGroupModal() {
            openModal('createGroupModal');
        }
        document.getElementById('createGroupForm').addEventListener('submit', function(e) {
            e.preventDefault();
            submitAdminAction(new FormData(this), 'createGroupModal');
        });

        // Edit Group
        function openEditGroupModal(gs) {
            document.getElementById('editGroupSessionId').value = gs.group_session_id;
            document.getElementById('editGroupTopic').value = gs.topic;
            document.getElementById('editGroupRoomName').value = gs.room_name || '';
            document.getElementById('editGroupDate').value = gs.session_date.replace(' ', 'T');
            document.getElementById('editGroupMaxPart').value = gs.max_participants;
            document.getElementById('editGroupTherapistId').value = gs.therapist_id || '';
            openModal('editGroupModal');
        }
        document.getElementById('editGroupForm').addEventListener('submit', function(e) {
            e.preventDefault();
            submitAdminAction(new FormData(this), 'editGroupModal');
        });

        // Close/Cancel Group Sessions
        function closeGroupSession(gsId) {
            if (!confirm('Mark this group session room as closed?')) return;
            const fd = new FormData();
            fd.append('action', 'close_group_session');
            fd.append('group_session_id', gsId);
            submitAdminAction(fd);
        }
        function cancelGroupSession(gsId) {
            if (!confirm('Cancel this group session room and notify participants?')) return;
            const fd = new FormData();
            fd.append('action', 'cancel_group_session');
            fd.append('group_session_id', gsId);
            submitAdminAction(fd);
        }

        // Moderate Q&A Item
        function moderateQa(subAction, postId) {
            if (!confirm(`Perform action '${subAction}' on Q&A post ID: ${postId}?`)) return;
            const fd = new FormData();
            fd.append('action', 'moderate_qa');
            fd.append('sub_action', subAction);
            fd.append('post_id', postId);
            submitAdminAction(fd);
        }

        // Resource actions
        function openCreateResourceModal() {
            document.getElementById('resourceAction').value = 'add_resource';
            document.getElementById('resourceForm').reset();
            document.getElementById('resourceModalTitle').textContent = 'Add Library Resource';
            document.getElementById('resourceSubmitBtn').textContent = 'Add Resource';
            openModal('resourceModal');
        }
        function openEditResourceModal(res) {
            document.getElementById('resourceAction').value = 'edit_resource';
            document.getElementById('resourceId').value = res.resource_id;
            document.getElementById('resourceTitle').value = res.title;
            document.getElementById('resourceType').value = res.type;
            document.getElementById('resourceCategory').value = res.category;
            document.getElementById('resourceDescription').value = res.description || '';
            document.getElementById('resourceUrl').value = res.url || '';
            document.getElementById('resourceModalTitle').textContent = 'Edit Library Resource';
            document.getElementById('resourceSubmitBtn').textContent = 'Save Changes';
            openModal('resourceModal');
        }
        function deleteResource(resId) {
            if (!confirm('Permanently delete this resource guide?')) return;
            const fd = new FormData();
            fd.append('action', 'delete_resource');
            fd.append('resource_id', resId);
            submitAdminAction(fd);
        }
        document.getElementById('resourceForm').addEventListener('submit', function(e) {
            e.preventDefault();
            // Using standard index.php action processing since resource CMS form matches original index.php handler
            const actionType = document.getElementById('resourceAction').value;
            const fd = new FormData(this);
            fd.set('action', actionType);
            submitAdminAction(fd, 'resourceModal');
        });

        // Resolve Report
        function openResolveReportModal(rid, ruid) {
            document.getElementById('resolveReportId').value = rid;
            document.getElementById('resolveReportUserId').value = ruid || '';
            // Reset fields
            document.getElementById('resolveReportUserAction').value = 'none';
            document.getElementById('resolveReportStatus').value = 'Under Review';
            openModal('resolveReportModal');
        }
        document.getElementById('resolveReportForm').addEventListener('submit', function(e) {
            e.preventDefault();
            submitAdminAction(new FormData(this), 'resolveReportModal');
        });

        // Sync report status with user actions
        document.getElementById('resolveReportUserAction').addEventListener('change', function() {
            const val = this.value;
            const statusSelect = document.getElementById('resolveReportStatus');
            if (val === 'ignore') {
                statusSelect.value = 'Dismissed';
            } else if (val === 'suspend' || val === 'ban') {
                statusSelect.value = 'Resolved';
            }
        });

        // Send Notification Announcement
        function openCreateNotificationModal() {
            openModal('notificationModal');
        }
        document.getElementById('notificationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            submitAdminAction(new FormData(this), 'notificationModal');
        });

        // Add Administrator Form
        document.getElementById('addAdminForm').addEventListener('submit', function(e) {
            e.preventDefault();
            submitAdminAction(new FormData(this), 'addAdminModal');
        });

        // Initialize Chart.js interactive charts
        document.addEventListener('DOMContentLoaded', () => {
            window.myCharts = {};

            // 1. User Growth Chart
            const ctxUsers = document.getElementById('userGrowthChart').getContext('2d');
            window.myCharts.userGrowth = new Chart(ctxUsers, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_column($chart_users, 'month')); ?>,
                    datasets: [{
                        label: 'Registered Users',
                        data: <?php echo json_encode(array_column($chart_users, 'count')); ?>,
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.08)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointBackgroundColor: '#6366f1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.03)' } },
                        x: { grid: { display: false } }
                    }
                }
            });

            // 3. Bookings Chart
            const ctxBookings = document.getElementById('sessionBookingChart').getContext('2d');
            window.myCharts.sessionBooking = new Chart(ctxBookings, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_column($chart_bookings, 'month')); ?>,
                    datasets: [{
                        label: 'Booked Sessions',
                        data: <?php echo json_encode(array_column($chart_bookings, 'count')); ?>,
                        backgroundColor: '#10b981',
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.03)' } },
                        x: { grid: { display: false } }
                    }
                }
            });

            // Apply grid and text colors based on the current theme
            const activeTheme = document.documentElement.getAttribute('data-theme') || 'light';
            updateChartsTheme(activeTheme);
        });
    </script>

    <!-- Isolated Sidebar Toggle Controller -->
    <script>
        (function() {
            function initSidebar() {
                const adminSidebar = document.querySelector('.admin-sidebar');
                const adminToggle = document.getElementById('adminSidebarToggle');
                const adminOverlay = document.getElementById('adminSidebarOverlay');

                if (adminToggle && adminSidebar) {
                    adminToggle.addEventListener('click', (e) => {
                        e.stopPropagation();
                        adminSidebar.classList.toggle('active');
                        if (adminOverlay) adminOverlay.classList.toggle('active');
                    });
                }

                if (adminOverlay) {
                    adminOverlay.addEventListener('click', () => {
                        if (adminSidebar) adminSidebar.classList.remove('active');
                        adminOverlay.classList.remove('active');
                    });
                }

                const sidebarLinks = document.querySelectorAll('.admin-sidebar .sidebar-link');
                sidebarLinks.forEach(link => {
                    link.addEventListener('click', () => {
                        if (window.innerWidth <= 1024) {
                            if (adminSidebar) adminSidebar.classList.remove('active');
                            if (adminOverlay) adminOverlay.classList.remove('active');
                        }
                    });
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initSidebar);
            } else {
                initSidebar();
            }
        })();
    </script>
</body>
</html>
