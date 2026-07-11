<?php
// privacy_policy.php
session_start();
$user_role = $_SESSION['role'] ?? 'Guest';

if (strtolower($user_role) === 'therapist') {
    $body_class = 'role-therapist';
} elseif (strtolower($user_role) === 'volunteer') {
    $body_class = 'role-volunteer';
} else {
    $body_class = 'role-client';
}

require_once 'includes/header.php';
?>

<style>
    .privacy-wrapper {
        background-color: #F4F1EA;
        padding: 80px 20px;
        min-height: calc(100vh - 80px);
        font-family: var(--font-body);
        transition: background-color 0.3s ease;
    }
    
    /* Role-based overrides */
    body.role-therapist .privacy-wrapper {
        background-color: #F4F9FD;
    }
    body.role-volunteer .privacy-wrapper {
        background-color: #F0F7F4;
    }
    
    .privacy-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .privacy-header {
        text-align: center;
        margin-bottom: 60px;
    }
    
    .privacy-header h1 {
        font-size: 3rem;
        font-weight: 800;
        color: #3D3530;
        margin-bottom: 18px;
        letter-spacing: -0.5px;
    }
    
    body.role-therapist .privacy-header h1 {
        color: #1A4D80;
    }
    body.role-volunteer .privacy-header h1 {
        color: #1B4332;
    }
    
    .privacy-header p {
        font-size: 1.2rem;
        color: #7D6B5E;
        max-width: 700px;
        margin: 0 auto;
        line-height: 1.6;
    }
    
    /* Layout grid */
    .privacy-grid {
        display: grid;
        grid-template-columns: 1fr 1.6fr;
        gap: 40px;
        align-items: start;
    }
    
    @media (max-width: 991px) {
        .privacy-grid {
            grid-template-columns: 1fr;
        }
    }
    
    /* Left column (Image card & highlights) */
    .privacy-sidebar {
        background: #ffffff;
        border-radius: 24px;
        border: 1px solid rgba(237, 201, 175, 0.4);
        box-shadow: 0 12px 40px rgba(124, 90, 58, 0.08);
        padding: 30px;
        overflow: hidden;
    }
    
    body.role-therapist .privacy-sidebar {
        border-color: rgba(51, 122, 183, 0.15);
        box-shadow: 0 12px 40px rgba(51, 122, 183, 0.08);
    }
    body.role-volunteer .privacy-sidebar {
        border-color: rgba(45, 106, 79, 0.15);
        box-shadow: 0 12px 40px rgba(45, 106, 79, 0.08);
    }
    
    .privacy-img-container {
        width: 100%;
        border-radius: 16px;
        overflow: hidden;
        margin-bottom: 24px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
    }
    
    .privacy-img-container img {
        width: 100%;
        height: auto;
        display: block;
        transition: transform 0.5s ease;
    }
    
    .privacy-img-container:hover img {
        transform: scale(1.03);
    }
    
    .highlights-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #7C5A3A;
        margin-bottom: 20px;
        border-bottom: 2px solid #F5F0E4;
        padding-bottom: 10px;
    }
    
    body.role-therapist .highlights-title {
        color: #337AB7;
        border-bottom-color: #E6F2FC;
    }
    body.role-volunteer .highlights-title {
        color: #2D6A4F;
        border-bottom-color: #EBF7F0;
    }
    
    .highlight-item {
        display: flex;
        gap: 16px;
        margin-bottom: 20px;
    }
    
    .highlight-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: #FDFBF7;
        color: #7C5A3A;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
        border: 1px solid #F5F0E4;
    }
    
    body.role-therapist .highlight-icon {
        background: #F4F9FD;
        color: #337AB7;
        border-color: #E6F2FC;
    }
    body.role-volunteer .highlight-icon {
        background: #F0F7F4;
        color: #2D6A4F;
        border-color: #EBF7F0;
    }
    
    .highlight-text h4 {
        font-size: 1rem;
        font-weight: 700;
        color: #3D3530;
        margin-bottom: 4px;
    }
    
    .highlight-text p {
        font-size: 0.88rem;
        color: #7D6B5E;
        line-height: 1.45;
        margin: 0;
    }
    
    /* Right column (Detailed content) */
    .privacy-content-card {
        background: #ffffff;
        border-radius: 24px;
        border: 1px solid rgba(237, 201, 175, 0.4);
        box-shadow: 0 12px 40px rgba(124, 90, 58, 0.08);
        padding: 40px;
    }
    
    body.role-therapist .privacy-content-card {
        border-color: rgba(51, 122, 183, 0.15);
        box-shadow: 0 12px 40px rgba(51, 122, 183, 0.08);
    }
    body.role-volunteer .privacy-content-card {
        border-color: rgba(45, 106, 79, 0.15);
        box-shadow: 0 12px 40px rgba(45, 106, 79, 0.08);
    }
    
    .policy-section {
        margin-bottom: 35px;
    }
    
    .policy-section:last-child {
        margin-bottom: 0;
    }
    
    .policy-section h2 {
        font-size: 1.45rem;
        font-weight: 700;
        color: #3D3530;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    body.role-therapist .policy-section h2 {
        color: #1A4D80;
    }
    body.role-volunteer .policy-section h2 {
        color: #1B4332;
    }
    
    .policy-section p {
        font-size: 1rem;
        color: #7D6B5E;
        line-height: 1.65;
        margin-bottom: 0;
    }
