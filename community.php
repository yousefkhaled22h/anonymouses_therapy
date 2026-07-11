<?php
// community.php
require_once 'includes/db_connect.php';
require_once 'includes/dashboard_components.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_role = ucfirst(strtolower($_SESSION['role'] ?? 'Guest'));
$__community_embedded = !empty($__embedded_mode);

if (!$__community_embedded && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $role_lc = strtolower($user_role);
    if ($role_lc === 'therapist') {
        $search_qs = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
        header("Location: therapist_dashboard.php?view=community" . ($search_qs ? '&' . substr($search_qs, 1) : ''));
        exit();
    } elseif ($role_lc === 'volunteer') {
        $search_qs = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
        header("Location: volunteer/dashboard.php?view=community" . ($search_qs ? '&' . substr($search_qs, 1) : ''));
        exit();
    }
}

$action_url = 'community.php';
$search_action_url = 'community.php';
$search_hidden_inputs = '';

if ($__community_embedded) {
    $role_lc = strtolower($user_role);
    if ($role_lc === 'therapist') {
        $action_url = 'community.php';
        $search_action_url = 'therapist_dashboard.php';
        $search_hidden_inputs = '<input type="hidden" name="view" value="community">';
    } elseif ($role_lc === 'volunteer') {
        $action_url = '../community.php';
        $search_action_url = 'dashboard.php';
        $search_hidden_inputs = '<input type="hidden" name="view" value="community">';
    }
}

$is_guest = !isset($_SESSION['user_id']);
$user_id = $_SESSION['user_id'] ?? null;
$display_name = $_SESSION['anonymous_id'] ?? 'Anonymous';

// Check Verification Status for Therapists (Bypassed for Q&A interactivity)
$is_verified = true;

// Role-based theme logic
$theme = [
    'bg' => '#FAF8F5',
    'primary' => '#8A7055',
    'secondary' => '#3E2723',
    'light' => '#FDFBF8',
    'border' => '#ede8e1'
];

if ($user_role === 'Therapist') {
    $theme = [
        'bg' => '#F4F7FA',
        'primary' => '#337AB7',
        'secondary' => '#1A4D80',
        'light' => '#E3F2FD',
        'border' => '#D1E5F7'
    ];
} elseif ($user_role === 'Volunteer') {
    $theme = [
        'bg' => '#F0F7F4',
        'primary' => '#2D6A4F',
        'secondary' => '#1B4332',
        'light' => '#D8F3DC',
        'border' => '#95D5B2'
    ];
} elseif ($user_role === 'Client' || $user_role === 'Guest') {
    $theme = [
        'bg' => '#FDFBF8',
        'primary' => '#8A7055',
        'secondary' => '#3E2723',
        'light' => '#FAF8F5',
        'border' => '#ede8e1'
    ];
}

// Permission Functions
$can_report = function($viewer_role, $author_role, $is_guest) {
    if ($is_guest) return false;
    $vr = strtolower($viewer_role);
    $ar = strtolower($author_role);
    
    if (!$vr || !$ar) return false;
    if ($ar === 'client') return true;
    if ($ar === 'therapist') {
        // Clients, Therapists, and Volunteers cannot report Therapists. Only Admins could, but we don't have an admin role here explicitly handled.
        // Actually, the user says "client cannot report therapist".
        if ($vr === 'therapist' || $vr === 'volunteer' || $vr === 'client') return false;
        return true; 
    }
    if ($ar === 'volunteer') return true;
    return false;
};

// Handle Logic Actions BEFORE Header Output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_guest && $is_verified) {
    // Define context-aware redirect targets
    $redirect_url = 'community.php';
    $role_lc = strtolower($user_role);
    if ($role_lc === 'therapist') {
        $redirect_url = 'therapist_dashboard.php?view=community';
    } elseif ($role_lc === 'volunteer') {
        $redirect_url = 'volunteer/dashboard.php?view=community';
    }

    $search_param = urlencode($_GET['search'] ?? '');
    $redirect_url_search = $redirect_url;
    if (!empty($search_param)) {
        $redirect_url_search .= (strpos($redirect_url_search, '?') !== false ? '&' : '?') . 'search=' . $search_param;
    }

    // Handle Post Submission
    if (isset($_POST['content']) && !isset($_POST['is_comment']) && !isset($_POST['action']) && strtolower($user_role) !== 'admin') {
        $content = trim($_POST['content']);
        $topic = $_POST['topic'] ?? 'Relationship';

        if (!empty($content)) {
            try {
                $post_id = 'post_' . bin2hex(random_bytes(8));
                $stmt = $pdo->prepare("INSERT INTO community_qna (post_id, user_id, parent_id, post_type, category, content, created_at) VALUES (?, ?, NULL, 'forum', ?, ?, ?)");
                $stmt->execute([$post_id, $user_id, $topic, $content, date('Y-m-d H:i:s')]);
                header("Location: " . $redirect_url);
                exit();
            } catch (PDOException $e) {
                $error = "Error posting: " . $e->getMessage();
            }
        }
    }

    // Handle Comment Submission
    if (isset($_POST['comment_content']) && isset($_POST['post_id']) && !isset($_POST['action']) && strtolower($user_role) !== 'admin') {
        $comment_content = trim($_POST['comment_content']);
        $parent_post_id = $_POST['post_id'];

        if (!empty($comment_content) && !empty($parent_post_id)) {
            try {
                $comment_id = 'cmt_' . bin2hex(random_bytes(8));
                $stmt = $pdo->prepare("INSERT INTO community_qna (post_id, user_id, parent_id, post_type, category, content, created_at) VALUES (?, ?, ?, 'reply', 'General', ?, ?)");
                $stmt->execute([$comment_id, $user_id, $parent_post_id, $comment_content, date('Y-m-d H:i:s')]);
                header("Location: " . $redirect_url_search);
                exit();
            } catch (PDOException $e) {
                $error = "Error posting comment: " . $e->getMessage();
            }
        }
    }

    // Handle Delete and Edit Actions
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'delete_post' && isset($_POST['post_id'])) {
            $post_id = $_POST['post_id'];
            
            $check = $pdo->prepare("SELECT p.user_id, u.role FROM community_qna p JOIN user u ON p.user_id = u.user_id WHERE p.post_id = ?");
            $check->execute([$post_id]);
            $target = $check->fetch(PDO::FETCH_ASSOC);
            
            if ($target) {
                $can_delete = ($target['user_id'] === $user_id) || ($user_role === 'Therapist' && strtolower($target['role'] ?? '') === 'client');
                
                if ($can_delete) {
                    if ($user_role === 'Therapist' && $target['user_id'] !== $user_id) {
                        $del = $pdo->prepare("UPDATE community_qna SET report_status = 'DeletedByTherapist' WHERE post_id = ?");
                    } else {
                        $del = $pdo->prepare("DELETE FROM community_qna WHERE post_id = ?");
                    }
                    $del->execute([$post_id]);
                }
            }
            header("Location: " . $redirect_url_search);
            exit();
        }
        
        if ($action === 'delete_comment' && isset($_POST['comment_id'])) {
            $comment_id = $_POST['comment_id'];
            
            $check = $pdo->prepare("SELECT cc.user_id, u.role FROM community_qna cc JOIN user u ON cc.user_id = u.user_id WHERE cc.post_id = ?");
            $check->execute([$comment_id]);
            $target = $check->fetch(PDO::FETCH_ASSOC);
            if ($target) {
                $can_delete = ($target['user_id'] === $user_id) || ($user_role === 'Therapist' && strtolower($target['role'] ?? '') === 'client');
                
                if ($can_delete) {
                    if ($user_role === 'Therapist' && $target['user_id'] !== $user_id) {
                        $del = $pdo->prepare("UPDATE community_qna SET report_status = 'DeletedByTherapist' WHERE post_id = ?");
                    } else {
                        $del = $pdo->prepare("DELETE FROM community_qna WHERE post_id = ?");
                    }
                    $del->execute([$comment_id]);
                }
            }
            header("Location: " . $redirect_url_search);
            exit();
        }
        
        if ($action === 'edit_post' && isset($_POST['post_id']) && isset($_POST['new_content'])) {
            $post_id = $_POST['post_id'];
            $new_content = trim($_POST['new_content']);
            
            $check = $pdo->prepare("SELECT user_id FROM community_qna WHERE post_id = ?");
            $check->execute([$post_id]);
            $owner = $check->fetchColumn();
            
            if ($owner === $user_id && !empty($new_content)) {
                $upd = $pdo->prepare("UPDATE community_qna SET content = ? WHERE post_id = ?");
                $upd->execute([$new_content, $post_id]);
            }
            header("Location: " . $redirect_url_search);
            exit();
        }

        if ($action === 'edit_comment' && isset($_POST['comment_id']) && isset($_POST['new_content'])) {
            $comment_id = $_POST['comment_id'];
            $new_content = trim($_POST['new_content']);
            
            $check = $pdo->prepare("SELECT user_id FROM community_qna WHERE post_id = ?");
            $check->execute([$comment_id]);
            $owner = $check->fetchColumn();
            
            if ($owner === $user_id && !empty($new_content)) {
                $upd = $pdo->prepare("UPDATE community_qna SET content = ? WHERE post_id = ?");
                $upd->execute([$new_content, $comment_id]);
            }
            header("Location: " . $redirect_url_search);
            exit();
        }
    }
}

