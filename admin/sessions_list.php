<?php
// admin_sessions.php
$body_class = 'role-therapist';
require_once '../includes/header.php';
require_once '../includes/db_connect.php';

if (strtolower($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../dashboard.php");
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT ps.*, 
               t.first_name as t_first, t.last_name as t_last,
               c.name as c_name, c.anonymous_id
        FROM private_sessions ps
        JOIN therapist t ON ps.therapist_id = t.therapist_id
        JOIN client c ON ps.client_id = c.client_id
        WHERE ps.status = 'scheduled'
        ORDER BY ps.session_date ASC
    ");
    $stmt->execute();
    $upcoming = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching sessions.");
}
?>

<div class="container" style="padding: 40px 20px;">
    <h1 style="margin-bottom: 30px; color: #1e293b;">Admin: Paid Session Management</h1>
    <p style="color: #64748b; margin-bottom: 40px;">Use this panel to force-start sessions early for testing purposes.</p>

    <div class="card" style="border-radius: 20px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.05); overflow: hidden;">
        <table class="table" style="margin: 0; background: white;">
            <thead style="background: #f8fafc;">
                <tr>
                    <th style="padding: 20px;">Session ID</th>
                    <th>Therapist</th>
                    <th>Client</th>
                    <th>Scheduled Date</th>
                    <th>Method</th>
                    <th style="text-align: right; padding: 20px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($upcoming)): ?>
                    <tr><td colspan="6" style="padding: 40px; text-align: center; color: #94a3b8;">No scheduled sessions found.</td></tr>
                <?php else: ?>
                    <?php foreach ($upcoming as $s): ?>
                        <tr>
                            <td style="padding: 20px; font-family: monospace; font-size: 0.85rem; color: #64748b;"><?php echo $s['private_session_id']; ?></td>
                            <td style="font-weight: 600;">Dr. <?php echo htmlspecialchars($s['t_first'] . ' ' . $s['t_last']); ?></td>
                            <td><?php echo htmlspecialchars($s['c_name'] ?: ($s['anonymous_id'] ?: 'Client')); ?></td>
                            <td><?php echo date('M d, H:i', strtotime($s['session_date'])); ?></td>
                            <td><span class="badge" style="background: #e0f2fe; color: #0369a1;"><?php echo $s['communication_method']; ?></span></td>
                            <td style="text-align: right; padding: 20px;">
                                <button onclick="forceStart('<?php echo $s['private_session_id']; ?>', this)" class="btn btn-sm btn-primary" style="border-radius: 10px; padding: 8px 15px;">
                                    <i class="fas fa-play me-1"></i> Force Start Now
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function forceStart(id, btn) {
    if(!confirm("Open this session now for both therapist and client?")) return;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    
    fetch('../api/booking/confirm_early.php?id=' + id)
        .then(r => r.json())
        .then(data => {
            if(data.status === 'success') {
                btn.className = "btn btn-sm btn-success";
                btn.innerHTML = '<i class="fas fa-check"></i> Session Opened';
                setTimeout(() => location.reload(), 1500);
            } else {
                alert(data.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-play me-1"></i> Force Start Now';
            }
        });
}
</script>

<?php include '../includes/footer.php'; ?>
