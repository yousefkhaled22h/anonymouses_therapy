<?php
// my_diary.php - Client's personal rich-text diary
require_once 'includes/db_connect.php';
$body_class = 'role-client';
require_once 'includes/header.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Client') {
    header("Location: login.php"); exit();
}

$user_id = $_SESSION['user_id'];
$client_id = $_SESSION['client_id'] ?? null;
if (!$client_id) {
    $stmt = $pdo->prepare("SELECT client_id FROM client WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $client_id = $stmt->fetchColumn();
    $_SESSION['client_id'] = $client_id;
}

// Fetch existing journal entries
$entries = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM Journal_Entry WHERE client_id = ? ORDER BY updated_at DESC");
    $stmt->execute([$client_id]);
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table might not exist yet, will be created below
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS Journal_Entry (
            entry_id INT AUTO_INCREMENT PRIMARY KEY,
            client_id VARCHAR(50) NOT NULL,
            title VARCHAR(255) DEFAULT 'Untitled',
            content LONGTEXT,
            mood VARCHAR(50) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_journal_entry_client FOREIGN KEY (client_id) REFERENCES client(client_id) ON DELETE CASCADE
        )");
    } catch (Exception $e2) {}
}
?>
<link rel="stylesheet" href="assets/css/dashboard-style.css">
<style>
    #main-header { background: rgba(255,255,255,0.97) !important; }
    .journal-container { max-width: 1200px; margin: 40px auto; padding: 0 24px; }

    .journal-hero {
        background: linear-gradient(135deg, #6c4b2a 0%, #a07850 50%, #c9a97a 100%);
        border-radius: 24px;
        padding: 50px 60px;
        color: white;
        margin-bottom: 36px;
        position: relative;
        overflow: hidden;
    }
    .journal-hero::before {
        content: '📖';
        position: absolute;
        right: 60px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 7rem;
        opacity: 0.15;
    }
    .journal-hero h1 { margin: 0 0 10px; font-size: 2.6rem; font-weight: 800; }
    .journal-hero p { margin: 0; opacity: 0.85; font-size: 1.1rem; }

    .journal-layout { display: grid; grid-template-columns: 300px 1fr; gap: 28px; align-items: start; }

    /* Sidebar – Entry list */
    .entry-sidebar {
        background: white;
        border-radius: 20px;
        box-shadow: 0 4px 24px rgba(0,0,0,0.06);
        overflow: hidden;
        position: sticky;
        top: 100px;
    }
    .sidebar-header {
        padding: 20px 24px;
        background: #faf7f4;
        border-bottom: 1px solid #ede8e1;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .sidebar-header h3 { margin: 0; font-size: 1rem; color: #5a3e28; font-weight: 700; }
    .btn-new-entry {
        background: #6c4b2a;
        color: white;
        border: none;
        border-radius: 8px;
        padding: 6px 14px;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
    }
    .btn-new-entry:hover { background: #4e3319; }

    .entry-list { max-height: 520px; overflow-y: auto; }
    .entry-item {
        padding: 16px 24px;
        border-bottom: 1px solid #f0ebe4;
        cursor: pointer;
        transition: background 0.2s;
    }
    .entry-item:hover, .entry-item.active { background: #fdf8f4; }
    .entry-item.active { border-left: 4px solid #6c4b2a; }
    .entry-item-title { font-weight: 600; font-size: 0.95rem; color: #333; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .entry-item-date { font-size: 0.78rem; color: #999; }
    .entry-item-mood { font-size: 0.85rem; margin-top: 3px; }
    .empty-entries { padding: 40px 24px; text-align: center; color: #aaa; font-size: 0.9rem; }

    /* Editor */
    .journal-editor {
        background: white;
        border-radius: 20px;
        box-shadow: 0 4px 24px rgba(0,0,0,0.06);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        min-height: 600px;
    }
    .editor-top {
        padding: 20px 28px;
        border-bottom: 1px solid #f0ebe4;
        display: flex;
        gap: 14px;
        align-items: center;
    }
    #journalTitle {
        flex: 1;
        border: none;
        outline: none;
        font-size: 1.4rem;
        font-weight: 700;
        color: #2d1a0a;
        background: transparent;
        font-family: var(--font-heading, Georgia, serif);
    }
    #journalTitle::placeholder { color: #ccc; }

    .mood-picker { display: flex; gap: 6px; }
    .mood-btn {
        background: none;
        border: 2px solid transparent;
        border-radius: 8px;
        font-size: 1.3rem;
        padding: 4px 6px;
        cursor: pointer;
        transition: all 0.2s;
        line-height: 1;
    }
    .mood-btn:hover, .mood-btn.selected { border-color: #c9a97a; background: #fdf8f4; }

    /* Quill-style toolbar */
    .editor-toolbar {
        padding: 10px 28px;
        border-bottom: 1px solid #f0ebe4;
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        background: #faf7f4;
    }
    .toolbar-btn {
        background: none;
        border: 1px solid transparent;
        border-radius: 6px;
        padding: 5px 8px;
        cursor: pointer;
        font-size: 0.85rem;
        color: #555;
        transition: all 0.15s;
        font-family: inherit;
    }
    .toolbar-btn:hover { background: #ede8e1; border-color: #d4c5b0; }
    .toolbar-btn.active { background: #6c4b2a; color: white; border-color: #6c4b2a; }
    .toolbar-sep { width: 1px; background: #e0d8cf; margin: 3px 4px; }

    /* Editor content area */
    #journalContent {
        flex: 1;
        padding: 28px;
        outline: none;
        font-size: 1.05rem;
        line-height: 1.8;
        color: #2d200f;
        min-height: 380px;
        overflow-y: auto;
    }
    #journalContent:empty::before {
        content: attr(data-placeholder);
        color: #c4b49e;
        pointer-events: none;
    }

    /* Emoji picker */
    .emoji-panel {
        display: none;
        position: absolute;
        background: white;
        border: 1px solid #e0d8cf;
        border-radius: 12px;
        padding: 12px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        z-index: 100;
        flex-wrap: wrap;
        gap: 5px;
        width: 280px;
        max-height: 200px;
        overflow-y: auto;
    }
    .emoji-panel.visible { display: flex; }
    .emoji-opt { font-size: 1.4rem; cursor: pointer; padding: 4px; border-radius: 6px; transition: background 0.1s; }
    .emoji-opt:hover { background: #f0ebe4; }

    /* Highlight picker */
    .highlight-colors { display: none; position: absolute; background: white; border: 1px solid #e0d8cf; border-radius: 10px; padding: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); z-index: 100; flex-wrap: wrap; gap: 6px; width: 160px; }
    .highlight-colors.visible { display: flex; }
    .h-color { width: 24px; height: 24px; border-radius: 50%; cursor: pointer; border: 2px solid transparent; transition: border 0.15s; }
    .h-color:hover { border-color: #555; }

    .editor-footer {
        padding: 16px 28px;
        border-top: 1px solid #f0ebe4;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #faf7f4;
    }
    .save-status { font-size: 0.85rem; color: #aaa; }
    .btn-save {
        background: linear-gradient(135deg,#6c4b2a,#9e6c3a);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 10px 28px;
        font-size: 0.95rem;
        font-weight: 600;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
        box-shadow: 0 4px 12px rgba(108,75,42,0.3);
    }
    .btn-save:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(108,75,42,0.4); }
    .btn-delete-entry { background: none; border: 1px solid #fca5a5; color: #ef4444; border-radius: 8px; padding: 6px 14px; font-size: 0.82rem; cursor: pointer; margin-right: 8px; }

    @media(max-width:768px){
        .journal-layout { grid-template-columns: 1fr; }
        .entry-sidebar { position: static; }
    }
</style>

<div class="dashboard-wrapper">
    <?php require_once 'includes/dashboard_components.php'; render_sidebar('diary'); ?>
    <div class="dashboard-main">
        <div class="container-fluid">
            <!-- Sidebar toggle button for mobile/desktop -->
            <div class="dashboard-header-row" style="display: flex; gap: 20px; align-items: stretch; margin-bottom: 30px;">
                <button id="sidebarToggle" class="sidebar-toggle-inline" aria-label="Toggle Sidebar" style="align-self: flex-start; margin-top: 10px; cursor: pointer;">
                    <i id="toggleIcon" class="fa-solid fa-bars-staggered"></i>
                </button>
            </div>
    <div class="journal-hero">
        <h1>My Diary</h1>
        <p>Your private space to reflect, feel, and grow. Write freely — only you can read this.</p>
    </div>

    <div class="journal-layout">
        <!-- Sidebar -->
        <div class="entry-sidebar">
            <div class="sidebar-header">
                <h3>📚 Entries</h3>
                <button class="btn-new-entry" onclick="newEntry()">+ New</button>
            </div>
            <div class="entry-list" id="entryList">
                <?php if (empty($entries)): ?>
                    <div class="empty-entries">No entries yet.<br>Click "+ New" to start writing.</div>
                <?php else: foreach ($entries as $idx => $e): ?>
                    <div class="entry-item <?= $idx === 0 ? 'active' : ''; ?>"
                         onclick="loadEntry(this, '<?= $e['entry_id']; ?>', <?= htmlspecialchars(json_encode($e['title'])); ?>, <?= htmlspecialchars(json_encode($e['content'])); ?>, '<?= htmlspecialchars($e['mood'] ?? ''); ?>')"
                         data-id="<?= $e['entry_id']; ?>">
                        <div class="entry-item-title"><?= htmlspecialchars($e['title'] ?: 'Untitled'); ?></div>
                        <div class="entry-item-date"><?= date('M d, Y', strtotime($e['updated_at'])); ?></div>
                        <?php if(!empty($e['mood'])): ?>
                            <div class="entry-item-mood"><?= htmlspecialchars($e['mood']); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- Editor -->
        <div class="journal-editor">
            <div class="editor-top">
                <input type="text" id="journalTitle" placeholder="Give this entry a title..." value="<?= !empty($entries) ? htmlspecialchars($entries[0]['title']) : ''; ?>">
                <div class="mood-picker">
                    <button class="mood-btn" onclick="selectMood(this,'😊')" title="Happy">😊</button>
                    <button class="mood-btn" onclick="selectMood(this,'😔')" title="Sad">😔</button>
                    <button class="mood-btn" onclick="selectMood(this,'😤')" title="Angry">😤</button>
                    <button class="mood-btn" onclick="selectMood(this,'😌')" title="Calm">😌</button>
                    <button class="mood-btn" onclick="selectMood(this,'😰')" title="Anxious">😰</button>
                    <button class="mood-btn" onclick="selectMood(this,'🥰')" title="Grateful">🥰</button>
                </div>
            </div>

            <div class="editor-toolbar">
                <button class="toolbar-btn" onclick="fmt('bold')" title="Bold"><b>B</b></button>
                <button class="toolbar-btn" onclick="fmt('italic')" title="Italic"><i>I</i></button>
                <button class="toolbar-btn" onclick="fmt('underline')" title="Underline"><u>U</u></button>
                <button class="toolbar-btn" onclick="fmt('strikeThrough')" title="Strike">S̶</button>
                <div class="toolbar-sep"></div>
                <button class="toolbar-btn" onclick="fmtBlock('h2')" title="Heading">H2</button>
                <button class="toolbar-btn" onclick="fmtBlock('h3')" title="Subheading">H3</button>
                <button class="toolbar-btn" onclick="fmtBlock('p')" title="Paragraph">¶</button>
                <div class="toolbar-sep"></div>
                <button class="toolbar-btn" onclick="fmt('insertUnorderedList')" title="Bullet List">• List</button>
                <button class="toolbar-btn" onclick="fmt('insertOrderedList')" title="Numbered List">1. List</button>
                <div class="toolbar-sep"></div>
                <button class="toolbar-btn" onclick="fmt('justifyLeft')" title="Align Left">≡L</button>
                <button class="toolbar-btn" onclick="fmt('justifyCenter')" title="Center">≡C</button>
                <button class="toolbar-btn" onclick="fmt('justifyRight')" title="Right">≡R</button>
                <div class="toolbar-sep"></div>
                <div style="position:relative;">
                    <button class="toolbar-btn" onclick="toggleHighlight(event)" title="Highlight">🖌 Highlight</button>
                    <div class="highlight-colors" id="highlightPanel">
                        <div class="h-color" style="background:#fde68a" onclick="applyHighlight('#fde68a')" title="Yellow"></div>
                        <div class="h-color" style="background:#bbf7d0" onclick="applyHighlight('#bbf7d0')" title="Green"></div>
                        <div class="h-color" style="background:#bfdbfe" onclick="applyHighlight('#bfdbfe')" title="Blue"></div>
                        <div class="h-color" style="background:#fecaca" onclick="applyHighlight('#fecaca')" title="Pink"></div>
                        <div class="h-color" style="background:#e9d5ff" onclick="applyHighlight('#e9d5ff')" title="Purple"></div>
                        <div class="h-color" style="background:#fed7aa" onclick="applyHighlight('#fed7aa')" title="Orange"></div>
                        <div class="h-color" style="background:transparent;border:2px solid #ccc;" onclick="applyHighlight('transparent')" title="Remove"></div>
                    </div>
                </div>
                <div style="position:relative;">
                    <button class="toolbar-btn" onclick="toggleEmoji(event)" title="Emoji">😊 Emoji</button>
                    <div class="emoji-panel" id="emojiPanel">
                        <?php
                        $emojis = ['❤️','💙','💚','💛','🧡','💜','🖤','🤍','💔','😀','😂','😍','🥰','😢','😤','😰','🥺','😴','🤔','✨','🌟','💫','🌸','🍀','🌻','🦋','🌈','🎵','🎶','📝','📖','💪','🙏','👍','🤗','💭','🔥','❄️','⭐','🌙','☀️','🌿','🍃'];
                        foreach ($emojis as $em) echo "<span class='emoji-opt' onclick='insertEmoji(\"$em\")'>$em</span>";
                        ?>
                    </div>
                </div>
                <div class="toolbar-sep"></div>
                <select id="fontSizeSelect" onchange="applyFontSize(this.value)" style="border:1px solid #ddd;border-radius:6px;padding:4px 6px;font-size:0.82rem;cursor:pointer;background:white;color:#555;">
                    <option value="">Size</option>
                    <option value="1">Small</option>
                    <option value="3">Normal</option>
                    <option value="5">Large</option>
                    <option value="7">Huge</option>
                </select>
            </div>

            <div id="journalContent"
                 contenteditable="true"
                 data-placeholder="Start writing your thoughts, feelings, stories... This is your space. ✨"
                 spellcheck="true">
                <?= !empty($entries) ? $entries[0]['content'] : ''; ?>
            </div>

            <div class="editor-footer">
                <div style="display:flex;align-items:center;gap:10px;">
                    <span class="save-status" id="saveStatus">All changes saved</span>
                    <button class="btn-delete-entry" id="btnDelete" onclick="deleteEntry()" style="display:none;">🗑 Delete Entry</button>
                </div>
                <button class="btn-save" onclick="saveEntry()">Save Entry 💾</button>
            </div>
        </div>
        </div>
        <?php require_once 'includes/footer.php'; ?>
    </div>
</div>

<input type="hidden" id="currentEntryId" value="<?= !empty($entries) ? $entries[0]['entry_id'] : ''; ?>">
<input type="hidden" id="currentMood" value="<?= !empty($entries) ? htmlspecialchars($entries[0]['mood'] ?? '') : ''; ?>">

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Mark active mood on load
    const initMood = document.getElementById('currentMood').value;
    if (initMood) {
        document.querySelectorAll('.mood-btn').forEach(b => {
            if (b.textContent.trim() === initMood) b.classList.add('selected');
        });
    }
    // Show delete if existing entry
    if (document.getElementById('currentEntryId').value) {
        document.getElementById('btnDelete').style.display = 'inline-block';
    }
    
    // Prevent formatting tools from stealing focus (excluding select dropdowns so they can be clicked)
    document.querySelectorAll('.toolbar-btn, .h-color, .emoji-opt').forEach(el => {
        el.addEventListener('mousedown', (e) => {
            e.preventDefault();
        });
    });
});

function fmt(cmd) { document.execCommand(cmd, false, null); document.getElementById('journalContent').focus(); }
function fmtBlock(tag) { document.execCommand('formatBlock', false, tag); document.getElementById('journalContent').focus(); }
function applyFontSize(v) { if (v) { document.execCommand('fontSize', false, v); } document.getElementById('journalContent').focus(); }

function applyHighlight(color) {
    document.execCommand('hiliteColor', false, color);
    closeAllPanels();
    document.getElementById('journalContent').focus();
}

function insertEmoji(emoji) {
    const editor = document.getElementById('journalContent');
    editor.focus();
    
    let sel = window.getSelection();
    if (sel.getRangeAt && sel.rangeCount) {
        let range = sel.getRangeAt(0);
        
        // Ensure the selection is inside the editor
        let node = range.commonAncestorContainer;
        let isInside = false;
        while (node) {
            if (node === editor) {
                isInside = true;
                break;
            }
            node = node.parentNode;
        }
        
        if (isInside) {
            range.deleteContents();
            let textNode = document.createTextNode(emoji);
            range.insertNode(textNode);
            
            range.setStartAfter(textNode);
            range.setEndAfter(textNode);
            sel.removeAllRanges();
            sel.addRange(range);
        } else {
            editor.innerHTML += emoji;
        }
    } else {
        editor.innerHTML += emoji;
    }
    closeAllPanels();
}

function toggleHighlight(e) { e.stopPropagation(); closeAllPanels(); document.getElementById('highlightPanel').classList.toggle('visible'); }
function toggleEmoji(e) { e.stopPropagation(); closeAllPanels(); document.getElementById('emojiPanel').classList.toggle('visible'); }
function closeAllPanels() {
    document.querySelectorAll('.emoji-panel, .highlight-colors').forEach(p => p.classList.remove('visible'));
}
document.addEventListener('click', closeAllPanels);

function selectMood(btn, mood) {
    if (btn.classList.contains('selected')) {
        btn.classList.remove('selected');
        document.getElementById('currentMood').value = '';
    } else {
        document.querySelectorAll('.mood-btn').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        document.getElementById('currentMood').value = mood;
    }
}

function newEntry() {
    document.getElementById('currentEntryId').value = '';
    document.getElementById('journalTitle').value = '';
    document.getElementById('journalContent').innerHTML = '';
    document.getElementById('currentMood').value = '';
    document.querySelectorAll('.mood-btn').forEach(b => b.classList.remove('selected'));
    document.querySelectorAll('.entry-item').forEach(e => e.classList.remove('active'));
    document.getElementById('saveStatus').textContent = 'New entry';
    document.getElementById('btnDelete').style.display = 'none';
    document.getElementById('journalContent').focus();
}

function loadEntry(el, id, title, content, mood) {
    document.querySelectorAll('.entry-item').forEach(e => e.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('currentEntryId').value = id;
    document.getElementById('journalTitle').value = title;
    document.getElementById('journalContent').innerHTML = content || '';
    document.getElementById('currentMood').value = mood;
    document.querySelectorAll('.mood-btn').forEach(b => {
        b.classList.toggle('selected', b.textContent.trim() === mood);
    });
    document.getElementById('saveStatus').textContent = 'Entry loaded';
    document.getElementById('btnDelete').style.display = 'inline-block';
}

async function saveEntry() {
    const id = document.getElementById('currentEntryId').value;
    const title = document.getElementById('journalTitle').value.trim() || 'Untitled';
    const content = document.getElementById('journalContent').innerHTML;
    const mood = document.getElementById('currentMood').value;

    document.getElementById('saveStatus').textContent = 'Saving...';

    const fd = new FormData();
    fd.append('entry_id', id);
    fd.append('title', title);
    fd.append('content', content);
    fd.append('mood', mood);

    const res = await fetch('api/journal/save.php', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.status === 'success') {
        document.getElementById('saveStatus').textContent = 'Saved ✓ ' + new Date().toLocaleTimeString();
        if (!id && data.entry_id) {
            document.getElementById('currentEntryId').value = data.entry_id;
            document.getElementById('btnDelete').style.display = 'inline-block';
            // Add to sidebar
            const list = document.getElementById('entryList');
            const empty = list.querySelector('.empty-entries');
            if (empty) empty.remove();
            const div = document.createElement('div');
            div.className = 'entry-item active';
            div.dataset.id = data.entry_id;
            div.innerHTML = `<div class="entry-item-title">${title}</div><div class="entry-item-date">Just now</div><div class="entry-item-mood">${mood}</div>`;
            div.onclick = () => loadEntry(div, data.entry_id, title, content, mood);
            list.prepend(div);
        } else {
            // Update sidebar item
            const existing = document.querySelector(`.entry-item[data-id="${id}"]`);
            if (existing) {
                existing.querySelector('.entry-item-title').textContent = title;
                existing.querySelector('.entry-item-mood').textContent = mood;
            }
        }
    } else {
        document.getElementById('saveStatus').textContent = 'Error saving!';
        alert(data.message || 'Could not save.');
    }
}

async function deleteEntry() {
    if (!confirm('Delete this entry permanently?')) return;
    const id = document.getElementById('currentEntryId').value;
    if (!id) { newEntry(); return; }
    const fd = new FormData();
    fd.append('entry_id', id);
    const res = await fetch('api/journal/delete.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.status === 'success') {
        const el = document.querySelector(`.entry-item[data-id="${id}"]`);
        if (el) el.remove();
        newEntry();
    }
}

// Auto-save every 60 seconds
setInterval(() => {
    const content = document.getElementById('journalContent').innerHTML;
    if (content.trim() && document.getElementById('currentEntryId').value) {
        saveEntry();
    }
}, 60000);
</script>

<?php require_once 'includes/sidebar_toggle_script.php'; ?>
