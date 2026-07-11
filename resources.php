<?php
// resources.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user_role = $_SESSION['role'] ?? 'Guest';
$__resources_embedded = !empty($__embedded_mode);

if (!$__resources_embedded && strtolower($user_role) === 'therapist') {
    header("Location: therapist_dashboard.php?view=resources");
    exit();
}

// Dynamic body class for role-based theming
if (strtolower($user_role) === 'therapist') {
    $body_class = 'role-therapist';
} elseif (strtolower($user_role) === 'volunteer') {
    $body_class = 'role-volunteer';
} else {
    $body_class = 'role-client';
}
// Embedded mode: skip header/footer when included from a dashboard shell.
if (!$__resources_embedded) {
    require_once 'includes/header.php';
}
require_once 'includes/db_connect.php';
$is_guest = !isset($_SESSION['user_id']);
if (!$is_guest) {
    require_once 'includes/dashboard_components.php';
}

// Fetch resources from database dynamically
$resources = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM resource ORDER BY created_at DESC");
    $stmt->execute();
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Ignore error, $resources will just be empty
}

// Function to generate dynamic border colors array based on category hash
function getBorderColor($category) {
    if (empty($category)) return '#64748b';
    $colors = ['#ef4444', '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#64748b'];
    $hash = md5($category);
    $idx = hexdec(substr($hash, 0, 2)) % count($colors);
    return $colors[$idx];
}

$unique_categories = [];
foreach ($resources as $res) {
    if (!empty($res['category'])) {
        $unique_categories[$res['category']] = true;
    }
}
$unique_categories = array_keys($unique_categories);

// Role-based dynamics for theme
if (strtolower($user_role) === 'therapist') {
    $sidebarTheme = '#337AB7';
} elseif (strtolower($user_role) === 'volunteer') {
    $sidebarTheme = '#27AE60';
} else {
    $sidebarTheme = '#A68A6C';
}
?>

