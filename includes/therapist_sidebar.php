<?php
// includes/therapist_sidebar.php

global $pdo, $user_id, $therapist_id, $root;

if (!isset($therapist_id) && isset($user_id)) {
    try {
        $stmt = $pdo->prepare("SELECT therapist_id FROM Therapist WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $therapist_id = $stmt->fetchColumn();
    } catch (PDOException $e) {}
}

$live_bookings_pending = 0;
$my_clients_active = 0;
$qa_new = 0;

if (isset($therapist_id) && $therapist_id) {
    $q_pending = $pdo->prepare("SELECT COUNT(*) FROM private_sessions WHERE therapist_id = ? AND LOWER(status) IN ('pending', 'pending_reschedule')");
    $q_pending->execute([$therapist_id]);
    $live_bookings_pending = $q_pending->fetchColumn();

    $q_clients = $pdo->prepare("SELECT COUNT(DISTINCT client_id) FROM private_sessions WHERE therapist_id = ? AND LOWER(status) = 'completed'");
    $q_clients->execute([$therapist_id]);
    $my_clients_active = $q_clients->fetchColumn();
}

$qa_new = $pdo->query("SELECT COUNT(*) FROM community_qna WHERE parent_id IS NULL AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn() ?: 0;

// Determine active view
$view = $_GET['view'] ?? 'home';
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page === 'community.php') $view = 'community';
if ($current_page === 'resources.php') $view = 'resources';
if ($current_page === 'group_management.php') $view = 'groups';

// Ensure $root is available
if (!isset($root)) $root = '';
?>

<style>
/* Sidebar and Toggle CSS */
.mobile-toggle-bar { display: none; background: var(--therapist-secondary, #1e293b); padding: 15px 20px; color: white; align-items: center; justify-content: space-between; }

.ds-sidebar {
    width: 250px;
    background: #ffffff !important;
    padding: 30px 0;
    flex-shrink: 0;
    border-right: 1px solid #cbd5e1;
    display: flex;
    flex-direction: column;
}

[dir="rtl"] .ds-sidebar {
    border-right: none;
    border-left: 1px solid #cbd5e1;
}

.sidebar-link {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: #475569 !important;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    border-left: none !important;
    font-size: 0.95rem;
    margin: 2px 16px;
    border-radius: 12px;
}

.sidebar-link i {
    width: 20px;
    margin-right: 12px;
    font-size: 1.1rem;
    color: #64748b;
    transition: color 0.25s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

[dir="rtl"] .sidebar-link i {
    margin-right: 0;
    margin-left: 12px;
}

.sidebar-link:hover {
    background: #f1f5f9;
    color: #2563eb !important;
}

.sidebar-link:hover i {
    color: #2563eb;
}

.sidebar-link.active {
    background: linear-gradient(135deg, #0f3ba5 0%, #0039a6 100%) !important;
    color: #ffffff !important;
    box-shadow: 0 4px 12px rgba(15, 59, 165, 0.2);
}

.sidebar-link.active i {
    color: #ffffff !important;
}

.mobile-only-link { display: none; }

@media (max-width: 992px) {
    .mobile-toggle-bar { display: flex !important; }
    .ds-sidebar { display: none; width: 100% !important; padding-top: 15px; border-right: none; border-left: none; }
    .ds-sidebar.open { display: block; }
    .sidebar-header-desk { display: none; }
    .mobile-only-link { display: block !important; }
    /* Hide the global header for therapists on mobile */
    body.role-therapist header#main-header { display: none !important; }
    
    /* Hide filters on mobile for resources and community */
    body.role-therapist .filters,
    body.role-therapist .topics-sidebar {
        display: none !important;
    }
}

@media (max-width: 425px) {
    .mobile-toggle-bar { display: none !important; }
    .ds-sidebar { display: none !important; }
}
</style>

<!-- Mobile Toggle -->
<div class="mobile-toggle-bar">
    <h5 style="margin: 0; font-weight: 800; text-transform: uppercase; font-size: 1.1rem; letter-spacing: 1px; color: white;">Safe Haven</h5>
    <button id="sidebarToggleBtn" style="background: transparent; border: none; color: white; font-size: 1.5rem; cursor: pointer;"><i class="fas fa-bars"></i></button>
</div>

<!-- Left Sidebar Column -->
<div class="ds-sidebar sidebar-router" id="dsSidebarMenu">
    <div style="padding: 10px 20px 15px;" class="sidebar-header-desk">
        <h5 style="color: #94a3b8; font-weight: 800; margin: 0; font-size: 0.75rem; letter-spacing: 1.5px; text-transform: uppercase;"><?php echo __('Workspace'); ?></h5>
    </div>
    
    <a href="<?php echo $root; ?>therapist_dashboard.php?view=home" class="sidebar-link <?php echo $view === 'home' ? 'active' : ''; ?>"><i class="fas fa-home"></i> <?php echo __('Dashboard Home'); ?></a>
    
    <a href="<?php echo $root; ?>therapist_dashboard.php?view=bookings" class="sidebar-link <?php echo $view === 'bookings' ? 'active' : ''; ?>" style="display: flex; justify-content: space-between; align-items: center;">
        <span><i class="fas fa-calendar-check"></i> <?php echo __('Live Bookings'); ?></span>
        <?php if($live_bookings_pending > 0): ?><span style="background: #fbbf24; color: #78350f; font-size: 0.75rem; font-weight: 700; padding: 4px 10px; border-radius: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); white-space: nowrap; flex-shrink: 0; display: inline-block;"><?php echo $live_bookings_pending; ?> <?php echo __('pending'); ?></span><?php endif; ?>
    </a>
    
    <a href="<?php echo $root; ?>therapist_dashboard.php?view=availability" class="sidebar-link <?php echo $view === 'availability' ? 'active' : ''; ?>"><i class="fas fa-clock"></i> <?php echo __('Manage Availability'); ?></a>
    
    <a href="<?php echo $root; ?>therapist_dashboard.php?view=clients" class="sidebar-link <?php echo $view === 'clients' ? 'active' : ''; ?>" style="display: flex; justify-content: space-between; align-items: center;">
        <span><i class="fas fa-users"></i> <?php echo __('My Clients'); ?></span>
        <?php if($my_clients_active > 0): ?><span style="background: #34d399; color: #064e3b; font-size: 0.75rem; font-weight: 700; padding: 4px 10px; border-radius: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); white-space: nowrap; flex-shrink: 0; display: inline-block;"><?php echo $my_clients_active; ?> <?php echo __('active'); ?></span><?php endif; ?>
    </a>
    
    <a href="<?php echo $root; ?>therapist_dashboard.php?view=groups" class="sidebar-link <?php echo $view === 'groups' ? 'active' : ''; ?>"><i class="fas fa-people-group"></i> <?php echo __('Group Session Hub'); ?></a>
    
    <a href="<?php echo $root; ?>therapist_dashboard.php?view=community" class="sidebar-link <?php echo $view === 'community' ? 'active' : ''; ?>" style="display: flex; justify-content: space-between; align-items: center;">
        <span><i class="fas fa-comments"></i> <?php echo __('Q&A Forum'); ?></span>
        <?php if($qa_new > 0): ?><span style="background: #ef4444; color: white; font-size: 0.75rem; font-weight: 700; padding: 4px 10px; border-radius: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); white-space: nowrap; flex-shrink: 0; display: inline-block;"><?php echo $qa_new; ?> <?php echo __('new'); ?></span><?php endif; ?>
    </a>
    
    <a href="<?php echo $root; ?>therapist_dashboard.php?view=resources" class="sidebar-link <?php echo $view === 'resources' ? 'active' : ''; ?>"><i class="fas fa-book"></i> <?php echo __('Resource Library'); ?></a>
    
    <!-- Mobile-only links (Profile & Logout) -->
    <a href="<?php echo $root; ?>therapist_profile.php" class="sidebar-link mobile-only-link"><i class="fas fa-user-circle"></i> <?php echo __('My Profile'); ?></a>
    <a href="<?php echo $root; ?>api/auth/logout.php" class="sidebar-link mobile-only-link" style="color: #fca5a5;"><i class="fas fa-sign-out-alt"></i> <?php echo __('Logout'); ?></a>

    <!-- Need Help Box (Mockup Integration) -->
    <div style="padding: 20px 16px 10px; margin-top: auto;" class="sidebar-header-desk">
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; display: flex; flex-direction: column; gap: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
            <div style="width: 36px; height: 36px; border-radius: 50%; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;">
                <i class="fa-solid fa-headset"></i>
            </div>
            <div>
                <h5 style="margin: 0; font-weight: 700; color: #1e293b; font-size: 0.88rem;"><?php echo __('Need Help?'); ?></h5>
                <p style="margin: 4px 0 0; color: #64748b; font-size: 0.75rem; line-height: 1.35; font-weight: 500;"><?php echo __('Our support team is available to assist you.'); ?></p>
            </div>
            <a href="<?php echo $root; ?>contact.php" style="display: flex; align-items: center; justify-content: center; gap: 6px; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; color: #334155; font-size: 0.78rem; font-weight: 700; text-decoration: none; background: #ffffff; transition: background 0.2s; text-align: center;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#ffffff'">
                <?php echo __('Contact Support'); ?> <i class="fa-solid fa-arrow-right" style="font-size: 0.7rem; margin-inline-start: 2px;"></i>
            </a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('sidebarToggleBtn');
    const sidebarMenu = document.getElementById('dsSidebarMenu');
    if(toggleBtn && sidebarMenu) {
        toggleBtn.addEventListener('click', function() {
            sidebarMenu.classList.toggle('open');
        });
    }
});
</script>
