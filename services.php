<?php
// services.php
$body_class = 'page-services';
require_once 'includes/header.php';

$user_role = $_SESSION['role'] ?? 'Client';
$is_therapist = (strtolower($user_role) === 'therapist');
$is_volunteer = (strtolower($user_role) === 'volunteer');

// Theme Colors Definition
if ($is_therapist) {
    $c_bg = '#F4F9FD';
    $c_text_main = '#1A4D80';
    $c_text_muted = '#4A7AAB';
    $c_primary = '#337AB7';
    $c_banner_bg = '#1A4D80';
} elseif ($is_volunteer) {
    $c_bg = '#F0F7F4';
    $c_text_main = '#1B4D3E';
    $c_text_muted = '#40916C';
    $c_primary = '#2D6A4F';
    $c_banner_bg = '#1B4332';
} else {
    $c_bg = '#f5f0e6';
    $c_text_main = '#4f4438';
    $c_text_muted = '#887d72';
    $c_primary = '#5A4A3A';
    $c_banner_bg = '#5A4A3A';
}
$c_card_bg = '#ffffff';
$c_banner_text = '#ffffff';
?>

<!-- AOS Animation CSS -->
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

<style>
    .services-page-wrap {
        background-color:
            <?php echo $c_bg; ?>
        ;
        font-family: 'Inter', sans-serif;
        color:
            <?php echo $c_text_main; ?>
        ;
        overflow-x: hidden;
    }

    /* Section 1: Hero / Intro */
    .services-hero {
        padding: 80px 0;
    }

    .hero-container {
        display: flex;
        align-items: center;
        gap: 60px;
    }

    .hero-text-col {
        flex: 1;
    }

    .hero-text-col h1 {
        font-family: var(--font-heading);
        font-size: 3rem;
        margin-bottom: 20px;
        color:
            <?php echo $c_text_main; ?>
        ;
        font-weight: 600;
    }

    .hero-text-col p.hero-subtitle {
        font-size: 1.15rem;
        color:
            <?php echo $c_text_muted; ?>
        ;
        margin-bottom: 30px;
        line-height: 1.7;
    }

    .hero-features {
        list-style: none;
        padding: 0;
        margin: 0 0 40px 0;
    }

    .hero-features li {
        display: flex;
        align-items: center;
        gap: 15px;
        font-size: 1.05rem;
        margin-bottom: 15px;
        color:
            <?php echo $c_text_main; ?>
        ;
    }

    .check-icon {
        background-color: rgba(0, 0, 0, 0.08);
        /* Adapts to theme */
        width: 25px;
        height: 25px;
        display: flex;
        justify-content: center;
        align-items: center;
        border-radius: 50%;
        color:
            <?php echo $c_text_main; ?>
        ;
        font-size: 0.85rem;
    }

    .btn-action-primary {
        background-color:
            <?php echo $c_primary; ?>
        ;
        color: #fff;
        text-decoration: none;
        padding: 14px 35px;
        border-radius: 8px;
        font-size: 1.1rem;
        font-weight: 600;
        display: inline-block;
        transition: opacity 0.2s;
    }

    .btn-action-primary:hover {
        opacity: 0.9;
        color: #fff;
    }

    .hero-image-col {
        flex: 1;
    }

    .hero-image-col img {
        width: 100%;
        border-radius: 16px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
        object-fit: cover;
    }

    /* Section 2: Comfort Level */
    .comfort-section {
        padding: 80px 0;
        text-align: center;
    }

    .comfort-section h2 {
        font-family: var(--font-heading);
        font-size: 2.5rem;
        color:
            <?php echo $c_text_main; ?>
        ;
        margin-bottom: 15px;
        font-weight: 600;
    }

    .comfort-section p.subtitle {
        font-size: 1.1rem;
        color:
            <?php echo $c_text_muted; ?>
        ;
        margin-bottom: 50px;
    }

    .comfort-cards {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
    }

    .comfort-card {
        background:
            <?php echo $c_card_bg; ?>
        ;
        border-radius: 16px;
        padding: 40px 30px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
        border: 1px solid rgba(0, 0, 0, 0.05);
        transition: transform 0.3s;
    }

    .comfort-card:hover {
        transform: translateY(-5px);
    }

    .card-icon {
        font-size: 3rem;
        margin-bottom: 20px;
        color:
            <?php echo $c_primary; ?>
        ;
    }

    /* Specific icon colors if they need to look colorful like the image */
    .icon-chat {
        color: #d4c8e8;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
    }

    .icon-voice {
        color: #e83e8c;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
    }

    .icon-video {
        color: #6f42c1;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
    }

    .comfort-card h3 {
        font-size: 1.4rem;
        color:
            <?php echo $c_text_main; ?>
        ;
        margin-bottom: 15px;
        font-weight: 600;
    }

    .comfort-card p {
        color:
            <?php echo $c_text_muted; ?>
        ;
        line-height: 1.6;
        font-size: 0.95rem;
        margin: 0;
    }

    /* Section 3: Areas of Expertise */
    .expertise-section {
        padding: 80px 0;
        background-color: rgba(0, 0, 0, 0.02);
        /* Subtle contrast against page bg */
        text-align: center;
    }

    .expertise-section h2 {
        font-family: var(--font-heading);
        font-size: 2.8rem;
        color:
            <?php echo $c_text_main; ?>
        ;
        margin-bottom: 15px;
        font-weight: 600;
    }

    .expertise-section p.subtitle {
        font-size: 1.15rem;
        color:
            <?php echo $c_text_muted; ?>
        ;
        margin-bottom: 60px;
    }

    .expertise-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        max-width: 1000px;
        margin: 0 auto;
    }

    .expertise-pill {
        background-color:
            <?php echo $c_card_bg; ?>
        ;
        color:
            <?php echo $c_text_main; ?>
        ;
        padding: 20px 15px;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 500;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
        border: 1px solid rgba(0, 0, 0, 0.05);
        transition: transform 0.2s, box-shadow 0.2s;
        cursor: default;
    }

    .expertise-pill:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.06);
        color:
            <?php echo $c_primary; ?>
        ;
    }

    /* Section 4: CTA Bar */
    .cta-banner {
        background-color:
            <?php echo $c_banner_bg; ?>
        ;
        color:
            <?php echo $c_banner_text; ?>
        ;
        padding: 80px 0;
        text-align: center;
    }

    .cta-banner h2 {
        font-family: var(--font-heading);
        font-size: 2.8rem;
        margin-bottom: 15px;
        font-weight: 600;
    }

    .cta-banner p {
        font-size: 1.15rem;
        margin-bottom: 40px;
        opacity: 0.9;
    }

    .btn-action-light {
        background-color: #fff;
        color:
            <?php echo $c_banner_bg; ?>
        ;
        text-decoration: none;
        padding: 16px 40px;
        border-radius: 12px;
        font-size: 1.15rem;
        font-weight: 600;
        display: inline-block;
        transition: opacity 0.2s, transform 0.2s;
    }

    .btn-action-light:hover {
        opacity: 0.95;
        color:
            <?php echo $c_banner_bg; ?>
        ;
        transform: scale(1.02);
    }

    /* Responsive */
    @media (max-width: 991px) {
        .hero-container {
            flex-direction: column;
            text-align: center;
        }

        .hero-features li {
            justify-content: center;
        }

        .comfort-cards {
            grid-template-columns: 1fr;
        }

        .expertise-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 768px) {
        .expertise-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 480px) {
        .expertise-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="services-page-wrap">
 
     <!-- Hero Section -->
     <section class="services-hero">
         <div class="container">
             <div class="hero-container">
                 <div class="hero-text-col" data-aos="fade-right" data-aos-duration="1000">
                     <h1><?php echo __('Individual Therapy'); ?></h1>
                     <p class="hero-subtitle"><?php echo __('One-on-one sessions with licensed therapists specializing in anxiety, depression, trauma, and more.'); ?></p>
 
                     <ul class="hero-features">
                         <li data-aos="fade-right" data-aos-delay="100">
                             <div class="check-icon"><i class="fa-solid fa-check"></i></div> <?php echo __('Personalized treatment plans'); ?>
                         </li>
                         <li data-aos="fade-right" data-aos-delay="200">
                             <div class="check-icon"><i class="fa-solid fa-check"></i></div> <?php echo __('Flexible scheduling'); ?>
                         </li>
                         <li data-aos="fade-right" data-aos-delay="300">
                             <div class="check-icon"><i class="fa-solid fa-check"></i></div> <?php echo __('Choice of communication method'); ?>
                         </li>
                         <li data-aos="fade-right" data-aos-delay="400">
                             <div class="check-icon"><i class="fa-solid fa-check"></i></div> <?php echo __('Progress tracking'); ?>
                         </li>
                     </ul>
 
                     <a href="auth_handler.php?action=role_selection" class="btn-action-primary" data-aos="zoom-in" data-aos-delay="500"><?php echo __('Get Started'); ?></a>
                 </div>
                 <div class="hero-image-col" data-aos="fade-left" data-aos-duration="1000">
                     <!-- High Quality Premium Office Placeholder Image -->
                     <img class="img-fluid"  src="https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&q=80&w=1000"
                         alt="Therapy Office">
                 </div>
             </div>
         </div>
     </section>
 
     <!-- Comfort Level Section -->
     <section class="comfort-section">
         <div class="container">
             <h2 data-aos="fade-up"><?php echo __('Choose Your Comfort Level'); ?></h2>
             <p class="subtitle" data-aos="fade-up" data-aos-delay="100"><?php echo __('We offer multiple ways to connect with your therapist based on what feels right for you.'); ?>
             </p>
 
             <div class="comfort-cards">
                 <div class="comfort-card" data-aos="fade-up" data-aos-delay="200" data-aos-duration="800">
                     <div class="card-icon icon-chat"><i class="fa-solid fa-comment-dots"></i></div>
                     <h3><?php echo __('Text Chat'); ?></h3>
                     <p><?php echo __('Type out your thoughts in a safe, text-based environment.'); ?></p>
                 </div>
                 <div class="comfort-card" data-aos="fade-up" data-aos-delay="400" data-aos-duration="800">
                     <div class="card-icon icon-voice"><i class="fa-solid fa-phone-volume"></i></div>
                     <h3><?php echo __('Voice Calls'); ?></h3>
                     <p><?php echo __('Speak with your therapist through secure audio sessions.'); ?></p>
                 </div>
                 <div class="comfort-card" data-aos="fade-up" data-aos-delay="600" data-aos-duration="800">
                     <div class="card-icon icon-video"><i class="fa-solid fa-video"></i></div>
                     <h3><?php echo __('Video Sessions'); ?></h3>
                     <p><?php echo __('Connect face-to-face through encrypted video calls.'); ?></p>
                 </div>
             </div>
         </div>
     </section>
 
     <!-- Areas of Expertise Section -->
     <section class="expertise-section">
         <div class="container">
             <h2 data-aos="fade-up"><?php echo __('Areas of Expertise'); ?></h2>
             <p class="subtitle" data-aos="fade-up" data-aos-delay="100"><?php echo __('Our therapists specialize in a wide range of mental health concerns to provide you with expert care.'); ?></p>
 
             <div class="expertise-grid">
                 <?php
                 $expertises = [
                     'Anxiety & Stress',
                     'Depression',
                     'Trauma & PTSD',
                     'Relationship Issues',
                     'Grief & Loss',
                     'Self-Esteem',
                     'Life Transitions',
                     'Addiction Recovery',
                     'Work-Life Balance',
                     'Family Conflicts',
                     'Eating Disorders',
                     'Sleep Issues'
                 ];
                 foreach ($expertises as $index => $expertise) {
                     $anim_delay = 100 + ($index % 4) * 100; // Stagger animation grouped by column
                     echo '<div class="expertise-pill" data-aos="zoom-in" data-aos-delay="' . $anim_delay . '">' . htmlspecialchars(__($expertise)) . '</div>';
                 }
                 ?>
             </div>
         </div>
     </section>
 
      <?php if (!isset($_SESSION['user_id'])): ?>
      <!-- Bottom CTA Banner -->
      <section class="cta-banner" data-aos="zoom-in" data-aos-duration="1000">
          <div class="container">
              <h2 data-aos="fade-down" data-aos-delay="300"><?php echo __('Ready to Begin Your Therapy Journey?'); ?></h2>
              <p data-aos="fade-up" data-aos-delay="500"><?php echo __('Connect with a licensed professional today and start your path to better mental health.'); ?></p>
              <a href="therapists.php" class="btn-action-light" data-aos="flip-up" data-aos-delay="700"><?php echo __('Find Your Therapist'); ?></a>
          </div>
      </section>
      <?php endif; ?>
 
 </div>

<!-- AOS Animation JS -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        AOS.init({
            once: true, // Animations fire only once when scrolling down
            offset: 50, // Triggers animation slightly sooner
        });
    });
</script>

<?php
require_once 'includes/footer.php';
?>