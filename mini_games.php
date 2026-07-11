<?php
require_once 'includes/header.php';
require_once 'includes/dashboard_components.php';
?>

<link rel="stylesheet" href="assets/css/dashboard-style.css">
<link rel="stylesheet" href="assets/css/mini-games.css">


<?php
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Client') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$client_id = $_SESSION['client_id'] ?? null;

// Fetch last safe space design
$stmt = $pdo->prepare("SELECT payload FROM client_activity_log WHERE user_id = ? AND activity_type = 'user_safe_space' ORDER BY updated_at DESC LIMIT 1");
$stmt->execute([$user_id]);
$safe_space_data = $stmt->fetchColumn();
$safe_space_json = $safe_space_data ? $safe_space_data : '[]';

// Fetch random thoughts for the catch game
$stmt = $pdo->query("SELECT * FROM game_negative_thoughts ORDER BY RAND() LIMIT 10");
$thoughts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="dashboard-wrapper">
    <?php render_sidebar('games'); ?>
    <div class="dashboard-main">
        <div class="container-fluid">
            <!-- Sidebar toggle button for mobile/desktop -->
            <div class="dashboard-header-row" style="display: flex; gap: 20px; align-items: stretch; margin-bottom: 30px;">
                <button id="sidebarToggle" class="sidebar-toggle-inline" aria-label="Toggle Sidebar" style="align-self: flex-start; margin-top: 10px;">
                    <i id="toggleIcon" class="fa-solid fa-bars-staggered"></i>
                </button>
            </div>
        <div style="text-align: center; margin-bottom: 40px;">
            <h1 style="font-size: 2.2rem; color: #1e293b; font-weight: 800;"><?php echo __("Wellness Games Hub"); ?></h1>
            <p style="color: #64748b; font-size: 1.05rem;"><?php echo __("Take a moment for yourself. Choose a tool that matches your current need."); ?></p>
        </div>
        <div class="games-container">
            <div class="mood-prompt-card" id="moodPrompt">
                <h2>How are you feeling today?</h2>
                <div class="mood-options">
                    <button class="mood-btn" onclick="suggestGame('Anxious')">Anxious 😰</button>
                    <button class="mood-btn" onclick="suggestGame('Stressed')">Stressed 😤</button>
                    <button class="mood-btn" onclick="suggestGame('Sad')">Sad 😔</button>
                    <button class="mood-btn" onclick="suggestGame('Overwhelmed')">Overwhelmed 🌊</button>
                    <button class="mood-btn" onclick="suggestGame('Disconnected')">Disconnected 🌫️</button>
                    <button class="mood-btn" onclick="suggestGame('Tense')">Tense 🧱</button>
                    <button class="mood-btn" onclick="suggestGame('Fine')">I'm okay ✨</button>
                </div>
            </div>

            <div class="game-grid" id="gameGrid">
                <div class="game-card" onclick="openGame('breathing')">
                    <div class="game-icon">🧘</div>
                    <h3>Breathing Sync</h3>
                    <p>Calm your nervous system with guided rhythmic breathing patterns.</p>
                    <button class="btn-play">Start Breathing</button>
                </div>

                <div class="game-card" onclick="openGame('thought-catch')">
                    <div class="game-icon">🫧</div>
                    <h3>Catch & Release</h3>
                    <p>Catch negative thoughts and watch them transform into positive affirmations.</p>
                    <button class="btn-play">Let Go</button>
                </div>

                <div class="game-card" onclick="openGame('safe-space')">
                    <div class="game-icon">🏡</div>
                    <h3>Virtual Safe Space</h3>
                    <p>Build your own serene environment by placing calming elements.</p>
                    <button class="btn-play">Build Space</button>
                </div>

                <div class="game-card" onclick="openGame('grounding')">
                    <div class="game-icon">🌍</div>
                    <h3>5-4-3-2-1 Grounding</h3>
                    <p>Reconnect with the present moment through your five senses.</p>
                    <button class="btn-play">Ground Myself</button>
                </div>

                <div class="game-card" onclick="openGame('coloring')">
                    <div class="game-icon">🎨</div>
                    <h3>Color Therapy</h3>
                    <p>Express yourself and find focus through gentle pattern coloring.</p>
                    <button class="btn-play">Start Coloring</button>
                </div>

                <div class="game-card" onclick="openGame('flow')">
                    <div class="game-icon">🍃</div>
                    <h3>Endless Flow</h3>
                    <p>Guide a gentle leaf through a peaceful, infinite breeze.</p>
                    <button class="btn-play">Enter Flow</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Breathing Game Overlay -->
