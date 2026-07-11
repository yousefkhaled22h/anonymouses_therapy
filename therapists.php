<?php
// therapists.php – Beige Collection Design
$body_class = 'role-client';
require_once 'includes/header.php';
require_once 'includes/db_connect.php';

if (!function_exists('normalize_specialty')) {
    function normalize_specialty($spec) {
        $s = strtolower(trim($spec));
        if (strpos($s, 'depress') !== false) {
            return 'Depression';
        }
        if (strpos($s, 'anxiety') !== false || strpos($s, 'stress') !== false) {
            return 'Anxiety & Stress';
        }
        if (strpos($s, 'family') !== false || strpos($s, 'conflict') !== false) {
            return 'Family Conflicts';
        }
        if (strpos($s, 'relationship') !== false || strpos($s, 'marriage') !== false || strpos($s, 'couple') !== false) {
            return 'Relationship Issues';
        }
        if (strpos($s, 'trauma') !== false || strpos($s, 'ptsd') !== false) {
            return 'Trauma';
        }
        return ucwords($spec);
    }
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT u.user_id, t.therapist_id, t.first_name, t.last_name, t.profile_image,
               t.bio, t.specialties, t.hourly_rate, t.years_experience, t.rating, t.review_count,
               tv.verification_status
        FROM user u
        JOIN therapist t ON u.user_id = t.user_id
        LEFT JOIN therapist_verification tv ON t.therapist_id = tv.therapist_id
        WHERE u.role = 'therapist' AND (t.verified = 1 OR tv.verification_status = 'Approved')
        ORDER BY t.rating DESC
    ");
    $stmt->execute();
    $therapists = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $therapists = [];
}