</style>

<div class="privacy-wrapper">
    <div class="privacy-container">
        <!-- Header -->
        <div class="privacy-header">
            <h1><?php echo __('Privacy Policy & Security'); ?></h1>
            <p><?php echo __('Your privacy and security are the core foundations of Safe Haven. We are committed to protecting your personal information and ensuring all interactions remain completely confidential.'); ?></p>
        </div>
        
        <!-- Main Grid -->
        <div class="privacy-grid">
            <!-- Sidebar -->
            <div class="privacy-sidebar">
                <div class="privacy-img-container">
                    <img src="assets/images/privacy_policy_banner.png" alt="<?php echo __('Privacy Banner'); ?>">
                </div>
                
                <h3 class="highlights-title"><?php echo __('Our Commitment to Your Privacy'); ?></h3>
                
                <div class="highlight-item">
                    <div class="highlight-icon">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                    <div class="highlight-text">
                        <h4><?php echo __('End-to-End Encryption'); ?></h4>
                        <p><?php echo __('All audio, video, and text sessions are encrypted to ensure zero interception.'); ?></p>
                    </div>
                </div>
                
                <div class="highlight-item">
                    <div class="highlight-icon">
                        <i class="fa-solid fa-user-secret"></i>
                    </div>
                    <div class="highlight-text">
                        <h4><?php echo __('Anonymity by Default'); ?></h4>
                        <p><?php echo __('You do not need to reveal your real identity to participate in group therapy or chat boards.'); ?></p>
                    </div>
                </div>
                
                <div class="highlight-item">
                    <div class="highlight-icon">
                        <i class="fa-solid fa-ban"></i>
                    </div>
                    <div class="highlight-text">
                        <h4><?php echo __('Zero-Data Sale'); ?></h4>
                        <p><?php echo __('We never sell, rent, or trade your personal or health data to third parties.'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Detailed Policies -->
            <div class="privacy-content-card">
                <div class="policy-section">
                    <h2><i class="fa-solid fa-database text-muted" style="font-size: 1.15rem;"></i> <?php echo __('1. Information We Collect'); ?></h2>
                    <p><?php echo __('We collect minimal information necessary to provide therapy services: account credentials, encrypted diary entries, and booking history. We do not store raw session audio or video.'); ?></p>
                </div>
                
                <div class="policy-section">
                    <h2><i class="fa-solid fa-gears text-muted" style="font-size: 1.15rem;"></i> <?php echo __('2. How We Use Information'); ?></h2>
                    <p><?php echo __('Your data is strictly used to facilitate bookings, personalize your dashboard recommendations, and maintain the safety and integrity of our community forums.'); ?></p>
                </div>
                
                <div class="policy-section">
                    <h2><i class="fa-solid fa-shield-halved text-muted" style="font-size: 1.15rem;"></i> <?php echo __('3. Security Measures'); ?></h2>
                    <p><?php echo __('We implement industry-standard security measures including SSL/TLS encryption, secure password hashing, and restricted database access to keep your records safe.'); ?></p>
                </div>
                
                <div class="policy-section">
                    <h2><i class="fa-solid fa-user-gear text-muted" style="font-size: 1.15rem;"></i> <?php echo __('4. Your Rights & Control'); ?></h2>
                    <p><?php echo __('You have full control over your data. You can export your diary logs, edit your profile at any time, or request permanent deletion of your account and all associated data.'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
