<?php
// index.php - High-End Immersive Anonymous Therapy Homepage
$session_role = strtolower($_SESSION['role'] ?? 'client');
$body_class = 'role-' . $session_role;
require_once 'includes/header.php';
require_once 'includes/db_connect.php';

$is_logged_in = isset($_SESSION['user_id']);
$home_cta_link = $is_logged_in ? $dashboard_link : 'auth_handler.php?action=role_selection';
?>

<!-- Premium UI Global Adjustments -->
<style>
    /* ── GLOBAL LOADER STYLES ── */
    #global-loader {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        z-index: 99999;
        background-color: #F8F5F2;
        background-image: url('assets/images/loading_bg.png');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        opacity: 1;
        transition: opacity 0.8s ease, transform 0.8s ease;
    }
    
    .loader-content {
        text-align: center;
        margin-bottom: 20px;
    }
    
    .loader-logo {
        width: 60px;
        height: 60px;
        margin: 0 auto 20px;
        color: #8B5A2B;
        animation: breathe 3s ease-in-out infinite;
    }
    
    @keyframes breathe {
        0%, 100% { transform: scale(1); opacity: 0.9; }
        50% { transform: scale(1.1); opacity: 1; }
    }
    
    .loader-title {
        font-family: 'Lora', serif;
        font-size: 2.8rem;
        color: #4A3B32;
        margin-bottom: 10px;
        font-weight: 500;
    }
    
    .loader-subtitle {
        font-family: 'Outfit', sans-serif;
        color: #6D5E55;
        font-size: 1.1rem;
        letter-spacing: 0.5px;
    }
    
    .loader-bottom {
        position: absolute;
        bottom: 50px;
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .loader-status {
        color: #6D5E55;
        font-family: 'Outfit', sans-serif;
        font-size: 1.05rem;
        margin-bottom: 15px;
    }
    
    .loader-progress-bar {
        width: 280px;
        height: 10px;
        background-color: #E6DCD3;
        border-radius: 20px;
        overflow: hidden;
        margin-bottom: 25px;
    }
    
    .loader-progress-fill {
        width: 0%;
        height: 100%;
        background: linear-gradient(90deg, #A8784F, #7C5A3A);
        border-radius: 20px;
        animation: loadProgress 2.5s cubic-bezier(0.4, 0, 0.2, 1) forwards;
    }
    
    @keyframes loadProgress {
        0% { width: 0%; }
        40% { width: 45%; }
        80% { width: 85%; }
        100% { width: 100%; }
    }
    
    .loader-privacy {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #8B7360;
        font-size: 0.9rem;
        font-family: 'Outfit', sans-serif;
    }
    
    .loader-privacy i {
        color: #A8784F;
    }
</style>

<div id="global-loader">
    <div class="loader-content">
        <div class="loader-logo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22c-3-3-6-8-6-12 0-3 2-5 5-5s5 2 5 5c0 4-3 9-6 12z"></path>
                <path d="M12 22V10"></path>
                <path d="M12 16c3-1 5-3 5-6"></path>
                <path d="M12 18c-2-1-4-2-4-5"></path>
            </svg>
        </div>
        <h1 class="loader-title">Safe Haven</h1>
        <p class="loader-subtitle">Anonymous support. Real healing.</p>
    </div>
    
    <div class="loader-bottom">
        <p class="loader-status">Creating a safe space for you...</p>
        <div class="loader-progress-bar">
            <div class="loader-progress-fill"></div>
        </div>
        <div class="loader-privacy">
            <i class="fa-solid fa-heart"></i> Your privacy is always protected
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const loader = document.getElementById('global-loader');
    
    // Check if this is a reload or first visit
    const navEntries = performance.getEntriesByType("navigation");
    const isReload = navEntries.length > 0 && navEntries[0].type === "reload";
    
    // Check if the user is coming from another page on our website
    const isInternalNavigation = document.referrer && document.referrer.indexOf(window.location.hostname) !== -1;
    
    // Show loader on refresh OR if coming from outside (external / first visit)
    if (isReload || !isInternalNavigation) {
        // Keep it visible, start sequence
        // After 2.5 seconds, fade it out
        setTimeout(() => {
            loader.style.opacity = '0';
            loader.style.transform = 'scale(1.02)';
            setTimeout(() => {
                loader.style.display = 'none';
                document.body.style.overflow = ''; // clear any inline overflow styles
            }, 800); // Wait for transition
        }, 2500);
    } else {
        // Hide immediately if navigating internally (e.g. clicking "Home" from navbar)
        loader.style.display = 'none';
    }
});
</script>
<style>
    :root {
        --font-serif: "Lora", serif;
        --font-sans: "Outfit", sans-serif;
        --bronze: #7C5A3A;
        --bronze-light: #A8784F;
        --sand: #EDC9AF;
        --deep-brown: #3D2B1F;
    }
    
    @import url('https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&family=Outfit:wght@100..900&display=swap');

    html, body {
        overflow-x: clip;
    }
    
    .premium-home-wrapper {
        background-color: #FAF9F6;
        color: #3D3530;
        line-height: 1.6;
        font-family: var(--font-sans);
    }

    .container { max-width: 1200px; margin: 0 auto; padding: 0 24px; position: relative; z-index: 10; }
    .section-padding { padding: 120px 0; }

    .btn-premium {
        display: inline-flex; align-items: center; justify-content: center;
        padding: 18px 45px; border-radius: 50px; font-weight: 700;
        text-decoration: none; transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        font-size: 1.05rem; cursor: pointer; border: none;
    }
    .btn-premium--primary {
        background: linear-gradient(135deg, var(--bronze), var(--bronze-light));
        color: white !important; box-shadow: 0 10px 30px rgba(124, 90, 58, 0.25);
    }
    .btn-premium--primary:hover { transform: translateY(-4px); box-shadow: 0 15px 40px rgba(124, 90, 58, 0.35); }
    
    .btn-premium--outline {
        border: 2px solid white; color: white !important;
        background: transparent;
    }
    .btn-premium--outline:hover { background: rgba(255,255,255,0.1); }

    /* ── HEADER OVERRIDE FOR HOME ── */
    body[data-role="client"] header, body.theme-client header, body header {
        background-color: transparent !important;
        border-bottom: none !important;
        backdrop-filter: none !important;
        box-shadow: none !important;
    }
    body[data-role="client"] header.scrolled, body.theme-client header.scrolled, body header.scrolled {
        background-color: rgba(244, 235, 225, 0.95) !important;
        backdrop-filter: blur(10px) !important;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05) !important;
    }

    .premium-home-wrapper {
        background-color: #F4EBE1; /* WARM BEIGE MATCHING PHOTO */
        color: #333;
        line-height: 1.6;
        font-family: var(--font-sans);
    }
    
    /* ── NEW HERO SECTION ── */
    .hero-new {
        position: relative;
        padding: 70px 0 100px;
        min-height: 85vh;
        display: flex;
        align-items: center;
        background: #F4EBE1;
    }
    .hero-new__container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 40px;
        position: relative;
    }
    .hero-new__content {
        flex: 1;
        max-width: 580px;
        position: relative;
        z-index: 2;
    }
    .hero-new__tag {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 255, 255, 0.6);
        padding: 6px 16px;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 600;
        color: #555;
        margin-bottom: 24px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.02);
    }
    .hero-new__title {
        font-family: var(--font-serif);
        font-size: 4.2rem;
        font-weight: 400;
        line-height: 1.1;
        color: #333;
        margin-bottom: 20px;
    }
    .hero-new__highlight {
        color: #8D6E63;
    }
    .hero-new__subtitle {
        font-size: 1.15rem;
        color: #555;
        margin-bottom: 35px;
        line-height: 1.5;
    }
    .hero-new__actions {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 40px;
    }
    .btn-new {
        padding: 15px 32px;
        border-radius: 50px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        font-size: 1rem;
    }
    .btn-new--primary {
        background: #8D6E63;
        color: #fff !important;
        box-shadow: 0 8px 20px rgba(141, 110, 99, 0.3);
    }
    .btn-new--primary:hover {
        background: #7A5B50;
        transform: translateY(-2px);
        box-shadow: 0 12px 25px rgba(141, 110, 99, 0.4);
    }
    .btn-new--secondary {
        background: transparent;
        color: #333 !important;
        border: 1px solid #ccc;
    }
    .btn-new--secondary:hover {
        background: rgba(0,0,0,0.03);
    }
    .hero-new__social {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .hero-new__avatars {
        display: flex;
    }
    .hero-new__avatars img {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: 2px solid #F4EBE1;
        margin-left: -12px;
        object-fit: cover;
    }
    .hero-new__avatars img:first-child { margin-left: 0; }
    .hero-new__social-text {
        font-size: 0.85rem;
        color: #666;
        line-height: 1.3;
    }
    .hero-new__image-wrapper {
        flex: 1;
        position: relative;
        display: flex;
        justify-content: flex-end;
    }
    .hero-new__image {
        max-width: 110%;
        width: 110%;
        height: auto;
        object-fit: contain;
    }
    .hero-new__floating-bar {
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(12px);
        border-radius: 30px;
        padding: 24px 16px;
        display: flex;
        flex-direction: column;
        gap: 30px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.05);
        border: 1px solid rgba(255,255,255,0.5);
    }
    .floating-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        font-size: 0.75rem;
        color: #555;
        font-weight: 500;
    }
    .floating-item i {
        font-size: 1.3rem;
        color: #8D6E63;
    }
    
    /* ── FEATURE BANNER ── */
    .feature-banner-wrapper {
        padding: 0 24px;
        margin-top: -60px; /* Pull up */
        position: relative;
        z-index: 10;
    }
    .feature-banner {
        background: rgba(238, 230, 220, 0.6);
        backdrop-filter: blur(10px);
        margin: 0 auto;
        max-width: 1150px;
        border-radius: 20px;
        padding: 35px 40px;
        display: flex;
        justify-content: space-between;
        gap: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.02);
    }
    .feature-item {
        display: flex;
        align-items: center;
        gap: 16px;
        flex: 1;
    }
    .feature-item:not(:last-child) {
        border-right: 1px solid rgba(0,0,0,0.06);
    }
    .feature-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: rgba(141, 110, 99, 0.08);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #8D6E63;
        font-size: 1.3rem;
        flex-shrink: 0;
    }
    .feature-text h4 {
        font-family: var(--font-sans);
        font-size: 0.95rem;
        margin: 0 0 4px;
        color: #333;
        font-weight: 600;
    }
    .feature-text p {
        font-size: 0.8rem;
        color: #666;
        margin: 0;
        line-height: 1.4;
    }
    
    /* ── NEW ABOUT US SECTION ── */
    .about-new {
        padding: 100px 0;
        background: #F4EBE1;
        position: relative;
        overflow: hidden;
    }
    .about-new__container {
        display: flex;
        align-items: center;
        gap: 60px;
        max-width: 1100px;
        margin: 0 auto;
    }
    .about-new__content {
        flex: 1;
    }
    .about-new__tag {
        font-size: 0.75rem;
        letter-spacing: 1.5px;
        color: #8D6E63;
        text-transform: uppercase;
        margin-bottom: 16px;
        font-weight: 700;
    }
    .about-new__title {
        font-family: var(--font-serif);
        font-size: 3rem;
        color: #333;
        margin-bottom: 20px;
        font-weight: 400;
    }
    .about-new__divider {
        width: 40px;
        height: 2px;
        background: #333;
        margin-bottom: 24px;
    }
    .about-new__text {
        font-size: 1rem;
        color: #555;
        margin-bottom: 35px;
        line-height: 1.7;
    }
    .about-new__image-wrapper {
        flex: 1;
        position: relative;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .about-new__image {
        max-width: 110%;
        height: auto;
        transform: scale(1.1);
    }
    .about-new__control-box {
        position: absolute;
        right: 0;
        top: 20%;
        background: rgba(245,237,228,0.9);
        backdrop-filter: blur(10px);
        padding: 30px;
        border-radius: 20px;
        box-shadow: 0 15px 40px rgba(0,0,0,0.05);
        max-width: 300px;
        border: 1px solid rgba(255,255,255,0.4);
    }
    .control-box__header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 24px;
    }
    .control-box__icon {
        width: 45px;
        height: 45px;
        background: #8D6E63;
        color: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }
    .control-box__header h3 {
        margin: 0;
        font-family: var(--font-serif);
        font-size: 1.25rem;
        color: #333;
    }
    .control-box__list {
        list-style: none;
        padding: 0;
        margin: 0 0 24px;
    }
    .control-box__list li {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 0.95rem;
        color: #444;
        margin-bottom: 15px;
    }
    .control-box__list li i {
        color: #8D6E63;
        font-size: 0.85rem;
    }
    
    .about-new__bottom-wave {
        position: absolute;
        bottom: -2px;
        left: 0;
        width: 100%;
        overflow: hidden;
        line-height: 0;
    }
    .about-new__bottom-wave svg {
        position: relative;
        display: block;
        width: calc(100% + 1.3px);
        height: 60px;
    }
    .about-new__bottom-wave .shape-fill {
        fill: #2a221d; /* Transition to Privacy section */
    }

    /* ── SECTION 3: PRIVACY WALL ── */
    .privacy {
        position: relative; background: #2a221d; color: white;
        text-align: center; overflow: hidden;
    }
    #privacyCanvas { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1; opacity: 0.6; }
    .privacy__content { max-width: 900px; margin: 0 auto; position: relative; z-index: 2; }
    .privacy__tag { display: inline-block; padding: 10px 24px; background: rgba(255,255,255,0.1); border-radius: 50px; font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 32px; }
    .privacy__headline { font-family: var(--font-serif); font-size: 4rem; margin-bottom: 80px; line-height: 1.2; color: white !important; }
    .privacy__grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 48px; text-align: left; }
    .privacy-item__num { font-size: 2rem; font-weight: 800; color: var(--sand) !important; margin-bottom: 16px; display: block; }
    .privacy-item__title { font-size: 1.4rem; font-weight: 700; margin-bottom: 16px; color: white !important; }
    .privacy-item__text { font-size: 1rem; color: rgba(255,255,255,0.85) !important; line-height: 1.7; }

    /* ── HOW IT WORKS (NEW) ── */
    .how-it-works-new {
        background: #F4EBE1;
        padding: 100px 0;
        text-align: center;
    }
    .how-it-works-new__header { margin-bottom: 60px; }
    .how-it-works-new__tag {
        font-size: 0.8rem; letter-spacing: 2px; color: #8D6E63;
        text-transform: uppercase; font-weight: 700; display: block; margin-bottom: 15px;
    }
    .how-it-works-new__title { font-family: var(--font-serif); font-size: 3rem; color: #333; font-weight: 400; }
    .how-it-works-new__grid {
        display: flex; align-items: center; justify-content: space-between;
        gap: 30px; max-width: 1100px; margin: 0 auto;
    }
    .hiw-step { display: flex; align-items: flex-start; gap: 20px; text-align: left; flex: 1; }
    .hiw-step__icon {
        width: 80px; height: 80px; border-radius: 50%; background: rgba(141, 110, 99, 0.1);
        display: flex; align-items: center; justify-content: center; font-size: 2rem;
        color: #66544A; flex-shrink: 0; border: 1px solid rgba(141, 110, 99, 0.2);
    }
    .hiw-step__content { display: flex; flex-direction: column; }
    .hiw-step__num { font-size: 0.95rem; font-weight: 700; color: #333; margin-bottom: 5px; }
    .hiw-step__name { font-family: var(--font-sans); font-size: 1.1rem; font-weight: 600; color: #333; margin: 0 0 10px; }
    .hiw-step__desc { font-size: 0.9rem; color: #666; line-height: 1.5; margin: 0; }
    .hiw-divider { width: 1px; height: 60px; border-left: 1px dashed rgba(0,0,0,0.15); }

    /* ── AREAS WE SUPPORT ── */
    .areas-we-support {
        background: #EBE0D4; display: flex; border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    .areas-container { display: flex; width: 100%; max-width: 1440px; margin: 0 auto; }
    .areas-content { flex: 1; padding: 100px 60px 100px 80px; display: flex; flex-direction: column; justify-content: center; }
    .areas__tag { font-size: 0.8rem; letter-spacing: 2px; color: #8D6E63; text-transform: uppercase; font-weight: 700; display: block; margin-bottom: 20px; }
    .areas__title { font-family: var(--font-serif); font-size: 2.8rem; color: #333; font-weight: 400; line-height: 1.2; margin-bottom: 50px; max-width: 600px; }
    .areas__grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 20px; text-align: center; }
    .area-item { display: flex; flex-direction: column; align-items: center; gap: 15px; }
    .area-icon { font-size: 2.2rem; color: #8D6E63; }
    .area-item span { font-size: 0.85rem; font-weight: 600; color: #444; }
    .areas-image-wrapper { flex: 1; position: relative; }
    .areas-image { width: 100%; height: 100%; object-fit: cover; }

    /* Remove original canvas classes */
    #heroCanvas, .hero__spline { display: none !important; }

    @media (max-width: 1024px) {
        .hero-new__container, .about-new__container { flex-direction: column; text-align: center; }
        .hero-new__content, .about-new__content { max-width: 100%; }
        .hero-new__actions { justify-content: center; }
        .hero-new__social { justify-content: center; }
        .hero-new__floating-bar { display: none; }
        .about-new__divider { margin: 0 auto 24px; }
        .about-new__image-wrapper {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
            gap: 30px;
            width: 100%;
            margin-top: 40px;
        }
        .about-new__image {
            max-width: 280px;
            height: auto;
            transform: none;
        }
        .about-new__control-box {
            position: static;
            max-width: 320px;
            margin: 0;
            text-align: left;
            transform: none;
        }
        .feature-banner { flex-direction: column; gap: 30px; }
        .feature-item:not(:last-child) { border-right: none; border-bottom: 1px solid rgba(0,0,0,0.06); padding-bottom: 20px; }
        .privacy__grid { grid-template-columns: 1fr; }
        .hero-new__title { font-size: 3rem; }
        .about-new__title { font-size: 2.5rem; }
        
        .how-it-works-new__grid { flex-direction: column; gap: 40px; }
        .hiw-step { flex-direction: column; align-items: center; text-align: center; }
        .hiw-divider { width: 60px; height: 1px; border-top: 1px dashed rgba(0,0,0,0.15); border-left: none; }
        .areas-container { flex-direction: column; }
        .areas-content { padding: 60px 20px; text-align: center; }
        .areas__title { font-size: 2.4rem; margin: 0 auto 40px; }
        .areas__grid { grid-template-columns: repeat(3, 1fr); gap: 30px; }
        .areas-image-wrapper { min-height: 400px; }
    }
    @media (max-width: 768px) {
        .areas__grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 600px) {
        .about-new__image-wrapper {
            flex-direction: column !important;
            gap: 24px !important;
        }
        .about-new__image {
            max-width: 220px !important;
        }
        .about-new__control-box {
            max-width: 100% !important;
            width: 100% !important;
        }
    }

    /* ══ TASK 3: Therapist Blue Theme — Home Page Overrides ══ */
    body[data-role="therapist"] {
        --bronze: #337AB7;
        --bronze-light: #4A90D9;
        --sand: #B8D4EF;
        --deep-brown: #1A3A5C;
    }
    body[data-role="therapist"] .premium-home-wrapper { background-color: #F4F9FD; color: #1A4D80; }
    body[data-role="therapist"] .hero-new { background: #F4F9FD; }
    body[data-role="therapist"] .about-new { background: #EBF4FB; }
    body[data-role="therapist"] .about-new__bottom-wave .shape-fill { fill: #12304D; }
    body[data-role="therapist"] .how-it-works-new { background: #F4F9FD; }
    body[data-role="therapist"] .areas-we-support { background: #EBF4FB; }
    body[data-role="therapist"] .hero { background: radial-gradient(circle at 20% 50%, #EBF4FB 0%, #7EB5D6 40%, #1A3A5C 100%); }
    body[data-role="therapist"] .btn-premium--primary { background: linear-gradient(135deg, #337AB7, #4A90D9); box-shadow: 0 10px 30px rgba(51, 122, 183, 0.3); }
    body[data-role="therapist"] .btn-premium--primary:hover { box-shadow: 0 15px 40px rgba(51, 122, 183, 0.4); }
    body[data-role="therapist"] .service-card { background: #EBF4FB; border-color: rgba(51, 122, 183, 0.15); }
    body[data-role="therapist"] .service-card:hover { border-color: #B8D4EF; }
    body[data-role="therapist"] .service-card__icon { color: #337AB7; }
    body[data-role="therapist"] .privacy { background: #12304D; }
    body[data-role="therapist"] .privacy-item__num { color: #B8D4EF !important; }

    /* New buttons & elements for therapist theme */
    body[data-role="therapist"] header { background-color: #F4F9FD !important; }
    body[data-role="therapist"] header.scrolled { background-color: rgba(244, 249, 253, 0.95) !important; }
    body[data-role="therapist"] .about-new__control-box { background: rgba(235, 244, 251, 0.9); border-color: rgba(51, 122, 183, 0.3); }
    body[data-role="therapist"] .btn-new--primary { background: #337AB7; color: white; border-color: #337AB7; }
    body[data-role="therapist"] .btn-new--primary:hover { background: #286090; border-color: #286090; color: white; }
    body[data-role="therapist"] .hero-new__tag { color: #337AB7; }
    body[data-role="therapist"] .hero-new__tag i { color: #337AB7 !important; }
    body[data-role="therapist"] .hero-new__highlight { color: #337AB7; }
    body[data-role="therapist"] .floating-item i,
    body[data-role="therapist"] .feature-icon,
    body[data-role="therapist"] .control-box__icon,
    body[data-role="therapist"] .control-box__list li i,
    body[data-role="therapist"] .hiw-step__icon,
    body[data-role="therapist"] .area-icon { color: #337AB7; }
    body[data-role="therapist"] .feature-icon,
    body[data-role="therapist"] .hiw-step__icon,
    body[data-role="therapist"] .control-box__icon { background: rgba(51, 122, 183, 0.1); border-color: rgba(51, 122, 183, 0.2); }
    body[data-role="therapist"] .about-new__tag,
    body[data-role="therapist"] .how-it-works-new__tag,
    body[data-role="therapist"] .areas__tag { color: #337AB7; }
    body[data-role="therapist"] .feature-banner { background: rgba(235, 244, 251, 0.8); }

    /* ══ Volunteer Green Theme — Home Page Overrides ══ */
    body[data-role="volunteer"] {
        --bronze: #2D6A4F;
        --bronze-light: #40916C;
        --sand: #D8F3DC;
        --deep-brown: #1B4332;
    }
    body[data-role="volunteer"] .premium-home-wrapper { background-color: #EAF2EC; color: #1B4332; }
    body[data-role="volunteer"] .hero-new { background: #EAF2EC; }
    body[data-role="volunteer"] .about-new { background: #F4F9F5; }
    body[data-role="volunteer"] .about-new__bottom-wave .shape-fill { fill: #1B4332; }
    body[data-role="volunteer"] .how-it-works-new { background: #EAF2EC; }
    body[data-role="volunteer"] .areas-we-support { background: #F4F9F5; }
    body[data-role="volunteer"] .hero { background: radial-gradient(circle at 20% 50%, #EAF2EC 0%, #95D5B2 40%, #1B4332 100%); }
    body[data-role="volunteer"] .btn-premium--primary { background: linear-gradient(135deg, #2D6A4F, #40916C); box-shadow: 0 10px 30px rgba(45, 106, 79, 0.3); }
    body[data-role="volunteer"] .btn-premium--primary:hover { box-shadow: 0 15px 40px rgba(45, 106, 79, 0.4); }
    body[data-role="volunteer"] .service-card { background: #EAF2EC; border-color: rgba(45, 106, 79, 0.15); }
    body[data-role="volunteer"] .service-card:hover { border-color: #D8F3DC; }
    body[data-role="volunteer"] .service-card__icon { color: #2D6A4F; }
    body[data-role="volunteer"] .privacy { background: #1B4332; }
    body[data-role="volunteer"] .privacy-item__num { color: #D8F3DC !important; }

    /* New buttons & elements for volunteer theme */
    body[data-role="volunteer"] header { background-color: rgba(240, 247, 244, 0.85) !important; }
    body[data-role="volunteer"] header.scrolled { background-color: rgba(240, 247, 244, 0.95) !important; }
    body[data-role="volunteer"] .about-new__control-box { background: rgba(244, 249, 245, 0.9); border-color: rgba(45, 106, 79, 0.3); }
    body[data-role="volunteer"] .btn-new--primary { background: #2D6A4F; color: white; border-color: #2D6A4F; }
    body[data-role="volunteer"] .btn-new--primary:hover { background: #1B4332; border-color: #1B4332; color: white; }
    body[data-role="volunteer"] .hero-new__tag { color: #2D6A4F; }
    body[data-role="volunteer"] .hero-new__tag i { color: #2D6A4F !important; }
    body[data-role="volunteer"] .hero-new__highlight { color: #2D6A4F; }
    body[data-role="volunteer"] .floating-item i,
    body[data-role="volunteer"] .feature-icon,
    body[data-role="volunteer"] .control-box__icon,
    body[data-role="volunteer"] .control-box__list li i,
    body[data-role="volunteer"] .hiw-step__icon,
    body[data-role="volunteer"] .area-icon { color: #2D6A4F; }
    body[data-role="volunteer"] .feature-icon,
    body[data-role="volunteer"] .hiw-step__icon,
    body[data-role="volunteer"] .control-box__icon { background: rgba(45, 106, 79, 0.1); border-color: rgba(45, 106, 79, 0.2); }
    body[data-role="volunteer"] .about-new__tag,
    body[data-role="volunteer"] .how-it-works-new__tag,
    body[data-role="volunteer"] .areas__tag { color: #2D6A4F; }
    body[data-role="volunteer"] .feature-banner { background: rgba(244, 249, 245, 0.8); }
</style>

<div class="premium-home-wrapper">
    <!-- SECTION 1: HERO -->
    <section class="hero-new">
        <div class="container hero-new__container">
            <div class="hero-new__content">
                <div class="hero-new__tag">
                    <i class="fa-solid fa-heart" style="color: #A68A6C;"></i> <?php echo __("You're not alone"); ?>
                </div>
                <h1 class="hero-new__title"><?php echo __('A safe space to heal,'); ?><br><?php echo __('grow, and feel'); ?> <span class="hero-new__highlight"><?php echo __('heard.'); ?></span></h1>
                <p class="hero-new__subtitle"><?php echo __('Anonymous therapy with real support,'); ?><br><?php echo __('whenever you need it.'); ?></p>
                <div class="hero-new__actions">
                    <a href="<?php echo $home_cta_link; ?>" class="btn-new btn-new--primary"><?php echo __('Start Your Journey'); ?></a>
                    <a href="#how-it-works" class="btn-new btn-new--secondary"><i class="fa-solid fa-play"></i> <?php echo __('How It Works'); ?></a>
                </div>
            </div>
            <div class="hero-new__image-wrapper">
                <img src="assets/images/home page images/1.png" alt="Safe space" class="hero-new__image">
                <div class="hero-new__floating-bar">
                    <div class="floating-item">
                        <i class="fa-solid fa-lock"></i>
                        <span><?php echo __('Private'); ?></span>
                    </div>
                    <div class="floating-item">
                        <i class="fa-regular fa-comment"></i>
                        <span><?php echo __('Anonymous'); ?></span>
                    </div>
                    <div class="floating-item">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span><?php echo __('Secure'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURE BANNER -->
    <div class="feature-banner-wrapper">
        <div class="feature-banner">
            <div class="feature-item">
                <div class="feature-icon"><i class="fa-regular fa-user"></i></div>
                <div class="feature-text">
                    <h4><?php echo __('100% Anonymous'); ?></h4>
                    <p><?php echo __('Your privacy is our'); ?><br><?php echo __('highest priority.'); ?></p>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class="fa-regular fa-comment-dots"></i></div>
                <div class="feature-text">
                    <h4><?php echo __('Professional Support'); ?></h4>
                    <p><?php echo __('Licensed therapists'); ?><br><?php echo __('who truly listen.'); ?></p>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class="fa-regular fa-calendar"></i></div>
                <div class="feature-text">
                    <h4><?php echo __('Flexible & Convenient'); ?></h4>
                    <p><?php echo __('Talk whenever and'); ?><br><?php echo __('wherever you feel safe.'); ?></p>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class="fa-regular fa-heart"></i></div>
                <div class="feature-text">
                    <h4><?php echo __('For Everyone'); ?></h4>
                    <p><?php echo __('Support for any challenge'); ?><br><?php echo __("you're facing."); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- ABOUT US SECTION -->
    <section class="about-new">
        <div class="container about-new__container">
            <div class="about-new__content">
                <div class="about-new__tag"><?php echo __('ABOUT US'); ?></div>
                <h2 class="about-new__title"><?php echo __('Therapy, on your terms.'); ?></h2>
                <div class="about-new__divider"></div>
                <p class="about-new__text"><?php echo __('At Anonymous Therapy, we believe everyone deserves a space to heal, grow, and feel heard—without fear or judgment. Our platform connects you with compassionate therapists in a completely secure and anonymous way.'); ?></p>
                <a href="about.php" class="btn-new btn-new--primary"><?php echo __('Learn More About Us'); ?></a>
            </div>
            <div class="about-new__image-wrapper">
                <img src="assets/images/home page images/2.png" alt="Relaxed user" class="about-new__image">
                <div class="about-new__control-box">
                    <div class="control-box__header">
                        <div class="control-box__icon"><i class="fa-solid fa-heart"></i></div>
                        <h3><?php echo __("You're in control."); ?></h3>
                    </div>
                    <ul class="control-box__list">
                        <li><i class="fa-solid fa-check"></i> <?php echo __('Choose your therapist'); ?></li>
                        <li><i class="fa-solid fa-check"></i> <?php echo __('Chat, call, or message'); ?></li>
                        <li><i class="fa-solid fa-check"></i> <?php echo __('Cancel anytime'); ?></li>
                    </ul>
                    <a href="<?php echo $home_cta_link; ?>" class="btn-new btn-new--primary" style="width: 100%;"><?php echo __('Get Started Now'); ?></a>
                </div>
            </div>
        </div>
        <div class="about-new__bottom-wave">
            <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="shape-fill"></path>
            </svg>
        </div>
    </section>

    <section class="privacy section-padding">
        <canvas id="privacyCanvas"></canvas>
        <div class="container privacy__content">
            <span class="privacy__tag"><?php echo __('Safe & Private'); ?></span>
            <h2 class="privacy__headline"><?php echo __('Your Privacy is Our Priority.'); ?></h2>
            <div class="privacy__grid">
                <div class="privacy-item"><span class="privacy-item__num">01</span><h4 class="privacy-item__title"><?php echo __('No Names Needed'); ?></h4><p class="privacy-item__text"><?php echo __("Use a nickname, not your real name. No ID required."); ?></p></div>
                <div class="privacy-item"><span class="privacy-item__num">02</span><h4 class="privacy-item__title"><?php echo __('Total Security'); ?></h4><p class="privacy-item__text"><?php echo __("Nobody can read your messages or listen to your calls."); ?></p></div>
                <div class="privacy-item"><span class="privacy-item__num">03</span><h4 class="privacy-item__title"><?php echo __('Automatic Deleting'); ?></h4><p class="privacy-item__text"><?php echo __("Messages are deleted as soon as your session ends."); ?></p></div>
            </div>
        </div>
    </section>

    <!-- HOW IT WORKS SECTION -->
    <section id="how-it-works" class="how-it-works-new">
        <div class="container">
            <div class="how-it-works-new__header">
                <span class="how-it-works-new__tag"><?php echo __('HOW IT WORKS'); ?></span>
                <h2 class="how-it-works-new__title"><?php echo __('Simple. Private. Effective.'); ?></h2>
            </div>
            <div class="how-it-works-new__grid">
                <div class="hiw-step">
                    <div class="hiw-step__icon"><i class="fa-regular fa-user"></i></div>
                    <div class="hiw-step__content">
                        <span class="hiw-step__num">01</span>
                        <h4 class="hiw-step__name"><?php echo __('Create Your Account'); ?></h4>
                        <p class="hiw-step__desc"><?php echo __('Sign up anonymously in just a few minutes.'); ?></p>
                    </div>
                </div>
                <div class="hiw-divider"></div>
                <div class="hiw-step">
                    <div class="hiw-step__icon"><i class="fa-regular fa-comment"></i></div>
                    <div class="hiw-step__content">
                        <span class="hiw-step__num">02</span>
                        <h4 class="hiw-step__name"><?php echo __('Choose Your Therapist'); ?></h4>
                        <p class="hiw-step__desc"><?php echo __('Browse and connect with a therapist that fits you.'); ?></p>
                    </div>
                </div>
                <div class="hiw-divider"></div>
                <div class="hiw-step">
                    <div class="hiw-step__icon"><i class="fa-regular fa-heart"></i></div>
                    <div class="hiw-step__content">
                        <span class="hiw-step__num">03</span>
                        <h4 class="hiw-step__name"><?php echo __('Start Your Session'); ?></h4>
                        <p class="hiw-step__desc"><?php echo __('Chat, call, or message—however you\'re comfortable.'); ?></p>
                    </div>
                </div>
            </div>
            <div style="margin-top: 80px; text-align:center;">
                <a href="<?php echo $home_cta_link; ?>" class="btn-new btn-new--primary"><?php echo __('Start Feeling Better Now'); ?></a>
            </div>
        </div>
    </section>

    <!-- AREAS WE SUPPORT SECTION -->
    <section class="areas-we-support">
        <div class="areas-container">
            <div class="areas-content">
                <span class="areas__tag"><?php echo __('AREAS WE SUPPORT'); ?></span>
                <h2 class="areas__title"><?php echo __('Whatever you\'re going through, we\'re here for you.'); ?></h2>
                <div class="areas__grid">
                    <div class="area-item">
                        <div class="area-icon"><i class="fa-solid fa-brain"></i></div>
                        <span><?php echo __('Anxiety'); ?></span>
                    </div>
                    <div class="area-item">
                        <div class="area-icon"><i class="fa-solid fa-cloud-rain"></i></div>
                        <span><?php echo __('Depression'); ?></span>
                    </div>
                    <div class="area-item">
                        <div class="area-icon"><i class="fa-solid fa-leaf"></i></div>
                        <span><?php echo __('Stress'); ?></span>
                    </div>
                    <div class="area-item">
                        <div class="area-icon"><i class="fa-solid fa-user-group"></i></div>
                        <span><?php echo __('Relationships'); ?></span>
                    </div>
                    <div class="area-item">
                        <div class="area-icon"><i class="fa-regular fa-user"></i></div>
                        <span><?php echo __('Self-Esteem'); ?></span>
                    </div>
                    <div class="area-item">
                        <div class="area-icon"><i class="fa-solid fa-sun"></i></div>
                        <span><?php echo __('Life Changes'); ?></span>
                    </div>
                </div>
            </div>
            <div class="areas-image-wrapper">
                <img src="assets/images/home_support_candle.png" alt="Support" class="areas-image">
            </div>
        </div>
    </section>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const privacyCanvas = document.getElementById('privacyCanvas');
    if (!privacyCanvas) return;

    // Only load Three.js on desktop viewports (> 1024px) to save bandwidth and keep mobile performance fast
    if (window.innerWidth > 1024) {
        const script = document.createElement('script');
        script.src = "https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js";
        script.defer = true;
        script.onload = function() {
            initThreePrivacy(privacyCanvas);
        };
        document.body.appendChild(script);
    }
});

function initThreePrivacy(canvas) {
    if (!canvas || !window.THREE) return;
    try {
        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
        
        // Use antialias: false to save GPU rendering cycles on lower-end laptops
        const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: false });
        renderer.setSize(window.innerWidth, window.innerHeight);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.5)); // cap pixel ratio to 1.5 for performance

        const particles = new THREE.BufferGeometry();
        const count = 3000; // slightly reduced count for speed
        const posArray = new Float32Array(count * 3);
        
        for (let i = 0; i < count * 3; i++) {
            posArray[i] = (Math.random() - 0.5) * 10;
        }
        
        particles.setAttribute('position', new THREE.BufferAttribute(posArray, 3));
        const pMesh = new THREE.Points(
            particles, 
            new THREE.PointsMaterial({ 
                size: 0.006, 
                color: '#EDC9AF', 
                transparent: true, 
                opacity: 0.6 
            })
        );
        scene.add(pMesh);
        camera.position.z = 2;

        let animationFrameId;
        function animate() {
            animationFrameId = requestAnimationFrame(animate);
            pMesh.rotation.y += 0.0006;
            pMesh.rotation.x += 0.0002;
            renderer.render(scene, camera);
        }
        animate();

        // Handle resize
        const handleResize = () => {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        };
        window.addEventListener('resize', handleResize, { passive: true });
    } catch (e) {
        console.warn('Three.js initialization failed:', e);
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>