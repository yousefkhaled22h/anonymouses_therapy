$file = 'c:\xampp\htdocs\test2\community.php'
$lines = Get-Content $file

# Get part 1: from line 0 to line 254 (0-indexed 0..254 is lines 1..255)
$part1 = $lines[0..254]

# Get part 2: from line 255 to end (0-indexed 255..end is lines 256..end)
$part2 = $lines[255..($lines.Length - 1)]

$missing = @"
        'Relationship' => 0,
        'Trauma' => 0,
        'Other' => 0
    ];

    // Merge actual counts into predefined topics
    // Add any dynamic topics that weren't predefined
    foreach (`$db_topics as `$topic => `$count) {
        `$all_topics[`$topic] = `$count;
    }

} catch (PDOException `$e) {
    // echo `$e->getMessage();
}
?>

<link rel="stylesheet" href="assets/css/dashboard-style.css">
<!-- FontAwesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
    integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
<style>
    /* Dynamic Theme Coloring */
    :root {
        --primary-color: <?php echo `$sidebarTheme; ?>;
        --qna-blue: <?php echo `$sidebarTheme; ?>;
    }

    .dashboard-main {
        padding: 40px;
        background: <?php echo (`$user_role === 'Therapist') ? '#FDFBF8' : ((`$user_role === 'Volunteer') ? '#f9fbf9' : '#FAF8F5'); ?>;
        min-height: 100vh;
        <?php if (`$user_role !== 'Volunteer'): ?>
        margin-left: 0;
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        <?php endif; ?>
    }

    .qna-layout {
        display: flex;
        gap: 25px;
        max-width: 1600px;
        margin: 0 auto;
        align-items: flex-start;
        <?php if (`$user_role !== 'Volunteer'): ?>
        width: 100%;
        justify-content: center;
        <?php endif; ?>
    }

    <?php if (`$user_role !== 'Volunteer'): ?>
    .main-qna-content {
        max-width: 900px;
        width: 100%;
    }
    <?php endif; ?>

    /* Left Sidebar: Topics */
    .topics-sidebar {
        flex: 0 0 300px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }

    .topics-header {
        padding: 20px 20px 15px;
        border-bottom: 1px solid #e2e8f0;
    }

    .topics-header h3 {
        margin: 0;
        color: #1e293b;
        font-size: 1.25rem;
        font-weight: 700;
    }

    .topics-list {
        list-style: none;
        padding: 0;
        margin: 0;
        max-height: 600px;
        overflow-y: auto;
    }

    /* Scrollbar styling for topics */
    .topics-list::-webkit-scrollbar {
        width: 8px;
    }

    .topics-list::-webkit-scrollbar-track {
        background: #f1f5f9;
    }

    .topics-list::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    .topics-list::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    .topics-list li {
        border-bottom: 1px solid #f1f5f9;
    }

    .topics-list li a {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 20px;
        color: #475569;
        text-decoration: none;
        transition: background-color 0.2s;
        font-size: 0.95rem;
    }

    .topics-list li a:hover {
        background-color: #f8fafc;
        color: var(--primary-color);
    }

    .topic-count {
        background: #94a3b8;
        color: white;
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    /* Right Side: Main Content */
    .main-qna-content {
        flex: 1;
        min-width: 0;
    }

    /* Search Bar Area */
    .search-container {
        display: flex;
        margin-bottom: 25px;
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        border: 2px solid var(--primary-color);
    }

    .search-input {
        flex: 1;
        padding: 15px 20px;
        border: none;
        font-size: 1.05rem;
        color: #475569;
        outline: none;
    }

    .search-input::placeholder {
        color: #94a3b8;
    }

    .search-btn {
        background: var(--primary-color);
        color: white;
        border: none;
        padding: 0 25px;
        cursor: pointer;
        font-size: 1.2rem;
        transition: background-color 0.2s, filter 0.2s;
    }

    .search-btn:hover {
        filter: brightness(0.85);
    }

    /* Recent Questions List */
    .questions-container {
        background: transparent;
        border: none;
        box-shadow: none;
        overflow: visible;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .questions-header {
        background: white;
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .questions-header h3 {
        margin: 0;
        color: #1e293b;
"@

$newContent = ($part1 -join "`r`n") + "`r`n" + $missing + "`r`n" + ($part2 -join "`r`n")
Set-Content $file -Value $newContent -Encoding UTF8
Write-Host 'Community.php restored and fixed.'