<div id="overlay-breathing" class="game-overlay">
    <span class="close-game" onclick="closeGame()">&times;</span>
    <h2 style="margin-bottom: 50px; color: #0369a1;">Breathing Sync</h2>
    
    <div id="breathing-circle">
        <div id="breath-text">Prepare...</div>
    </div>

    <div style="margin-top: 60px; display: flex; gap: 20px;">
        <button class="mood-btn" onclick="startBreathing('box')">Box Breathing (4-4-4-4)</button>
        <button class="mood-btn" onclick="startBreathing('478')">4-7-8 Technique</button>
    </div>
</div>

<!-- Thought Catch Overlay -->
<div id="overlay-thought-catch" class="game-overlay">
    <span class="close-game" onclick="closeGame()">&times;</span>
    <h2 style="margin-bottom: 20px;">Thought Catch & Release</h2>
    <p style="margin-bottom: 20px; color: #64748b;">Tap the bubbles to release negative thoughts.</p>
    <div id="thought-canvas"></div>
</div>

<!-- Safe Space Overlay -->
<div id="overlay-safe-space" class="game-overlay">
    <span class="close-game" onclick="closeGame()">&times;</span>
    <h2 style="margin-bottom: 20px;">Your Virtual Safe Space</h2>
    
    <div id="safe-space-builder">
        <div class="item-library">
            <p style="font-weight: 700; margin-bottom: 10px;">Library</p>
            <div class="library-item" draggable="true" data-emoji="🌳">🌳</div>
            <div class="library-item" draggable="true" data-emoji="🌊">🌊</div>
            <div class="library-item" draggable="true" data-emoji="🛋️">🛋️</div>
            <div class="library-item" draggable="true" data-emoji="🔥">🔥</div>
            <div class="library-item" draggable="true" data-emoji="🐱">🐱</div>
            <div class="library-item" draggable="true" data-emoji="🕯️">🕯️</div>
            <div class="library-item" draggable="true" data-emoji="🏔️">🏔️</div>
            <div class="library-item" draggable="true" data-emoji="☕">☕</div>
            <button class="btn-play" onclick="saveSafeSpace()" style="width: 100%; margin-top: 20px;">Save Space</button>
        </div>
        <div class="safe-area" id="safeArea"></div>
    </div>
</div>

