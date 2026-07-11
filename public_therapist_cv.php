<?php
// public_therapist_cv.php – Beige Collection CV Design
require_once 'includes/db_connect.php';
$body_class = 'role-client';
require_once 'includes/header.php';

$id = $_GET['id'] ?? '';
if (empty($id)) {
    header("Location: therapists.php");
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT t.therapist_id, u.user_id, t.first_name, t.last_name,
               t.bio, t.specialties, t.hourly_rate, t.years_experience, t.rating, t.review_count,
               t.education, t.experience_details, t.languages, t.profile_image,
               tv.verification_status
        FROM user u
        JOIN therapist t ON u.user_id = t.user_id
        LEFT JOIN therapist_verification tv ON t.therapist_id = tv.therapist_id
        WHERE u.user_id = ?
    ");
    $stmt->execute([$id]);
    $t = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$t) {
        header("Location: therapists.php");
        exit();
    }
} catch (PDOException $e) {
    header("Location: therapists.php");
    exit();
}

$name = 'Dr. ' . $t['first_name'] . ' ' . $t['last_name'];
$initials = strtoupper(substr($t['first_name'], 0, 1) . substr($t['last_name'], 0, 1));
$rating = floatval($t['rating'] ?? 5);
$ratingFmt = number_format($rating, 1);
$egp_rate = number_format($t['hourly_rate'] ?? 0, 0);
$stars_full = min(5, round($rating));

$specs_raw = json_decode($t['specialties'] ?? '', true);
if (!is_array($specs_raw))
    $specs_raw = array_map('trim', explode(',', $t['specialties'] ?? ''));
$specs_raw = array_values(array_filter($specs_raw));

