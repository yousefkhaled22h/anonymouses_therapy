<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/i18n.php';
// Set root path for the project dynamically
if (file_exists('includes/header.php')) {
    $root = '';
} elseif (file_exists('../includes/header.php')) {
    $root = '../';
} elseif (file_exists('../../includes/header.php')) {
    $root = '../../';
} else {
    $root = '';
}

ob_start('translate_html_buffer');
require_once __DIR__ . '/db_connect.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Safe Haven | Premium Online Therapy</title>
    <link rel="stylesheet" href="<?php echo $root; ?>assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php
    if (!isset($current_role)) {
        $current_role = 'client'; // Default
    }
    if (isset($_SESSION['role'])) {
        $current_role = strtolower($_SESSION['role']);
    } elseif (isset($_GET['role'])) {
        $current_role = strtolower(htmlspecialchars($_GET['role']));
    } elseif (isset($_COOKIE['safehaven_role'])) {
        $current_role = strtolower(htmlspecialchars($_COOKIE['safehaven_role']));
    }
    $valid_roles = ['client', 'therapist', 'volunteer'];
    if (!in_array($current_role, $valid_roles)) $current_role = 'client';

    $user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Client';
    $is_logged_in = isset($_SESSION['user_id']) ? 'true' : 'false';

    // Link and avatar variables defined early for global header availability
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

    $is_volunteer = (strtolower($user_role) === 'volunteer');
    $is_therapist_role = (strtolower($user_role) === 'therapist');
    $base_home = $is_volunteer ? $root.'volunteer/home.php' : $root.'index.php';
    $base_about = $is_volunteer ? $root.'volunteer/about.php' : $root.'about.php';
    $base_diary = $is_volunteer ? $root.'volunteer/diary.php' : $root.'my_diary.php';
    $base_community = $is_therapist_role ? $root.'therapist_dashboard.php?view=community' : ($is_volunteer ? $root.'volunteer/dashboard.php?view=community' : $root.'community.php');
    $base_resources = $is_therapist_role ? $root.'therapist_dashboard.php?view=resources' : ($is_volunteer ? $root.'volunteer/dashboard.php?view=resources' : $root.'resources.php');
    $base_groups = $is_therapist_role ? $root.'therapist_dashboard.php?view=groups' : ($is_volunteer ? $root.'volunteer/dashboard.php?view=groups' : $root.'group_management.php');

    $user_name_for_avatar = $_SESSION['name'] ?? 'User';
    $default_avatar = 'https://ui-avatars.com/api/?name=' . urlencode($user_name_for_avatar) . '&background=random&color=fff&size=128';
    $avatar_path = $default_avatar;

    if (isset($_SESSION['user_id'])) {
        try {
            global $pdo;
            if (isset($pdo)) {
                $fetched_avatar = null;
                if ($current_role === 'therapist') {
                    $stmt = $pdo->prepare("SELECT profile_image FROM therapist WHERE user_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $fetched_avatar = $stmt->fetchColumn();
                    if ($fetched_avatar === 'assets/images/default_avatar.jpg') {
                        $fetched_avatar = null;
                    }
                } elseif ($current_role === 'client') {
                    $stmt = $pdo->prepare("SELECT avatar_path FROM client WHERE user_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $fetched_avatar = $stmt->fetchColumn();
                }
                
                if (!empty($fetched_avatar)) {
                    $avatar_path = $root . $fetched_avatar;
                }
            }
        } catch (Exception $e) {}
    }

    $header_initials = '';
    $clean_name = preg_replace('/^(Dr\.|Dr|Mr\.|Mr|Ms\.|Ms|Mrs\.)\s+/i', '', $user_name_for_avatar);
    $h_name_parts = preg_split('/[\s\-_]+/', $clean_name);
    if (count($h_name_parts) > 1) {
        $header_initials = strtoupper(substr($h_name_parts[0], 0, 1) . substr($h_name_parts[1], 0, 1));
    } else {
        preg_match_all('/[A-Z]/', $user_name_for_avatar, $h_capitals);
        if (count($h_capitals[0]) > 1) {
            $header_initials = $h_capitals[0][0] . $h_capitals[0][1];
        } else {
            $header_initials = strtoupper(substr($user_name_for_avatar, 0, 1));
        }
    }
    
    if (strpos($avatar_path, 'ui-avatars.com') !== false) {
        $avatar_path = 'https://ui-avatars.com/api/?name=' . urlencode($header_initials) . '&background=random&color=fff&size=128';
    }

    $dashboard_link = (strtolower($user_role) === 'therapist') ? $root.'therapist_dashboard.php' : ((strtolower($user_role) === 'volunteer') ? $root.'volunteer/dashboard.php' : $root.'dashboard.php');
    $profile_link = (strtolower($user_role) === 'therapist') ? $root.'therapist_profile.php' : ((strtolower($user_role) === 'volunteer') ? $root.'volunteer/profile.php' : $root.'client_profile.php');
    ?>
    <!-- ══ Zero-flash theme init: runs before CSS paints ══ -->
    <script>
    (function() {
        var VALID      = ['client', 'therapist', 'volunteer'];
        var serverRole = '<?php echo $current_role; ?>';
        var isLoggedIn = <?php echo $is_logged_in; ?>;

        // Guests ALWAYS get beige — no localStorage override for non-logged-in users
        var role = 'client';
        if (isLoggedIn && VALID.indexOf(serverRole) !== -1) {
            role = serverRole;
        }

        // Stamp the body immediately — before stylesheet renders
        document.documentElement.setAttribute('data-role-pending', role);
    })();
    </script>
    <style>
        /* GLOBAL SCROLLBAR REMOVAL & OVERFLOW FIX (Inline to bypass cache) */
        html, body {
            overflow-x: clip !important;
            max-width: 100vw !important;
            -ms-overflow-style: none !important;
            scrollbar-width: none !important;
        }
        ::-webkit-scrollbar {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
        }
        * {
            -ms-overflow-style: none !important;
            scrollbar-width: none !important;
        }
        *::-webkit-scrollbar {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
        }

        /* Top Announcement Bar Styling */
        .top-announcement-bar {
            background-color: var(--primary-dark);
            color: rgba(255, 255, 255, 0.95);
            font-size: 0.82rem;
            font-family: 'Outfit', sans-serif;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            z-index: 1001;
            direction: ltr; /* Default LTR */
        }
        
        html[dir="rtl"] .top-announcement-bar {
            direction: rtl;
        }

        .top-bar-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            width: 100%;
        }

        .top-bar-left, .top-bar-right, .top-bar-middle {
            display: flex;
            align-items: center;
            white-space: nowrap;
        }

        .top-bar-left {
            border-inline-end: 1px solid rgba(255, 255, 255, 0.25);
            padding-inline-end: 15px;
        }

        .top-bar-right {
            border-inline-start: 1px solid rgba(255, 255, 255, 0.25);
            padding-inline-start: 15px;
        }

        .top-bar-middle {
            flex-grow: 1;
            justify-content: center;
            font-weight: 500;
            text-align: center;
            letter-spacing: 0.2px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .welcome-heart-icon {
            margin-inline-end: 8px;
            color: #ff6b6b;
            font-size: 0.85rem;
        }

        .contact-envelope-icon {
            margin-inline-end: 8px;
            color: rgba(255, 255, 255, 0.85);
            font-size: 0.85rem;
        }

        .slogan-text {
            transition: opacity 0.5s ease-in-out;
            opacity: 1;
            display: inline-block;
        }

        .slogan-fade-out {
            opacity: 0;
        }

        @media (max-width: 768px) {
            .top-bar-left, .top-bar-right {
                display: none !important;
            }
            .top-bar-middle {
                width: 100%;
                justify-content: center;
            }
        }

        /* Client Mobile Actions & Overlay Styles */
        .mobile-client-header-actions {
            display: none;
            align-items: center;
            gap: 16px;
            margin-inline-end: 16px;
        }

        .mobile-client-bell-wrapper {
            position: relative;
        }

        .mobile-bell-trigger {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: transparent;
            color: #8B5A2B;
            text-decoration: none;
            transition: background 0.2s;
        }

        .mobile-bell-badge {
            position: absolute;
            top: 2px;
            right: 2px;
            background: #8B5A2B;
            color: white;
            border-radius: 50%;
            font-size: 0.65rem;
            font-weight: 800;
            width: 15px;
            height: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 0 2px #F4EBE1;
        }

        .mobile-client-avatar-wrapper {
            display: flex;
            align-items: center;
        }

        .mobile-client-avatar-img {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #8B5A2B;
        }

        .mobile-notif-dropdown {
            position: absolute;
            top: 45px;
            right: 0;
            width: 320px;
            background: #ffffff;
            border: 1px solid rgba(166, 138, 108, 0.2);
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(166, 138, 108, 0.12);
            z-index: 10000;
            overflow: hidden;
            direction: ltr;
        }

        html[dir="rtl"] .mobile-notif-dropdown {
            direction: rtl;
            right: auto;
            left: 0;
        }

        .mobile-notif-header {
            padding: 12px 16px;
            background: #fbf9f6;
            border-bottom: 1px solid rgba(166, 138, 108, 0.1);
            font-weight: 700;
            font-size: 0.88rem;
            color: #4A3B32;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .mobile-notif-clear-btn {
            background: transparent;
            border: none;
            color: #8B5A2B;
            font-size: 0.75rem;
            cursor: pointer;
            text-decoration: underline;
            font-weight: 600;
        }

        .mobile-notif-list-container {
            max-height: 280px;
            overflow-y: auto;
        }

        /* Client Mobile Overlay Menu Styling */
        .client-mobile-menu-overlay {
            position: fixed;
            top: 70px;
            left: 0;
            width: 100vw;
            height: calc(100vh - 70px);
            background: #F4EBE1;
            z-index: 9998;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            padding: 24px 16px;
            box-sizing: border-box;
        }
        body.mobile-menu-open {
            position: fixed !important;
            width: 100vw !important;
            height: 100vh !important;
            overflow: hidden !important;
        }

        .client-mobile-menu-container {
            max-width: 600px;
            margin: 0 auto;
        }

        .client-mobile-menu-card {
            display: flex;
            flex-direction: column;
            gap: 14px;
            background: transparent;
        }

        /* Overlay Row elements styles */
        .menu-overlay-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            background: #ffffff;
            border: 1px solid rgba(166, 138, 108, 0.12);
            border-radius: 16px;
            text-decoration: none !important;
            transition: all 0.2s ease;
            box-shadow: 0 2px 6px rgba(166, 138, 108, 0.03);
            cursor: pointer;
        }

        .menu-overlay-row:active {
            transform: scale(0.99);
            background: #fbf9f6;
        }

        .menu-chevron {
            color: #a68a6c;
            font-size: 0.95rem;
            transition: transform 0.2s ease;
        }

        /* RTL chevron rotate */
        html[dir="rtl"] .menu-chevron {
            transform: rotate(180deg);
        }

        /* Profile Row Specific styling */
        .profile-row {
            background: #ffffff;
            border: 1px solid rgba(166, 138, 108, 0.2);
            padding: 18px 20px;
        }

        .profile-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .profile-avatar-img {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #a68a6c;
        }

        .profile-meta {
            display: flex;
            flex-direction: column;
            gap: 2px;
            text-align: left;
        }

        html[dir="rtl"] .profile-meta {
            text-align: right;
        }

        .profile-name {
            font-weight: 700;
            font-size: 1.1rem;
            color: #4A3B32;
        }

        .profile-role {
            font-size: 0.8rem;
            color: #8A7055;
            font-weight: 600;
        }

        /* Dashboard Row Specific styling */
        .dashboard-row {
            background: #f4ebe1;
            border: 1px solid rgba(166, 138, 108, 0.25);
        }

        .dashboard-row .row-label {
            color: #8B5A2B;
            font-weight: 700;
        }

        .dashboard-row .row-icon {
            color: #8B5A2B;
        }

        .dashboard-row .menu-chevron {
            color: #8B5A2B;
        }

        /* Left element details */
        .row-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .row-icon {
            font-size: 1.25rem;
            color: #8A7055;
            width: 24px;
            text-align: center;
        }

        .row-label {
            font-weight: 600;
            font-size: 0.98rem;
            color: #4A3B32;
        }

        /* More links menu */
        .menu-overlay-sub-links {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 0 12px 6px;
            margin-top: -6px;
        }

        .sub-link-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.75);
            border: 1px solid rgba(166, 138, 108, 0.08);
            border-radius: 12px;
            text-decoration: none !important;
            color: #4A3B32;
            font-size: 0.88rem;
            font-weight: 600;
            text-align: left;
        }

        html[dir="rtl"] .sub-link-item {
            text-align: right;
        }

        .sub-link-item i {
            color: #a68a6c;
            width: 18px;
            text-align: center;
        }

        /* Language Dropdown Selector styling */
        .language-row {
            background: #ffffff;
        }

        .lang-selector-mobile {
            position: relative;
            display: flex;
            align-items: center;
        }

        .mobile-lang-dropdown {
            appearance: none;
            -webkit-appearance: none;
            background: #F4EBE1;
            border: none;
            border-radius: 8px;
            padding: 6px 28px 6px 14px;
            font-family: inherit;
            font-weight: 700;
            font-size: 0.85rem;
            color: #8B5A2B;
            cursor: pointer;
        }

        .select-caret {
            position: absolute;
            right: 10px;
            font-size: 0.75rem;
            color: #8B5A2B;
            pointer-events: none;
        }

        @media (max-width: 992px) {
            body[data-logged-in="true"][data-role="client"] .nav-links,
            body[data-logged-in="true"][data-role="volunteer"] .nav-links {
                display: none !important;
            }
            body[data-role="client"] .mobile-client-header-actions,
            body[data-role="volunteer"] .mobile-client-header-actions {
                display: flex;
                margin-inline-start: auto;
            }
            .client-mobile-menu-overlay {
                top: 70px;
                height: calc(100vh - 70px);
            }

            /* Volunteer Mobile Actions Styling overrides */
            body[data-role="volunteer"] .mobile-bell-trigger {
                color: #2D6A4F;
            }
            body[data-role="volunteer"] .mobile-bell-badge {
                background: #2D6A4F;
                box-shadow: 0 0 0 2px #F0F7F4;
            }
            body[data-role="volunteer"] .mobile-client-avatar-img {
                border-color: #2D6A4F;
            }

            /* Volunteer Mobile Menu Overlay Styling overrides */
            body[data-role="volunteer"] .client-mobile-menu-overlay {
                background: #F0F7F4;
            }
            body[data-role="volunteer"] .menu-overlay-row {
                background: #ffffff;
                border-color: rgba(45, 106, 79, 0.12);
            }
            body[data-role="volunteer"] .profile-avatar-img {
                border-color: #2D6A4F;
            }
            body[data-role="volunteer"] .profile-name {
                color: #1B4D3E;
            }
            body[data-role="volunteer"] .profile-role {
                color: #40916C;
            }
            body[data-role="volunteer"] .dashboard-row {
                background: #D8F3DC;
                border-color: rgba(45, 106, 79, 0.25);
            }
            body[data-role="volunteer"] .dashboard-row .row-label,
            body[data-role="volunteer"] .dashboard-row .row-icon,
            body[data-role="volunteer"] .dashboard-row .menu-chevron {
                color: #2D6A4F;
            }
            body[data-role="volunteer"] .row-label {
                color: #1B4D3E;
            }
            body[data-role="volunteer"] .row-icon {
                color: #40916C;
            }
            body[data-role="volunteer"] .menu-chevron {
                color: #95D5B2;
            }
            body[data-role="volunteer"] .sub-link-item {
                background: rgba(255, 255, 255, 0.75);
                border-color: rgba(45, 106, 79, 0.08);
                color: #1B4D3E;
            }
            body[data-role="volunteer"] .sub-link-item i {
                color: #2D6A4F;
            }
            body[data-role="volunteer"] .mobile-lang-dropdown {
                background: #D8F3DC;
                color: #2D6A4F;
            }
            body[data-role="volunteer"] .select-caret {
                color: #2D6A4F;
            }
        }

        /* Responsive menu rules for Therapist up to 1024px */
        @media (max-width: 1024px) {
            body[data-logged-in="true"][data-role="therapist"] .nav-links {
                display: none !important;
            }
            body[data-role="therapist"] .mobile-client-header-actions {
                display: flex;
                margin-inline-start: auto;
            }
            body[data-role="therapist"] .nav-toggle {
                display: flex !important;
            }
            body[data-role="therapist"] .client-mobile-menu-overlay {
                top: 70px;
                height: calc(100vh - 70px);
            }
            body[data-role="therapist"] .mobile-bell-trigger {
                color: #337AB7;
            }
            body[data-role="therapist"] .mobile-bell-badge {
                background: #337AB7;
                box-shadow: 0 0 0 2px #F0F7FF;
            }
            body[data-role="therapist"] .mobile-client-avatar-img {
                border-color: #337AB7;
            }

            /* Therapist Mobile Menu Overlay Styling overrides */
            body[data-role="therapist"] .client-mobile-menu-overlay {
                background: #F0F7FF;
            }
            body[data-role="therapist"] .menu-overlay-row {
                background: #ffffff;
                border-color: rgba(51, 122, 183, 0.12);
            }
            body[data-role="therapist"] .profile-avatar-img {
                border-color: #337AB7;
            }
            body[data-role="therapist"] .profile-name {
                color: #1A4D80;
            }
            body[data-role="therapist"] .profile-role {
                color: #337AB7;
            }
            body[data-role="therapist"] .dashboard-row {
                background: #E3F2FD;
                border-color: rgba(51, 122, 183, 0.25);
            }
            body[data-role="therapist"] .dashboard-row .row-label,
            body[data-role="therapist"] .dashboard-row .row-icon,
            body[data-role="therapist"] .dashboard-row .menu-chevron {
                color: #337AB7;
            }
            body[data-role="therapist"] .row-label {
                color: #1A4D80;
            }
            body[data-role="therapist"] .row-icon {
                color: #337AB7;
            }
            body[data-role="therapist"] .menu-chevron {
                color: #BBDEFB;
            }
            body[data-role="therapist"] .sub-link-item {
                background: rgba(255, 255, 255, 0.75);
                border-color: rgba(51, 122, 183, 0.08);
                color: #1A4D80;
            }
            body[data-role="therapist"] .sub-link-item i {
                color: #337AB7;
            }
            body[data-role="therapist"] .mobile-lang-dropdown {
                background: #E3F2FD;
                color: #337AB7;
            }
            body[data-role="therapist"] .select-caret {
                color: #337AB7;
            }
        }
    </style>