// Collect all unique specialties for filter
$all_specs = [];
foreach ($therapists as $t) {
    $specs = json_decode($t['specialties'] ?? '', true);
    if (!is_array($specs)) $specs = array_map('trim', explode(',', $t['specialties'] ?? ''));
    foreach ($specs as $s) { if (!empty(trim($s))) $all_specs[trim($s)] = true; }
}
$all_specs = array_keys($all_specs);
?>
<link rel="stylesheet" href="assets/css/dashboard-style.css">
<style>
/* ══ BEIGE COLLECTION DESIGN SYSTEM ══ */
:root {
    --beige:   #F5F5DC;
    --cream:   #FFFDD0;
    --sand:    #EDC9AF;
    --bronze:  #7C5A3A;
    --bronze-l:#A8784F;
    --charcoal:#3D3530;
    --muted:   #7D6B5E;
    --green-s: #4A7C59;
    --green-bg:#EAF5EE;
    --white-g: rgba(255,255,255,0.72);
    --shadow:  0 12px 40px rgba(124,90,58,.10);
}
body { background: #F7F3EC; }

/* ── Page wrapper ── */
.dir-wrap { max-width: 1380px; margin: 0 auto; padding: 48px 28px 80px; }

/* ── Hero ── */
.dir-hero {
    text-align: center; margin-bottom: 52px;
    padding: 56px 20px 48px;
    background: linear-gradient(145deg, #FFFDF5 0%, #F5F0E4 100%);
    border-radius: 28px;
    border: 1px solid #E8DDC8;
    box-shadow: var(--shadow);
    position: relative; overflow: hidden;
}
.dir-hero::before {
    content: ''; position: absolute; top: -60px; right: -60px;
    width: 220px; height: 220px; border-radius: 50%;
    background: radial-gradient(circle, rgba(237,201,175,.35) 0%, transparent 70%);
}
.dir-hero h1 {
    font-size: 2.75rem; font-weight: 800; color: var(--charcoal);
    margin-bottom: 12px; letter-spacing: -.5px; line-height: 1.2;
}
.dir-hero h1 span { color: var(--bronze); }
.dir-hero p { color: var(--muted); font-size: 1.12rem; max-width: 560px; margin: 0 auto 28px; line-height: 1.6; }

/* ── Search bar ── */
.dir-search {
    display: flex; max-width: 520px; margin: 0 auto;
    background: white; border-radius: 50px;
    border: 1.5px solid #E0D4C2;
    box-shadow: 0 4px 16px rgba(0,0,0,.06);
    overflow: hidden;
}
.dir-search input {
    flex: 1; border: none; outline: none; padding: 14px 22px;
    font-size: .97rem; color: var(--charcoal); background: transparent;
}
.dir-search button {
    padding: 14px 22px; background: var(--bronze); border: none;
    color: white; cursor: pointer; font-size: 1rem; transition: background .2s;
}
.dir-search button:hover { background: var(--bronze-l); }

/* ── Filter chips ── */
.filter-row {
    display: flex; flex-wrap: wrap; gap: 10px;
    margin-bottom: 40px; align-items: center;
}
.filter-chip {
    padding: 7px 18px; border-radius: 50px; font-size: .84rem; font-weight: 600;
    border: 1.5px solid #DDD4C6; background: white; color: var(--muted);
    cursor: pointer; transition: all .18s; white-space: nowrap;
}
.filter-chip:hover, .filter-chip.active {
    background: var(--bronze); color: white; border-color: var(--bronze);
}
.filter-count {
    display: inline-block; background: #EDE8DF; color: var(--bronze);
    border-radius: 50%; font-size: .72rem; font-weight: 700;
    width: 21px; height: 21px; line-height: 21px; text-align: center; margin-left: 2px;
}

/* ── Grid ── */
.therapist-grid-new {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 28px;
}

/* ── Card ── */
.tc-new {
    background: rgba(255,255,255,0.88);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-radius: 24px;
    border: 1px solid rgba(237,201,175,.4);
    box-shadow: 0 8px 32px rgba(124,90,58,.07);
    overflow: hidden;
    display: flex; flex-direction: column;
    transition: transform .28s cubic-bezier(.4,0,.2,1), box-shadow .28s;
    cursor: pointer;
    animation: cardFadeUp .4s ease both;
}
.tc-new:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 50px rgba(124,90,58,.14);
}
@keyframes cardFadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
.tc-new:nth-child(1) { animation-delay: .05s; }
.tc-new:nth-child(2) { animation-delay: .10s; }
.tc-new:nth-child(3) { animation-delay: .15s; }
.tc-new:nth-child(4) { animation-delay: .20s; }
.tc-new:nth-child(5) { animation-delay: .25s; }
.tc-new:nth-child(6) { animation-delay: .30s; }

/* Card banner (gradient top strip) */
.tc-banner {
    height: 6px;
    background: linear-gradient(90deg, var(--sand), var(--bronze-l), var(--sand));
}

/* Card body */
.tc-body { padding: 22px 24px 18px; flex: 1; }

/* Top row: avatar + info */
.tc-top { display: flex; gap: 16px; align-items: flex-start; margin-bottom: 16px; }
.tc-photo {
    width: 76px; height: 76px; border-radius: 18px; flex-shrink: 0;
    object-fit: cover; border: 3px solid white;
    box-shadow: 0 4px 14px rgba(0,0,0,.1);
}
.tc-photo-placeholder {
    width: 76px; height: 76px; border-radius: 18px; flex-shrink: 0;
    background: linear-gradient(135deg, var(--sand), var(--beige));
    display: flex; align-items: center; justify-content: center;
    font-size: 1.9rem; font-weight: 800; color: var(--bronze);
    border: 3px solid white; box-shadow: 0 4px 14px rgba(0,0,0,.1);
}
.tc-name-area { flex: 1; min-width: 0; }
.tc-verified {
    display: inline-flex; align-items: center; gap: 4px;
    background: #EAF5EE; color: var(--green-s); font-size: .71rem;
    font-weight: 700; padding: 2px 9px; border-radius: 50px;
    margin-bottom: 4px; letter-spacing: .2px;
}
.tc-fullname {
    font-size: 1.13rem; font-weight: 800; color: var(--charcoal);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    line-height: 1.25; margin-bottom: 3px;
}
.tc-title { font-size: .8rem; color: var(--muted); margin-bottom: 4px; }

/* Stars */
.tc-stars { display: flex; align-items: center; gap: 4px; }
.stars-el { color: #F5A623; font-size: .85rem; letter-spacing: 1px; }
.tc-rcount { font-size: .78rem; color: var(--muted); }

/* Bio snippet */
.tc-bio-snip {
    font-size: .88rem; color: var(--muted); line-height: 1.55;
    margin-bottom: 14px;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}

/* Specialty tags */
.tc-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 0; }
.tc-tag-green {
    padding: 4px 12px; border-radius: 50px; font-size: .76rem; font-weight: 600;
    background: var(--green-bg); color: var(--green-s);
    border: 1px solid rgba(74,124,89,.18);
}

/* Card footer */
.tc-footer-new {
    padding: 16px 24px; border-top: 1px solid rgba(237,201,175,.35);
    display: flex; align-items: center; justify-content: space-between;
    background: rgba(255,253,240,.6);
}
.tc-price-block { }
.tc-price-egp {
    font-size: 1.22rem; font-weight: 800; color: var(--bronze);
    line-height: 1.1;
}
.tc-price-label { font-size: .73rem; color: var(--muted); }
.tc-exp-tag {
    font-size: .78rem; color: var(--muted); font-weight: 500;
}
.btn-view-profile {
    padding: 10px 22px; border-radius: 50px; font-size: .88rem; font-weight: 700;
    background: linear-gradient(135deg, var(--bronze), var(--bronze-l));
    color: white; border: none; cursor: pointer; text-decoration: none;
    display: inline-block; transition: all .22s;
    box-shadow: 0 4px 14px rgba(124,90,58,.25);
}
.btn-view-profile:hover {
    transform: translateY(-2px); box-shadow: 0 8px 20px rgba(124,90,58,.35);
    color: white;
}

/* Empty state */
.dir-empty { text-align: center; padding: 80px 20px; color: var(--muted); }
.dir-empty i { font-size: 3rem; opacity: .3; margin-bottom: 16px; display: block; }

@media (max-width: 640px) {
    .dir-hero h1 { font-size: 1.9rem; }
    .therapist-grid-new { grid-template-columns: 1fr; }
}
</style>

<div class="dashboard-wrapper">
    <?php require_once 'includes/dashboard_components.php'; render_sidebar('therapists'); ?>
    <div class="dashboard-main">
        <div class="container-fluid">
            <!-- Sidebar toggle button for mobile/desktop -->
            <div class="dashboard-header-row" style="display: flex; gap: 20px; align-items: stretch; margin-bottom: 30px;">
                <button id="sidebarToggle" class="sidebar-toggle-inline" aria-label="Toggle Sidebar" style="align-self: flex-start; margin-top: 10px; cursor: pointer;">
                    <i id="toggleIcon" class="fa-solid fa-bars-staggered"></i>
                </button>
            </div>
    <!-- Hero -->
    <div class="dir-hero">
        <h1><?php echo __('Find Your'); ?> <span><?php echo __('Perfect Therapist'); ?></span></h1>
        <p><?php echo __('Browse verified, licensed professionals — each one ready to support your journey in a private, judgment-free space.'); ?></p>
        <div class="dir-search">
            <input type="text" id="dirSearch" placeholder="<?php echo __('Search by name or specialty…'); ?>" oninput="filterCards()">
            <button><i class="fas fa-search"></i></button>
        </div>
    </div>

    <!-- Filters -->
    <?php
    $desired_specs = [
        'Depression',
        'Anxiety & Stress',
        'Family Conflicts',
        'Relationship Issues',
        'Trauma'
    ];
    ?>
    <div class="filter-row" id="filterRow">
        <button class="filter-chip active" data-spec="all" onclick="setFilter('all',this)">
            <?php echo __('All'); ?> <span class="filter-count"><?php echo count($therapists); ?></span>
        </button>
        <?php foreach ($desired_specs as $s): ?>
        <button class="filter-chip" data-spec="<?php echo htmlspecialchars(strtolower($s)); ?>" onclick="setFilter('<?php echo htmlspecialchars(addslashes(strtolower($s))); ?>',this)">
            <?php echo htmlspecialchars(__($s)); ?>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- Grid -->
    <?php if (empty($therapists)): ?>
    <div class="dir-empty">
        <i class="fas fa-user-md"></i>
        <p><?php echo __('No verified therapists found yet. Check back soon.'); ?></p>
    </div>
    <?php else: ?>
    <div class="therapist-grid-new" id="therapistGrid">
        <?php foreach ($therapists as $t):
            $name = 'Dr. ' . $t['first_name'] . ' ' . $t['last_name'];
            $initials = strtoupper(substr($t['first_name'],0,1) . substr($t['last_name'],0,1));
            $specs_raw = json_decode($t['specialties'] ?? '', true);
            if (!is_array($specs_raw)) $specs_raw = array_map('trim', explode(',', $t['specialties'] ?? ''));
            $specs_raw = array_values(array_filter($specs_raw));
            
            // Normalize specialties
            $normalized_specs = [];
            foreach ($specs_raw as $sp) {
                $norm = normalize_specialty($sp);
                if (!empty($norm)) {
                    $normalized_specs[] = $norm;
                }
            }
            $normalized_specs = array_unique($normalized_specs);
            
            $rating = floatval($t['rating'] ?? 5);
            $stars = str_repeat('★', min(5, round($rating))) . str_repeat('☆', max(0,5-round($rating)));
            $egp = number_format($t['hourly_rate'] ?? 0, 0);
            
            // Encode as json of lowercase normalized specs for filter matching
            $specs_json = htmlspecialchars(json_encode(array_map('strtolower', $normalized_specs)));
        ?>
        <div class="tc-new"
             data-name="<?php echo htmlspecialchars(strtolower($name)); ?>"
             data-specs="<?php echo $specs_json; ?>"
             onclick="window.location='public_therapist_cv.php?id=<?php echo $t['user_id']; ?>'">
            <div class="tc-banner"></div>
            <div class="tc-body">
                <div class="tc-top">
                    <?php if (!empty($t['profile_image'])): ?>
                        <img src="<?php echo htmlspecialchars($t['profile_image']); ?>" alt="<?php echo htmlspecialchars($name); ?>" class="tc-photo">
                    <?php else: ?>
                        <div class="tc-photo-placeholder"><?php echo $initials; ?></div>
                    <?php endif; ?>
                    <div class="tc-name-area">
                        <div class="tc-verified"><i class="fas fa-check-circle"></i> <?php echo __('Verified'); ?></div>
                        <div class="tc-fullname"><?php echo htmlspecialchars($name); ?></div>
                        <div class="tc-title"><?php echo __('Licensed Therapist'); ?> &bull; <?php echo intval($t['years_experience']); ?> <?php echo __('years experience'); ?></div>
                        <div class="tc-stars">
                             <span class="stars-el"><?php echo $stars; ?></span>
                             <span class="tc-rcount"><?php echo number_format($rating,1); ?> (<?php echo intval($t['review_count']); ?>)</span>
                        </div>
                    </div>
                </div>

                <div class="tc-bio-snip"><?php echo htmlspecialchars($t['bio'] ?: 'Compassionate, evidence-based therapy tailored to your unique needs.'); ?></div>

                <div class="tc-tags">
                    <?php foreach (array_slice($normalized_specs, 0, 4) as $sp): ?>
                    <span class="tc-tag-green"><?php echo htmlspecialchars(__($sp)); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="tc-footer-new">
                <div class="tc-price-block">
                    <div class="tc-price-egp"><?php echo $egp; ?> EGP</div>
                    <div class="tc-price-label"><?php echo __('per 60 min session'); ?></div>
                </div>
                <a href="public_therapist_cv.php?id=<?php echo $t['user_id']; ?>"
                   class="btn-view-profile"
                   onclick="event.stopPropagation()"><?php echo __('View Profile'); ?> →</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
        </div>
        <?php require_once 'includes/footer.php'; ?>
    </div>
</div>

<script>
let activeSpec = 'all';

function setFilter(spec, btn) {
    activeSpec = spec;
    document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
    btn.classList.add('active');
    filterCards();
}

function filterCards() {
    const q = document.getElementById('dirSearch').value.toLowerCase().trim();
    document.querySelectorAll('.tc-new').forEach(card => {
        const nameMatch  = card.dataset.name.includes(q);
        let specsList = [];
        try {
            specsList = JSON.parse(card.dataset.specs || "[]");
        } catch (e) {
            specsList = card.dataset.specs ? card.dataset.specs.split(' ') : [];
        }
        const filterPass = activeSpec === 'all' || specsList.includes(activeSpec.toLowerCase());
        const specMatch  = specsList.some(s => s.includes(q));
        const searchPass = q === '' || nameMatch || specMatch;
        card.style.display = (filterPass && searchPass) ? '' : 'none';
    });
}
</script>

<?php require_once 'includes/sidebar_toggle_script.php'; ?>