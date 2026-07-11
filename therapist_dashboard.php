<?php
// therapist_dashboard.php
$body_class = 'role-therapist';
require_once 'includes/header.php';
require_once 'includes/db_connect.php';

// 1. Role Check
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Therapist') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$therapist_id = $_SESSION['therapist_id'] ?? null;

// Handle Procedural POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $session_id = $_POST['session_id'] ?? null;
    
    if ($action === 'process_reschedule' && $session_id) {
        $p_date = $_POST['proposed_date'] ?? null;
        $p_from = $_POST['proposed_from_time'] ?? null;
        $p_to = $_POST['proposed_to_time'] ?? null;
        
        if ($p_date && $p_from && $p_to) {
            $new_datetime = $p_date . ' ' . $p_from . ':00';
            try {
                // We map the "From" time into proposed_datetime, and "To" time into early_start_to to avoid adding new columns
                $up1 = $pdo->prepare("UPDATE private_sessions SET status = 'pending_reschedule', proposed_datetime = ?, early_start_to = ?, reschedule_requested = 1 WHERE private_session_id = ? AND therapist_id = ?");
                $up1->execute([$new_datetime, $p_to, $session_id, $therapist_id]);
                // Redirect to avoid form resubmission
                header("Location: therapist_dashboard.php?view=bookings");
                exit();
            } catch (PDOException $e) {}
        }
    }
    
    if ($action === 'process_cancellation' && $session_id) {
        $reason = $_POST['cancel_reason'] ?? '';
        if ($reason) {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("SELECT client_id, amount, status FROM private_sessions WHERE private_session_id = ? AND therapist_id = ?");
                $stmt->execute([$session_id, $therapist_id]);
                $session = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($session && $session['status'] !== 'cancelled') {
                    $prefix_reason = 'Cancelled by Therapist: ' . $reason;
                    $up1 = $pdo->prepare("UPDATE private_sessions SET status = 'cancelled', cancel_reason = ?, cancellation_acknowledged = 0 WHERE private_session_id = ?");
                    $up1->execute([$prefix_reason, $session_id]);
                    
                    $up2 = $pdo->prepare("UPDATE client SET wallet_balance = wallet_balance + ? WHERE client_id = ?");
                    $up2->execute([$session['amount'], $session['client_id']]);
                }
                $pdo->commit();
                // Redirect to avoid form resubmission
                header("Location: therapist_dashboard.php?view=bookings");
                exit();
            } catch (PDOException $e) {
                if($pdo->inTransaction()) $pdo->rollBack();
            }
        }
    }
}

