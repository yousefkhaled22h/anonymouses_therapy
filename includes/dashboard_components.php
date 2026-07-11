<?php
// includes/dashboard_components.php

/**
 * Renders the persistent sidebar for the dashboard.
 */
function render_sidebar($active_tab = 'dashboard') {
    global $root, $user_role;
    
    // If the user is a therapist, load the new dedicated Therapist Workspace sidebar
    if (isset($user_role) && strtolower($user_role) === 'therapist') {
        include __DIR__ . '/therapist_sidebar.php';
        return;
    }
    ?>
    <!-- Sidebar Overlay (Mobile) -->
    <div id="sidebarOverlay" class="sidebar-overlay"></div>
    
    <aside class="dashboard-sidebar" id="dashboardSidebar">
        <div class="sidebar-branding" style="margin-bottom: 40px; display: flex; align-items: center; gap: 12px; padding: 0 10px;">
            <span style="font-size: 1.2rem; font-weight: 800; color: var(--primary-color); letter-spacing: -0.5px;"><?php echo __('Safe Haven'); ?></span>
        </div>
        <ul class="sidebar-menu">
            <?php 
            $dashboard_url = (isset($user_role) && strtolower($user_role) === 'volunteer') ? $root . 'volunteer/dashboard.php' : $root . 'dashboard.php';
            ?>
            <li><a href="<?php echo $dashboard_url; ?>" class="<?php echo $active_tab === 'dashboard' ? 'active' : ''; ?>"><i class="fa-solid fa-house icon"></i> <span><?php echo __('Dashboard'); ?></span></a></li>
            <?php if ($user_role === 'Client' || $user_role === 'client'): ?>
                <li><a href="<?php echo $root; ?>therapists.php" class="<?php echo $active_tab === 'therapists' ? 'active' : ''; ?>"><i class="fa-regular fa-user icon"></i> <span><?php echo __('Find a Therapist'); ?></span></a></li>
                <li><a href="<?php echo $root; ?>my_diary.php" class="<?php echo $active_tab === 'diary' ? 'active' : ''; ?>"><i class="fa-solid fa-book-open icon"></i> <span><?php echo __('My Diary'); ?></span></a></li>
                <li><a href="<?php echo $root; ?>group_management.php" class="<?php echo $active_tab === 'groups' ? 'active' : ''; ?>"><i class="fa-solid fa-users icon"></i> <span><?php echo __('Group Therapy'); ?></span></a></li>
                <li><a href="<?php echo $root; ?>challenges.php" class="<?php echo $active_tab === 'challenges' ? 'active' : ''; ?>"><i class="fa-solid fa-trophy icon"></i> <span><?php echo __('Challenges'); ?></span></a></li>
                <li><a href="<?php echo $root; ?>mini_games.php" class="<?php echo $active_tab === 'games' ? 'active' : ''; ?>"><i class="fa-solid fa-gamepad icon"></i> <span><?php echo __('Mini Games'); ?></span></a></li>
                <li><a href="<?php echo $root; ?>resources.php" class="<?php echo $active_tab === 'resources' ? 'active' : ''; ?>"><i class="fa-solid fa-book icon"></i> <span><?php echo __('Resource Library'); ?></span></a></li>
            <?php endif; ?>
            <?php if (isset($user_role) && strtolower($user_role) === 'volunteer'): ?>
                <li><a href="<?php echo $root; ?>volunteer/dashboard.php?view=resources" class="<?php echo $active_tab === 'resources' ? 'active' : ''; ?>"><i class="fa-solid fa-book icon"></i> <span><?php echo __('Resource Library'); ?></span></a></li>
                <li><a href="<?php echo $root; ?>volunteer/dashboard.php?view=groups" class="<?php echo $active_tab === 'groups' ? 'active' : ''; ?>"><i class="fa-solid fa-users icon"></i> <span><?php echo __('Group Session'); ?></span></a></li>
            <?php endif; ?>
            <?php if ($user_role === 'Therapist' || $user_role === 'therapist'): ?>
                <li><a href="<?php echo $root; ?>therapist_profile.php"><i class="fa-solid fa-user icon"></i> <span><?php echo __('My Profile'); ?></span></a></li>
                <li><a href="<?php echo $root; ?>progress_notes.php"><i class="fa-solid fa-file-lines icon"></i> <span><?php echo __('Patient Notes'); ?></span></a></li>
                <li><a href="<?php echo $root; ?>group_management.php"><i class="fa-solid fa-users icon"></i> <span><?php echo __('Group Therapy'); ?></span></a></li>
            <?php endif; ?>
            <?php
            $community_url = (isset($user_role) && strtolower($user_role) === 'volunteer') ? $root . 'volunteer/dashboard.php?view=community' : $root . 'community.php';
            ?>
            <li><a href="<?php echo $community_url; ?>" class="<?php echo $active_tab === 'community' ? 'active' : ''; ?>"><i class="fa-solid fa-comments icon"></i> <span><?php echo __('Community'); ?></span></a></li>
        </ul>
    </aside>
    <?php
}

/**
 * Renders the top header banner for each tab.
 */
function render_header_banner($title, $subtitle) {
    ?>
    <div class="dashboard-header-row">
        <button id="sidebarToggle" class="menu-toggle-btn" aria-label="Toggle Sidebar">
            <i id="toggleIcon" class="fa-solid fa-bars-staggered"></i>
        </button>
    </div>

    <div class="dashboard-header-banner animate-up">
        <h1><?php echo htmlspecialchars(__($title)); ?></h1>
        <p><?php echo htmlspecialchars(__($subtitle)); ?></p>
    </div>
    <?php
}