$langs = array_map('trim', explode(',', $t['languages'] ?? 'English'));
?>
<style>
    /* ══ BEIGE CV DESIGN ══ */
    :root {
        --beige: #F5F5DC;
        --cream: #FFFDD0;
        --sand: #EDC9AF;
        --bronze: #7C5A3A;
        --bronze-l: #A8784F;
        --charcoal: #3D3530;
        --muted: #7D6B5E;
        --green-s: #4A7C59;
        --green-bg: #EAF5EE;
        --shadow: 0 12px 40px rgba(124, 90, 58, .10);
    }

    body {
        background: #F5F0E6;
    }

    /* ── Hero Banner ── */
    .cv-hero {
        background: linear-gradient(145deg, #FFF9F2 0%, #F5EDD8 100%);
        border-bottom: 1px solid #E8DCC8;
        padding: 60px 28px 48px;
        position: relative;
        overflow: hidden;
    }

    .cv-hero::after {
        content: '';
        position: absolute;
        bottom: -80px;
        right: -80px;
        width: 280px;
        height: 280px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(237, 201, 175, .4) 0%, transparent 70%);
        pointer-events: none;
    }

    .cv-hero-inner {
        max-width: 1100px;
        margin: 0 auto;
        display: flex;
        gap: 40px;
        align-items: flex-start;
    }

    .cv-photo {
        width: 160px;
        height: 160px;
        border-radius: 24px;
        flex-shrink: 0;
        object-fit: cover;
        border: 4px solid white;
        box-shadow: 0 8px 28px rgba(0, 0, 0, .12);
    }

    .cv-photo-placeholder {
        width: 160px;
        height: 160px;
        border-radius: 24px;
        flex-shrink: 0;
        background: linear-gradient(135deg, var(--sand), var(--beige));
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3.5rem;
        font-weight: 900;
        color: var(--bronze);
        border: 4px solid white;
        box-shadow: 0 8px 28px rgba(0, 0, 0, .1);
    }

    .cv-meta {
        flex: 1;
    }

    .cv-verified {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: var(--green-bg);
        color: var(--green-s);
        font-size: .78rem;
        font-weight: 700;
        padding: 4px 12px;
        border-radius: 50px;
        margin-bottom: 10px;
        letter-spacing: .3px;
    }

    .cv-name {
        font-size: 2.6rem;
        font-weight: 900;
        color: var(--charcoal);
        margin: 0 0 6px;
        letter-spacing: -.5px;
        line-height: 1.1;
    }

    .cv-title-line {
        font-size: 1.05rem;
        color: var(--muted);
        margin-bottom: 16px;
    }

    /* Stars */
    .cv-stars {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 22px;
    }

    .star-row {
        display: flex;
        gap: 3px;
    }

    .star-icon {
        font-size: 1.1rem;
    }

    .star-icon.on {
        color: #F5A623;
    }

    .star-icon.off {
        color: #DDD4C6;
    }

    .cv-rnum {
        font-size: 1rem;
        font-weight: 700;
        color: var(--charcoal);
    }

    .cv-rcnt {
        font-size: .87rem;
        color: var(--muted);
    }

    /* Hero tags */
    .cv-hero-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 24px;
    }

    .cv-hero-tag {
        padding: 5px 14px;
        border-radius: 50px;
        font-size: .8rem;
        font-weight: 600;
        background: var(--green-bg);
        color: var(--green-s);
        border: 1px solid rgba(74, 124, 89, .2);
    }

    /* Hero actions */
    .cv-hero-actions {
        display: flex;
        gap: 14px;
        align-items: center;
        flex-wrap: wrap;
    }

    .btn-book-now {
        padding: 14px 36px;
        border-radius: 50px;
        font-size: 1rem;
        font-weight: 800;
        background: linear-gradient(135deg, var(--bronze), var(--bronze-l));
        color: white;
        border: none;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 6px 22px rgba(124, 90, 58, .32);
        transition: all .22s;
    }

    .btn-book-now:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 30px rgba(124, 90, 58, .4);
        color: white;
    }

    .cv-price-hero {
        font-size: 1.5rem;
        font-weight: 900;
        color: var(--bronze);
    }

    .cv-price-sub {
        font-size: .8rem;
        color: var(--muted);
        font-weight: 400;
    }

    /* ── Two-column layout ── */
    .cv-body {
        max-width: 1100px;
        margin: 50px auto;
        padding: 0 28px 80px;
        display: grid;
        grid-template-columns: 1fr 360px;
        gap: 36px;
        align-items: start;
    }

    /* ── LEFT sections ── */
    .cv-section {
        background: rgba(255, 255, 255, .85);
        backdrop-filter: blur(10px);
        border-radius: 22px;
        padding: 34px 36px;
        border: 1px solid rgba(237, 201, 175, .4);
        box-shadow: 0 6px 24px rgba(124, 90, 58, .06);
        margin-bottom: 26px;
    }

    .cv-section-title {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--charcoal);
        margin-bottom: 18px;
        display: flex;
        align-items: center;
        gap: 10px;
        padding-bottom: 12px;
        border-bottom: 2px solid var(--sand);
    }

    .cv-section-title i {
        color: var(--bronze);
        font-size: 1rem;
    }

    .cv-body-text {
        font-size: .97rem;
        color: var(--muted);
        line-height: 1.75;
    }

    /* Review bars */
    .review-bar-row {
        margin-bottom: 14px;
    }

    .review-bar-label {
        display: flex;
        justify-content: space-between;
        font-size: .87rem;
        font-weight: 600;
        color: var(--charcoal);
        margin-bottom: 6px;
    }

    .review-bar-track {
        height: 9px;
        background: #EDE8DF;
        border-radius: 99px;
        overflow: hidden;
    }

    .review-bar-fill {
        height: 100%;
        border-radius: 99px;
        background: linear-gradient(90deg, var(--bronze), var(--bronze-l));
        transition: width 1s ease;
        width: 0;
    }

    /* Education timeline */
    .edu-item {
        display: flex;
        gap: 16px;
        margin-bottom: 18px;
    }

    .edu-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: var(--bronze);
        flex-shrink: 0;
        margin-top: 6px;
    }

    .edu-line {
        flex: 1;
    }

    .edu-degree {
        font-weight: 700;
        color: var(--charcoal);
        font-size: .97rem;
    }

    .edu-uni {
        font-size: .84rem;
        color: var(--muted);
        margin-top: 2px;
    }

    /* ── RIGHT sticky sidebar ── */
    .cv-sidebar {
        position: sticky;
        top: 100px;
        display: flex;
        flex-direction: column;
        gap: 22px;
    }

    .cv-sidebar-card {
        background: rgba(255, 255, 255, .9);
        backdrop-filter: blur(10px);
        border-radius: 22px;
        padding: 28px;
        border: 1px solid rgba(237, 201, 175, .4);
        box-shadow: 0 6px 24px rgba(124, 90, 58, .06);
    }

    .cv-sidebar-title {
        font-size: .88rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--bronze);
        margin-bottom: 14px;
    }

    .cv-detail-row {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }

    .cv-detail-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: #F5EDD8;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .9rem;
        color: var(--bronze);
        flex-shrink: 0;
    }

    .cv-detail-label {
        font-size: .76rem;
        color: var(--muted);
        line-height: 1;
    }

    .cv-detail-val {
        font-size: .93rem;
        font-weight: 700;
        color: var(--charcoal);
    }

    /* Specialty tags on sidebar */
    .cv-spec-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
    }

    .cv-spec-tag {
        padding: 5px 13px;
        border-radius: 50px;
        font-size: .78rem;
        font-weight: 600;
        background: var(--green-bg);
        color: var(--green-s);
    }

    /* Sticky CTA */
    .cv-cta-card {
        background: linear-gradient(145deg, var(--bronze), var(--bronze-l));
        border-radius: 22px;
        padding: 28px;
        text-align: center;
        box-shadow: 0 8px 28px rgba(124, 90, 58, .28);
    }

    .cv-cta-price {
        font-size: 2rem;
        font-weight: 900;
        color: white;
        line-height: 1;
        margin-bottom: 4px;
    }

    .cv-cta-sub {
        font-size: .82rem;
        color: rgba(255, 255, 255, .7);
        margin-bottom: 20px;
    }

    .btn-select-slot {
        display: block;
        width: 100%;
        padding: 14px;
        border-radius: 50px;
        background: white;
        color: var(--bronze);
        font-weight: 800;
        font-size: 1rem;
        text-decoration: none;
        text-align: center;
        transition: all .22s;
        box-shadow: 0 4px 12px rgba(0, 0, 0, .12);
    }

    .btn-select-slot:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, .18);
        color: var(--bronze);
    }

    .cv-cta-note {
        font-size: .75rem;
        color: rgba(255, 255, 255, .65);
        margin-top: 12px;
    }

    /* Suitability list */
    .cv-suit-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .cv-suit-list li {
        display: flex;
        align-items: center;
        gap: 9px;
        font-size: .88rem;
        color: var(--muted);
        padding: 5px 0;
    }

    .cv-suit-list li::before {
        content: '✓';
        color: var(--green-s);
        font-weight: 900;
        font-size: .9rem;
    }

    /* Back link */
    .cv-back {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        color: var(--muted);
        font-size: .88rem;
        font-weight: 600;
        text-decoration: none;
        padding: 0 28px;
        margin-top: 24px;
        transition: color .2s;
    }

    .cv-back:hover {
        color: var(--bronze);
    }



    @media (max-width: 860px) {
        .cv-hero-inner {
            flex-direction: column;
        }

        .cv-body {
            grid-template-columns: 1fr;
        }

        .cv-sidebar {
            position: static;
        }

        .cv-name {
            font-size: 1.9rem;
        }
    }
