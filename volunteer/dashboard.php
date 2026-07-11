<?php
session_start();
$view = $_GET['view'] ?? 'dashboard';

// Prevent browser caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

// Ensure only volunteers can access this page
if (isset($_SESSION['role']) && strtolower($_SESSION['role']) !== 'volunteer') {
    session_destroy();
    header("Location: signin.php");
    exit();
}

try {
    require_once __DIR__ . '/../includes/db_connect.php';

    $userId = $_SESSION['user_id'];

    // 1. Sync Profile Data
    $stmt = $pdo->prepare("SELECT v.first_name, v.last_name, u.email, v.bio, v.skills, v.verification_status FROM volunteer v JOIN user u ON v.user_id = u.user_id WHERE u.user_id = ?");
    $stmt->execute([$userId]);
    $volunteer = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($volunteer) $volunteer['profile_image'] = ''; // Ensure graceful if not in DB schema yet
    
    // Check if user exists
    if (!$volunteer) {
        session_destroy();
        header("Location: signin.php");
        exit();
    }

    $isPending = (strtolower($volunteer['verification_status'] ?? '') === 'pending');

    // Stats
    // 1. Sessions Joined
    $stmtJoined = $pdo->prepare("SELECT COUNT(*) FROM group_session_participants WHERE user_id = ?");
    $stmtJoined->execute([$userId]);
    $sessionsJoined = (int) $stmtJoined->fetchColumn();

    // 2. Posts Created
    $stmtPosts = $pdo->prepare("SELECT COUNT(*) FROM community_qna WHERE user_id = ? AND parent_id IS NULL");
    $stmtPosts->execute([$userId]);
    $postsCreated = (int) $stmtPosts->fetchColumn();

    // 3. Helpful Comments
    $stmtHelpful = $pdo->prepare("SELECT COALESCE(SUM(helpful_count), 0) FROM community_qna WHERE user_id = ? AND parent_id IS NOT NULL");
    $stmtHelpful->execute([$userId]);
    $helpfulComments = (int) $stmtHelpful->fetchColumn();

    // 4. Reports Submitted
    $stmtReports = $pdo->prepare("SELECT COUNT(*) FROM report WHERE reporter_user_id = ?");
    $stmtReports->execute([$userId]);
    $reportsSubmitted = (int) $stmtReports->fetchColumn();

    // Upcoming Group Sessions
    $stmtSessions = $pdo->prepare("
        SELECT gs.topic, gs.session_date as date_time, 'Facilitator' as role 
        FROM group_sessions gs
        JOIN group_session_participants gsu ON gs.group_session_id = gsu.group_session_id
        WHERE gsu.user_id = ? AND gs.status = 'scheduled'
        ORDER BY gs.session_date ASC
    ");
    $stmtSessions->execute([$userId]);
    $upcomingSessions = $stmtSessions->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    if ($e->getCode() == '42S02' || $e->getCode() == '42S22') {
        $errorMsg = "Database hasn't been updated yet. Please run the schema updates (Task 1).";
    } else {
        $errorMsg = "Database Error: " . $e->getMessage();
    }
}

// Use the global header (green volunteer theme)
$body_class = 'role-volunteer';
require_once '../includes/header.php';
require_once '../includes/dashboard_components.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/dashboard-style.css">
<link rel="stylesheet" href="profile.css?v=<?php echo filemtime(__DIR__ . '/profile.css'); ?>">
<link rel="stylesheet" href="dashboard.css?v=<?php echo filemtime(__DIR__ . '/dashboard.css'); ?>">

<style>
    /* Override body background for volunteer green theme */
    body.role-volunteer {
        background-color: #EAF2EC;
        color: #081C15;
    }

    /* Ensure the page wrapper has proper top padding after the sticky global header */
    .vol-dashboard-wrapper {
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 20px;
    }

    /* Override profile header card to match green theme and support banner background */
    .profile-header-card {
        background: url('../assets/images/volunteer_banner.png') center/cover no-repeat;
        border-radius: 20px;
        padding: 40px 30px;
        margin-bottom: 28px;
        color: white;
        position: relative;
        overflow: hidden;
    }

    .profile-header-card::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, rgba(27, 67, 50, 0.85) 0%, rgba(45, 106, 79, 0.5) 100%);
        z-index: 1;
    }

    .profile-header-card > * {
        position: relative;
        z-index: 2;
    }

    .header-card-content {
        display: flex;
        align-items: center;
        gap: 24px;
    }

    .profile-image-container {
        flex-shrink: 0;
    }

    .header-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        border: 3px solid rgba(255,255,255,0.4);
        background: rgba(255,255,255,0.15);
        object-fit: cover;
    }

    .header-name {
        font-size: 1.6rem;
        font-weight: 700;
        color: white;
        margin: 0 0 8px 0;
    }

    .header-contact-info {
        margin-bottom: 10px;
    }

    .header-email-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255,255,255,0.15);
        padding: 4px 12px;
        border-radius: 50px;
        font-size: 0.85rem;
        color: rgba(255,255,255,0.9);
    }

    .header-badges {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .badge-item {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: rgba(255,255,255,0.15);
        padding: 4px 12px;
        border-radius: 50px;
        font-size: 0.8rem;
        color: rgba(255,255,255,0.9);
        font-weight: 600;
    }

    .badge-status {
        background: rgba(255,255,255,0.25);
    }

    /* Alert */
    .alert-error {
        background: #FEF2F2;
        color: #991B1B;
        padding: 16px 20px;
        border-radius: 12px;
        border: 1px solid #FCA5A5;
        margin-bottom: 24px;
    }
</style>

<?php
$active_tab = 'dashboard';
if ($view === 'groups') $active_tab = 'groups';
elseif ($view === 'community') $active_tab = 'community';
elseif ($view === 'resources') $active_tab = 'resources';
?>
<div class="dashboard-wrapper">
    <?php render_sidebar($active_tab); ?>
    
    <div class="dashboard-main">
        <div class="dashboard-header-row" style="display: flex; gap: 20px; align-items: stretch; margin-bottom: 20px; padding-left: 20px; padding-top: 20px;">
            <button id="sidebarToggle" class="sidebar-toggle-inline" aria-label="Toggle Sidebar" style="background: transparent; border: none; cursor: pointer; font-size: 1.5rem; color: #2E6649;">
                <i id="toggleIcon" class="fa-solid fa-bars-staggered"></i>
            </button>
        </div>

        <div class="vol-dashboard-wrapper" style="padding-top: 0; width: 100%; max-width: 100%;">
    <?php if (isset($errorMsg)): ?>
        <div class="alert alert-error"><?php echo $errorMsg; ?></div>
    <?php else: ?>
        <?php if ($view === 'groups'): ?>
            <?php
            $__embedded_mode = true;
            $user_role = 'volunteer';
            include '../group_management.php';
            ?>
        <?php elseif ($view === 'community'): ?>
            <?php
            $__embedded_mode = true;
            include '../community.php';
            ?>
        <?php elseif ($view === 'resources'): ?>
            <?php
            $__embedded_mode = true;
            include '../resources.php';
            ?>
        <?php else: ?>

        <!-- AWAITING APPROVAL ALERT -->
        <?php if ($isPending): ?>
            <div style="background: #FFF9C4; color: #856404; padding: 20px; border-radius: 16px; margin-bottom: 30px; border-left: 6px solid #FBC02D; display: flex; align-items: center; gap: 20px; text-align: left;">
                <div style="font-size: 2rem;"><i class="fa-solid fa-clock-rotate-left"></i></div>
                <div>
                    <h3 style="margin: 0; font-size: 1.2rem; font-weight: 800;">Awaiting Account Approval</h3>
                    <p style="margin: 5px 0 0; font-size: 0.95rem; opacity: 0.9;">Your volunteer application is currently being reviewed. Access to facilitating group sessions is restricted until your account is approved by an administrator.</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- PROFILE HEADER CARD -->
        <div class="profile-header-card animate-up delay-100" style="display: flex; align-items: center;">
            <div class="header-card-content" style="width: 100%;">
                <?php
                    $avatar = !empty($volunteer['profile_image']) ? htmlspecialchars($volunteer['profile_image']) : '';
                    $initials = strtoupper(substr($volunteer['first_name'] ?? '', 0, 1) . substr($volunteer['last_name'] ?? '', 0, 1));
                    if (!$initials) $initials = 'U';
                ?>
                <div class="profile-image-container">
                    <?php if ($avatar): ?>
                        <img src="<?php echo $avatar; ?>" alt="Profile Picture" class="header-avatar">
                    <?php else: ?>
                        <div class="header-avatar" style="width: 100%; height: 100%; margin: 0; padding: 0; border-radius: 50%; background: rgba(255,255,255,0.15); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 40px; font-weight: 600;">
                            <?php echo htmlspecialchars($initials); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="profile-info-container">
                    <h1 class="header-name"><?php echo htmlspecialchars($volunteer['first_name'] . ' ' . $volunteer['last_name']); ?></h1>
                    <div class="header-contact-info">
                        <span class="header-email-badge">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            <?php echo htmlspecialchars($volunteer['email']); ?>
                        </span>
                    </div>
                    
                    <div class="header-badges">
                        <div class="badge-item">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                            <span>5.0 Rating</span>
                        </div>
                        <div class="badge-item">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                            <span>Volunteer</span>
                        </div>
                        <div class="badge-item badge-status">
                            <span>Status: <?php echo htmlspecialchars(ucfirst($volunteer['verification_status'] ?? 'Active')); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- STATS GRID (4 columns) -->
        <div class="stats-grid animate-up delay-200">
            <div class="custom-stat-card">
                <div class="stat-top" style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                    <div class="stat-icon-circle" style="width: 36px; height: 36px; border-radius: 8px; background: #e8f5e9; color: #2d6a4f; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fa-solid fa-user-group" style="font-size: 0.95rem;"></i>
                    </div>
                    <span class="stat-title" style="font-size: 0.9rem; font-weight: 700; color: #40916c; margin: 0;"><?php echo __('Sessions Joined'); ?></span>
                </div>
                <div class="stat-value-large" style="font-size: 2rem; font-weight: 800; color: #081c15; margin-bottom: 5px;"><?php echo $sessionsJoined; ?></div>
                <div class="stat-subtitle" style="font-size: 0.8rem; color: #64748b; font-weight: 500;"><?php echo __('Total group sessions'); ?></div>
            </div>
            <div class="custom-stat-card">
                <div class="stat-top" style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                    <div class="stat-icon-circle" style="width: 36px; height: 36px; border-radius: 8px; background: #e8f5e9; color: #2d6a4f; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fa-solid fa-comments" style="font-size: 0.95rem;"></i>
                    </div>
                    <span class="stat-title" style="font-size: 0.9rem; font-weight: 700; color: #40916c; margin: 0;"><?php echo __('Posts Created'); ?></span>
                </div>
                <div class="stat-value-large" style="font-size: 2rem; font-weight: 800; color: #081c15; margin-bottom: 5px;"><?php echo $postsCreated; ?></div>
                <div class="stat-subtitle" style="font-size: 0.8rem; color: #64748b; font-weight: 500;"><?php echo __('In Q&A community'); ?></div>
            </div>
            <div class="custom-stat-card">
                <div class="stat-top" style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                    <div class="stat-icon-circle" style="width: 36px; height: 36px; border-radius: 8px; background: #e8f5e9; color: #2d6a4f; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fa-solid fa-comment-dots" style="font-size: 0.95rem;"></i>
                    </div>
                    <span class="stat-title" style="font-size: 0.9rem; font-weight: 700; color: #40916c; margin: 0;"><?php echo __('Helpful Comments'); ?></span>
                </div>
                <div class="stat-value-large" style="font-size: 2rem; font-weight: 800; color: #081c15; margin-bottom: 5px;"><?php echo $helpfulComments; ?></div>
                <div class="stat-subtitle" style="font-size: 0.8rem; color: #64748b; font-weight: 500;"><?php echo __('Helpful interactions'); ?></div>
            </div>
            <div class="custom-stat-card">
                <div class="stat-top" style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                    <div class="stat-icon-circle" style="width: 36px; height: 36px; border-radius: 8px; background: #e8f5e9; color: #2d6a4f; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fa-solid fa-flag" style="font-size: 0.95rem;"></i>
                    </div>
                    <span class="stat-title" style="font-size: 0.9rem; font-weight: 700; color: #40916c; margin: 0;"><?php echo __('Reports Submitted'); ?></span>
                </div>
                <div class="stat-value-large" style="font-size: 2rem; font-weight: 800; color: #081c15; margin-bottom: 5px;"><?php echo $reportsSubmitted; ?></div>
                <div class="stat-subtitle" style="font-size: 0.8rem; color: #64748b; font-weight: 500;"><?php echo __('Reports & supports'); ?></div>
            </div>
        </div>

        <!-- LOWER DASHBOARD LAYOUT -->
        <div class="dashboard-layout animate-up delay-300" style="grid-template-columns: 1fr;">
            <!-- LEFT COLUMN: Upcoming Sessions -->
            <div class="main-content-card">
                <h2 class="main-title" style="margin-bottom: 8px;">Upcoming Group Sessions</h2>
                <p class="main-subtitle" style="margin-bottom: 24px;">Your scheduled group sessions</p>
                
                <?php if (empty($upcomingSessions)): ?>
                    <div class="empty-state-card">
                        No upcoming sessions yet
                    </div>
                <?php else: ?>
                    <div>
                        <?php foreach ($upcomingSessions as $session): ?>
                            <div class="session-card">
                                <div class="session-icon-circle">
                                    <svg style="width:24px; height:24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <div class="session-details">
                                    <div class="session-topic"><?php echo htmlspecialchars($session['topic']); ?></div>
                                    <div class="session-date-row"><?php echo htmlspecialchars(date('M j, Y - g:i A', strtotime($session['date_time']))); ?></div>
                                </div>
                                <a href="#" class="btn-join-session">View</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php endif; ?>
    <?php endif; ?>
        </div>
        <?php require_once '../includes/footer.php'; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('dashboardSidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    if(toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => {
            const isMobile = window.innerWidth <= 992;
            const icon = document.getElementById('toggleIcon');
            
            if (isMobile) {
                sidebar.classList.toggle('active');
                document.getElementById('sidebarOverlay').classList.toggle('active');
            } else {
                sidebar.classList.toggle('is-closed');
                document.querySelector('.dashboard-wrapper').classList.toggle('sidebar-closed');
                
                if (sidebar.classList.contains('is-closed')) {
                    icon.className = 'fa-solid fa-arrow-right-long';
                } else {
                    icon.className = 'fa-solid fa-bars-staggered';
                }
                localStorage.setItem('sidebarClosed', sidebar.classList.contains('is-closed'));
                
                // Trigger reflow to ensure charts/divs resize properly
                setTimeout(() => {
                    window.dispatchEvent(new Event('resize'));
                }, 350);
            }
        });

        document.getElementById('sidebarOverlay').addEventListener('click', () => {
            sidebar.classList.remove('active');
            document.getElementById('sidebarOverlay').classList.remove('active');
        });

        if (window.innerWidth > 992 && localStorage.getItem('sidebarClosed') === 'true') {
            sidebar.classList.add('is-closed');
            document.querySelector('.dashboard-wrapper').classList.add('sidebar-closed');
            document.getElementById('toggleIcon').className = 'fa-solid fa-arrow-right-long';
        }
    }
    
    // Initial animation trigger
    setTimeout(() => {
        const animatedElements = document.querySelectorAll('.animate-up');
        animatedElements.forEach(el => el.classList.add('visible'));
    }, 50);
});
</script>
