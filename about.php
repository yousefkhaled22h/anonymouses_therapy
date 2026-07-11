<?php
// about.php
$user_role = $_SESSION['role'] ?? 'Client';
// Dynamic body class for role-based theming
if (strtolower($user_role) === 'therapist') {
    $body_class = 'role-therapist';
} elseif (strtolower($user_role) === 'volunteer') {
    $body_class = 'role-volunteer';
} else {
    $body_class = 'role-client';
}
require_once 'includes/header.php';

// Role-based dynamics
$is_therapist = (strtolower($user_role) === 'therapist');
$is_volunteer = (strtolower($user_role) === 'volunteer');

// Colors
if ($is_therapist) {
    $primary_color = '#337AB7';
    $accent_color  = '#1A4D80';
    $light_bg      = '#F4F9FD';
} elseif ($is_volunteer) {
    $primary_color = '#059669';
    $accent_color  = '#064E3B';
    $light_bg      = '#F0FDF4';
} else {
    $primary_color = '#A68A6C';
    $accent_color  = '#8A7055';
    $light_bg      = '#FAF8F5';
}
$card_shadow = '0 10px 40px rgba(0,0,0,0.08)';

// Images
$hero_img = 'https://images.unsplash.com/photo-1497366216548-37526070297c?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80';
?>

<style>
    :root {
        --about-primary:
            <?php echo $primary_color; ?>
        ;
        --about-accent:
            <?php echo $accent_color; ?>
        ;
        --about-light:
            <?php echo $light_bg; ?>
        ;
    }

    .breadcrumb-area {
        padding: 40px 0;
        background: #fff;
        border-bottom: 1px solid #eee;
    }

    .breadcrumb-flex {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .breadcrumb-flex h1 {
        margin: 0;
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--about-accent);
    }

    .breadcrumb-nav {
        font-size: 0.9rem;
        color: #666;
    }

    .breadcrumb-nav a {
        color: var(--about-primary);
    }

    /* Hero Section */
    .about-hero-sec {
        position: relative;
        height: 500px;
        background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), url('<?php echo $hero_img; ?>');
        background-size: cover;
        background-position: center;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: white;
    }

    .about-hero-sec h2 {
        font-size: 3.5rem;
        font-weight: 800;
        color: white;
        max-width: 900px;
        line-height: 1.2;
    }

    /* Overlapping Card */
    .mission-overlap-card {
        max-width: 1200px;
        margin: -100px auto 100px;
        background: white;
        border-radius: 12px;
        box-shadow:
            <?php echo $card_shadow; ?>
        ;
        padding: 60px;
        position: relative;
        z-index: 10;
        animation: fadeInUp 1s ease-out;
    }

    .card-title-line {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--about-accent);
        text-transform: uppercase;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .card-title-line::after {
        content: '';
        height: 3px;
        width: 60px;
        background: var(--about-primary);
    }

    .card-intro {
        font-size: 1.1rem;
        color: #555;
        line-height: 1.8;
        margin-bottom: 50px;
        max-width: 800px;
    }

    .mission-grid-3 {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
    }

    .grid-box {
        padding: 30px;
        border: 1px solid #f0f0f0;
        border-radius: 12px;
        transition: all 0.3s ease;
    }

    .grid-box:hover {
        border-color: var(--about-primary);
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }

    .grid-box i {
        font-size: 2rem;
        color: var(--about-primary);
        margin-bottom: 20px;
        display: block;
    }

    .grid-box h3 {
        font-size: 1.2rem;
        font-weight: 700;
        margin-bottom: 15px;
        color: #2c3e50;
    }

    .grid-box p {
        font-size: 0.95rem;
        color: #666;
        line-height: 1.6;
    }



    /* Bottom CTA Section */
    .about-cta-sec {
        height: 400px;
        background: linear-gradient(rgba(255, 255, 255, 0.8), rgba(255, 255, 255, 0.8)), url('<?php echo $hero_img; ?>');
        background-attachment: fixed;
        background-size: cover;
        background-position: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
    }

    .about-cta-sec h3 {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--about-accent);
        margin-bottom: 40px;
    }

    .cta-btns {
        display: flex;
        gap: 20px;
    }

    .btn-solid {
        background: var(--about-primary);
        color: white;
        padding: 15px 35px;
        border-radius: 50px;
        font-weight: 700;
        transition: all 0.3s ease;
    }

    .btn-outline {
        border: 2px solid var(--about-primary);
        color: var(--about-primary);
        background: white;
        padding: 13px 33px;
        border-radius: 50px;
        font-weight: 700;
        transition: all 0.3s ease;
    }

    .btn-solid:hover {
        background: var(--about-accent);
        transform: translateY(-3px);
    }

    .btn-outline:hover {
        background: var(--about-primary);
        color: white;
        transform: translateY(-3px);
    }

    @media (max-width: 992px) {

        .mission-grid-3 {
            grid-template-columns: 1fr;
        }

        .mission-overlap-card {
            margin-left: 20px;
            margin-right: 20px;
            padding: 30px;
        }

        .about-hero-sec h2 {
            font-size: 2.5rem;
            padding: 20px;
        }
    }
</style>

<div class="breadcrumb-area">
    <div class="container breadcrumb-flex">
        <h1>About Us</h1>
        <div class="breadcrumb-nav">
            <a href="index.php">Home</a> / About Us
        </div>
    </div>
</div>

<section class="about-hero-sec">
    <div class="container">
        <h2 class="animate-up">A Secure Sanctuary <br> for Healing and Growth.</h2>
    </div>
</section>

<div class="container" style="position: relative;">
    <div class="mission-overlap-card">
        <div class="card-title-line">Our Mission & Vision</div>
        <p class="card-intro">
            Safe Haven is a therapy platform built around one core belief: every person deserves a safe,
            private space to talk and get support. We keep your identity fully protected at every step.
        </p>

        <div class="mission-grid-3">
            <div class="grid-box">
                <i class="fas fa-bullseye"></i>
                <h3>Our Mission</h3>
                <p>We make it easy for anyone to access mental health support online, in a safe and private
                    environment. Your identity stays protected and your conversations stay confidential,
                    always.</p>
            </div>
            <div class="grid-box">
                <i class="fas fa-eye"></i>
                <h3>Our Vision</h3>
                <p>We want mental health support to be available to everyone across the MENA region, without
                    shame or barriers. Safe Haven aims to make seeking help as simple and normal as possible.</p>
            </div>
            <div class="grid-box">
                <i class="fas fa-shield-alt"></i>
                <h3>Your Privacy Comes First</h3>
                <p>We never share your personal information. Your identity is always protected on our platform,
                    so you can speak freely and focus on what matters most &mdash; your well-being.</p>
            </div>
        </div>
    </div>
</div>



<?php if (!isset($_SESSION['user_id'])): ?>
<section class="about-cta-sec">
    <div class="container">
        <h3>Ready to start your journey?</h3>
        <div class="cta-btns">
            <a href="register.php" class="btn-solid">Get Started Now</a>
            <a href="therapists.php" class="btn-outline">Meet Our Doctors</a>
        </div>
    </div>
</section>
<?php endif; ?>

<script>
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

<?php require_once 'includes/footer.php'; ?>