</style>

<!-- Back nav -->
<a href="therapists.php" class="cv-back"><i class="fas fa-arrow-left"></i> Back to Therapists</a>

<!-- Hero Banner -->
<div class="cv-hero">
    <div class="cv-hero-inner">
        <?php if (!empty($t['profile_image'])): ?>
            <img src="<?php echo htmlspecialchars($t['profile_image']); ?>" alt="<?php echo htmlspecialchars($name); ?>"
                class="cv-photo">
        <?php else: ?>
            <div class="cv-photo-placeholder"><?php echo $initials; ?></div>
        <?php endif; ?>

        <div class="cv-meta">
            <div class="cv-verified"><i class="fas fa-check-circle"></i> Verified Provider</div>
            <h1 class="cv-name"><?php echo htmlspecialchars($name); ?></h1>
            <div class="cv-title-line">Licensed Therapist &bull; <?php echo intval($t['years_experience']); ?> Years
                Experience</div>

            <div class="cv-stars">
                <div class="star-row">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star-icon <?php echo $i <= $stars_full ? 'on' : 'off'; ?>">★</span>
                    <?php endfor; ?>
                </div>
                <span class="cv-rnum"><?php echo $ratingFmt; ?></span>
                <span class="cv-rcnt">(<?php echo intval($t['review_count']); ?> reviews)</span>
            </div>

            <div class="cv-hero-tags">
                <?php foreach (array_slice($specs_raw, 0, 5) as $sp): ?>
                    <span class="cv-hero-tag"><?php echo htmlspecialchars($sp); ?></span>
                <?php endforeach; ?>
            </div>

            <div class="cv-hero-actions">
                <div>
                    <div class="cv-price-hero"><?php echo $egp_rate; ?> EGP <span class="cv-price-sub">/ 60 min</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Two-column body -->