</head>

<body class="<?php echo $body_class ?? ''; ?>" data-role="<?php echo $current_role; ?>" data-logged-in="<?php echo $is_logged_in; ?>">
    <?php if (basename($_SERVER['PHP_SELF']) === 'index.php' && !isset($_SESSION['user_id'])): ?>
    <div class="top-announcement-bar">
        <div class="top-bar-container">
            <div class="top-bar-left">
                <span class="welcome-text">
                    <i class="fa-solid fa-heart welcome-heart-icon"></i>
                    <?php echo __('Welcome to Safe Haven'); ?>
                </span>
            </div>
            <div class="top-bar-middle">
                <span id="slogan-container" class="slogan-text">
                    <?php echo __('You are not alone on this journey'); ?>
                </span>
            </div>
            <div class="top-bar-right">
                <span class="contact-email">
                    <i class="fa-solid fa-envelope contact-envelope-icon"></i>
                    safehaven@gmail.com
                </span>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <header id="main-header">
        <div class="container">
            <nav>
                <div class="logo">
                    <a href="<?php echo $root; ?>index.php" style="display: flex; align-items: center; gap: 12px; text-decoration: none; background: transparent;">
                        <img src="<?php echo $root; ?>assets/images/logo.png" alt="Safe Haven Logo" style="height: 45px; width: auto; object-fit: contain; background: transparent;">
                        <span style="font-size: 1.45rem; font-weight: 800; letter-spacing: -0.3px; color: var(--logo-color); white-space: nowrap;">
                            <?php 
                            $is_volunteer_register = 
                                (basename($_SERVER['PHP_SELF']) === 'signup.php' && strpos($_SERVER['PHP_SELF'], '/volunteer/') !== false) ||
                                (basename($_SERVER['PHP_SELF']) === 'register.php' && 
                                ( (isset($role) && strtolower($role) === 'volunteer') || 
                                  (isset($current_role) && strtolower($current_role) === 'volunteer') || 
                                  (isset($_GET['role']) && strtolower($_GET['role']) === 'volunteer') ) );
                            echo $is_volunteer_register ? __('Safe Heaven') : __('Safe Haven'); 
                            ?>
                        </span>
                    </a>
                </div>
                <?php if (isset($_SESSION['user_id']) && ($current_role === 'client' || $current_role === 'volunteer' || $current_role === 'therapist')): ?>
                <!-- Mobile Actions for Logged-In Clients (Bell + Avatar) -->
                <div class="mobile-client-header-actions">
                    <!-- Notification Bell -->
                    <div class="mobile-client-bell-wrapper">
                        <a href="#" id="mobileClientNotifBell" class="mobile-bell-trigger">
                            <i class="fa-solid fa-bell" style="font-size: 1.3rem;"></i>
                            <span id="mobileClientNotifBadge" class="mobile-bell-badge" style="display: none;">0</span>
                        </a>
                        <div id="mobileClientNotifDropdown" class="mobile-notif-dropdown" style="display: none;">
                            <div class="mobile-notif-header">
                                <span><i class="fa-solid fa-bell"></i> <?php echo __('Notifications'); ?></span>
                                <button id="mobileClientNotifClearAll" class="mobile-notif-clear-btn"><?php echo __('Mark all as read'); ?></button>
                            </div>
                            <div id="mobileClientNotifList" class="mobile-notif-list-container">
                                <!-- Injected by JS -->
                            </div>
                        </div>
                    </div>
                    <!-- Profile Avatar -->
                    <a href="<?php echo $profile_link; ?>" class="mobile-client-avatar-wrapper">
                        <img src="<?php echo htmlspecialchars($avatar_path); ?>" alt="Avatar" class="mobile-client-avatar-img">
                    </a>
                </div>
                <?php endif; ?>

                <button class="nav-toggle" id="navToggle" aria-label="Toggle Navigation">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </button>
                <ul class="nav-links">
                    <?php
                    // Variables defined globally in header top
                    ?>
                    
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <li><a href="<?php echo $base_home; ?>"><?php echo __('Home'); ?></a></li>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['user_id'])): 
                        $dashboard_link = (strtolower($user_role) === 'therapist') ? $root.'therapist_dashboard.php' : ((strtolower($user_role) === 'volunteer') ? $root.'volunteer/dashboard.php' : $root.'dashboard.php');
                        $profile_link = (strtolower($user_role) === 'therapist') ? $root.'therapist_profile.php' : ((strtolower($user_role) === 'volunteer') ? $root.'volunteer/profile.php' : $root.'client_profile.php');
                    ?>
                        <li><a href="<?php echo $dashboard_link; ?>" class="nav-pill active"><i class="fa-solid fa-border-all"></i> <?php echo __('Dashboard'); ?></a></li>
                        <?php if (!($is_logged_in === 'true' && (strtolower($user_role) === 'client' || strtolower($user_role) === 'therapist' || strtolower($user_role) === 'volunteer'))): ?>
                        <li class="nav-dropdown">
                            <a href="#" class="dropdown-toggle"><?php echo __('Wellness Hub'); ?> <span class="dropdown-icon">▼</span></a>
                            <ul class="dropdown-menu">
                                <li><a href="<?php echo $base_resources; ?>"><?php echo __('Resource Library'); ?></a></li>
                                <li><a href="<?php echo $base_community; ?>"><?php echo __('Q & A'); ?></a></li>
                                <li><a href="<?php echo $base_groups; ?>"><?php echo __('Group Therapy'); ?></a></li>
                            </ul>
                        </li>
                        <?php endif; ?>
                        <!-- Notification Bell -->
                        <li class="nav-dropdown nav-notif-item" id="navNotifDropdown">
                            <a href="#" class="dropdown-toggle" id="navNotifBell" style="position:relative; display:flex; align-items:center; justify-content:center; width:40px; height:40px; border-radius:50%; transition: background-color 0.2s;">
                                <i class="fa-solid fa-bell" style="font-size: 1.25rem;"></i>
                                <span id="navNotifBadge" style="display:none; position:absolute; top:4px; right:4px; background:#ef4444; color:white; border-radius:50%; font-size:0.65rem; font-weight:800; width:16px; height:16px; align-items:center; justify-content:center; box-shadow: 0 0 0 2px var(--header-bg, #fff);">0</span>
                            </a>
                            <ul class="dropdown-menu" id="navNotifMenu" style="width:360px; right:0; left:auto; padding:0; border-radius:12px; overflow:hidden; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); border: 1px solid var(--accent-color);">
                                <li style="padding:14px 18px; border-bottom:1px solid var(--accent-color); font-weight:700; display:flex; justify-content:space-between; align-items:center; background: var(--secondary-color); color: var(--text-color);">
                                    <span style="display:flex; align-items:center; gap:8px;"><i class="fa-solid fa-bell"></i> <?php echo __('Notifications'); ?></span>
                                    <button id="navNotifClearAll" style="background:transparent; border:none; color: var(--primary-dark); font-size:0.8rem; cursor:pointer; font-weight:600; text-decoration:underline; padding:0; display:none;"><?php echo __('Mark all as read'); ?></button>
                                </li>
                                <div id="navNotifList" style="max-height:360px; overflow-y:auto;">
                                    <li id="navNotifEmpty" style="padding:32px 24px; text-align:center; color:#94a3b8; display:flex; flex-direction:column; align-items:center; gap:12px;">
                                        <i class="fa-solid fa-bell-slash" style="font-size:2rem; opacity:0.4;"></i>
                                        <span><?php echo __('No new notifications'); ?></span>
                                    </li>
                                </div>
                            </ul>
                        </li>
                        <!-- User Profile Stack -->
                        <li style="display: flex; align-items: center;">
                            <a href="<?php echo $profile_link; ?>" style="display: flex; align-items: center; gap: 10px; color: inherit; text-decoration: none; padding: 4px 8px;">
                                <?php if (strpos($avatar_path, 'ui-avatars.com') === false): ?>
                                    <img src="<?php echo htmlspecialchars($avatar_path); ?>" alt="Avatar" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary-color);">
                                <?php else: ?>
                                    <div class="nav-avatar-placeholder" style="width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: var(--secondary-color); color: var(--primary-color); border: 2px solid var(--primary-color); font-size: 0.95rem; font-weight: 700; flex-shrink: 0;">
                                        <?php echo htmlspecialchars($header_initials); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="nav-user-info-text">
                                    <span class="user-name-header" style="font-weight: 700; font-size: 0.9rem; color: var(--text-color);"><?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?></span>
                                    <span style="font-size: 0.75rem; color: #64748b; font-weight: 600;"><?php echo __($_SESSION['role'] ?? 'User'); ?></span>
                                </div>
                            </a>
                        </li>
                        <!-- Divider & Logout -->
                        <div class="nav-divider" style="width: 1px; height: 26px; background-color: var(--accent-color); opacity: 0.6; margin: 0 10px; align-self: center;"></div>
                        <li><a href="<?php echo $root; ?>api/auth/logout.php" class="btn-logout" style="font-weight: 600; font-size: 0.85rem; padding: 6px 12px; border-radius: 8px; border: 1px solid var(--accent-color); transition: all 0.2s;"><?php echo __('Logout'); ?></a></li>
                    <?php else: ?>
                        <li><a href="<?php echo $root; ?>services.php"><?php echo __('Services'); ?></a></li>
                        <li><a href="<?php echo $base_about; ?>"><?php echo __('About Us'); ?></a></li>
                        <li><a href="<?php echo $root; ?>therapists.php"><?php echo __('Find a Therapist'); ?></a></li>
                        <li class="nav-dropdown">
                            <a href="#" class="dropdown-toggle"><?php echo __('Wellness Hub'); ?> <span class="dropdown-icon">▼</span></a>
                            <ul class="dropdown-menu">
                                <li><a href="<?php echo $base_resources; ?>"><?php echo __('Resource Library'); ?></a></li>
                                <li><a href="<?php echo $base_community; ?>"><?php echo __('Q & A'); ?></a></li>
                                <li><a href="<?php echo $base_groups; ?>"><?php echo __('Group Therapy'); ?></a></li>
                            </ul>
                        </li>
                        <li><a href="<?php echo $root; ?>auth_handler.php?action=role_selection" class="btn-get-started"><?php echo __('Get Started'); ?></a></li>
                    <?php endif; ?>
                    <li class="nav-dropdown nav-lang-switcher" style="display: flex; align-items: center;">
                        <a href="#" class="dropdown-toggle" style="display: flex; align-items: center; gap: 6px; text-decoration: none; font-weight: 600; color: var(--nav-link-color); font-size: 0.9rem;">
                            <i class="fa-solid fa-globe"></i>
                            <span class="lang-text"><?php echo $lang === 'en' ? 'EN' : 'AR'; ?></span>
                            <span class="dropdown-icon" style="font-size: 0.65rem; margin-inline-start: 2px;">▼</span>
                        </a>
                        <ul class="dropdown-menu" style="right: 0; left: auto; min-width: 120px;">
                            <li><a href="<?php echo htmlspecialchars($url_en); ?>">English</a></li>
                            <li><a href="<?php echo htmlspecialchars($url_ar); ?>">العربية</a></li>
                        </ul>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <script>
        // Prevent duplicate dropdown initialization from main.js
        window._headerDropdownsInit = true;

        // ── Optimized scroll handler: rAF-throttled, flicker-free ──
        let _ticking = false;
        window.addEventListener('scroll', function () {
            if (!_ticking) {
                window.requestAnimationFrame(function () {
                    const header = document.getElementById('main-header');
                    if (!header) { _ticking = false; return; }
                    if (window.scrollY > 50) {
                        header.classList.add('scrolled');
                        document.body.classList.add('header-scrolled');
                    } else {
                        header.classList.remove('scrolled');
                        document.body.classList.remove('header-scrolled');
                    }
                    _ticking = false;
                });
                _ticking = true;
            }
        }, { passive: true });

        document.addEventListener('DOMContentLoaded', () => {
            const dropdowns = document.querySelectorAll('.nav-dropdown');
            dropdowns.forEach(dropdown => {
                dropdown.querySelector('.dropdown-toggle')?.addEventListener('click', (e) => {
                    e.preventDefault(); e.stopPropagation();
                    dropdowns.forEach(d => { if (d !== dropdown) d.classList.remove('active'); });
                    dropdown.classList.toggle('active');
                });
            });
            document.addEventListener('click', () => dropdowns.forEach(d => d.classList.remove('active')));

            // Mobile Menu Toggle
            const navToggle = document.getElementById('navToggle');
            const navLinks = document.querySelector('.nav-links');
            const currentRole = '<?php echo $current_role; ?>';
            const isLoggedIn = <?php echo $is_logged_in; ?>;
            const isClient = currentRole === 'client' || currentRole === 'volunteer';
            const isTherapist = currentRole === 'therapist';
            
            if (navToggle && navLinks) {
                navToggle.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const useOverlay = isLoggedIn && ((isClient && window.innerWidth <= 992) || (isTherapist && window.innerWidth <= 1024));
                    if (useOverlay) {
                        const clientOverlay = document.getElementById('clientMobileMenuOverlay');
                        if (clientOverlay) {
                            const isVisible = clientOverlay.style.display === 'block';
                            clientOverlay.style.display = isVisible ? 'none' : 'block';
                            navToggle.classList.toggle('active');
                            document.body.classList.toggle('mobile-menu-open', !isVisible);
                            return;
                        }
                    }
                    navLinks.classList.toggle('active');
                    navToggle.classList.toggle('active');
                });

                window.addEventListener('resize', () => {
                    const useOverlay = isLoggedIn && ((isClient && window.innerWidth <= 992) || (isTherapist && window.innerWidth <= 1024));
                    if (!useOverlay) {
                        const clientOverlay = document.getElementById('clientMobileMenuOverlay');
                        if (clientOverlay) {
                            clientOverlay.style.display = 'none';
                        }
                        if (navToggle) navToggle.classList.remove('active');
                        document.body.classList.remove('mobile-menu-open');
                    }
                });

                document.addEventListener('click', (e) => {
                    const clientOverlay = document.getElementById('clientMobileMenuOverlay');
                    if (clientOverlay && clientOverlay.style.display === 'block') {
                        const container = document.querySelector('.client-mobile-menu-container');
                        if (container && !container.contains(e.target) && !navToggle.contains(e.target)) {
                            clientOverlay.style.display = 'none';
                            navToggle.classList.remove('active');
                            document.body.classList.remove('mobile-menu-open');
                        }
                        return;
                    }
                    if (!navLinks.contains(e.target) && !navToggle.contains(e.target)) {
                        navLinks.classList.remove('active');
                        navToggle.classList.remove('active');
                    }
                });
            }
            
            // Notification System Controller
            <?php if (isset($_SESSION['user_id'])): ?>
            (function() {
                const badge = document.getElementById('navNotifBadge');
                const clearAllBtn = document.getElementById('navNotifClearAll');
                const notifList = document.getElementById('navNotifList');
                
                // Mobile Notification selectors
                const mobileBadge = document.getElementById('mobileClientNotifBadge');
                const mobileNotifList = document.getElementById('mobileClientNotifList');
                const mobileClearBtn = document.getElementById('mobileClientNotifClearAll');
                
                const isAr = document.documentElement.getAttribute('dir') === 'rtl';

                function escapeHtml(str) {
                    if (!str) return '';
                    return str.replace(/&/g, "&amp;")
                              .replace(/</g, "&lt;")
                              .replace(/>/g, "&gt;")
                              .replace(/"/g, "&quot;")
                              .replace(/'/g, "&#039;");
                }

                function timeAgo(dateString) {
                    if (!dateString) return '';
                    const date = new Date(dateString.replace(/-/g, "/"));
                    const now = new Date();
                    const seconds = Math.floor((now - date) / 1000);
                    
                    if (seconds < 0 || isNaN(seconds)) {
                        return isAr ? 'الآن' : 'Just now';
                    }
                    if (seconds < 60) {
                        return isAr ? 'الآن' : 'Just now';
                    }
                    const minutes = Math.floor(seconds / 60);
                    if (minutes < 60) {
                        return isAr ? `منذ ${minutes} دقيقة` : `${minutes}m ago`;
                    }
                    const hours = Math.floor(minutes / 60);
                    if (hours < 24) {
                        return isAr ? `منذ ${hours} ساعة` : `${hours}h ago`;
                    }
                    const days = Math.floor(hours / 24);
                    if (days === 1) {
                        return isAr ? 'أمس' : 'Yesterday';
                    }
                    if (days < 7) {
                        return isAr ? `منذ ${days} أيام` : `${days}d ago`;
                    }
                    return date.toLocaleDateString(isAr ? 'ar-EG' : 'en-US', {month: 'short', day: 'numeric'});
                }

                let currentNotifs = [];

                function renderNotifications(notifs) {
                    currentNotifs = notifs;
                    
                    // Count unread
                    const unreadCount = notifs.filter(n => !n.read).length;
                    
                    // Update desktop badge
                    if (unreadCount > 0) {
                        if (badge) {
                            badge.textContent = unreadCount;
                            badge.style.display = 'flex';
                        }
                        if (clearAllBtn) clearAllBtn.style.display = 'block';
                    } else {
                        if (badge) badge.style.display = 'none';
                        if (clearAllBtn) clearAllBtn.style.display = 'none';
                    }

                    // Update mobile badge
                    if (unreadCount > 0) {
                        if (mobileBadge) {
                            mobileBadge.textContent = unreadCount;
                            mobileBadge.style.display = 'flex';
                        }
                        if (mobileClearBtn) mobileClearBtn.style.display = 'block';
                    } else {
                        if (mobileBadge) mobileBadge.style.display = 'none';
                        if (mobileClearBtn) mobileClearBtn.style.display = 'none';
                    }

                    if (notifs.length === 0) {
                        const emptyHtml = `
                            <li id="navNotifEmpty" style="padding:32px 24px; text-align:center; color:#94a3b8; display:flex; flex-direction:column; align-items:center; gap:12px;">
                                <i class="fa-solid fa-bell-slash" style="font-size:2rem; opacity:0.4;"></i>
                                <span>${isAr ? 'لا توجد إشعارات جديدة' : 'No new notifications'}</span>
                            </li>
                        `;
                        if (notifList) notifList.innerHTML = emptyHtml;
                        if (mobileNotifList) {
                            mobileNotifList.innerHTML = `
                                <div style="padding:32px 24px; text-align:center; color:#94a3b8; display:flex; flex-direction:column; align-items:center; gap:12px;">
                                    <i class="fa-solid fa-bell-slash" style="font-size:1.8rem; opacity:0.4;"></i>
                                    <span style="font-size:0.85rem;">${isAr ? 'لا توجد إشعارات جديدة' : 'No new notifications'}</span>
                                </div>
                            `;
                        }
                        return;
                    }

                    let html = '';
                    notifs.forEach(n => {
                        const unreadClass = n.read ? '' : 'notif-unread';
                        html += `
                            <li>
                                <a class="notif-item ${unreadClass} notif-theme-${n.role_theme}" data-id="${n.id}" href="${n.link && n.link !== '#' ? '<?php echo $root; ?>' + n.link : '#'}">
                                    <div class="notif-icon-wrapper">
                                        <i class="fa-solid ${n.icon || 'fa-bell'}"></i>
                                    </div>
                                    <div class="notif-content-wrapper">
                                        <span class="notif-title">${escapeHtml(n.title)}</span>
                                        <span class="notif-body">${escapeHtml(n.body)}</span>
                                        <span class="notif-time">${timeAgo(n.created_at)}</span>
                                    </div>
                                    ${n.read ? '' : '<div class="notif-unread-dot"></div>'}
                                </a>
                            </li>
                        `;
                    });
                    if (notifList) notifList.innerHTML = html;
                    if (mobileNotifList) mobileNotifList.innerHTML = html;
                }

                function loadNotifications() {
                    fetch('<?php echo $root; ?>api/notifications/get.php')
                        .then(r => r.json())
                        .then(data => {
                            if (data.status === 'success') {
                                renderNotifications(data.notifications || []);
                            }
                        })
                        .catch(() => {});
                }

                // Handle single click mark as read & navigation for desktop
                if (notifList) {
                    notifList.addEventListener('click', function(e) {
                        const notifItem = e.target.closest('.notif-item');
                        if (notifItem) {
                            e.preventDefault();
                            const id = notifItem.getAttribute('data-id');
                            const href = notifItem.getAttribute('href');

                            // Call backend API to mark read
                            fetch('<?php echo $root; ?>api/notifications/mark_read.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ id: id })
                            }).catch(() => {});

                            // UI feedback: immediately style as read
                            notifItem.classList.remove('notif-unread');
                            const dot = notifItem.querySelector('.notif-unread-dot');
                            if (dot) dot.remove();

                            // Update badge locally before next poll
                            const wasUnread = currentNotifs.find(n => n.id === id && !n.read);
                            if (wasUnread) {
                                wasUnread.read = 1;
                                const unreadCount = currentNotifs.filter(n => !n.read).length;
                                if (unreadCount > 0) {
                                    if (badge) badge.textContent = unreadCount;
                                    if (mobileBadge) mobileBadge.textContent = unreadCount;
                                } else {
                                    if (badge) badge.style.display = 'none';
                                    if (clearAllBtn) clearAllBtn.style.display = 'none';
                                    if (mobileBadge) mobileBadge.style.display = 'none';
                                    if (mobileClearBtn) mobileClearBtn.style.display = 'none';
                                }
                            }

                            // Navigate
                            if (href && href !== '#') {
                                setTimeout(() => {
                                    window.location.href = href;
                                }, 150);
                            }
                        }
                    });
                }

                // Handle clear all for desktop
                if (clearAllBtn) {
                    clearAllBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        const unreadIds = currentNotifs.filter(n => !n.read).map(n => n.id);
                        if (unreadIds.length === 0) return;

                        // Mark read in backend
                        fetch('<?php echo $root; ?>api/notifications/mark_read.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ all: true, ids: unreadIds })
                        }).catch(() => {});

                        // Update local list state
                        currentNotifs.forEach(n => n.read = 1);
                        renderNotifications(currentNotifs);
                    });
                }

                // Handle mobile bell toggle
                const mobileBell = document.getElementById('mobileClientNotifBell');
                const mobileDropdown = document.getElementById('mobileClientNotifDropdown');
                
                if (mobileBell && mobileDropdown) {
                    mobileBell.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        const isVisible = mobileDropdown.style.display === 'block';
                        mobileDropdown.style.display = isVisible ? 'none' : 'block';
                    });
                    document.addEventListener('click', (e) => {
                        if (mobileDropdown.style.display === 'block' && !mobileDropdown.contains(e.target) && !mobileBell.contains(e.target)) {
                            mobileDropdown.style.display = 'none';
                        }
                    });
                }

                // Handle single click mark as read & navigation for mobile
                if (mobileNotifList) {
                    mobileNotifList.addEventListener('click', function(e) {
                        const notifItem = e.target.closest('.notif-item');
                        if (notifItem) {
                            e.preventDefault();
                            const id = notifItem.getAttribute('data-id');
                            const href = notifItem.getAttribute('href');

                            // Call backend API to mark read
                            fetch('<?php echo $root; ?>api/notifications/mark_read.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ id: id })
                            }).catch(() => {});

                            // UI feedback: immediately style as read
                            notifItem.classList.remove('notif-unread');
                            const dot = notifItem.querySelector('.notif-unread-dot');
                            if (dot) dot.remove();

                            // Update badge locally before next poll
                            const wasUnread = currentNotifs.find(n => n.id === id && !n.read);
                            if (wasUnread) {
                                wasUnread.read = 1;
                                const unreadCount = currentNotifs.filter(n => !n.read).length;
                                if (unreadCount > 0) {
                                    if (badge) badge.textContent = unreadCount;
                                    if (mobileBadge) mobileBadge.textContent = unreadCount;
                                } else {
                                    if (badge) badge.style.display = 'none';
                                    if (clearAllBtn) clearAllBtn.style.display = 'none';
                                    if (mobileBadge) mobileBadge.style.display = 'none';
                                    if (mobileClearBtn) mobileClearBtn.style.display = 'none';
                                }
                            }

                            // Navigate
                            if (href && href !== '#') {
                                setTimeout(() => {
                                    window.location.href = href;
                                }, 150);
                            }
                        }
                    });
                }

                // Handle clear all for mobile
                if (mobileClearBtn) {
                    mobileClearBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        const unreadIds = currentNotifs.filter(n => !n.read).map(n => n.id);
                        if (unreadIds.length === 0) return;

                        // Mark read in backend
                        fetch('<?php echo $root; ?>api/notifications/mark_read.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ all: true, ids: unreadIds })
                        }).catch(() => {});

                        // Update local list state
                        currentNotifs.forEach(n => n.read = 1);
                        renderNotifications(currentNotifs);
                    });
                }

                // Initial load and poll
                loadNotifications();
                setInterval(loadNotifications, 30000);
            })();
            <?php endif; ?>

            // Slogan looping controller for homepage top announcement bar
            (function() {
                const sloganEl = document.getElementById('slogan-container');
                if (!sloganEl) return;
                const slogans = <?php echo json_encode([
                    __("You are not alone on this journey"),
                    __("A safe space for healing and growth"),
                    __("Your mental wellness is our priority"),
                    __("Connect, share, and find inner peace"),
                    __("Professional guidance, complete anonymity")
                ]); ?>;
                if (!slogans || slogans.length <= 1) return;
                let currentIndex = 0;
                setInterval(function() {
                    sloganEl.classList.add('slogan-fade-out');
                    setTimeout(function() {
                        currentIndex = (currentIndex + 1) % slogans.length;
                        sloganEl.textContent = slogans[currentIndex];
                        sloganEl.classList.remove('slogan-fade-out');
                    }, 500);
                }, 4500);
            })();
        });
    </script>

    <?php if (isset($_SESSION['user_id']) && ($current_role === 'client' || $current_role === 'volunteer' || $current_role === 'therapist')): ?>
    <!-- Custom Client Mobile Menu Overlay HTML -->
    <div id="clientMobileMenuOverlay" class="client-mobile-menu-overlay" style="display: none;">
        <div class="client-mobile-menu-container">
            <div class="client-mobile-menu-card">
                
                <!-- 1. Profile Row -->
                <a href="<?php echo $profile_link; ?>" class="menu-overlay-row profile-row">
                    <div class="profile-left">
                        <img src="<?php echo htmlspecialchars($avatar_path); ?>" alt="Avatar" class="profile-avatar-img">
                        <div class="profile-meta">
                            <span class="profile-name"><?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?></span>
                            <span class="profile-role"><?php echo __($user_role); ?></span>
                        </div>
                    </div>
                    <i class="fa-solid fa-chevron-right menu-chevron"></i>
                </a>

                <!-- 2. Dashboard Row -->
                <a href="<?php echo $dashboard_link; ?>" class="menu-overlay-row dashboard-row">
                    <div class="row-left">
                        <i class="fa-solid fa-border-all row-icon"></i>
                        <span class="row-label"><?php echo __('Dashboard'); ?></span>
                    </div>
                    <i class="fa-solid fa-chevron-right menu-chevron"></i>
                </a>

                <!-- 3. More Row / Exposed Links -->
                <?php if ($current_role === 'therapist'): ?>
                    <div class="menu-overlay-row more-row-toggle" id="menuMoreRowToggle">
                        <div class="row-left">
                            <i class="fa-solid fa-ellipsis row-icon"></i>
                            <span class="row-label"><?php echo __('More'); ?></span>
                        </div>
                        <i class="fa-solid fa-chevron-right menu-chevron" id="moreChevron"></i>
                    </div>
                    <!-- Collapsible sub-menu for More links (Therapist: no Home, no About Us) -->
                    <div class="menu-overlay-sub-links" id="menuMoreSubLinks" style="display: none;">
                        <a href="<?php echo $base_resources; ?>" class="sub-link-item"><i class="fa-solid fa-book-open"></i> <?php echo __('Resource Library'); ?></a>
                        <a href="<?php echo $base_community; ?>" class="sub-link-item"><i class="fa-solid fa-comments"></i> <?php echo __('Q & A'); ?></a>
                        <a href="<?php echo $base_groups; ?>" class="sub-link-item"><i class="fa-solid fa-people-group"></i> <?php echo __('Group Therapy'); ?></a>
                    </div>
                <?php else: ?>
                    <!-- Client / Volunteer: Expose links directly as top-level rows -->
                    <a href="<?php echo $base_resources; ?>" class="menu-overlay-row">
                        <div class="row-left">
                            <i class="fa-solid fa-book-open row-icon"></i>
                            <span class="row-label"><?php echo __('Resource Library'); ?></span>
                        </div>
                        <i class="fa-solid fa-chevron-right menu-chevron"></i>
                    </a>
                    <a href="<?php echo $base_community; ?>" class="menu-overlay-row">
                        <div class="row-left">
                            <i class="fa-solid fa-comments row-icon"></i>
                            <span class="row-label"><?php echo __('Q & A'); ?></span>
                        </div>
                        <i class="fa-solid fa-chevron-right menu-chevron"></i>
                    </a>
                    <a href="<?php echo $base_groups; ?>" class="menu-overlay-row">
                        <div class="row-left">
                            <i class="fa-solid fa-people-group row-icon"></i>
                            <span class="row-label"><?php echo __('Group Therapy'); ?></span>
                        </div>
                        <i class="fa-solid fa-chevron-right menu-chevron"></i>
                    </a>
                <?php endif; ?>

                <!-- 4. Logout Row -->
                <a href="<?php echo $root; ?>api/auth/logout.php" class="menu-overlay-row logout-row">
                    <div class="row-left">
                        <i class="fa-solid fa-right-from-bracket row-icon"></i>
                        <span class="row-label"><?php echo __('Logout'); ?></span>
                    </div>
                    <i class="fa-solid fa-chevron-right menu-chevron"></i>
                </a>

                <!-- 5. Language Row -->
                <div class="menu-overlay-row language-row">
                    <div class="row-left">
                        <i class="fa-solid fa-globe row-icon"></i>
                        <span class="row-label"><?php echo __('Language'); ?></span>
                    </div>
                    <div class="lang-selector-mobile">
                        <select id="mobileLangSelect" class="mobile-lang-dropdown">
                            <option value="en" <?php echo $lang === 'en' ? 'selected' : ''; ?>>EN</option>
                            <option value="ar" <?php echo $lang === 'ar' ? 'selected' : ''; ?>>AR</option>
                        </select>
                        <i class="fa-solid fa-chevron-down select-caret"></i>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Client Mobile Interactive Script handlers -->
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // Toggle 'More' section links inside overlay
        const moreToggle = document.getElementById('menuMoreRowToggle');
        const moreSubLinks = document.getElementById('menuMoreSubLinks');
        const moreChevron = document.getElementById('moreChevron');
        
        if (moreToggle && moreSubLinks) {
            moreToggle.addEventListener('click', () => {
                const isVisible = moreSubLinks.style.display === 'block' || moreSubLinks.style.display === 'flex';
                moreSubLinks.style.display = isVisible ? 'none' : 'flex';
                if (moreChevron) {
                    const rot = document.documentElement.getAttribute('dir') === 'rtl' ? '180deg' : '0deg';
                    moreChevron.style.transform = isVisible ? `rotate(${rot})` : 'rotate(90deg)';
                }
            });
        }

        // Language select reload handler
        const mobileLangSelect = document.getElementById('mobileLangSelect');
        if (mobileLangSelect) {
            mobileLangSelect.addEventListener('change', function() {
                const selectedLang = this.value;
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('lang', selectedLang);
                window.location.href = currentUrl.toString();
            });
        }
    });
    </script>
    <?php endif; ?>