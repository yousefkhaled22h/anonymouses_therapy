<?php
// booking_success.php
require_once 'includes/db_connect.php';
session_start();

$session_id = $_GET['session_id'] ?? null;

if (!$session_id || !isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT ps.*, t.first_name, t.last_name, t.zoom_link 
    FROM private_sessions ps 
    JOIN therapist t ON ps.therapist_id = t.therapist_id 
    WHERE ps.private_session_id = ? AND ps.client_id = (SELECT client_id FROM client WHERE user_id = ?)
");
$stmt->execute([$session_id, $_SESSION['user_id']]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$session) {
    header("Location: dashboard.php");
    exit();
}

$therapist_name = trim($session['first_name'] . ' ' . $session['last_name']);
$date_display = date('l, F j, Y', strtotime($session['session_date']));
$time_display = date('H:i', strtotime($session['session_date']));
$amount = number_format($session['amount'], 0);
$method = $session['communication_method'] ?? 'Video Session';

// Joinability logic
$session_ts = strtotime($session['session_date']);
$current_ts = time();
$is_joinable = ($current_ts >= ($session_ts - 300)) || ($session['status'] === 'Active'); // 5 mins before or Active

$body_class = 'role-client';
include 'includes/header.php';
?>
<style>
.success-wrapper { max-width: 900px; margin: 40px auto; font-family: 'Inter', sans-serif; }
.success-title { text-align: center; color: #444; font-family: var(--font-heading); font-size: 2.2rem; font-weight: 700; margin-bottom: 5px; }
.success-subtitle { text-align: center; color: #78716c; margin-bottom: 40px; font-size: 1.1rem; }

.cards-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 30px; }
.info-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px; }
.info-card h3 { font-size: 1.1rem; color: #1e293b; margin-top: 0; margin-bottom: 20px; font-weight: 600; display: flex; align-items: center; gap: 8px; }

.detail-row { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 0.95rem; color: #64748b; }
.detail-row.amount { border-top: 1px solid #e2e8f0; padding-top: 15px; margin-top: 20px; margin-bottom: 0; }
.detail-row span:last-child { font-weight: 600; color: #0f172a; }
.detail-row.amount span:last-child { font-size: 1.2rem; color: #15803d; } /* Green amount */

.meeting-input { background: #e9e3d9; border: none; border-radius: 6px; padding: 12px 15px; width: 100%; font-weight: 600; color: #1e293b; margin-bottom: 15px; font-size: 1rem; }
.btn-zoom { background: #2563eb; color: white; border: none; border-radius: 8px; padding: 12px; width: 100%; font-weight: 600; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; transition: all 0.2s; }
.btn-zoom:hover { background: #1d4ed8; color: white; }
.btn-zoom.disabled { background: #94a3b8; pointer-events: none; cursor: not-allowed; }
.btn-outline { background: #fdfbf7; color: #1e293b; border: 1px solid #e9e3d9; border-radius: 8px; padding: 12px; width: 100%; font-weight: 600; transition: all 0.2s; cursor: pointer; }
.btn-outline:hover { background: #e9e3d9; }

.notes-card { background: #fdfbf7; border: 1px solid #fde68a; border-radius: 12px; padding: 25px; }
.notes-card h3 { font-size: 1.1rem; color: #78350f; margin-top: 0; margin-bottom: 15px; font-weight: 600; }
.notes-list { list-style: none; padding: 0; margin: 0; }
.notes-list li { display: flex; align-items: center; gap: 10px; color: #92400e; margin-bottom: 12px; font-size: 0.95rem; }
.notes-list li i { width: 16px; text-align: center; }
</style>

<div class="success-wrapper">
    <h1 class="success-title">Session Booked Successfully!</h1>
    <p class="success-subtitle">Your therapy session has been confirmed and paid</p>

    <div class="cards-grid">
        <!-- Card 1: Details -->
        <div class="info-card" style="border-color: #bbf7d0;">
            <h3 style="color: #166534;"><i class="far fa-calendar-check border rounded p-1"></i> Session Details</h3>
            <div class="detail-row">
                <span>Therapist:</span>
                <span>Dr. <?php echo htmlspecialchars($therapist_name); ?></span>
            </div>
            <div class="detail-row">
                <span>Date:</span>
                <span><?php echo $date_display; ?></span>
            </div>
            <div class="detail-row">
                <span>Time:</span>
                <span><?php echo $time_display; ?></span>
            </div>
            <div class="detail-row amount">
                <span>Amount Paid:</span>
                <span style="color: #16a34a;"><?php echo $amount; ?> EGP</span>
            </div>
        </div>

        <!-- Card 2: Action -->
        <div class="info-card" style="border-color: #bfdbfe;">
            <h3 style="color: #1e40af;"><i class="fas fa-video"></i> <?php echo $method === 'Video Session' ? 'Meeting Information' : 'Session Access'; ?></h3>
                        <?php if ($session['communication_method'] === 'Video Session'): ?>
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 25px; border-radius: 12px; margin-bottom: 30px; text-align: left;">
                            <h4 style="margin-bottom: 15px; color: #1e293b;">Zoom Meeting Details</h4>
                            <div style="display: flex; flex-direction: column; gap: 8px; color: #64748b; font-size: 0.95rem;">
                                <div><strong>Meeting ID:</strong> <span style="font-family: monospace;"><?php echo htmlspecialchars($session['zoom_meeting_id'] ?? '845 2391 0024'); ?></span></div>
                                <div><strong>Passcode:</strong> <span style="font-family: monospace;">SH2026 (Embedded in Link)</span></div>
                            </div>
                            <button onclick="joinZoom('<?php echo $session['private_session_id']; ?>', '<?php echo htmlspecialchars($session['zoom_join_url'] ?: ($session['zoom_link'] ?? 'https://zoom.us/test')); ?>')" class="btn btn-primary" style="margin-top: 20px; width: 100%; padding: 12px; <?php if(!$is_joinable) echo 'opacity: 0.5; cursor: not-allowed;'; ?>" <?php if(!$is_joinable) echo 'disabled'; ?>>
                                <i class="fas fa-video me-2"></i> Join Zoom Meeting
                            </button>
                        </div>
                    <?php else: ?>
                        <a href="session_room.php?id=<?php echo $session['private_session_id']; ?>" class="btn btn-primary" style="margin-top: 20px; width: 100%; padding: 12px; <?php if(!$is_joinable) echo 'opacity: 0.5; cursor: not-allowed;'; ?>" <?php if(!$is_joinable) echo ($is_joinable ? '' : 'onclick="return false;"'); ?>>
                            <i class="fas fa-door-open me-2"></i> Enter Secure Room
                        </a>
                    <?php endif; ?>

<script>
function joinZoom(id, url) {
    if(!confirm("Open session and redirect to Zoom?")) return;
    const fd = new FormData();
    fd.append('id', id);
    // Mark session as Active first
    fetch('api/booking/confirm_early.php', {method:'POST', body: fd})
        .then(() => { window.open(url, '_blank'); location.reload(); });
}

// Session manually ended by user or therapist

</script>
        </div>
    </div>

    <!-- Important Notes -->
    <div class="notes-card">
        <h3>Important Notes</h3>
        <ul class="notes-list">
            <li><i class="far fa-clock"></i> Please join the meeting 5 minutes before the scheduled time</li>
            <?php if ($method === 'Video Session'): ?>
                <li><i class="fas fa-video"></i> Make sure you have Zoom installed on your device</li>
            <?php endif; ?>
            <li><i class="far fa-check-circle"></i> Find a quiet, private space for your session</li>
            <li><i class="far fa-check-circle"></i> Have a stable internet connection</li>
        </ul>
    </div>
    
    <div style="text-align: center; margin-top: 30px;">
        <a href="dashboard.php" class="btn-wizard" style="background: #57534e; text-decoration: none; padding: 12px 30px; display: inline-block; width: auto;">Return to Dashboard</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