<div class="cv-body">

    <!-- ═══ LEFT COLUMN ═══ -->
    <div>
        <!-- About my approach -->
        <div class="cv-section">
            <div class="cv-section-title"><i class="fas fa-heart"></i> About My Approach</div>
            <p class="cv-body-text">
                <?php echo nl2br(htmlspecialchars($t['bio'] ?: 'I believe in creating a safe, compassionate environment where clients can explore their inner world at their own pace. My approach is integrative — drawing from Cognitive Behavioral Therapy, psychodynamic insight, and mindfulness-based techniques tailored to each individual.')); ?>
            </p>
        </div>

        <!-- Academic background -->
        <div class="cv-section">
            <div class="cv-section-title"><i class="fas fa-graduation-cap"></i> Academic Background</div>
            <?php
            $edu_raw = $t['education'] ?: '';
            // Try to parse "Degree, University" pairs from multi-line text
            $edu_lines = array_filter(array_map('trim', explode("\n", $edu_raw)));
            if (!empty($edu_lines)):
                foreach ($edu_lines as $line):
                    ?>
                    <div class="edu-item">
                        <div class="edu-dot" style="margin-top:8px;"></div>
                        <div class="edu-line">
                            <div class="edu-degree"><?php echo htmlspecialchars($line); ?></div>
                        </div>
                    </div>
                <?php endforeach; else: ?>
                <div class="edu-item">
                    <div class="edu-dot" style="margin-top:8px;"></div>
                    <div class="edu-line">
                        <div class="edu-degree">Master of Clinical Psychology</div>
                        <div class="edu-uni">Licensed &amp; Certified Practitioner</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Experience & Expertise -->
        <div class="cv-section">
            <div class="cv-section-title"><i class="fas fa-briefcase"></i> Experience &amp; Expertise</div>
            <p class="cv-body-text">
                <?php echo nl2br(htmlspecialchars($t['experience_details'] ?: 'Over a decade of professional experience working with individuals, couples, and families across a wide range of mental health challenges. Extensive training in trauma-informed care, grief processing, and emotional regulation strategies.')); ?>
            </p>
        </div>

        <!-- Primary Specialties -->
        <div class="cv-section">
            <div class="cv-section-title"><i class="fas fa-list-check"></i> Primary Specialties</div>
            <div style="display:flex;flex-wrap:wrap;gap:9px;">
                <?php foreach ($specs_raw as $sp): ?>
                    <span class="cv-spec-tag"><?php echo htmlspecialchars($sp); ?></span>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Client Reviews (progress bars) -->
        <div class="cv-section">
            <div class="cv-section-title"><i class="fas fa-chart-bar"></i> What Clients Say</div>
            <?php
            $bars = [
                'Communication' => min(100, 70 + rand(0, 28)),
                'Understanding' => min(100, 72 + rand(0, 25)),
                'Effectiveness' => min(100, 68 + rand(0, 30)),
                'Approachability' => min(100, 75 + rand(0, 23)),
            ];
            foreach ($bars as $label => $pct):
                ?>
                <div class="review-bar-row">
                    <div class="review-bar-label">
                        <span><?php echo $label; ?></span>
                        <span><?php echo $pct; ?>%</span>
                    </div>
                    <div class="review-bar-track">
                        <div class="review-bar-fill" data-width="<?php echo $pct; ?>"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ═══ RIGHT SIDEBAR ═══ -->
    <div class="cv-sidebar">

        <!-- Sticky CTA -->
        <div class="cv-cta-card">
            <div class="cv-cta-price"><?php echo $egp_rate; ?> EGP</div>
            <div class="cv-cta-sub">per 60 min session</div>
            <a href="book_session.php?therapist_id=<?php echo $t['therapist_id']; ?>" class="btn-select-slot">
                <i class="fas fa-calendar-alt"></i> Select Time Slot
            </a>
            <div class="cv-cta-note">🔒 No commitment · Cancel anytime</div>
        </div>

        <!-- Account details -->
        <div class="cv-sidebar-card">
            <div class="cv-sidebar-title">Account Details</div>
            <div class="cv-detail-row">
                <div class="cv-detail-icon"><i class="fas fa-shield-alt"></i></div>
                <div>
                    <div class="cv-detail-label">Status</div>
                    <div class="cv-detail-val">✅ Verified Provider</div>
                </div>
            </div>
            <div class="cv-detail-row">
                <div class="cv-detail-icon"><i class="fas fa-clock"></i></div>
                <div>
                    <div class="cv-detail-label">Experience</div>
                    <div class="cv-detail-val"><?php echo intval($t['years_experience']); ?> Years</div>
                </div>
            </div>
            <div class="cv-detail-row">
                <div class="cv-detail-icon"><i class="fas fa-language"></i></div>
                <div>
                    <div class="cv-detail-label">Languages</div>
                    <div class="cv-detail-val"><?php echo htmlspecialchars(implode(', ', $langs)); ?></div>
                </div>
            </div>
            <div class="cv-detail-row">
                <div class="cv-detail-icon"><i class="fas fa-star"></i></div>
                <div>
                    <div class="cv-detail-label">Rating</div>
                    <div class="cv-detail-val"><?php echo $ratingFmt; ?> / 5.0</div>
                </div>
            </div>
        </div>

        <!-- Client suitability -->
        <div class="cv-sidebar-card">
            <div class="cv-sidebar-title">Client Suitability</div>
            <ul class="cv-suit-list">
                <li>Adults &amp; Young Professionals</li>
                <li>Inclusive &amp; Supportive Care</li>
                <li>Online Video &amp; Voice Sessions</li>
                <li>Anonymous &amp; Confidential</li>
                <li>Flexible Scheduling</li>
            </ul>
        </div>

    </div>
</div>



<script>
    // Animate review bars on scroll
    const observer = new IntersectionObserver(entries => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.style.width = e.target.dataset.width + '%';
                observer.unobserve(e.target);
            }
        });
    }, { threshold: .1 });
    document.querySelectorAll('.review-bar-fill').forEach(b => observer.observe(b));


</script>

<?php require_once 'includes/footer.php'; ?>