// Set body class dynamically for role-based theming
if (strtolower($user_role) === 'therapist') {
    $body_class = 'role-therapist';
} elseif (strtolower($user_role) === 'volunteer') {
    $body_class = 'role-volunteer';
} else {
    $body_class = 'role-client';
}
// Embedded mode: when included from inside a dashboard (therapist/volunteer),
// skip the global header/footer – the parent page already provides them.
if (!$__community_embedded) {
    require_once 'includes/header.php';
}

// Determine Sidebar Content based on Role
$dashboardLink = 'dashboard.php';
$sidebarTheme = '#a88b68'; // Default (Client/Guest) - Beige

if ($user_role === 'Therapist') {
    $dashboardLink = 'therapist_dashboard.php';
    $sidebarTheme = '#337AB7'; // Professional Blue for Therapist
} elseif ($user_role === 'Volunteer') {
    $dashboardLink = 'volunteer/dashboard.php';
    $sidebarTheme = '#27ae60';
}

// Fetch Posts and their Comment Counts
$posts = [];
$search_query = $_GET['search'] ?? '';

// Predefined topics to match the image
$all_topics = [
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

try {
    $where_clause = " WHERE p.parent_id IS NULL AND (p.report_status IS NULL OR p.report_status != 'DeletedByTherapist')";
    $params = [];
    $selected_topic = $_GET['topic'] ?? '';
    if (!empty($selected_topic)) {
        $where_clause .= " AND p.category = ?";
        $params[] = $selected_topic;
    }

    if (!empty($search_query)) {
        $where_clause .= " AND (p.content LIKE ? OR p.category LIKE ?)";
        $params[] = "%$search_query%";
        $params[] = "%$search_query%";
    }

    $query = "
        SELECT 
            p.post_id, p.content, p.created_at as timestamp, p.category as topic_type, p.post_type, p.user_id as post_user_id, p.helpful_count,
            COALESCE(c.anonymous_id, CONCAT(t.first_name, ' ', t.last_name), CONCAT(v.first_name, ' ', v.last_name), 'Unknown') as author_name,
            u.role, t.profile_image, c.avatar_path,
            (SELECT COUNT(*) FROM community_qna cc WHERE cc.parent_id = p.post_id) as answer_count
        FROM community_qna p
        LEFT JOIN client c ON p.user_id = c.user_id
        LEFT JOIN therapist t ON p.user_id = t.user_id
        LEFT JOIN volunteer v ON p.user_id = v.user_id
        LEFT JOIN user u ON p.user_id = u.user_id
        $where_clause
        ORDER BY p.created_at DESC
        LIMIT 20
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch comments for all fetched posts
    $comments = [];
    if (!empty($posts)) {
        $post_ids = array_column($posts, 'post_id');
        $in_clause = str_repeat('?,', count($post_ids) - 1) . '?';
        $comment_query = "
            SELECT 
                cc.post_id as comment_id, cc.parent_id as post_id, cc.content, cc.created_at as timestamp, cc.user_id as comment_user_id,
                COALESCE(c.anonymous_id, CONCAT(t.first_name, ' ', t.last_name), CONCAT(v.first_name, ' ', v.last_name), 'Unknown') as author_name,
                u.role, t.profile_image, c.avatar_path
            FROM community_qna cc
            LEFT JOIN client c ON cc.user_id = c.user_id
            LEFT JOIN therapist t ON cc.user_id = t.user_id
            LEFT JOIN volunteer v ON cc.user_id = v.user_id
            LEFT JOIN user u ON cc.user_id = u.user_id
            WHERE cc.parent_id IN ($in_clause) AND (cc.report_status IS NULL OR cc.report_status != 'DeletedByTherapist')
            ORDER BY cc.created_at ASC
        ";
        $comment_stmt = $pdo->prepare($comment_query);
        $comment_stmt->execute($post_ids);
        $all_comments = $comment_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Group by post_id
        foreach ($all_comments as $c) {
            $comments[$c['post_id']][] = $c;
        }
    }

    // Fetch user's helpful vote status
    $helpful_counts = [];  // [post_id => ['count'=>N, 'voted'=>bool]]
    if (!empty($posts)) {
        // Initialize from pre-calculated counts
        foreach ($posts as $p) {
            $helpful_counts[$p['post_id']] = ['count' => (int)$p['helpful_count'], 'voted' => false];
        }

        if ($user_id) {
            $post_ids = array_column($posts, 'post_id');
            $in_ph    = implode(',', array_fill(0, count($post_ids), '?'));
            try {
                $vstmt = $pdo->prepare("SELECT item_id FROM helpful_votes WHERE user_id=? AND item_id IN ($in_ph) AND item_type='post'");
                $vstmt->execute(array_merge([$user_id], $post_ids));
                foreach ($vstmt->fetchAll(PDO::FETCH_COLUMN) as $vid) {
                    if (isset($helpful_counts[$vid])) {
                        $helpful_counts[$vid]['voted'] = true;
                    }
                }
            } catch (PDOException $e) { /* ignore */ }
        }
    }

    // Fetch topic counts
    $topic_stmt = $pdo->query("SELECT category as post_type, COUNT(*) as count FROM community_qna WHERE parent_id IS NULL GROUP BY category");
    $db_topics = $topic_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Merge actual counts into predefined topics
    // Add any dynamic topics that weren't predefined
    foreach ($db_topics as $topic => $count) {
        $all_topics[$topic] = $count;
    }

} catch (PDOException $e) {
    // echo $e->getMessage();
}
?>

<link rel="stylesheet" href="assets/css/dashboard-style.css">
<!-- FontAwesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
    integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
<style>
    /* Dynamic Theme Coloring */
    :root {
        --primary-color: <?php echo $theme['primary']; ?>;
        --secondary-color: <?php echo $theme['secondary']; ?>;
        --bg-color: <?php echo $theme['bg']; ?>;
        --light-bg: <?php echo $theme['light']; ?>;
        --border-color: <?php echo $theme['border']; ?>;
        --qna-blue: var(--primary-color);
    }

    <?php if ($is_guest): ?>
    .page-container {
        padding: 40px;
        background: var(--bg-color);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: stretch;
    }
    <?php else: ?>
    .dashboard-main {
        padding: 40px;
        background: var(--bg-color);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: stretch;
    }
    <?php endif; ?>

    .topic-select-dropdown {
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
        .topics-sidebar {
            display: none !important;
        }
        .topic-select-dropdown {
            display: inline-block;
        }
    }

    @media (max-width: 768px) {
        .qna-top-row {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 12px !important;
        }
        .qna-top-row .btn-ask {
            width: 100% !important;
            margin-top: 5px !important;
        }
    }

    @media (max-width: 600px) {
        .search-bar {
            flex-direction: column;
            border-radius: 16px;
        }
        .topic-select-dropdown {
            border-inline-end: none;
            border-bottom: 1px solid var(--border-color);
            padding: 10px 15px;
            width: 100%;
            height: auto;
        }
    }

    .container-fluid {
        width: 100%;
        max-width: 1500px;
        margin: 0 auto;
    }

    /* Apply theme background to entire page body */
    body {
        background-color: var(--bg-color) !important;
    }

    /* Therapist-specific card/sidebar colors */
    body.role-therapist .topics-sidebar {
        background: #ffffff !important;
        border-color: #D1E5F7 !important;
    }
    body.role-therapist .question-item {
        background: #ffffff !important;
        border-color: #D1E5F7 !important;
    }
    body.role-therapist .questions-header {
        background: #EBF4FD !important;
        border-color: #D1E5F7 !important;
    }
    body.role-therapist .topics-list li a:hover {
        background-color: #EBF4FD !important;
    }
    body.role-therapist .modal-content {
        background-color: #ffffff !important;
    }

    .qna-layout {
        display: flex;
        gap: 25px;
        max-width: 1750px;
        margin: 0 auto;
        align-items: flex-start;
        width: 100%;
        justify-content: flex-start;
        margin-bottom: 50px;
    }

    /* Topics sidebar sits at the top, same level as the search bar */
    .topics-sidebar {
        position: sticky;
        top: 90px;
        align-self: flex-start;
    }

    .main-qna-content {
        max-width: 1200px;
        width: 100%;
    }

    /* Left Sidebar: Topics */
    .topics-sidebar {
        flex: 0 0 300px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }

    .topics-header {
        padding: 20px 20px 15px;
        border-bottom: 1px solid #e2e8f0;
    }

    .topics-header h3 {
        margin: 0;
        color: #1e293b;
        font-size: 1.25rem;
        font-weight: 700;
    }

    .topics-list {
        list-style: none;
        padding: 0;
        margin: 0;
        max-height: calc(100vh - 250px);
        overflow-y: auto;
    }

    /* Scrollbar styling for topics */
    .topics-list::-webkit-scrollbar {
        width: 8px;
    }

    .topics-list::-webkit-scrollbar-track {
        background: #f1f5f9;
    }

    .topics-list::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    .topics-list::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    .topics-list li {
        border-bottom: 1px solid #f1f5f9;
    }

    .topics-list li a {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 20px;
        color: #475569;
        text-decoration: none;
        transition: background-color 0.2s;
        font-size: 0.95rem;
    }

    .topics-list li a:hover {
        background-color: #f8fafc;
        color: var(--primary-color);
    }

    .topic-count {
        background: #94a3b8;
        color: white;
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    /* Right Side: Main Content */
    .main-qna-content {
        flex: 1;
        min-width: 0;
    }

    /* Search Bar Area */
    .search-bar {
        display: flex;
        margin-bottom: 25px;
        background: var(--primary-light);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        border: 2px solid var(--primary-color);
    }

    .search-input {
        flex: 1;
        padding: 15px 20px;
        border: none;
        font-size: 1.05rem;
        color: var(--text);
        outline: none;
        background: transparent;
    }

    .search-input::placeholder {
        color: #94a3b8;
    }

    .search-btn, .reply-form button, .btn-ask {
        background: var(--primary-color);
        color: white;
        border: none;
        padding: 12px 25px;
        cursor: pointer;
        font-size: 1.05rem;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    body.role-therapist .search-btn, 
    body.role-therapist .reply-form button, 
    body.role-therapist .btn-ask {
        background: rgba(51, 122, 183, 0.85);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,0.2);
    }

    .search-btn:hover {
        filter: brightness(0.85);
    }

    /* Recent Questions List */
    .questions-container {
        background: transparent;
        border: none;
        box-shadow: none;
        overflow: visible;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .questions-header {
        background: white;
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .questions-header h3 {
        margin: 0;
        color: #1e293b;
        font-size: 1.2rem;
    }

    .questions-header .clock-icon {
        color: var(--primary-color);
        font-size: 1.2rem;
    }

    .questions-list {
        display: flex;
        flex-direction: column;
        gap: 20px;
        max-height: calc(100vh - 250px);
        overflow-y: auto;
        padding-right: 8px;
    }

    /* Scrollbar styling for questions list */
    .questions-list::-webkit-scrollbar {
        width: 8px;
    }

    .questions-list::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }

    .questions-list::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    .questions-list::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    .question-item {
        background: white;
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        padding: 24px;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .question-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }
    
    .card-header-row {
        display: flex;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 20px;
    }

    .card-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background-color: color-mix(in srgb, var(--primary-color) 15%, #d1fae5);
        color: var(--primary-color);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        font-weight: bold;
        flex-shrink: 0;
    }

    .card-title-area {
        flex: 1;
    }

    .card-title-text {
        font-size: 1.15rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 6px 0;
        line-height: 1.4;
    }

    .card-meta-text {
        font-size: 0.9rem;
        color: #64748b;
        font-weight: 500;
    }

    .card-tags {
        display: flex;
        gap: 10px;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }

    .card-tag {
        padding: 4px 14px;
        border: 1px solid #cbd5e1;
        border-radius: 9999px;
        font-size: 0.85rem;
        color: #475569;
        font-weight: 600;
        background: #f8fafc;
    }

    .card-footer-row {
        display: flex;
        align-items: center;
        gap: 24px;
        color: #64748b;
        font-weight: 600;
        font-size: 0.95rem;
    }

    .card-footer-item {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        background: none;
        border: none;
        color: inherit;
        font: inherit;
        padding: 0;
        transition: opacity 0.2s;
    }
    
    .card-footer-item:hover {
        opacity: 0.8;
    }

    /* â”€â”€ Helpful button â”€â”€ */
    .helpful-btn {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 6px 14px; border-radius: 50px;
        font-size: 0.88rem; font-weight: 600;
        border: 1.5px solid #e2e8f0;
        background: white; color: #64748b;
        cursor: pointer; transition: all .2s;
        user-select: none;
    }
    .helpful-btn:hover {
        border-color: #22c55e; color: #16a34a; background: #f0fdf4;
        transform: translateY(-1px);
        box-shadow: 0 3px 10px rgba(34,197,94,.15);
    }
    .helpful-btn.voted {
        border-color: #22c55e; color: #16a34a;
        background: linear-gradient(135deg, #f0fdf4, #dcfce7);
        box-shadow: 0 2px 8px rgba(34,197,94,.18);
    }
    .helpful-btn.voted .hb-icon { color: #22c55e; }
    .helpful-btn .hb-icon { font-size: 1rem; transition: transform .2s; }
    .helpful-btn.voted .hb-icon { transform: scale(1.2); }
    .helpful-btn.loading { opacity: .55; pointer-events: none; }

    .new-post-btn-container {
        margin-bottom: 25px;
        display: flex;
        justify-content: flex-end;
    }

    .btn-ask {
        background: var(--primary-color);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: opacity 0.2s;
        text-decoration: none;
    }

    .btn-ask:hover {
        background: var(--secondary-color);
        transform: translateY(-2px);
        color: white;
    }

    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        align-items: center;
        justify-content: center;
    }

    .modal-content {
        background-color: white;
        padding: 30px;
        border-radius: 12px;
        width: 100%;
        max-width: 500px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    }

    .close-btn {
        float: right;
        font-size: 1.5rem;
        font-weight: bold;
        color: #94a3b8;
        cursor: pointer;
        line-height: 1;
    }

    .close-btn:hover {
        color: #1e293b;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-control {
        width: 100%;
        padding: 12px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-family: inherit;
    }

    @media (max-width: 768px) {
        .qna-layout {
            flex-direction: column;
        }

        .topics-sidebar {
            flex: 0 0 auto;
            width: 100%;
        }

        .question-meta {
            flex-wrap: wrap;
            gap: 10px;
        }
    }
</style>

<?php if ($is_guest): ?>
<div class="page-container">
    <div style="display: none;"> <!-- Dummy div to balance the dashboard-main / sidebar script --> </div>
    <div> <!-- Dummy div to balance the dashboard-main div -->
        <div class="container-fluid">
            <!-- Full-width top row: Back Arrow + Search -->
            <div style="display: flex; gap: 15px; margin-bottom: 25px; align-items: center; width: 100%;">
                <div style="margin-right: 5px;">
                    <a href="index.php" style="border-radius: 50%; width: 45px; height: 45px; display: inline-flex; align-items: center; justify-content: center; padding: 0; color: #6c4b2a; border: 2px solid #ede8e1; background: white; transition: all 0.2s; box-shadow: 0 2px 5px rgba(0,0,0,0.05); text-decoration: none;" onmouseover="this.style.transform='scale(1.05)';" onmouseout="this.style.transform='scale(1)';">
                        <i class="fa-solid fa-arrow-left"></i>
                    </a>
                </div>
<?php elseif ($__community_embedded): ?>
<div style="width:100%;">
    <div style="display:none;"></div>
    <div>
        <div class="container-fluid">
            <div class="qna-top-row" style="display: flex; gap: 15px; margin-bottom: 25px; align-items: center; width: 100%;">
                <!-- No sidebar toggle or back arrow in embedded mode -->
<?php else: ?>
<div class="dashboard-wrapper">
    <?php render_sidebar('community'); ?>
    <div class="dashboard-main">
        <div class="container-fluid">
            <!-- Full-width top row: Sidebar Toggle + Search + Ask Button -->
            <div class="qna-top-row" style="display: flex; gap: 15px; margin-bottom: 25px; align-items: center; width: 100%;">
                <!-- Sidebar toggle button for mobile/desktop -->
                <button id="sidebarToggle" class="sidebar-toggle-inline" aria-label="Toggle Sidebar" style="border-radius: 50%; width: 45px; height: 45px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; padding: 0; background: white; color: var(--primary-color); border: 2px solid #ede8e1; box-shadow: 0 2px 5px rgba(0,0,0,0.05); cursor: pointer;">
                    <i id="toggleIcon" class="fa-solid fa-bars-staggered"></i>
                </button>
<?php endif; ?>
            <!-- Search Bar -->
            <form action="<?php echo htmlspecialchars($search_action_url); ?>" method="GET" class="search-bar" style="flex: 1; margin-bottom: 0;">
                <?php echo $search_hidden_inputs; ?>
                <select name="topic" class="topic-select-dropdown">
                    <option value=""><?php echo __('All Topics'); ?></option>
                    <?php foreach ($all_topics as $topic_name => $count): ?>
                        <option value="<?php echo htmlspecialchars($topic_name); ?>" <?php echo (($_GET['topic'] ?? '') === $topic_name) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(__($topic_name)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="search" class="search-input form-control" placeholder="<?php echo strtolower($user_role) === 'admin' ? __('Search Questions') : __('Search/Ask Questions'); ?>"
                    value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i>
                </button>
            </form>
            <!-- Ask Button -->
            <?php if (!$is_guest && strtolower($user_role) !== 'admin'): ?>
                <?php if ($is_verified): ?>
                    <button class="btn-ask shadow-sm" onclick="document.getElementById('askModal').style.display='flex'" style="margin-bottom: 0;">
                        <i class="fas fa-plus"></i> <?php echo __('Ask a Question'); ?>
                    </button>
                <?php else: ?>
                    <button class="btn-ask shadow-sm" onclick="alert('<?php echo __('Account pending approval. You can only view for now.'); ?>')" style="margin-bottom: 0; opacity: 0.7; cursor: not-allowed; background: #94a3b8;">
                        <i class="fas fa-lock"></i> <?php echo __('Pending Approval'); ?>
                    </button>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Two-column layout: Topics | Questions (both start at same level) -->
        <div class="qna-layout animate-up delay-100">
            <!-- Left Topics Card -->
            <div class="topics-sidebar">
                <div class="topics-header">
                    <h3><?php echo __('Q & A Topics'); ?></h3>
                </div>
                <ul class="topics-list">
                    <?php foreach ($all_topics as $topic => $count): ?>
                        <li>
                            <a href="<?php echo ($__community_embedded ? '' : 'community.php') . '?topic=' . urlencode($topic) . ($__community_embedded ? '&view=community' : '') . (!empty($search_query) ? '&search=' . urlencode($search_query) : ''); ?>">
                                <?php echo htmlspecialchars(__($topic)); ?>
                                <span class="topic-count"><?php echo $count; ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Right Content: Questions only (no search bar here) -->
            <div class="main-qna-content">
                <!-- Questions List -->
                <div class="questions-container">
                    <div class="questions-header">
                        <i class="far fa-clock clock-icon"></i>
                        <h3><?php echo __('Recent Questions'); ?></h3>
                    </div>

                    <div class="questions-list">
                        <?php if (empty($posts)): ?>
                            <div style="text-align: center; color: #94a3b8; padding: 40px;"><?php echo __('No questions found. Be the first to ask!'); ?></div>
                        <?php else: ?>
                            <?php foreach ($posts as $post): ?>
                                <?php $post_comments = $comments[$post['post_id']] ?? []; ?>
                                <div class="question-item">
                                    <div class="card-header-row">
                                        <div class="card-avatar" style="<?php echo (!empty($post['profile_image']) || !empty($post['avatar_path'])) ? 'padding: 0; background: transparent;' : ''; ?>">
                                            <?php if (strtolower($post['role'] ?? '') === 'therapist'): ?>
                                                <?php if (!empty($post['profile_image'])): ?>
                                                    <a href="public_therapist_cv.php?id=<?php echo urlencode($post['post_user_id']); ?>" style="width: 100%; height: 100%; display: block; position: relative;">
                                                        <img src="<?php echo htmlspecialchars($root . $post['profile_image']); ?>" alt="Therapist" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                        <div style="display:none; align-items:center; justify-content:center; width: 100%; height: 100%; background: #e2e8f0; border-radius: 50%; color: #475569; font-weight: bold; font-size: 1.2rem;">
                                                            <?php 
                                                            $c_initials = '';
                                                            $c_name_parts = preg_split('/[\s\-_]+/', $post['author_name']);
                                                            if (count($c_name_parts) > 1) {
                                                                $c_initials = strtoupper(substr($c_name_parts[0], 0, 1) . substr($c_name_parts[1], 0, 1));
                                                            } else {
                                                                preg_match_all('/[A-Z]/', $post['author_name'], $c_capitals);
                                                                if (count($c_capitals[0]) > 1) {
                                                                    $c_initials = $c_capitals[0][0] . $c_capitals[0][1];
                                                                } else {
                                                                    $c_initials = strtoupper(substr($post['author_name'], 0, 1));
                                                                }
                                                            }
                                                            echo htmlspecialchars($c_initials); 
                                                            ?>
                                                        </div>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="public_therapist_cv.php?id=<?php echo urlencode($post['post_user_id']); ?>" style="text-decoration: none; color: inherit; display:flex; align-items:center; justify-content:center; width: 100%; height: 100%;">
                                                                                                    <?php 
                                                $c_initials = '';
                                                $c_name_parts = preg_split('/[\s\-_]+/', $post['author_name']);
                                                if (count($c_name_parts) > 1) {
                                                    $c_initials = strtoupper(substr($c_name_parts[0], 0, 1) . substr($c_name_parts[1], 0, 1));
                                                } else {
                                                    preg_match_all('/[A-Z]/', $post['author_name'], $c_capitals);
                                                    if (count($c_capitals[0]) > 1) {
                                                        $c_initials = $c_capitals[0][0] . $c_capitals[0][1];
                                                    } else {
                                                        $c_initials = strtoupper(substr($post['author_name'], 0, 1));
                                                    }
                                                }
                                                echo htmlspecialchars($c_initials); 
                                            ?>

                                                    </a>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                                          <?php if (!empty($post['avatar_path'])): ?>
                                                <img src="<?php echo htmlspecialchars($root . $post['avatar_path']); ?>" alt="User" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                            <?php else: ?>
                                                <?php 
                                                    $c_initials = '';
                                                    $c_name_parts = preg_split('/[\s\-_]+/', $post['author_name']);
                                                    if (count($c_name_parts) > 1) {
                                                        $c_initials = strtoupper(substr($c_name_parts[0], 0, 1) . substr($c_name_parts[1], 0, 1));
                                                    } else {
                                                        preg_match_all('/[A-Z]/', $post['author_name'], $c_capitals);
                                                        if (count($c_capitals[0]) > 1) {
                                                            $c_initials = $c_capitals[0][0] . $c_capitals[0][1];
                                                        } else {
                                                            $c_initials = strtoupper(substr($post['author_name'], 0, 1));
                                                        }
                                                    }
                                                    echo htmlspecialchars($c_initials); 
                                                ?>
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
                                                else echo date('j/n', strtotime($post['timestamp']));
                                                ?>
                                            </div>
                                        </div>
                                        <div class="card-actions-menu" style="display: flex; gap: 10px; align-items: center;">
                                            <?php if ($can_report($user_role, $post['role'] ?? '', $is_guest) && $post['post_user_id'] !== $user_id): ?>
                                                <button type="button" onclick="openReportModal('<?php echo htmlspecialchars($post['post_user_id']); ?>')" style="background: none; border: none; cursor: pointer; color: #94a3b8;" title="Report User">
                                                    <i class="fas fa-flag"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php 
                                            $can_del_post = ($post['post_user_id'] === $user_id) || ($user_role === 'Therapist' && strtolower($post['role'] ?? '') === 'client');
                                            $can_edit_post = ($post['post_user_id'] === $user_id);
                                            ?>
                                            <?php if ($can_edit_post): ?>
                                                <?php if ($is_verified): ?>
                                                    <button type="button" onclick="editPost('<?php echo $post['post_id']; ?>', '<?php echo htmlspecialchars(addslashes($post['content'])); ?>')" style="background: none; border: none; cursor: pointer; color: var(--primary-color);" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <i class="fas fa-edit" style="color: #cbd5e1; cursor: not-allowed;" title="Pending Approval"></i>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <?php if ($can_del_post): ?>
                                                <?php if ($is_verified): ?>
                                                    <form action="<?php echo htmlspecialchars($action_url); ?>" method="POST" style="margin:0; padding:0; display:inline;" onsubmit="return confirm('Delete this post?');">
                                                        <input type="hidden" name="action" value="delete_post">
                                                        <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                                        <button type="submit" style="background: none; border: none; cursor: pointer; color: #ef4444;" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <i class="fas fa-trash" style="color: #cbd5e1; cursor: not-allowed;" title="Pending Approval"></i>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <?php // Removed 3-dot UI placeholder ?>
                                        </div>
                                    </div>

                                    <div class="card-tags">
                                        <span class="card-tag"><?php echo htmlspecialchars(__($post['topic_type'] ?? 'General')); ?></span>
                                    </div>

                                    <?php
                                        $hdata  = $helpful_counts[$post['post_id']] ?? ['count'=>0,'voted'=>false];
                                        $hcount = $hdata['count'];
                                        $hvoted = $hdata['voted'];
                                    ?>
                                    <div class="card-footer-row">
                                        <button type="button"
                                            class="helpful-btn <?php echo $hvoted ? 'voted' : ''; ?>"
                                            id="hbtn_<?php echo $post['post_id']; ?>"
                                            data-id="<?php echo $post['post_id']; ?>"
                                            data-type="post"
                                            data-voted="<?php echo $hvoted ? '1' : '0'; ?>">
                                            <i class="<?php echo $hvoted ? 'fas' : 'far'; ?> fa-thumbs-up hb-icon"></i>
                                            <span class="hb-count"><?php echo $hcount; ?></span>
                                            <?php echo __('Helpful'); ?>
                                        </button>
                                        <script>
                                            document.getElementById('hbtn_<?php echo $post['post_id']; ?>').onclick = function() {
                                                <?php if ($is_guest): ?>
                                                    alert('<?php echo __('Please log in to mark answers as helpful.'); ?>');
                                                <?php elseif (!$is_verified): ?>
                                                    alert('<?php echo __('Account pending approval. You can only view for now.'); ?>');
                                                <?php else: ?>
                                                    toggleHelpful(this);
                                                <?php endif; ?>
                                            };
                                        </script>
                                        <button type="button" class="card-footer-item" onclick="toggleComments('comments_<?php echo $post['post_id']; ?>')">
                                            <i class="far fa-comment-alt"></i> <?php echo $post['answer_count']; ?> <?php echo $post['answer_count'] == 1 ? __('reply') : __('replies'); ?>
                                        </button>
                                    </div>

                                    <!-- Comments Section (Hidden by default) -->
                                    <div id="comments_<?php echo $post['post_id']; ?>" class="comments-section"
                                        style="display: none; margin-top: 20px; padding-top: 20px; border-top: 1px dashed #e2e8f0;">

                                        <?php if (!empty($post_comments)): ?>
                                            <div class="comments-list" style="margin-bottom: 20px;">
                                                <?php foreach ($post_comments as $cmt): ?>
                                                    <div class="comment-item"
                                                        style="background: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 10px;">
                                                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                                <?php if (strtolower($cmt['role'] ?? '') === 'therapist' && !empty($cmt['profile_image'])): ?>
                                                                    <a href="public_therapist_cv.php?id=<?php echo urlencode($cmt['comment_user_id']); ?>" style="display: block; width: 24px; height: 24px; border-radius: 50%; overflow: hidden; position: relative;">
                                                                        <img src="<?php echo htmlspecialchars($root . $cmt['profile_image']); ?>" style="width: 100%; height: 100%; object-fit: cover;" alt="P" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                                        <div style="display:none; align-items:center; justify-content:center; width: 100%; height: 100%; background: #e2e8f0; color: #475569; font-weight: bold; font-size: 0.7rem;">
                                                                            <?php 
                                                                            $c_initials = '';
                                                                            $c_name_parts = preg_split('/[\s\-_]+/', $cmt['author_name']);
                                                                            if (count($c_name_parts) > 1) {
                                                                                $c_initials = strtoupper(substr($c_name_parts[0], 0, 1) . substr($c_name_parts[1], 0, 1));
                                                                            } else {
                                                                                preg_match_all('/[A-Z]/', $cmt['author_name'], $c_capitals);
                                                                                if (count($c_capitals[0]) > 1) {
                                                                                    $c_initials = $c_capitals[0][0] . $c_capitals[0][1];
                                                                                } else {
                                                                                    $c_initials = strtoupper(substr($cmt['author_name'], 0, 1));
                                                                                }
                                                                            }
                                                                            echo htmlspecialchars($c_initials); 
                                                                            ?>
                                                                        </div>
                                                                    </a>
                                                                <?php elseif (!empty($cmt['avatar_path'])): ?>
                                                                    <div style="width: 24px; height: 24px; border-radius: 50%; overflow: hidden;">
                                                                        <img src="<?php echo htmlspecialchars($root . $cmt['avatar_path']); ?>" style="width: 100%; height: 100%; object-fit: cover;" alt="P">
                                                                    </div>
                                                                <?php else: ?>
                                                                    <div style="width: 24px; height: 24px; border-radius: 50%; background-color: color-mix(in srgb, var(--primary-color) 15%, #d1fae5); color: var(--primary-color); display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold; flex-shrink: 0;">
                                                                        <?php 
                                                                            $c_initials = '';
                                                                            $c_name_parts = preg_split('/[\s\-_]+/', $cmt['author_name']);
                                                                            if (count($c_name_parts) > 1) {
                                                                                $c_initials = strtoupper(substr($c_name_parts[0], 0, 1) . substr($c_name_parts[1], 0, 1));
                                                                            } else {
                                                                                preg_match_all('/[A-Z]/', $cmt['author_name'], $c_capitals);
                                                                                if (count($c_capitals[0]) > 1) {
                                                                                    $c_initials = $c_capitals[0][0] . $c_capitals[0][1];
                                                                                } else {
                                                                                    $c_initials = strtoupper(substr($cmt['author_name'], 0, 1));
                                                                                }
                                                                            }
                                                                            echo htmlspecialchars($c_initials); 
                                                                        ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <strong style="color: #334155; font-size: 0.95rem;">
                                                                    <?php if (strtolower($cmt['role'] ?? '') === 'therapist'): ?>
                                                                        <a href="public_therapist_cv.php?id=<?php echo urlencode($cmt['comment_user_id']); ?>" style="color: inherit; text-decoration: none;">
                                                                            <?php echo htmlspecialchars($cmt['author_name']); ?>
                                                                        </a>
                                                                    <?php else: ?>
                                                                        <?php echo htmlspecialchars($cmt['author_name']); ?>
                                                                    <?php endif; ?>
                                                                    <?php if (strtolower($cmt['role'] ?? '') === 'therapist'): ?>
                                                                        <span style="color: #22c55e; font-size: 0.8em; margin-left: 5px;"><i
                                                                                class="fas fa-check-circle"></i> Pro</span>
                                                                    <?php endif; ?>
                                                                </strong>
                                                            </div>
                                                            <span style="color: #94a3b8; font-size: 0.8rem; display: flex; align-items: center;">
                                                                <?php echo date('M d, H:i', strtotime($cmt['timestamp'])); ?>
                                                                <?php 
                                                                $can_del_cmt = ($cmt['comment_user_id'] === $user_id) || ($user_role === 'Therapist' && strtolower($cmt['role'] ?? '') === 'client');
                                                                $can_edit_cmt = ($cmt['comment_user_id'] === $user_id);
                                                                ?>
                                                                <?php if ($can_report($user_role, $cmt['role'] ?? '', $is_guest) && $cmt['comment_user_id'] !== $user_id): ?>
                                                                    <button type="button" onclick="openReportModal('<?php echo htmlspecialchars($cmt['comment_user_id']); ?>')" style="background: none; border: none; cursor: pointer; color: #94a3b8; margin-left:10px;" title="Report User">
                                                                        <i class="fas fa-flag"></i>
                                                                    </button>
                                                                <?php endif; ?>
                                                                 <?php if ($can_edit_cmt): ?>
                                                                    <?php if ($is_verified): ?>
                                                                        <button type="button" onclick="editComment('<?php echo $cmt['comment_id']; ?>', '<?php echo htmlspecialchars(addslashes($cmt['content'])); ?>')" style="background:none; border:none; cursor:pointer; color:#64748b; margin-left:10px;"><i class="fas fa-edit"></i></button>
                                                                    <?php else: ?>
                                                                        <i class="fas fa-edit" style="color: #cbd5e1; cursor: not-allowed; margin-left:10px;" title="Pending Approval"></i>
                                                                    <?php endif; ?>
                                                                <?php endif; ?>
                                                                <?php if ($can_del_cmt): ?>
                                                                    <?php if ($is_verified): ?>
                                                                        <form action="<?php echo htmlspecialchars($action_url); ?>" method="POST" style="display:inline; margin:0;" onsubmit="return confirm('Delete this comment?');">
                                                                            <input type="hidden" name="action" value="delete_comment">
                                                                            <input type="hidden" name="comment_id" value="<?php echo $cmt['comment_id']; ?>">
                                                                            <button type="submit" style="background:none; border:none; cursor:pointer; color:#ef4444; margin-left:5px;"><i class="fas fa-trash"></i></button>
                                                                        </form>
                                                                    <?php else: ?>
                                                                        <i class="fas fa-trash" style="color: #cbd5e1; cursor: not-allowed; margin-left:5px;" title="Pending Approval"></i>
                                                                    <?php endif; ?>
                                                                <?php endif; ?>
                                                            </span>
                                                        </div>
                                                        <p style="margin: 0; color: #475569; font-size: 0.95rem; line-height: 1.5;">
                                                            <?php echo nl2br(htmlspecialchars($cmt['content'])); ?>
                                                        </p>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Reply Form -->
                                        <?php if (!$is_guest && strtolower($user_role) !== 'admin'): ?>
                                            <?php if ($is_verified): ?>
                                                <form action="<?php echo htmlspecialchars($action_url); ?>" method="POST" class="reply-form"
                                                    style="display: flex; gap: 10px;">
                                                    <input type="hidden" name="is_comment" value="1">
                                                    <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                                    <input type="text" name="comment_content" placeholder="<?php echo __('Write an answer...'); ?>" required
                                                        style="flex: 1; padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 20px; outline: none; transition: border-color 0.2s;">
                                                    <button type="submit"
                                                        style="background: var(--primary-color); color: white; border: none; padding: 10px 20px; border-radius: 20px; cursor: pointer; font-weight: 600; white-space: nowrap;"><?php echo __('Post'); ?></button>
                                                </form>
                                            <?php else: ?>
                                                <div style="text-align: center; background: #f8fafc; padding: 10px; border-radius: 12px; border: 1px dashed #cbd5e1;">
                                                    <p style="margin: 0; color: #64748b; font-size: 0.9rem;">
                                                        <i class="fas fa-lock"></i> <?php echo __('Posting restricted until account is approved.'); ?>
                                                    </p>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                        <div style="text-align: center; background: #f8fafc; padding: 15px; border-radius: 12px; margin-top: 10px; border: 1px dashed #cbd5e1;">
                                            <p style="margin: 0; color: #64748b; font-size: 0.9rem;">
                                                <?php echo __('Please'); ?> <a href="auth_handler.php?action=role_selection" style="color: var(--primary-color); font-weight: 700;"><?php echo __('Get Started'); ?></a> <?php echo __('to join the conversation.'); ?>
                                            </p>
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

        <!-- Ask Question Modal -->
        <div id="askModal" class="modal">
            <div class="modal-content">
                <span class="close-btn"
                    onclick="document.getElementById('askModal').style.display='none'">&times;</span>
                <h3 style="margin-top: 0; margin-bottom: 20px; color: #1e293b;"><?php echo __('Ask a Question'); ?></h3>
                <form action="<?php echo htmlspecialchars($action_url); ?>" method="POST">
                    <div class="form-group">
                        <select name="topic" class="form-control" required>
                            <option value=""><?php echo __('Select a Topic'); ?></option>
                            <?php foreach (array_keys($all_topics) as $topicName): ?>
                                <option value="<?php echo htmlspecialchars($topicName); ?>">
                                    <?php echo htmlspecialchars(__($topicName)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <textarea name="content" class="form-control" placeholder="<?php echo __("What's your question?"); ?>" required
                            style="height: 120px; resize: vertical;"></textarea>
                    </div>
                    <button type="submit" class="btn-ask" style="width: 100%; justify-content: center;"><?php echo __('Post Question'); ?></button>
                </form>
            </div>
        </div>

        <!-- Report User Modal -->
        <div id="reportModal" class="modal">
            <div class="modal-content" style="max-width: 500px;">
                <span class="close-btn" onclick="closeReportModal()">&times;</span>
                <h3 style="margin-top: 0; margin-bottom: 20px; color: #1e293b;"><?php echo __('Report User'); ?></h3>
                <form id="reportForm" onsubmit="submitReport(event)">
                    <input type="hidden" id="report_reported_user_id" name="reported_user_id">
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom:8px; font-weight:bold;"><?php echo __('Reason for Report'); ?></label>
                        <select name="report_type" id="report_type" class="form-control" style="width:100%; padding:10px; border-radius:8px; border:1px solid #ccc;" required>
                            <option value=""><?php echo __('Select a reason...'); ?></option>
                            <option value="Harassment"><?php echo __('Harassment'); ?></option>
                            <option value="Spam"><?php echo __('Spam'); ?></option>
                            <option value="Hate Speech"><?php echo __('Hate Speech'); ?></option>
                            <option value="Inappropriate Content"><?php echo __('Inappropriate Content'); ?></option>
                            <option value="Bad Words"><?php echo __('Bad Words'); ?></option>
                            <option value="Other"><?php echo __('Other'); ?></option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom:8px; font-weight:bold;"><?php echo __('Additional Details'); ?></label>
                        <textarea name="description" id="report_description" rows="4" class="form-control" style="width:100%; padding:10px; border-radius:8px; border:1px solid #ccc; resize:vertical;"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 1.1rem; border-radius: 12px; font-weight: 600; background: var(--primary-color); color: white; border: none; cursor: pointer;"><?php echo __('Submit Report'); ?></button>
                </form>
            </div>
        </div>
        </div>
        <?php if (!$is_guest && !$__community_embedded) { require_once 'includes/footer.php'; } ?>
    </div>
</div>
<?php if ($is_guest) { require_once 'includes/footer.php'; } ?>

<script>
    // â”€â”€ Helpful / Upvote â”€â”€
    function toggleHelpful(btn) {
        if (btn.classList.contains('loading')) return;
        btn.classList.add('loading');

        const itemId   = btn.dataset.id;
        const itemType = btn.dataset.type;
        const voted    = btn.dataset.voted === '1';

        fetch('<?php echo $root; ?>api/qa/helpful.php', {
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
                // Micro-bounce
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

    // Edit Functions
    function editPost(id, oldContent) {
        let newContent = prompt("Edit your post:", oldContent);
        if (newContent !== null && newContent.trim() !== '') {
            let form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?php echo $action_url; ?>';
            
            let act = document.createElement('input'); act.type = 'hidden'; act.name = 'action'; act.value = 'edit_post';
            let pid = document.createElement('input'); pid.type = 'hidden'; pid.name = 'post_id'; pid.value = id;
            let val = document.createElement('input'); val.type = 'hidden'; val.name = 'new_content'; val.value = newContent;
            
            form.appendChild(act); form.appendChild(pid); form.appendChild(val);
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function editComment(id, oldContent) {
        let newContent = prompt("Edit your comment:", oldContent);
        if (newContent !== null && newContent.trim() !== '') {
            let form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?php echo $action_url; ?>';
            
            let act = document.createElement('input'); act.type = 'hidden'; act.name = 'action'; act.value = 'edit_comment';
            let cid = document.createElement('input'); cid.type = 'hidden'; cid.name = 'comment_id'; cid.value = id;
            let val = document.createElement('input'); val.type = 'hidden'; val.name = 'new_content'; val.value = newContent;
            
            form.appendChild(act); form.appendChild(cid); form.appendChild(val);
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Close modal when clicking outside
    window.onclick = function (event) {
        const askModal = document.getElementById('askModal');
        const reportModal = document.getElementById('reportModal');
        if (event.target == askModal) {
            askModal.style.display = "none";
        }
        if (event.target == reportModal) {
            reportModal.style.display = "none";
        }
    }

    // Report Functions
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

        fetch('<?php echo $root; ?>api/report/submit_report.php', {
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

    document.addEventListener('DOMContentLoaded', () => {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        document.querySelectorAll('.animate-up').forEach(el => observer.observe(el));
    });
</script>

<?php if (!$is_guest && !$__community_embedded) { require_once 'includes/sidebar_toggle_script.php'; } ?>