<!-- Grounding Overlay -->
<div id="overlay-grounding" class="game-overlay">
    <span class="close-game" onclick="closeGame()">&times;</span>
    
    <div id="grounding-steps-container">
        <!-- Step 5: See -->
        <div class="grounding-step active" data-step="5">
            <div class="grounding-number">5</div>
            <div class="grounding-instruction">Things you can <strong>see</strong> right now</div>
            <div class="grounding-input-group">
                <input type="text" class="grounding-input" placeholder="1. ...">
                <input type="text" class="grounding-input" placeholder="2. ...">
                <input type="text" class="grounding-input" placeholder="3. ...">
                <input type="text" class="grounding-input" placeholder="4. ...">
                <input type="text" class="grounding-input" placeholder="5. ...">
            </div>
            <button class="btn-play" style="margin-top: 30px;" onclick="nextGroundingStep()">Next Step</button>
        </div>
        <!-- Step 4: Feel -->
        <div class="grounding-step" data-step="4">
            <div class="grounding-number">4</div>
            <div class="grounding-instruction">Things you can <strong>feel</strong> (touch)</div>
            <div class="grounding-input-group">
                <input type="text" class="grounding-input" placeholder="1. ...">
                <input type="text" class="grounding-input" placeholder="2. ...">
                <input type="text" class="grounding-input" placeholder="3. ...">
                <input type="text" class="grounding-input" placeholder="4. ...">
            </div>
            <button class="btn-play" style="margin-top: 30px;" onclick="nextGroundingStep()">Next Step</button>
        </div>
        <!-- Step 3: Hear -->
        <div class="grounding-step" data-step="3">
            <div class="grounding-number">3</div>
            <div class="grounding-instruction">Things you can <strong>hear</strong></div>
            <div class="grounding-input-group">
                <input type="text" class="grounding-input" placeholder="1. ...">
                <input type="text" class="grounding-input" placeholder="2. ...">
                <input type="text" class="grounding-input" placeholder="3. ...">
            </div>
            <button class="btn-play" style="margin-top: 30px;" onclick="nextGroundingStep()">Next Step</button>
        </div>
        <!-- Step 2: Smell -->
        <div class="grounding-step" data-step="2">
            <div class="grounding-number">2</div>
            <div class="grounding-instruction">Things you can <strong>smell</strong></div>
            <div class="grounding-input-group">
                <input type="text" class="grounding-input" placeholder="1. ...">
                <input type="text" class="grounding-input" placeholder="2. ...">
            </div>
            <button class="btn-play" style="margin-top: 30px;" onclick="nextGroundingStep()">Next Step</button>
        </div>
        <!-- Step 1: Taste -->
        <div class="grounding-step" data-step="1">
            <div class="grounding-number">1</div>
            <div class="grounding-instruction">Thing you can <strong>taste</strong></div>
            <div class="grounding-input-group">
                <input type="text" class="grounding-input" placeholder="1. ...">
            </div>
            <button class="btn-play" style="margin-top: 30px;" onclick="finishGrounding()">Complete Reflection</button>
        </div>
        <!-- Completion -->
        <div class="grounding-step" id="grounding-complete">
            <div class="game-icon">✨</div>
            <div class="grounding-instruction">You are grounded in the present moment.</div>
            <p style="color: #64748b;">Take a deep breath. You are safe.</p>
            <button class="btn-play" style="margin-top: 30px;" onclick="closeGame()">Return to Games</button>
        </div>
    </div>
</div>

<!-- Coloring Overlay -->
<div id="overlay-coloring" class="game-overlay">
    <span class="close-game" onclick="closeGame()">&times;</span>
    <h2 style="margin-bottom: 20px;">Color Therapy</h2>
    
    <div id="coloring-tabs" style="display: flex; gap: 20px; margin-bottom: 20px;">
        <button class="mood-btn active" onclick="showColoringTab('new')">New Designs</button>
        <button class="mood-btn" onclick="showColoringTab('gallery')">My Gallery</button>
    </div>

    <div id="coloring-new-designs" class="coloring-tab-content">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px; width: 100%; max-width: 800px;">
            <div class="game-card" style="padding: 15px;" onclick="startNewColoring('mandala')">
                <div style="font-size: 2rem;">☸️</div>
                <p style="margin: 5px 0;">Mandala</p>
            </div>
            <div class="game-card" style="padding: 15px;" onclick="startNewColoring('lotus')">
                <div style="font-size: 2rem;">🪷</div>
                <p style="margin: 5px 0;">Lotus</p>
            </div>
            <div class="game-card" style="padding: 15px;" onclick="startNewColoring('star')">
                <div style="font-size: 2rem;">⭐</div>
                <p style="margin: 5px 0;">Geometric Star</p>
            </div>
            <div class="game-card" style="padding: 15px;" onclick="startNewColoring('waves')">
                <div style="font-size: 2rem;">🌊</div>
                <p style="margin: 5px 0;">Ocean Waves</p>
            </div>
        </div>
    </div>

    <div id="coloring-gallery" class="coloring-tab-content" style="display: none; width: 100%; max-width: 800px;">
        <div id="gallery-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 20px;">
            <!-- Gallery items will be added here -->
        </div>
    </div>

    <div id="coloring-workspace" class="coloring-tab-content" style="display: none; width: 100%; height: 70vh;">
        <div id="coloring-container">
            <div class="palette" id="colorPalette"></div>
            <div id="mandala-container" class="mandala-svg"></div>
        </div>
        <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: center;">
            <button class="mood-btn" onclick="saveArtwork()">Save Project</button>
            <button class="mood-btn" onclick="showColoringTab('new')">Back to Library</button>
        </div>
    </div>
        </div>
    </div>