<link rel="stylesheet" href="assets/css/dashboard-style.css">
<!-- FontAwesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<?php
$_is_therapist = (strtolower($user_role) === 'therapist');
$_is_volunteer = (strtolower($user_role) === 'volunteer');
$_res_bg  = $_is_therapist ? '#F4F9FD' : ($_is_volunteer ? '#f0f7f4' : '#FDFBF8');
$_arr_color = $_is_therapist ? '#337AB7' : ($_is_volunteer ? '#27ae60' : '#6c4b2a');
?>
<!-- Force background color before first paint -->
<script>document.documentElement.style.backgroundColor='<?php echo $_res_bg; ?>';document.addEventListener('DOMContentLoaded',function(){document.body.style.setProperty('background-color','<?php echo $_res_bg; ?>','important');});</script>
<style>
    html, body, .page-container, .page-container > div { background-color: <?php echo $_res_bg; ?> !important; }

    .page-container .container-fluid {
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 20px;
    }

    /* Page Specific Styles */
    .resource-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 30px;
        margin-bottom: 50px;
    }

    .resource-card {
        background: white;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        padding: 24px;
        transition: transform 0.2s, box-shadow 0.2s;
        display: flex;
        flex-direction: column;
        border-top: 6px solid #e2e8f0;
    }

    .resource-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }
    
    .card-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .category-pill {
        background: #f8fafc;
        color: #475569;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 6px 14px;
        border-radius: 9999px;
        border: 1px solid #e2e8f0;
    }
    
    .card-icons {
        display: flex;
        gap: 12px;
        color: #94a3b8;
    }
    
    .resource-title {
        font-size: 1.15rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 8px 0;
        line-height: 1.4;
    }
    
    .resource-meta {
        font-size: 0.85rem;
        color: #64748b;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .resource-desc {
        font-size: 0.95rem;
        color: #475569;
        margin: 0 0 25px 0;
        line-height: 1.6;
        flex: 1;
    }
    
    .card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: auto;
    }
    
    .resource-info {
        font-size: 0.85rem;
        color: #64748b;
    }
    
    .action-btn {
        background: var(--primary-color);
        color: white;
        padding: 8px 20px;
        border-radius: 8px;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: opacity 0.2s;
        border: none;
        cursor: pointer;
    }
    
    .action-btn:hover {
        opacity: 0.9;
        color: white;
    }
    
    .header-actions {
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 40px;
        flex-wrap: wrap;
    }
    
    .search-wrap {
        display: flex;
        align-items: center;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 10px 16px;
        flex: 1;
        min-width: 250px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02);
    }
    
    .search-wrap i {
        color: #94a3b8;
        margin-right: 10px;
    }
    
    .search-input {
        border: none;
        outline: none;
        width: 100%;
        color: #475569;
        font-size: 0.95rem;
        background: transparent;
    }
    
    .filters {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .filter-btn {
        padding: 8px 20px;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        border: 1px solid transparent;
        background: color-mix(in srgb, var(--primary-color) 8%, #fdfdfd);
        color: #475569;
        border-color: color-mix(in srgb, var(--primary-color) 15%, #e2e8f0);
    }
    
    .filter-btn.active {
        background: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
    }

    /* Dynamic Theme Coloring */
    :root {
        --primary-color:
            <?php echo $sidebarTheme; ?>
        ;
    }

    /* Modal Styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        backdrop-filter: blur(5px);
    }

    .modal-card {
        background: #faf8f5;
        width: 100%;
        max-width: 600px;
        border-radius: 16px;
        padding: 32px;
        position: relative;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        animation: modalFadeIn 0.3s ease-out;
    }

    .modal-card .form-control {
        border: 1px solid #e7e5e4;
        border-radius: 10px;
        background: white;
        padding: 12px 16px;
        font-family: inherit;
        font-size: 0.95rem;
        outline: none;
        color: #444;
        width: 100%;
    }

    .modal-card .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary-color) 20%, transparent);
    }

    .modal-actions {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 20px;
        margin-top: 30px;
    }

    .btn-cancel {
        background: none;
        border: none;
        color: #57534e;
        font-weight: 600;
        cursor: pointer;
        padding: 10px;
        font-size: 0.95rem;
    }

    .btn-submit-modal {
        background: var(--primary-color);
        color: white;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.95rem;
        border: none;
        cursor: pointer;
        transition: opacity 0.2s;
    }

    .btn-submit-modal:hover {
        opacity: 0.9;
    }

    @keyframes modalFadeIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .close-modal {
        position: absolute;
        top: 20px;
        right: 20px;
        font-size: 1.5rem;
        cursor: pointer;
        color: #64748b;
        transition: color 0.2s;
    }

    .close-modal:hover {
        color: #1e293b;
    }

    .btn-add-resource {
        position: fixed;
        bottom: 40px;
        right: 40px;
        padding: 15px 30px;
        border-radius: 50px;
        background: var(--primary-color);
        color: white;
        font-weight: 700;
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        border: none;
        cursor: pointer;
        z-index: 1000;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s ease;
    }

    .btn-add-resource:hover {
        transform: translateY(-5px) scale(1.05);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
    }

    .btn-top-add {
        background: var(--primary-color);
        color: white;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.95rem;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: transform 0.2s, box-shadow 0.2s, opacity 0.2s;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .btn-top-add:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 8px -1px rgba(0, 0, 0, 0.15);
        opacity: 0.9;
    }

    /* ── Therapist theme overrides ── */
    body.role-therapist {
        background-color: #F4F9FD !important;
    }
    body.role-therapist .modal-card {
        background: #EBF4FD !important;
        border: 1px solid #D1E5F7 !important;
    }
    body.role-therapist .resource-card {
        background: #ffffff !important;
        border-color: #D1E5F7 !important;
    }
    body.role-therapist .category-pill {
        background: #EBF4FD !important;
        border-color: #D1E5F7 !important;
        color: #1A4D80 !important;
    }
    body.role-therapist .filter-btn {
        background: color-mix(in srgb, #337AB7 8%, #f4f9fd) !important;
        color: #1A4D80 !important;
        border-color: color-mix(in srgb, #337AB7 15%, #D1E5F7) !important;
    }
    body.role-therapist .filter-btn.active {
        background: #337AB7 !important;
        color: white !important;
        border-color: #337AB7 !important;
    }
</style>

<?php
$page_bg = (strtolower($user_role) === 'therapist') ? '#F4F9FD' : ((strtolower($user_role) === 'volunteer') ? '#f0f7f4' : '#FDFBF8');
?>

<?php if ($__resources_embedded): ?>
<div style="width:100%;">
    <div style="background: <?php echo $page_bg; ?>;">
        <div class="container-fluid" style="background: <?php echo $page_bg; ?>;">
<?php elseif ($is_guest): ?>
<div class="page-container">
    <div style="display: none;"></div>
    <div>
        <div class="container-fluid" style="background: <?php echo $page_bg; ?>;">
<?php else: ?>
<div class="dashboard-wrapper">
    <?php render_sidebar('resources'); ?>
    <div class="dashboard-main">
        <div class="container-fluid" style="background: <?php echo $page_bg; ?>;">
            <!-- Sidebar toggle button for mobile/desktop -->
            <div class="dashboard-header-row" style="display: flex; gap: 20px; align-items: stretch; margin-bottom: 30px;">
                <button id="sidebarToggle" class="sidebar-toggle-inline" aria-label="Toggle Sidebar" style="align-self: flex-start; margin-top: 10px;">
                    <i id="toggleIcon" class="fa-solid fa-bars-staggered"></i>
                </button>
            </div>
<?php endif; ?>
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px;">
            <div>
                <h1 style="margin: 0; font-size: 2.2rem; color: #1e293b; font-family: var(--font-heading); font-weight: 800;">Resource Library</h1>
                <p style="color: #64748b; margin-top: 8px; font-size: 1.05rem;">Training materials and helpful resources for facilitators</p>
            </div>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Therapist'): ?>
                <button class="btn-top-add" id="openAddResource">
                    <i class="fas fa-plus"></i> Add Resource
                </button>
            <?php endif; ?>
        </div>

        <!-- Filter and Search -->
        <div class="header-actions">
            <div class="search-wrap">
                <i class="fas fa-search search-icon"></i>
                <input type="text" placeholder="Search resources..." id="searchInput" class="search-input">
            </div>
            <div class="filters" id="filterContainer">
                <button class="filter-btn active" data-filter="all">All</button>
                <?php foreach ($unique_categories as $cat): ?>
                    <button class="filter-btn" data-filter="<?php echo htmlspecialchars($cat); ?>">
                        <?php echo htmlspecialchars($cat); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Grid -->
        <?php if (empty($resources)): ?>
            <div style="text-align: center; color: #64748b; padding: 40px;">
                <p>Check back soon! Our therapists are currently writing new resources.</p>
            </div>
        <?php else: ?>
            <div class="resource-grid animate-up"
                style="opacity: 0; transform: translateY(20px); transition: all 0.6s ease;">
                <?php foreach ($resources as $res): ?>
                    <?php 
                        $link = '#';
                        if (!empty($res['url'])) {
                            if (strpos($res['url'], 'http') === 0 || strpos($res['url'], 'file://') === 0 || strpos($res['url'], '/') === 0) {
                                $link = $res['url'];
                            } else {
                                $root_prefix = isset($root) ? $root : '';
                                $link = $root_prefix . $res['url'];
                            }
                        }
                    ?>
                    <div class="resource-card" data-category="<?php echo htmlspecialchars($res['category']); ?>" style="border-top-color: <?php echo getBorderColor($res['category']); ?>;">
                        <div class="card-top">
                            <span class="category-pill"><?php echo htmlspecialchars($res['category'] ?: 'General'); ?></span>
                            <div class="card-icons">
                                <i class="far fa-bookmark bookmark-btn" data-id="<?php echo htmlspecialchars($res['resource_id']); ?>" style="cursor:pointer;" title="Bookmark"></i>
                                <i class="far fa-share-square share-btn" data-url="<?php echo htmlspecialchars($link); ?>" data-title="<?php echo htmlspecialchars($res['title']); ?>" style="cursor:pointer;" title="Share"></i>
                            </div>
                        </div>
                        <h3 class="resource-title"><?php echo htmlspecialchars($res['title']); ?></h3>
                        <div class="resource-meta">
                            <?php if($res['type'] == 'Video'): ?>
                                <i class="fas fa-video"></i> Video &bull; 
                            <?php else: ?>
                                <i class="far fa-file-pdf"></i> <?php echo htmlspecialchars($res['type']); ?> &bull; 
                            <?php endif; ?>
                            Clinical Team
                        </div>
                        <p class="resource-desc"><?php echo htmlspecialchars($res['content'] ?? 'A comprehensive guide and materials for platform facilitators and members.'); ?></p>
                        <div class="card-footer">
                            <span class="resource-info"><?php echo date('M j, Y', strtotime($res['created_at'])); ?></span>
                            <a href="<?php echo htmlspecialchars($link); ?>" target="_blank" class="action-btn">
                                <?php if($res['type'] == 'Video'): ?>
                                    <i class="fas fa-external-link-alt"></i> Watch
                                <?php elseif($res['type'] == 'Article' || $res['type'] == 'Paper'): ?>
                                    <i class="fas fa-external-link-alt"></i> Read
                                <?php else: ?>
                                    <i class="fas fa-download"></i> Download
                                <?php endif; ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        </div>
        <?php if (!$__resources_embedded) { require_once 'includes/footer.php'; } ?>
    </div>
</div>

<!-- Add Resource Modal -->
<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Therapist'): ?>
    <div class="modal-overlay" id="resourceModal">
        <div class="modal-card">
            <span class="close-modal" id="closeModal">&times;</span>
            <div style="margin-bottom: 25px;">
                <h2 style="font-family: var(--font-heading); font-size: 1.6rem; color: #1e293b; margin: 0 0 5px 0;">Add Resource</h2>
                <p style="color: #57534e; font-size: 0.95rem; margin: 0;">Share valuable knowledge with the community facilitators.</p>
            </div>

            <form id="addResourceForm" class="premium-form" enctype="multipart/form-data">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #444; font-size: 0.9rem;">Resource Title</label>
                    <input type="text" name="title" class="form-control" required placeholder="e.g. 5 Techniques for Stress Management">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div class="form-group">
                        <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #444; font-size: 0.9rem;">Resource Type</label>
                        <select name="type" id="resource_type" class="form-control" required onchange="handleResourceTypeChange()">
                            <option value="PDF Document">PDF Document</option>
                            <option value="Article">Article</option>
                            <option value="Video">Video</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #444; font-size: 0.9rem;">Category</label>
                        <select name="category" class="form-control" required>
                            <option value="Mental Health">Mental Health</option>
                            <option value="Anxiety">Anxiety</option>
                            <option value="Depression">Depression</option>
                            <option value="Crisis Management">Crisis Management</option>
                            <option value="Mindfulness">Mindfulness</option>
                            <option value="Self-Care">Self-Care</option>
                            <option value="Relationships">Relationships</option>
                            <option value="Facilitation">Facilitation</option>
                            <option value="Training">Training</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <textarea name="content" class="form-control" rows="4" required placeholder="Brief description..."></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #444; font-size: 0.9rem;" id="media-label">Provide URL or Upload PDF</label>
                    <div id="url-container" style="margin-bottom: 10px;">
                        <input type="url" name="link_url" id="link_url" class="form-control" placeholder="https://...">
                    </div>
                    <div id="file-container">
                        <input type="file" name="resource_file" id="resource_file" class="form-control" style="padding: 9px; cursor: pointer;">
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="document.getElementById('resourceModal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn-submit-modal">Add Resource</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<script>
    function handleResourceTypeChange() {
        const type = document.getElementById('resource_type');
        if(!type) return;
        const urlContainer = document.getElementById('url-container');
        const fileContainer = document.getElementById('file-container');
        const mediaLabel = document.getElementById('media-label');
        const linkInput = document.getElementById('link_url');
        const fileInput = document.getElementById('resource_file');

        if (type.value === 'Video') {
            urlContainer.style.display = 'block';
            fileContainer.style.display = 'none';
            mediaLabel.innerText = 'Video URL (Required)';
            linkInput.required = true;
            fileInput.required = false;
        } else if (type.value === 'Article') {
            urlContainer.style.display = 'none';
            fileContainer.style.display = 'block';
            mediaLabel.innerText = 'Upload Article (Example)';
            linkInput.required = false;
            fileInput.required = false;
        } else {
            urlContainer.style.display = 'block';
            fileContainer.style.display = 'block';
            mediaLabel.innerText = 'Provide URL or Upload PDF';
            linkInput.required = false;
            fileInput.required = false;
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        handleResourceTypeChange(); // Initialize fields correctly

        const grid = document.querySelector('.resource-grid');
        if (grid) {
            grid.style.opacity = '1';
            grid.style.transform = 'translateY(0)';
        }

        // Modal Logic
        const modal = document.getElementById('resourceModal');
        const openBtn = document.getElementById('openAddResource');
        const closeBtn = document.getElementById('closeModal');

        if (openBtn) {
            openBtn.onclick = () => modal.style.display = 'flex';
        }
        if (closeBtn) {
            closeBtn.onclick = () => modal.style.display = 'none';
        }
        window.onclick = (e) => {
            if (e.target == modal) modal.style.display = 'none';
        }

        // Form Submission logic
        const addForm = document.getElementById('addResourceForm');
        if (addForm) {
            addForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                const btn = this.querySelector('button[type="submit"]');
                const originalText = btn.textContent;

                btn.textContent = 'PUBLISHING...';
                btn.disabled = true;

                try {
                    const formData = new FormData(this);
                    const response = await fetch('api/add_resource.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();

                    if (data.success) {
                        alert('Resource published successfully!');
                        location.reload();
                    } else {
                        alert(data.message || 'Error publishing resource.');
                    }
                } catch (err) {
                    alert('A network error occurred.');
                } finally {
                    btn.textContent = originalText;
                    btn.disabled = false;
                }
            });
        }

        // Check for ?add=true param to open modal automatically
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('add') === 'true' && modal) {
            modal.style.display = 'flex';
        }

        const filterBtns = document.querySelectorAll('.filter-btn');
        const cards = document.querySelectorAll('.resource-card');
        const searchInput = document.getElementById('searchInput');

        function filterResources() {
            const activeFilterBtn = document.querySelector('.filter-btn.active');
            const filter = activeFilterBtn ? activeFilterBtn.getAttribute('data-filter') : 'all';
            const query = searchInput ? searchInput.value.toLowerCase() : '';

            cards.forEach(card => {
                const category = card.getAttribute('data-category');
                const title = card.querySelector('.resource-title').textContent.toLowerCase();
                const desc = card.querySelector('.resource-desc').textContent.toLowerCase();
                
                const matchesCategory = (filter === 'all' || category === filter);
                const matchesSearch = title.includes(query) || desc.includes(query);

                if (matchesCategory && matchesSearch) {
                    card.style.display = 'flex';
                    setTimeout(() => card.style.opacity = '1', 10);
                } else {
                    card.style.opacity = '0';
                    setTimeout(() => card.style.display = 'none', 300);
                }
            });
        }

        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                filterBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                filterResources();
            });
        });
        
        if (searchInput) searchInput.addEventListener('input', filterResources);

        // --- BOOKMARK LOGIC ---
        const bookmarkBtns = document.querySelectorAll('.bookmark-btn');
        let bookmarks = JSON.parse(localStorage.getItem('resource_bookmarks')) || [];

        bookmarkBtns.forEach(btn => {
            const id = btn.getAttribute('data-id');
            // Check if already bookmarked
            if (bookmarks.includes(id)) {
                btn.classList.remove('far');
                btn.classList.add('fas');
                btn.style.color = 'var(--primary-color)';
            }

            btn.addEventListener('click', () => {
                if (bookmarks.includes(id)) {
                    // Remove bookmark
                    bookmarks = bookmarks.filter(b => b !== id);
                    btn.classList.remove('fas');
                    btn.classList.add('far');
                    btn.style.color = '';
                } else {
                    // Add bookmark
                    bookmarks.push(id);
                    btn.classList.remove('far');
                    btn.classList.add('fas');
                    btn.style.color = 'var(--primary-color)';
                }
                localStorage.setItem('resource_bookmarks', JSON.stringify(bookmarks));
            });
        });

        // --- SHARE LOGIC ---
        const shareBtns = document.querySelectorAll('.share-btn');
        shareBtns.forEach(btn => {
            btn.addEventListener('click', async () => {
                let url = btn.getAttribute('data-url');
                const title = btn.getAttribute('data-title');
                
                // Convert relative URLs to absolute
                if (!url.startsWith('http')) {
                    const baseUrl = window.location.origin + window.location.pathname.replace('resources.php', '');
                    url = baseUrl + url;
                }

                if (navigator.share) {
                    try {
                        await navigator.share({
                            title: title,
                            text: 'Check out this resource from Safe Haven: ' + title,
                            url: url
                        });
                    } catch (err) {
                        console.log('Share canceled or failed');
                    }
                } else {
                    // Fallback: Copy to clipboard
                    try {
                        await navigator.clipboard.writeText(url);
                        alert('Link copied to clipboard!');
                    } catch (err) {
                        alert('Failed to copy link.');
                    }
                }
            });
        });

    });
</script>

<?php if (!$__resources_embedded) { require_once 'includes/sidebar_toggle_script.php'; } ?>