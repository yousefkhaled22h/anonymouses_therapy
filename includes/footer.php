<?php
// includes/footer.php
?>
</main>

<footer class="minimal-footer">
    <div class="container">
        <div class="footer-grid-minimal">
            <!-- Brand Column -->
            <div>
                <div class="footer-brand-minimal">
                    <div class="footer-brand-img" style="margin-bottom: 10px;">
                        <img src="<?php echo $root; ?>assets/images/logo.png" alt="Safe Haven" style="height: 55px; width: auto; object-fit: contain;">
                    </div>
                </div>
                <p class="footer-desc">Professional, anonymous therapy services to support your mental well-being
                    journey.</p>
            </div>

            <!-- Quick Links -->
            <div>
                <h4 class="footer-col-title">Quick Links</h4>
                <ul class="footer-link-list">
                    <li><a href="<?php echo $root; ?>services.php">Services</a></li>
                    <li><a href="<?php echo $root; ?>about.php">About Us</a></li>
                    <li><a href="<?php echo $root; ?>contact.php">Contact</a></li>
                </ul>
            </div>

            <!-- Resources -->
            <div>
                <h4 class="footer-col-title">Resources</h4>
                <ul class="footer-link-list">
                    <li><a href="<?php echo $root; ?>privacy_policy.php">Privacy Policy</a></li>
                    <li><a href="<?php echo $root; ?>faq.php">FAQ</a></li>
                </ul>
            </div>

            <!-- Contact -->
            <div>
                <h4 class="footer-col-title">Contact</h4>
                <ul class="footer-contact-list">
                    <li><i class="fa-regular fa-envelope"></i> safehaven@gmail.com</li>
                    <li><i class="fa-solid fa-phone"></i> Available 24/7</li>
                    <li><i class="fa-solid fa-shield-halved"></i> Secure &amp; Anonymous</li>
                </ul>
            </div>
        </div>

        <div class="footer-divider"></div>

        <div class="footer-bottom-minimal">
            <div>
                &copy; <?php echo date("Y"); ?> Safe Haven. All rights reserved.
            </div>
            <div>
                Your privacy is our priority. All sessions are confidential.
            </div>
        </div>
    </div>
</footer>

<?php
$sess_role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : '';
if (isset($_SESSION['user_id']) && $sess_role === 'therapist'):
    $current_page = basename($_SERVER['PHP_SELF']);
    $current_view = $_GET['view'] ?? '';
    // Active states
    $is_home = ($current_page === 'therapist_dashboard.php' && ($current_view === 'home' || $current_view === ''));
    $is_availability = ($current_page === 'therapist_dashboard.php' && $current_view === 'availability');
    $is_qa = ($current_page === 'therapist_dashboard.php' && $current_view === 'community') || ($current_page === 'community.php');
    $is_profile = ($current_page === 'therapist_profile.php');
?>
<!-- Therapist Mobile Navigation Bar -->
<style>
/* Hide main global navbar for therapist on mobile */
@media (max-width: 767px) {
    body[data-role="therapist"] header#main-header {
        display: none !important;
    }
    
    /* Push content up to avoid being covered by fixed bottom navigation */
    body {
        padding-bottom: 70px !important;
    }

    .therapist-mobile-navbar {
        display: flex !important;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        height: 60px;
        background: #ffffff;
        border-top: 1px solid #e2e8f0;
        box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.08);
        z-index: 99999;
        justify-content: space-around;
        align-items: center;
        padding: 4px 0;
        direction: ltr; /* Layout always left-to-right */
    }
    
    html[dir="rtl"] .therapist-mobile-navbar {
        direction: rtl;
    }

    .therapist-mobile-nav-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #64748b !important;
        text-decoration: none !important;
        font-size: 0.75rem;
        font-weight: 700;
        gap: 3px;
        flex: 1;
        height: 100%;
        transition: color 0.15s ease;
    }

    .therapist-mobile-nav-item i {
        font-size: 1.25rem;
        color: #64748b;
        transition: color 0.15s ease;
    }

    .therapist-mobile-nav-item.active {
        color: #2563eb !important;
    }

    .therapist-mobile-nav-item.active i {
        color: #2563eb !important;
    }
}

.therapist-mobile-navbar {
    display: none;
}
</style>

<div class="therapist-mobile-navbar">
    <a href="<?php echo $root; ?>therapist_dashboard.php?view=home" class="therapist-mobile-nav-item <?php echo $is_home ? 'active' : ''; ?>">
        <i class="fas fa-home"></i>
        <span><?php echo __('Home'); ?></span>
    </a>
    <a href="<?php echo $root; ?>therapist_dashboard.php?view=availability" class="therapist-mobile-nav-item <?php echo $is_availability ? 'active' : ''; ?>">
        <i class="fas fa-clock"></i>
        <span><?php echo __('Availability'); ?></span>
    </a>
    <a href="<?php echo $root; ?>therapist_dashboard.php?view=community" class="therapist-mobile-nav-item <?php echo $is_qa ? 'active' : ''; ?>">
        <i class="fas fa-comments"></i>
        <span><?php echo __('Q&A'); ?></span>
    </a>
    <a href="<?php echo $root; ?>therapist_profile.php" class="therapist-mobile-nav-item <?php echo $is_profile ? 'active' : ''; ?>">
        <i class="fas fa-user-circle"></i>
        <span><?php echo __('Profile'); ?></span>
    </a>
</div>
<?php endif; ?>

    <!-- Floating Back-to-Top Action Button -->
    <button id="scrollToTopBtn" class="scroll-to-top-btn" aria-label="Scroll to top">
        <i class="fa-solid fa-arrow-up"></i>
    </button>

<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js" defer></script>
<script src="<?php echo $root; ?>assets/js/main.js" defer></script>
</body>

</html>