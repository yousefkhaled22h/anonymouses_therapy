<?php
// challenges.php
require_once 'includes/header.php';
require_once 'includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'Client';

if ($user_role !== 'Client') {
    header("Location: index.php");
    exit();
}

$display_name = $_SESSION['name'] ?? 'User';
$client_id = $_SESSION['client_id'] ?? null;

if (!$client_id) {
    $stmt = $pdo->prepare("SELECT client_id FROM client WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $client_id = $stmt->fetchColumn();
    $_SESSION['client_id'] = $client_id;
}

// Auto-renew challenges every 24 hours (reset completed challenges older than today)
try {
    $stmt = $pdo->prepare("DELETE FROM completed_challenges WHERE DATE(completed_at) < CURRENT_DATE() AND client_id = ?");
    $stmt->execute([$client_id]);
} catch (PDOException $e) {
    // Fail silently
}

// Fetch current points
$stmt = $pdo->prepare("SELECT points FROM client WHERE client_id = ?");
$stmt->execute([$client_id]);
$current_points = $stmt->fetchColumn() ?: 0;

// Fetch challenges
$stmt = $pdo->prepare("
    SELECT c.*, 
    (SELECT COUNT(*) FROM completed_challenges cc WHERE cc.challenge_id = c.challenge_id AND cc.client_id = ?) as is_done
    FROM daily_challenges c
    WHERE c.client_id IS NULL OR c.client_id = ?
");
$stmt->execute([$client_id, $client_id]);
$challenges = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($challenges)) {
    $default_challenges = [
        ['ch_01', 'Drink Water', 'Drink 8 glasses of water today.', 'Easy', 10],
        ['ch_02', 'Deep Breathing', 'Perform 5 minutes of deep breathing.', 'Easy', 10],
        ['ch_03', 'Walk 4,000 steps', 'Walk a total of 4,000 steps today.', 'Medium', 20],
        ['ch_04', 'Note 3 daily wins', 'Write down three things you achieved today.', 'Easy', 10],
        ['ch_05', 'Do 20 air squats', 'Complete 20 repetitions of air squats.', 'Medium', 15],
        ['ch_06', 'Read 5 book pages', 'Read at least 5 pages of a book.', 'Easy', 10],
        ['ch_07', 'Mindfulness (10 mins)', 'Practice mindfulness or meditation for 10 minutes.', 'Medium', 20],
        ['ch_08', 'Call a relative (1 time)', 'Reach out to a relative for a quick chat.', 'Medium', 15],
        ['ch_09', 'Tidy your desk (1 time)', 'Organize and clean your workspace.', 'Easy', 10],
        ['ch_10', 'Eat 2 fruit pieces', 'Include two portions of fruit in your diet.', 'Easy', 10]
    ];
    try {
        $insert_stmt = $pdo->prepare("INSERT IGNORE INTO daily_challenges (challenge_id, title, description, difficulty, points) VALUES (?, ?, ?, ?, ?)");
        foreach ($default_challenges as $c) {
            $insert_stmt->execute($c);
        }
        $stmt->execute([$client_id]);
        $challenges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Fail silently
    }
}

require_once 'includes/dashboard_components.php';
?>

<link rel="stylesheet" href="assets/css/dashboard-style.css">


<div class="dashboard-wrapper">
    <?php render_sidebar('challenges'); ?>
    <div class="dashboard-main">
        <div class="container-fluid">
            <!-- Sidebar toggle button for mobile/desktop -->
            <div class="dashboard-header-row" style="display: flex; gap: 20px; align-items: stretch; margin-bottom: 30px;">
                <button id="sidebarToggle" class="sidebar-toggle-inline" aria-label="Toggle Sidebar" style="align-self: flex-start; margin-top: 10px;">
                    <i id="toggleIcon" class="fa-solid fa-bars-staggered"></i>
                </button>
            </div>
        <div style="text-align: center; margin-bottom: 40px;">
            <h1 style="font-size: 2.2rem; color: #1e293b; font-weight: 800;"><?php echo __("Daily Challenges 🏆"); ?></h1>
            <p style="color: #64748b; font-size: 1.05rem;"><?php echo __("Complete challenges to earn wellness points and grow your streak!"); ?></p>
        </div>


            <!-- Points Counter -->
            <div class="points-banner animate-up">
                <div class="points-label"><?php echo __('Total Wellness Points'); ?></div>
                <div class="points-value" id="totalPoints"><?php echo number_format($current_points); ?></div>
                <div class="points-icon"><i class="fas fa-star"></i></div>
            </div>

            <div class="challenges-grid">
                <?php foreach ($challenges as $c): ?>
                    <?php 
                        $is_done = $c['is_done'] > 0;
                        $difficulty_class = strtolower($c['difficulty']);
                        $emoji = '';
                        // Basic emoji mapping based on title
                        if (stripos($c['title'], 'water') !== false) $emoji = '💧';
                        elseif (stripos($c['title'], 'breath') !== false) $emoji = '🧘';
                        elseif (stripos($c['title'], 'walk') !== false) $emoji = '🚶';
                        elseif (stripos($c['title'], 'wins') !== false || stripos($c['title'], 'gratitude') !== false) $emoji = '✍️';
                        elseif (stripos($c['title'], 'squats') !== false || stripos($c['title'], 'jumping') !== false) $emoji = '🤸';
                        elseif (stripos($c['title'], 'read') !== false) $emoji = '📖';
                        elseif (stripos($c['title'], 'mindful') !== false) $emoji = '🧘';
                        elseif (stripos($c['title'], 'call') !== false || stripos($c['title'], 'text') !== false) $emoji = '📞';
                        elseif (stripos($c['title'], 'tidy') !== false || stripos($c['title'], 'clean') !== false || stripos($c['title'], 'declutter') !== false) $emoji = '🧹';
                        elseif (stripos($c['title'], 'fruit') !== false || stripos($c['title'], 'sugar') !== false || stripos($c['title'], 'vegetable') !== false) $emoji = '🍎';
                        elseif (stripos($c['title'], 'stairs') !== false) $emoji = '🪜';
                        elseif (stripos($c['title'], 'tea') !== false) $emoji = '🍵';
                        elseif (stripos($c['title'], 'sunlight') !== false) $emoji = '☀️';
                        elseif (stripos($c['title'], 'caffeine') !== false) $emoji = '☕';
                        elseif (stripos($c['title'], 'podcast') !== false) $emoji = '🎧';
                        elseif (stripos($c['title'], 'compliment') !== false || stripos($c['title'], 'smile') !== false) $emoji = '😊';
                        elseif (stripos($c['title'], 'soda') !== false) $emoji = '🥤';
                        elseif (stripos($c['title'], 'stretch') !== false) $emoji = '🧘';
                        elseif (stripos($c['title'], 'plan') !== false) $emoji = '📝';
                        elseif (stripos($c['title'], 'phone') !== false) $emoji = '📵';
                        elseif (stripos($c['title'], 'nothing') !== false) $emoji = '🤫';
                        else $emoji = '✨';
                    ?>
                    <div class="challenge-card animate-up <?php echo $is_done ? 'is-completed' : ''; ?>" id="card-<?php echo $c['challenge_id']; ?>">
                        <div class="challenge-header">
                            <span class="difficulty-badge badge-<?php echo $difficulty_class; ?>"><?php echo __($c['difficulty']); ?></span>
                            <span class="points-badge">+<?php echo $c['points']; ?> <?php echo __('pts'); ?></span>
                        </div>
                        <div class="challenge-content">
                            <div class="challenge-emoji"><?php echo $emoji; ?></div>
                            <h3 class="challenge-title"><?php echo htmlspecialchars(__($c['title'])); ?></h3>
                            <p class="challenge-desc"><?php echo htmlspecialchars(__($c['description'])); ?></p>
                        </div>
                        <div class="challenge-footer">
                            <?php if ($is_done): ?>
                                <button class="btn btn-done" disabled><i class="fas fa-check-circle"></i> <?php echo __('Completed'); ?></button>
                            <?php else: ?>
                                <button class="btn btn-complete" onclick="completeChallenge('<?php echo $c['challenge_id']; ?>', this, <?php echo $c['points']; ?>)"><?php echo __('Mark as Done'); ?></button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Custom "Other" Challenge Card -->
                <div class="challenge-card other-card animate-up" id="card-custom-other">
                    <div class="challenge-header">
                        <span class="difficulty-badge badge-custom"><?php echo __('Custom'); ?></span>
                        <span class="points-badge">+10 <?php echo __('pts'); ?></span>
                    </div>
                    <div class="challenge-content">
                        <div class="challenge-emoji">💡</div>
                        <h3 class="challenge-title"><?php echo __('Other'); ?></h3>
                        <p class="challenge-desc"><?php echo __('Did something positive not on the list? Write it down and earn points!'); ?></p>
                        <textarea id="customChallengeText" class="custom-input" placeholder="<?php echo __('e.g. Helped a neighbor, cooked a healthy meal...'); ?>" rows="3"></textarea>
                    </div>
                    <div class="challenge-footer">
                        <button class="btn btn-complete" id="customSubmitBtn" onclick="submitCustomChallenge()"><?php echo __('Submit &amp; Earn 10 pts'); ?></button>
                    </div>
                </div>
        </div>
        <?php require_once 'includes/footer.php'; ?>
    </div>
</div>
<?php require_once 'includes/sidebar_toggle_script.php'; ?>

<link rel="stylesheet" href="assets/css/dashboard-style.css">
<style>
    .menu-toggle-btn {
        display: none !important;
    }
    .points-banner {
        background: linear-gradient(135deg, #A68A6C 0%, #8D735B 100%);
        color: white;
        padding: 30px;
        border-radius: 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        margin-bottom: 40px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 10px 25px rgba(166, 138, 108, 0.3);
        width: 100%;
        max-width: 800px;
        margin-left: auto;
        margin-right: auto;
    }

    .points-label {
        font-size: 1rem;
        text-transform: uppercase;
        letter-spacing: 2px;
        font-weight: 700;
        opacity: 0.9;
        margin-bottom: 10px;
    }

    .points-value {
        font-size: 4rem;
        font-weight: 800;
        line-height: 1;
    }

    .points-icon {
        position: absolute;
        right: -20px;
        bottom: -20px;
        font-size: 8rem;
        opacity: 0.1;
        transform: rotate(-15deg);
    }

    .challenges-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 25px;
        width: 100%;
        max-width: 1200px;
        margin-bottom: 50px;
    }

    .challenge-card {
        background: white;
        border-radius: 20px;
        padding: 25px;
        display: flex;
        flex-direction: column;
        transition: all 0.3s ease;
        border: 1px solid #e2e8f0;
        position: relative;
    }

    .challenge-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.08);
    }

    .challenge-card.is-completed {
        background: #f8fafc;
        border-color: #cbd5e1;
        opacity: 0.8;
    }

    .challenge-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20px;
    }

    .difficulty-badge {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        padding: 4px 10px;
        border-radius: 50px;
    }

    .badge-easy { background: #dcfce7; color: #166534; }
    .badge-medium { background: #fef9c3; color: #854d0e; }
    .badge-hard { background: #fee2e2; color: #991b1b; }

    .points-badge {
        font-size: 0.85rem;
        font-weight: 700;
        color: #A68A6C;
    }

    .challenge-content {
        text-align: center;
        flex: 1;
    }

    .challenge-emoji {
        font-size: 3rem;
        margin-bottom: 15px;
    }

    .challenge-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 10px;
    }

    .challenge-desc {
        font-size: 0.9rem;
        color: #64748b;
        line-height: 1.5;
    }

    .challenge-footer {
        margin-top: 25px;
    }

    .btn-complete {
        width: 100%;
        background: white;
        color: #A68A6C;
        border: 2px solid #A68A6C;
        padding: 12px;
        border-radius: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-complete:hover {
        background: #A68A6C;
        color: white;
    }

    .btn-done {
        width: 100%;
        background: #22c55e;
        color: white;
        border: 2px solid #22c55e;
        padding: 12px;
        border-radius: 12px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    /* Custom Other Card */
    .other-card {
        border: 2px dashed #A68A6C;
        background: #fdfaf5;
    }

    .other-card:hover {
        border-style: solid;
    }

    .badge-custom {
        background: #fdf6ee;
        color: #A68A6C;
    }

    .custom-input {
        width: 100%;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 12px;
        font-family: inherit;
        font-size: 0.9rem;
        resize: none;
        margin-top: 15px;
        outline: none;
        transition: border-color 0.2s;
        box-sizing: border-box;
    }

    .custom-input:focus {
        border-color: #A68A6C;
        box-shadow: 0 0 0 3px rgba(166, 138, 108, 0.15);
    }

    @media (max-width: 768px) {
        .points-value { font-size: 3rem; }
    }
</style>

<script>
    function completeChallenge(challengeId, btn, points) {
        if (!confirm("Did you complete this challenge?")) return;

        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        btn.disabled = true;

        fetch('api/challenges/complete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ challenge_id: challengeId })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Update Points UI
                const pointsDisplay = document.getElementById('totalPoints');
                const current = parseInt(pointsDisplay.textContent.replace(/,/g, ''));
                const newValue = current + points;
                
                // Animate points
                animateValue(pointsDisplay, current, newValue, 1000);

                // Update Card UI
                const card = document.getElementById('card-' + challengeId);
                card.classList.add('is-completed');
                btn.className = 'btn btn-done';
                btn.innerHTML = '<i class="fas fa-check-circle"></i> Completed';
                
                // Fire confetti if available or alert
                alert("Great job! You earned " + points + " wellness points!");
            } else {
                alert(data.message || "Error completing challenge.");
                btn.innerHTML = 'Mark as Done';
                btn.disabled = false;
            }
        })
        .catch(err => {
            console.error(err);
            alert("Something went wrong.");
            btn.innerHTML = 'Mark as Done';
            btn.disabled = false;
        });
    }

    function submitCustomChallenge() {
        const text = document.getElementById('customChallengeText').value.trim();
        if (!text) {
            alert('Please describe what you did before submitting.');
            return;
        }
        if (text.length < 3) {
            alert('Please write a bit more about what you did.');
            return;
        }

        const btn = document.getElementById('customSubmitBtn');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        btn.disabled = true;

        fetch('api/challenges/complete_custom.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ description: text })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const pointsDisplay = document.getElementById('totalPoints');
                const current = parseInt(pointsDisplay.textContent.replace(/,/g, ''));
                animateValue(pointsDisplay, current, current + 10, 1000);

                document.getElementById('customChallengeText').value = '';
                btn.className = 'btn btn-done';
                btn.innerHTML = '<i class="fas fa-check-circle"></i> Submitted!';

                setTimeout(() => {
                    btn.className = 'btn btn-complete';
                    btn.innerHTML = 'Submit &amp; Earn 10 pts';
                    btn.disabled = false;
                }, 3000);

                alert('Awesome! You earned 10 wellness points for: "' + text + '"');
            } else {
                alert(data.message || 'Error submitting custom challenge.');
                btn.innerHTML = 'Submit &amp; Earn 10 pts';
                btn.disabled = false;
            }
        })
        .catch(err => {
            console.error(err);
            alert('Something went wrong.');
            btn.innerHTML = 'Submit &amp; Earn 10 pts';
            btn.disabled = false;
        });
    }

    function animateValue(obj, start, end, duration) {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            obj.innerHTML = Math.floor(progress * (end - start) + start).toLocaleString();
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    }

    // --- Dashboard Core JS ---
    document.addEventListener('DOMContentLoaded', () => {

        // Animate elements on load
        document.querySelectorAll('.animate-up').forEach((el, index) => {
            setTimeout(() => {
                el.classList.add('visible');
            }, index * 100);
        });
    });

    // ... existing challenges logic ...

</script>