</div>

<!-- Flow Overlay -->
<div id="overlay-flow" class="game-overlay">
    <span class="close-game" onclick="closeGame()">&times;</span>
    <h2 style="margin-bottom: 20px; color: #166534;">Endless Flow</h2>
    <p style="margin-bottom: 10px; color: #64748b;">Gently guide the leaf with your cursor. No rush, just flow.</p>
    <div style="margin-bottom: 20px;">
        <button class="mood-btn" onclick="toggleSound()" id="soundToggle">🔊 Ambient Sound: On</button>
    </div>
    <canvas id="flow-canvas"></canvas>
</div>

<script>
    let sessionStartTime = null;
    let currentGame = null;

    function suggestGame(mood) {
        const grid = document.getElementById('gameGrid');
        grid.classList.add('visible');
        
        const cards = document.querySelectorAll('.game-card');
        cards.forEach(c => c.style.border = '1px solid #f1f5f9');

        if (mood === 'Anxious') {
            cards[0].style.borderColor = 'var(--accent-primary)';
            cards[0].style.background = '#f0f9ff';
        } else if (mood === 'Stressed' || mood === 'Overwhelmed') {
            cards[1].style.borderColor = 'var(--accent-primary)';
            cards[1].style.background = '#fef2f2';
        } else if (mood === 'Disconnected') {
            cards[3].style.borderColor = 'var(--accent-primary)';
            cards[3].style.background = '#fdfaf5';
        } else if (mood === 'Tense') {
            cards[5].style.borderColor = 'var(--accent-primary)';
            cards[5].style.background = '#f0fdf4';
        } else {
            cards[2].style.borderColor = 'var(--accent-primary)';
            cards[2].style.background = '#f0fdf4';
        }
    }

    function openGame(gameId) {
        document.getElementById('overlay-' + gameId).style.display = 'flex';
        sessionStartTime = new Date();
        currentGame = gameId;

        if (gameId === 'thought-catch') initThoughtGame();
        if (gameId === 'safe-space') initSafeSpace();
        if (gameId === 'grounding') initGrounding();
        if (gameId === 'coloring') initColoring();
        if (gameId === 'flow') initFlow();
    }

    function closeGame() {
        if (currentGame) {
            const duration = Math.floor((new Date() - sessionStartTime) / 1000);
            saveSession(currentGame, duration);
        }
        document.querySelectorAll('.game-overlay').forEach(o => o.style.display = 'none');
        resetBreathing();
        currentGame = null;
    }

    function saveSession(type, duration) {
        if (duration < 5) return; // Don't save very short sessions
        fetch('api/games/save_session.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ game_type: type, duration: duration })
        });
    }

    // --- Breathing Game Logic ---
    let breathInterval = null;
    function resetBreathing() {
        clearInterval(breathInterval);
        const circle = document.getElementById('breathing-circle');
        circle.style.transform = 'scale(1)';
        document.getElementById('breath-text').textContent = 'Prepare...';
    }

    function startBreathing(mode) {
        resetBreathing();
        const circle = document.getElementById('breathing-circle');
        const text = document.getElementById('breath-text');
        
        let step = 0;
        const patterns = {
            'box': [
                { text: 'Inhale...', scale: 1.8, time: 4000 },
                { text: 'Hold...', scale: 1.8, time: 4000 },
                { text: 'Exhale...', scale: 1.0, time: 4000 },
                { text: 'Hold...', scale: 1.0, time: 4000 }
            ],
            '478': [
                { text: 'Inhale...', scale: 1.8, time: 4000 },
                { text: 'Hold...', scale: 1.8, time: 7000 },
                { text: 'Exhale...', scale: 1.0, time: 8000 }
            ]
        };

        const pattern = patterns[mode];
        function nextStep() {
            const p = pattern[step];
            text.textContent = p.text;
            circle.style.transition = `transform ${p.time}ms ease-in-out`;
            circle.style.transform = `scale(${p.scale})`;
            
            setTimeout(() => {
                step = (step + 1) % pattern.length;
                nextStep();
            }, p.time);
        }
        nextStep();
    }

    // --- Thought Catch Logic ---
    const thoughtsData = <?php echo json_encode($thoughts); ?>;
    let currentThoughtIndex = 0;
    function initThoughtGame() {
        const canvas = document.getElementById('thought-canvas');
        canvas.innerHTML = '';
        currentThoughtIndex = 0;
        
        if (thoughtsData.length > 0) {
            createBubble(thoughtsData[currentThoughtIndex]);
        }
    }

    function createBubble(thought) {
        const canvas = document.getElementById('thought-canvas');
        if (!canvas) return;

        const b = document.createElement('div');
        b.className = 'thought-bubble';
        b.textContent = thought.thought_text;
        
        // Random position
        let x = Math.random() * (canvas.offsetWidth - 150);
        let y = Math.random() * (canvas.offsetHeight - 100);
        b.style.left = x + 'px';
        b.style.top = y + 'px';

        b.onclick = () => {
            if (b.classList.contains('clicked')) return;
            b.classList.add('clicked');

            b.style.background = '#dcfce7';
            b.textContent = thought.positive_transformation;
            b.style.transform = 'scale(1.2)';

            // Trigger next bubble after 2 seconds
            currentThoughtIndex++;
            if (currentThoughtIndex < thoughtsData.length) {
                setTimeout(() => {
                    createBubble(thoughtsData[currentThoughtIndex]);
                }, 2000);
            }

            setTimeout(() => {
                b.style.opacity = '0';
                setTimeout(() => b.remove(), 500);
            }, 2000);
        };

        canvas.appendChild(b);

        // Simple floating animation
        let dx = (Math.random() - 0.5) * 2;
        let dy = (Math.random() - 0.5) * 2;
        
        function move() {
            if (!b.parentNode) return;
            x += dx;
            y += dy;
            if (x < 0 || x > canvas.offsetWidth - 150) dx *= -1;
            if (y < 0 || y > canvas.offsetHeight - 100) dy *= -1;
            b.style.left = x + 'px';
            b.style.top = y + 'px';
            requestAnimationFrame(move);
        }
        move();
    }

    // --- Safe Space Logic ---
    let safeSpaceDesign = <?php echo $safe_space_json; ?>;
    function initSafeSpace() {
        const area = document.getElementById('safeArea');
        area.innerHTML = '';
        safeSpaceDesign.forEach(item => {
            renderItem(item.emoji, item.x, item.y);
        });

        // Drag and drop setup
        document.querySelectorAll('.library-item').forEach(item => {
            item.ondragstart = (e) => {
                e.dataTransfer.setData('emoji', item.dataset.emoji);
            };
        });

        area.ondragover = (e) => e.preventDefault();
        area.ondrop = (e) => {
            const emoji = e.dataTransfer.getData('emoji');
            const rect = area.getBoundingClientRect();
            const x = ((e.clientX - rect.left) / rect.width) * 100;
            const y = ((e.clientY - rect.top) / rect.height) * 100;
            renderItem(emoji, x, y);
        };
    }

    function renderItem(emoji, x, y) {
        const area = document.getElementById('safeArea');
        const el = document.createElement('div');
        el.className = 'placed-item';
        el.textContent = emoji;
        el.style.left = x + '%';
        el.style.top = y + '%';
        
        // Make draggable within area
        el.onmousedown = (e) => {
            let shiftX = e.clientX - el.getBoundingClientRect().left;
            let shiftY = e.clientY - el.getBoundingClientRect().top;
            
            function moveAt(pageX, pageY) {
                const rect = area.getBoundingClientRect();
                let newX = ((pageX - rect.left - shiftX) / rect.width) * 100;
                let newY = ((pageY - rect.top - shiftY) / rect.height) * 100;
                el.style.left = newX + '%';
                el.style.top = newY + '%';
            }

            function onMouseMove(e) { moveAt(e.pageX, e.pageY); }
            document.addEventListener('mousemove', onMouseMove);
            el.onmouseup = () => {
                document.removeEventListener('mousemove', onMouseMove);
                el.onmouseup = null;
            };
        };

        // Right click to remove
        el.oncontextmenu = (e) => {
            e.preventDefault();
            el.remove();
        };

        area.appendChild(el);
    }

    function saveSafeSpace() {
        const area = document.getElementById('safeArea');
        const items = Array.from(area.querySelectorAll('.placed-item')).map(el => ({
            emoji: el.textContent,
            x: parseFloat(el.style.left),
            y: parseFloat(el.style.top)
        }));

        fetch('api/games/save_safe_space.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ design: items })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) alert('Safe space saved! ✨');
        });
    }
    // --- Grounding Logic ---
    function initGrounding() {
        document.querySelectorAll('.grounding-step').forEach(s => s.classList.remove('active'));
        document.querySelector('.grounding-step[data-step="5"]').classList.add('active');
        document.querySelectorAll('.grounding-input').forEach(i => i.value = '');
    }

    function nextGroundingStep() {
        const current = document.querySelector('.grounding-step.active');
        const next = current.nextElementSibling;
        if (next && next.classList.contains('grounding-step')) {
            current.classList.remove('active');
            next.classList.add('active');
        }
    }

    function finishGrounding() {
        const responses = {};
        document.querySelectorAll('.grounding-step[data-step]').forEach(step => {
            const stepNum = step.dataset.step;
            responses[stepNum] = Array.from(step.querySelectorAll('input')).map(i => i.value);
        });

        fetch('api/games/save_grounding.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ responses: responses })
        });

        document.querySelector('.grounding-step.active').classList.remove('active');
        document.getElementById('grounding-complete').classList.add('active');
    }

    // --- Coloring Logic ---
    const colors = ['#fecaca', '#fed7aa', '#fef08a', '#dcfce7', '#d1fae5', '#e0f2fe', '#eef2ff', '#f3e8ff', '#fce7f3', '#f1f5f9'];
    let selectedColor = colors[0];
    let currentDesignType = 'mandala';

    function showColoringTab(tab) {
        document.querySelectorAll('.coloring-tab-content').forEach(t => t.style.display = 'none');
        document.querySelectorAll('#coloring-tabs .mood-btn').forEach(b => b.classList.remove('active'));
        
        if (tab === 'new') {
            document.getElementById('coloring-new-designs').style.display = 'block';
            document.querySelector('#coloring-tabs .mood-btn:nth-child(1)').classList.add('active');
        } else if (tab === 'gallery') {
            document.getElementById('coloring-gallery').style.display = 'block';
            document.querySelector('#coloring-tabs .mood-btn:nth-child(2)').classList.add('active');
            loadGallery();
        } else if (tab === 'workspace') {
            document.getElementById('coloring-workspace').style.display = 'flex';
            document.getElementById('coloring-workspace').style.flexDirection = 'column';
            document.getElementById('coloring-workspace').style.alignItems = 'center';
        }
    }

    function initColoring() {
        showColoringTab('new');
        
        const palette = document.getElementById('colorPalette');
        palette.innerHTML = '';
        colors.forEach(c => {
            const s = document.createElement('div');
            s.className = 'color-swatch' + (c === selectedColor ? ' active' : '');
            s.style.background = c;
            s.onclick = () => {
                selectedColor = c;
                document.querySelectorAll('.color-swatch').forEach(sw => sw.classList.remove('active'));
                s.classList.add('active');
            };
            palette.appendChild(s);
        });
    }

    function startNewColoring(type) {
        currentDesignType = type;
        const container = document.getElementById('mandala-container');
        let paths = '';
        if (type === 'mandala') paths = generateMandalaPaths();
        else if (type === 'lotus') paths = generateLotusPaths();
        else if (type === 'star') paths = generateStarPaths();
        else if (type === 'waves') paths = generateWavesPaths();

        container.innerHTML = `
        <svg viewBox="0 0 100 100" width="100%" height="100%" id="mandalaSvg">
            <rect width="100" height="100" fill="#fff" />
            <g transform="translate(50,50)">
                ${paths}
            </g>
        </svg>`;
        showColoringTab('workspace');
    }

    function loadGallery() {
        const grid = document.getElementById('gallery-grid');
        grid.innerHTML = '<p>Loading your gallery...</p>';
        
        fetch('api/games/get_all_artworks.php')
            .then(r => r.json())
            .then(data => {
                grid.innerHTML = '';
                if (data.success && data.artworks.length > 0) {
                    data.artworks.forEach(art => {
                        const item = document.createElement('div');
                        item.className = 'game-card';
                        item.style.padding = '10px';
                        item.style.cursor = 'pointer';
                        item.innerHTML = `
                            <div style="width: 100px; height: 100px; overflow: hidden; border-radius: 8px; margin: 0 auto;">
                                ${art.svg_data}
                            </div>
                            <p style="font-size: 0.8rem; margin-top: 5px;">${new Date(art.updated_at).toLocaleDateString()}</p>
                        `;
                        item.onclick = () => {
                            const container = document.getElementById('mandala-container');
                            container.innerHTML = art.svg_data;
                            // Re-attach events
                            container.querySelectorAll('path, circle').forEach(el => {
                                el.onclick = () => {
                                    if (el.tagName === 'circle') el.setAttribute('fill', selectedColor);
                                    else el.style.fill = selectedColor;
                                };
                            });
                            showColoringTab('workspace');
                        };
                        grid.appendChild(item);
                    });
                } else {
                    grid.innerHTML = '<p style="grid-column: 1/-1;">Your gallery is empty. Start a new project!</p>';
                }
            });
    }

    function generateMandalaPaths() {
        let h = '';
        for (let ring = 1; ring <= 4; ring++) {
            const count = ring * 8;
            const radius = ring * 10;
            for (let i = 0; i < count; i++) {
                const angle = (i / count) * 360;
                h += `<path d="M 0,0 Q ${radius},${radius/2} ${radius},0 T 0,0" 
                    transform="rotate(${angle})" 
                    fill="white" stroke="#94a3b8" stroke-width="0.2"
                    onclick="this.style.fill=selectedColor" />`;
            }
        }
        return h;
    }

    function generateLotusPaths() {
        let h = '';
        for (let i = 0; i < 12; i++) {
            const angle = (i / 12) * 360;
            h += `<path d="M 0,0 C 20,-20 40,-20 0,-40 C -40,-20 -20,-20 0,0" 
                transform="rotate(${angle}) scale(0.8)" 
                fill="white" stroke="#94a3b8" stroke-width="0.3"
                onclick="this.style.fill=selectedColor" />`;
            h += `<path d="M 0,0 C 15,-15 30,-15 0,-30 C -30,-15 -15,-15 0,0" 
                transform="rotate(${angle + 15}) scale(0.6)" 
                fill="white" stroke="#94a3b8" stroke-width="0.3"
                onclick="this.style.fill=selectedColor" />`;
        }
        return h;
    }

    function generateStarPaths() {
        let h = '';
        for (let i = 0; i < 8; i++) {
            const angle = i * 45;
            h += `<path d="M 0,0 L 40,10 L 40,-10 Z" transform="rotate(${angle})" fill="white" stroke="#94a3b8" stroke-width="0.3" onclick="this.style.fill=selectedColor" />`;
            h += `<path d="M 0,0 L 30,30 L 0,40 Z" transform="rotate(${angle})" fill="white" stroke="#94a3b8" stroke-width="0.3" onclick="this.style.fill=selectedColor" />`;
        }
        return h;
    }

    function generateWavesPaths() {
        let h = '';
        for (let r = 0; r < 5; r++) {
            const radius = 10 + r * 8;
            for (let i = 0; i < 16; i++) {
                const angle = (i / 16) * 360;
                h += `<circle cx="${radius * Math.cos(angle * Math.PI / 180)}" cy="${radius * Math.sin(angle * Math.PI / 180)}" r="4" fill="white" stroke="#94a3b8" stroke-width="0.3" onclick="this.setAttribute('fill', selectedColor)" />`;
            }
        }
        return h;
    }

    function saveArtwork() {
        const svgData = document.getElementById('mandala-container').innerHTML;
        fetch('api/games/save_artwork.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ svg_data: svgData, type: currentDesignType })
        }).then(() => {
            alert('Artwork saved to your gallery! 🎨');
            showColoringTab('gallery');
        });
    }

    function resetColoring() { startNewColoring(currentDesignType); }

    // --- Dashboard Core JS ---
    document.addEventListener('DOMContentLoaded', () => {

        // Animate elements on load
        document.querySelectorAll('.animate-up').forEach((el, index) => {
            setTimeout(() => {
                el.classList.add('visible');
            }, index * 100);
        });
    });


    // --- Endless Flow Logic ---
    let flowAnimation = null;
    let soundOn = true;
    function toggleSound() {
        soundOn = !soundOn;
        document.getElementById('soundToggle').textContent = `🔊 Ambient Sound: ${soundOn ? 'On' : 'Off'}`;
    }

    function initFlow() {
        const canvas = document.getElementById('flow-canvas');
        const ctx = canvas.getContext('2d');
        canvas.width = canvas.offsetWidth;
        canvas.height = canvas.offsetHeight;

        let mouse = { x: canvas.width / 2, y: canvas.height / 2 };
        let leaf = { x: mouse.x, y: mouse.y, angle: 0 };
        
        canvas.onmousemove = (e) => {
            const rect = canvas.getBoundingClientRect();
            mouse.x = e.clientX - rect.left;
            mouse.y = e.clientY - rect.top;
        };

        const particles = [];
        for(let i=0; i<50; i++) {
            particles.push({
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height,
                size: Math.random() * 2 + 1,
                speed: Math.random() * 1 + 0.5
            });
        }

        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // Draw particles (wind)
            ctx.fillStyle = 'rgba(255, 255, 255, 0.5)';
            particles.forEach(p => {
                p.x -= p.speed;
                if(p.x < 0) p.x = canvas.width;
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
                ctx.fill();
            });

            // Update leaf (lerp to mouse)
            leaf.x += (mouse.x - leaf.x) * 0.05;
            leaf.y += (mouse.y - leaf.y) * 0.05;
            leaf.angle += 0.02;

            // Draw leaf
            ctx.save();
            ctx.translate(leaf.x, leaf.y);
            ctx.rotate(leaf.angle + Math.sin(leaf.angle) * 0.2);
            ctx.fillStyle = '#4ade80';
            ctx.beginPath();
            ctx.ellipse(0, 0, 15, 8, 0, 0, Math.PI * 2);
            ctx.fill();
            ctx.strokeStyle = '#166534';
            ctx.lineWidth = 1;
            ctx.stroke();
            ctx.restore();

            flowAnimation = requestAnimationFrame(animate);
        }
        if(flowAnimation) cancelAnimationFrame(flowAnimation);
        animate();
    }
</script>

<?php require_once 'includes/sidebar_toggle_script.php'; ?>
</body>
</html>