// Ensure therapist_id is set
$is_verified = false;
if (!$therapist_id) {
    try {
        $stmt = $pdo->prepare("SELECT therapist_id, verified FROM therapist WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch();
        $therapist_id = $row['therapist_id'] ?? null;
        $is_verified = ($row['verified'] ?? 0) == 1;
        $_SESSION['therapist_id'] = $therapist_id;
    } catch (PDOException $e) {}
} else {
    try {
        $stmt = $pdo->prepare("SELECT verified FROM therapist WHERE therapist_id = ?");
        $stmt->execute([$therapist_id]);
        $is_verified = $stmt->fetchColumn() == 1;
    } catch (PDOException $e) {
        $is_verified = false;
    }
}

// Fetch Therapist Info
try {
    $stmt = $pdo->prepare("SELECT t.first_name, t.last_name, t.profile_image, u.email 
                            FROM therapist t 
                            JOIN user u ON t.user_id = u.user_id 
                            WHERE t.user_id = ?");
    $stmt->execute([$user_id]);
    $t_info = $stmt->fetch();
    $first = $t_info['first_name'] ?? '';
    $last = $t_info['last_name'] ?? '';
    $name = trim($first . ' ' . $last);
    $email = $t_info['email'] ?? 'therapist@example.com';
    $profile_image = $t_info['profile_image'] ?? null;

    if (empty($name)) {
        $email_part = explode('@', $email)[0];
        $name = ucwords(str_replace(['.', '_', '-'], ' ', $email_part));
    }
} catch (PDOException $e) {
    $email = 'therapist@example.com';
    $name = 'Therapist';
}

$view = $_GET['view'] ?? 'home';
$user_role = $_SESSION['role'] ?? 'Therapist';
define('IN_DASHBOARD', true);

// Dynamic Badges are now fetched inside includes/therapist_sidebar.php
?>


<style>
    /* Premium SaaS CSS Variables */
    :root {
        --therapist-primary: #2563eb;
        --therapist-secondary: #1e293b;
        --therapist-accent-light: #bfdbfe;
        --therapist-accent-dark: #60a5fa;
        --therapist-bg: #f8fafc;
        
        --client-bg: #eff6ff;
        --client-text: #1d4ed8;
        
        --card-border: rgba(0,0,0,0.05);
    }

    body {
        background-color: var(--therapist-bg);
        color: var(--therapist-secondary);
    }
    
    /* Header/Footer Overrides for Therapist Dashboard */
    body.role-therapist header {
        background-color: rgba(240, 249, 255, 0.85) !important; /* Light baby blue, slightly transparent */
        backdrop-filter: blur(12px);
        box-shadow: 0 4px 15px -3px rgba(37, 99, 235, 0.08) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.3) !important;
    }

    /* Ensure footer inside therapist main is full width and at bottom */
    .ds-main-content .minimal-footer {
        margin-top: auto !important;
        margin-left: -30px !important;
        margin-right: -30px !important;
        margin-bottom: -30px !important;
        padding: 60px 40px 30px !important;
        width: auto !important;
        box-sizing: border-box !important;
        background: transparent !important;
        border-top: none !important;
    }
    .ds-main-content .minimal-footer::before {
        display: none !important;
    }
    /* Layout Framework */
    .ds-container {
        display: flex;
        flex-direction: column;
        min-height: calc(100vh - 80px);
    }
    @media (min-width: 993px) {
        .ds-container {
            flex-direction: row;
        }
    }
    .ds-sidebar {
        width: 100%;
        background: var(--therapist-secondary);
        padding: 30px 0;
    }
    @media (min-width: 993px) {
        .ds-sidebar {
            width: 250px;
            flex-shrink: 0;
            position: fixed;
            top: 80px;
            left: 0;
            height: calc(100vh - 80px);
            overflow-y: auto;
            transition: all 0.3s ease;
        }
        [dir="rtl"] .ds-sidebar {
            right: auto;
            left: 0;
        }
    }
    .ds-main {
        flex-grow: 1;
        padding: 40px;
        background: var(--therapist-bg);
        min-height: calc(100vh - 80px);
        display: flex;
        flex-direction: column;
        box-sizing: border-box;
        width: 100%;
    }
    @media (min-width: 993px) {
        .ds-main {
            margin-left: 250px;
            width: calc(100% - 250px);
            transition: all 0.3s ease;
        }
    }
    /* Collapsed Sidebar for Therapist Dashboard */
    @media (min-width: 993px) {
        .ds-container.sidebar-closed .ds-sidebar {
            transform: translateX(-250px);
        }
        .ds-container.sidebar-closed .ds-main {
            margin-left: 0;
            width: 100%;
        }
    }
    @media (max-width: 992px) {
        .dashboard-header-row {
            display: none !important;
        }
    }
    .sidebar-toggle-inline {
        background: #2563eb !important;
        border: none !important;
        color: white !important;
        width: 52px !important;
        height: 52px !important;
        border-radius: 16px !important;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem !important;
        transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55) !important;
        box-shadow: 0 8px 25px rgba(37, 99, 235, 0.4) !important;
        flex-shrink: 0;
        margin-top: 5px;
    }
    .sidebar-toggle-inline:hover {
        transform: scale(1.1) rotate(5deg) !important;
        box-shadow: 0 12px 30px rgba(37, 99, 235, 0.6) !important;
        filter: brightness(1.1);
    }
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .sidebar-link {
        display: block;
        padding: 15px 30px;
        color: var(--therapist-accent-light);
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
        font-size: 1.05rem;
    }
    
    .sidebar-link:hover, .sidebar-link.active {
        background: rgba(255,255,255,0.1);
        color: white;
        border-left-color: var(--therapist-accent-light);
    }
    
    .main-canvas {
        padding: 40px;
        background: var(--therapist-bg);
        min-height: calc(100vh - 80px);
    }

    /* Common Card Styles */
    .section-card {
        background: white;
        border-radius: 24px;
        padding: 35px;
        border: 1px solid var(--card-border);
        box-shadow: 0 4px 6px rgba(0,0,0,0.02);
    }
    .stat-mini-card {
        background: white;
        padding: 24px 28px;
        border-radius: 16px;
        border: 1px solid rgba(0,0,0,0.03);
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        height: 100%;
        position: relative;
        overflow: hidden;
    }
    .stat-mini-card::before {
        content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: var(--therapist-primary); opacity: 0; transition: opacity 0.3s ease;
    }
    .stat-mini-card:hover::before { opacity: 1; }
    .stat-mini-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.08), 0 4px 6px -2px rgba(0,0,0,0.04);
    }
    .stat-mini-card h4 {
        margin: 0; font-size: 0.9rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;
    }
    .stat-mini-card div.val {
        font-size: 2.2rem; font-weight: 800; margin-top: 10px; color: var(--therapist-secondary); line-height: 1.1;
    }
    .stat-icon-s {
        font-size: 1.15rem; width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white !important; flex-shrink: 0;
    }
    .stat-icon-total { background: #2563eb !important; }
    .stat-icon-pending { background: #f97316 !important; }
    .stat-icon-completed { background: #22c55e !important; }
    .stat-icon-earnings { background: #8b5cf6 !important; }


    .profile-header-card {
        background: url('assets/images/therapist_banner.png') center/cover no-repeat;
        background-color: var(--therapist-primary);
        color: white; border-radius: 20px; display: flex; align-items: center; gap: 35px;
        box-shadow: 0 10px 25px rgba(37, 99, 235, 0.15); position: relative; overflow: hidden;
    }
    .profile-header-card::after {
        content: ''; position: absolute; inset: 0; background: linear-gradient(90deg, rgba(30,41,59,0.8) 0%, rgba(37,99,235,0.4) 100%); z-index: 1;
    }
    .profile-header-card > * { z-index: 2; position: relative; }
    .badge-verified-inline {
        background: var(--therapist-accent-dark); color: var(--therapist-secondary); padding: 5px 12px;
        border-radius: 10px; font-size: 0.9rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;
    }
    .badge-licensed {
        background: var(--therapist-accent-light); color: var(--therapist-secondary); padding: 8px 22px;
        border-radius: 25px; font-size: 0.95rem; font-weight: 700; display: inline-block;
    }
    .btn-join {
        padding: 10px 25px; border-radius: 10px; font-weight: 600; border: none; cursor: pointer; transition: all 0.3s;
    }
    .btn-join:hover { filter: brightness(0.95); }
    @media (max-width: 768px) {
        .profile-header-card {
            flex-direction: column !important;
            text-align: center !important;
            padding: 25px 20px !important;
            gap: 20px !important;
        }
        .profile-header-card .profile-info h1 {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }
        .profile-header-card .badge-verified-inline {
            margin-left: 0 !important;
            margin-top: 5px !important;
        }
    }

    /* Fallback Modal Styles (since Bootstrap CSS is missing globally) */
    .modal { display: none; position: fixed; z-index: 1055; left: 0; top: 0; width: 100%; height: 100%; overflow-x: hidden; overflow-y: auto; outline: 0; background-color: rgba(0,0,0,0.55); backdrop-filter: blur(2px); }
    .modal.show { display: flex; align-items: center; justify-content: center; }
    @media (max-width: 768px) {
        .modal.show {
            align-items: flex-start;
            overflow-y: auto;
            padding: 40px 15px;
        }
        .modal-dialog {
            margin: 0 auto;
        }
    }
    .modal-dialog { position: relative; width: 100%; max-width: 500px; pointer-events: none; margin: 1.75rem auto; }
    .modal-content { position: relative; display: flex; flex-direction: column; width: 100%; pointer-events: auto; background-color: #fff; background-clip: padding-box; border: 1px solid rgba(0,0,0,.2); border-radius: 16px; outline: 0; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
    .modal-header { display: flex; flex-shrink: 0; align-items: center; justify-content: space-between; padding: 20px 25px; border-bottom: 1px solid #e2e8f0; border-top-left-radius: 15px; border-top-right-radius: 15px; }
    .modal-title { margin: 0; line-height: 1.5; font-size: 1.25rem; font-weight: 700; color: #1e293b; }
    .btn-close { box-sizing: content-box; width: 1em; height: 1em; padding: .25em .25em; color: #000; background: transparent; border: 0; border-radius: .25rem; opacity: .5; cursor: pointer; }
    .btn-close:before { content: "×"; font-size: 1.5rem; line-height: 1; }
    .modal-body { position: relative; flex: 1 1 auto; padding: 25px; }
    .modal-footer { display: flex; flex-wrap: wrap; flex-shrink: 0; align-items: center; justify-content: flex-end; padding: 15px 25px; border-top: 1px solid #e2e8f0; border-bottom-right-radius: 15px; border-bottom-left-radius: 15px; gap: 10px; }

    /* Button and Form Fallbacks */
    .btn { display: inline-block; font-weight: 600; line-height: 1.5; text-align: center; text-decoration: none; vertical-align: middle; cursor: pointer; user-select: none; background-color: transparent; border: 1px solid transparent; padding: .375rem .75rem; font-size: 1rem; border-radius: 8px; transition: all .15s ease-in-out; }
    .btn-warning { color: #000; background-color: #FBC02D; border-color: #FBC02D; }
    .btn-warning:hover { filter: brightness(0.95); }
    .btn-danger { color: #fff; background-color: #ef4444; border-color: #ef4444; }
    .btn-danger:hover { background-color: #dc2626; border-color: #dc2626; }
    .btn-outline-danger { color: #ef4444; border-color: #ef4444; background: transparent; }
    .btn-outline-danger:hover { color: #fff; background-color: #ef4444; }
    .btn-light { color: #334155; background-color: #f1f5f9; border-color: #f1f5f9; }
    .btn-light:hover { background-color: #e2e8f0; border-color: #e2e8f0; }
    .btn-sm { padding: .25rem .5rem; font-size: .875rem; border-radius: 6px; }
    .form-control { display: block; width: 100%; box-sizing: border-box; padding: 10px 15px; font-size: 1rem; font-weight: 400; line-height: 1.5; color: #334155; background-color: #fff; background-clip: padding-box; border: 1px solid #cbd5e1; border-radius: 8px; transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out; }
    .mb-3 { margin-bottom: 1rem!important; }
    .form-label { margin-bottom: .5rem; font-weight: 600; color: #334155; display: block; }
    .text-muted { color: #64748b!important; margin-top: 0; }
</style>
<script>
    window.serverTimeOffset = <?php echo time(); ?> * 1000 - Date.now();
</script>

<div class="ds-container">
    <?php include 'includes/therapist_sidebar.php'; ?>
    
    <!-- Right Main Canvas Column -->
    <div class="ds-main main-canvas">
        <!-- Sidebar toggle button for mobile/desktop -->
        <div class="dashboard-header-row" style="display: flex; gap: 20px; align-items: stretch; margin-bottom: 30px;">
            <button id="sidebarToggle" class="sidebar-toggle-inline" aria-label="Toggle Sidebar">
                <i id="toggleIcon" class="fa-solid fa-bars-staggered"></i>
            </button>
        </div>

        

        <?php
            if (!$is_verified && in_array($view, ['bookings', 'clients', 'group_hub'])) {
                echo '<div style="background: #FFF9C4; color: #856404; padding: 20px; border-radius: 16px; margin-bottom: 30px; border-left: 6px solid #FBC02D; display: flex; align-items: center; gap: 20px;">
                        <div style="font-size: 2rem;"><i class="fa-solid fa-clock-rotate-left"></i></div>
                        <div>
                            <h3 style="margin: 0; font-size: 1.2rem; font-weight: 800;">Awaiting Account Approval</h3>
                            <p style="margin: 5px 0 0; font-size: 0.95rem; opacity: 0.9;">Your professional credentials are currently being reviewed. Access to this section is restricted until your account is verified.</p>
                        </div>
                      </div>';
            } else {
                switch ($view) {
                    case 'home':
                        include 'views/dashboard_home.php';
                        break;
                    case 'bookings':
                        include 'views/bookings.php';
                        break;
                    case 'availability':
                        include 'views/availability.php';
                        break;
                    case 'clients':
                        include 'views/clients.php';
                        break;
                    case 'groups':
                        $__embedded_mode = true;
                        $user_role = 'Therapist';
                        include 'group_management.php';
                        break;
                    case 'community':
                        $__embedded_mode = true;
                        include 'community.php';
                        break;
                    case 'resources':
                        $__embedded_mode = true;
                        include 'resources.php';
                        break;
                    default:
                        include 'views/dashboard_home.php';
                        break;
                }
            }
            ?>
        <?php require_once 'includes/footer.php'; ?>
    </div>
</div>

<!-- Reschedule Modal -->
<div class="modal fade" id="rescheduleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">📅 Reschedule Session</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="therapist_dashboard.php?view=bookings" method="POST" id="rescheduleForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="process_reschedule">
                    <input type="hidden" name="session_id" id="reschedule_session_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Proposed Date</label>
                        <input type="date" name="proposed_date" id="proposedDate" required class="form-control">
                    </div>
                    <div style="display: flex; gap: 15px;">
                        <div class="mb-3" style="flex: 1;">
                            <label class="form-label">From Time</label>
                            <select name="proposed_from_time" id="proposedFromTime" required class="form-select"></select>
                        </div>
                        <div class="mb-3" style="flex: 1;">
                            <label class="form-label">To Time</label>
                            <select name="proposed_to_time" id="proposedToTime" required class="form-select"></select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Send Proposal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Request Cancellation Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-top: 4px solid #ef4444;">
            <div class="modal-header">
                <h5 class="modal-title">⚠️ Request Cancellation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="therapist_dashboard.php?view=bookings" method="POST" id="cancelForm">
                <div class="modal-body">
                    <p class="text-muted">The client will be notified and their wallet will be automatically refunded.</p>
                    <input type="hidden" name="action" value="process_cancellation">
                    <input type="hidden" name="session_id" id="cancel_session_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Reason for Cancellation</label>
                        <textarea name="cancel_reason" required class="form-control" placeholder="Please specify your medical or scheduling reason for cancellation..." rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Back</button>
                    <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let reschModal;
let cancModal;

document.addEventListener('DOMContentLoaded', function() {
    if (typeof bootstrap !== 'undefined') {
        reschModal = new bootstrap.Modal(document.getElementById('rescheduleModal'));
        cancModal = new bootstrap.Modal(document.getElementById('cancelModal'));
    }
});

function rescheduleSession(id, duration_minutes) {
    document.getElementById('reschedule_session_id').value = id;
    duration_minutes = duration_minutes || 60;
    
    // Set min constraint to current date to prevent past bookings
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    const todayStr = now.toISOString().slice(0,10);
    document.getElementById('proposedDate').min = todayStr;
    document.getElementById('proposedDate').value = '';
    
    let fromSelect = document.getElementById('proposedFromTime');
    let toSelect = document.getElementById('proposedToTime');
    
    fromSelect.innerHTML = '<option value="">Select Time</option>';
    toSelect.innerHTML = '<option value="">Select Time</option>';
    
    let stepMinutes = (duration_minutes == 30) ? 30 : 60;
    
    for (let h = 0; h < 24; h++) {
        for (let m = 0; m < 60; m += stepMinutes) {
            let hr = String(h).padStart(2, '0');
            let mn = String(m).padStart(2, '0');
            let timeVal = hr + ':' + mn;
            
            let displayHr = h % 12 || 12;
            let ampm = h >= 12 ? 'PM' : 'AM';
            let displayStr = String(displayHr).padStart(2, '0') + ':' + mn + ' ' + ampm;
            
            fromSelect.innerHTML += `<option value="${timeVal}">${displayStr}</option>`;
            toSelect.innerHTML += `<option value="${timeVal}">${displayStr}</option>`;
        }
    }
    
    if (reschModal) {
        reschModal.show();
    } else {
        // Fallback if bootstrap JS is missing
        const modal = document.getElementById('rescheduleModal');
        modal.style.display = 'flex';
        modal.classList.add('show');
    }
}

function requestCancelFlow(id) {
    document.getElementById('cancel_session_id').value = id;
    if (cancModal) {
        cancModal.show();
    } else {
        // Fallback if bootstrap JS is missing
        const modal = document.getElementById('cancelModal');
        modal.style.display = 'flex';
        modal.classList.add('show');
    }
}

// Fallback close buttons for modals
document.querySelectorAll('[data-bs-dismiss="modal"]').forEach(btn => {
    btn.addEventListener('click', function() {
        const modal = this.closest('.modal');
        if (modal && !window.bootstrap) {
            modal.style.display = 'none';
            modal.classList.remove('show');
        }
    });
});

// Sidebar Toggle Logic
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('sidebarToggle');
    const container = document.querySelector('.ds-container');
    const sidebar = document.getElementById('dsSidebarMenu');
    const icon = document.getElementById('toggleIcon');
    
    // Read preference from localStorage
    if (window.innerWidth > 992 && localStorage.getItem('therapistSidebarClosed') === 'true') {
        if (container) container.classList.add('sidebar-closed');
        if (icon) icon.className = 'fa-solid fa-arrow-right-long';
    }

    if (toggleBtn && container) {
        toggleBtn.addEventListener('click', function() {
            const isMobile = window.innerWidth <= 992;
            if (isMobile) {
                if (sidebar) {
                    sidebar.classList.toggle('open');
                }
            } else {
                container.classList.toggle('sidebar-closed');
                const isClosed = container.classList.contains('sidebar-closed');
                localStorage.setItem('therapistSidebarClosed', isClosed);
                if (icon) {
                    if (isClosed) {
                        icon.className = 'fa-solid fa-arrow-right-long';
                    } else {
                        icon.className = 'fa-solid fa-bars-staggered';
                    }
                }
            }
        });
    }

    // Countdown Timer Logic
    function updateCountdowns() {
        const now = Date.now() + (window.serverTimeOffset || 0);
        
        // Update countdown badges
        document.querySelectorAll('.session-countdown').forEach(el => {
            const sessionTime = parseInt(el.dataset.time) * 1000;
            const diff = sessionTime - now;
            
            if (diff <= 300 * 1000) {
                el.innerHTML = '<span style="color: #22c55e;"><i class="fa-solid fa-circle-play me-1"></i> Session is Open</span>';
            } else {
                const totalSec = Math.max(0, Math.floor(diff / 1000));
                const d = Math.floor(totalSec / 86400);
                const h = Math.floor((totalSec % 86400) / 3600);
                const m = Math.floor((totalSec % 3600) / 60);
                const s = totalSec % 60;
                
                let str = '';
                if (d > 0) {
                    str += d + 'd : ';
                }
                str += String(h).padStart(2, '0') + 'h : ' + String(m).padStart(2, '0') + 'm : ' + String(s).padStart(2, '0') + 's';
                el.textContent = 'Opens in ' + str;
            }
        });

        // Update join buttons
        document.querySelectorAll('.session-join-btn').forEach(btn => {
            const sessionTime = parseInt(btn.dataset.time) * 1000;
            const diff = sessionTime - now;
            const isVideo = btn.dataset.isVideo === '1';
            const joinLink = btn.dataset.joinLink;
            
            if (diff <= 300 * 1000) {
                btn.href = joinLink;
                btn.style.background = '#2563eb';
                btn.style.color = 'white';
                btn.style.cursor = 'pointer';
                btn.innerHTML = isVideo ? '<i class="fas fa-video me-1"></i> Join Zoom' : '<i class="fas fa-comments me-1"></i> Join Chat';
            } else {
                btn.href = '#';
                btn.style.background = '#f1f5f9';
                btn.style.color = '#94a3b8';
                btn.style.cursor = 'not-allowed';
                
                const totalMin = Math.max(1, Math.round(diff / 60000));
                if (totalMin > 60) {
                    const h = Math.floor(totalMin / 60);
                    const m = totalMin % 60;
                    btn.textContent = 'Opens in ' + h + 'h ' + m + 'm';
                } else {
                    btn.textContent = 'Opens in ' + totalMin + ' min';
                }
            }
        });
    }
    
    updateCountdowns();
    setInterval(updateCountdowns, 1000);
});
</script>