<?php
if (!defined('IN_DASHBOARD')) {
    header("Location: ../therapist_dashboard.php?view=bookings");
    exit();
}

$future = [];
$pending = [];
$historical = [];

if ($therapist_id) {
    // Future Sessions
    $stmt = $pdo->prepare("
        SELECT ps.private_session_id as id, ps.session_date, ps.status, ps.amount,
        ps.communication_method, c.name as client_name, c.anonymous_id
        FROM private_sessions ps 
        JOIN client c ON ps.client_id = c.client_id 
        WHERE ps.therapist_id = ? AND DATE_ADD(ps.session_date, INTERVAL ps.duration_minutes MINUTE) >= NOW() AND LOWER(ps.status) IN ('reserved', 'active')
        ORDER BY ps.session_date ASC
    ");
    $stmt->execute([$therapist_id]);
    $future = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pending Adjustments
    $stmt = $pdo->prepare("
        SELECT ps.private_session_id as id, ps.session_date, ps.status, ps.amount,
        ps.communication_method, c.name as client_name, c.anonymous_id
        FROM private_sessions ps 
        JOIN client c ON ps.client_id = c.client_id 
        WHERE ps.therapist_id = ? AND LOWER(ps.status) IN ('pending', 'pending_reschedule')
    ");
    $stmt->execute([$therapist_id]);
    $pending = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Historical Log
    $stmt = $pdo->prepare("
        SELECT ps.private_session_id as id, ps.session_date, ps.status, ps.amount,
        ps.communication_method, c.name as client_name, c.anonymous_id
        FROM private_sessions ps 
        JOIN client c ON ps.client_id = c.client_id 
        WHERE ps.therapist_id = ? AND (DATE_ADD(ps.session_date, INTERVAL ps.duration_minutes MINUTE) < NOW() OR LOWER(ps.status) IN ('completed', 'cancelled'))
        ORDER BY ps.session_date DESC
    ");
    $stmt->execute([$therapist_id]);
    $historical = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function renderSessionRow($session, $type) {
    $client_label = $session['client_name'] ?: ($session['anonymous_id'] ?: 'Client');
    $initials = strtoupper(substr($client_label, 0, 2));
    $time_str = date('D, M j, Y h:i A', strtotime($session['session_date']));
    $status = ucfirst(str_replace('_', ' ', strtolower($session['status'])));
    
    echo '<div class="session-item-row" style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px 20px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.02); flex-wrap: wrap; gap: 15px;">';
    echo '<div style="display: flex; align-items: center; gap: 15px;">';
    // Client Tag (Soft Beige / Warm Coffee)
    echo '<div style="width: 45px; height: 45px; background: var(--client-bg, #F4F1EA); color: var(--client-text, #8D6E63); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">' . $initials . '</div>';
    echo '<div>';
    echo '<strong style="color: var(--client-text, #8D6E63); display: block; margin-bottom: 3px;">' . htmlspecialchars($client_label) . '</strong>';
    echo '<div style="font-size: 0.85rem; color: #64748b; display: flex; gap: 15px;">';
    echo '<span><i class="far fa-calendar-alt"></i> ' . $time_str . '</span>';
    echo '<span><i class="fas fa-video"></i> ' . htmlspecialchars($session['communication_method']) . '</span>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="session-actions-wrapper" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">';
    if ($type === 'future' || $type === 'pending') {
        if ($type === 'future') {
            $session_time = strtotime($session['session_date']);
            $current_time = time();
            $is_joinable = ($current_time >= ($session_time - 300));
            $is_video = strtolower($session['communication_method']) === 'video session';
            $join_link = 'session_room.php?id=' . $session['id'];
            $btn_style = $is_joinable ? 'background:#8D6E63;color:white;border:none;' : 'background:#e2e8f0;color:#64748b;border:none;cursor:not-allowed;';
            $btn_text = $is_joinable ? ($is_video ? '<i class="fas fa-video"></i> Join Zoom' : '<i class="fas fa-comments"></i> Join Chat') : 'Opens in ' . max(1,round(($session_time-$current_time)/60)) . ' min';
            $href = $is_joinable ? $join_link : '#';
            echo '<a href="'.$href.'" class="btn btn-sm session-join-btn" data-time="'.$session_time.'" data-is-video="'.($is_video ? 1 : 0).'" data-join-link="'.$join_link.'" style="font-weight:600;padding:6px 14px;border-radius:20px;'.$btn_style.'">'.$btn_text.'</a>';
        }
        echo '<button onclick="rescheduleSession(\'' . $session['id'] . '\')" class="btn btn-warning btn-sm" style="font-weight: 600;"><i class="fas fa-calendar-alt"></i> Reschedule</button>';
        echo '<button onclick="requestCancelFlow(\'' . $session['id'] . '\')" class="btn btn-outline-danger btn-sm" style="font-weight: 600;"><i class="fas fa-times"></i> Request Cancellation</button>';
    } else {
        // Historical static badges
        $bg = ($status === 'Completed') ? '#DCFCE7' : '#FEE2E2';
        $col = ($status === 'Completed') ? '#166534' : '#991B1B';
        echo '<span style="background: ' . $bg . '; color: ' . $col . '; padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">' . $status . '</span>';
    }
    echo '</div>';
    echo '</div>';
}
?>

<style>
/* CSS fallback for Nav-Tabs and Buttons in case Bootstrap is missing */
.nav-tabs {
    display: flex;
    flex-wrap: wrap;
    padding-left: 0;
    margin-bottom: 20px;
    list-style: none;
    border-bottom: 1px solid #dee2e6;
}
.nav-tabs .nav-item { margin-bottom: -1px; }
.nav-tabs .nav-link {
    display: block;
    padding: .5rem 1rem;
    color: var(--therapist-primary, #337AB7);
    text-decoration: none;
    background: 0 0;
    border: 1px solid transparent;
    border-top-left-radius: .25rem;
    border-top-right-radius: .25rem;
    font-weight: 600;
    cursor: pointer;
}
.nav-tabs .nav-link:hover { border-color: #e9ecef #e9ecef #dee2e6; }
.nav-tabs .nav-link.active {
    color: #495057;
    background-color: #fff;
    border-color: #dee2e6 #dee2e6 #fff;
}
.tab-content > .tab-pane { display: none; }
.tab-content > .active { display: block; }
.btn-warning { background-color: #ffc107; color: #000; border: 1px solid #ffc107; padding: .35rem .75rem; border-radius: .25rem; cursor: pointer; }
.btn-warning:hover { background-color: #e0a800; border-color: #d39e00; }
.btn-outline-danger { color: #dc3545; border: 1px solid #dc3545; background-color: transparent; padding: .35rem .75rem; border-radius: .25rem; cursor: pointer; transition: all 0.2s; }
.btn-outline-danger:hover { background-color: #dc3545; color: white; }

@media (max-width: 768px) {
    .session-item-row {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 15px !important;
    }
    .session-actions-wrapper {
        flex-direction: column !important;
        align-items: stretch !important;
        width: 100% !important;
        gap: 8px !important;
    }
    .session-actions-wrapper .btn {
        width: 100% !important;
        text-align: center !important;
    }
}
</style>

<div class="section-card" style="background: #F8FAFC; padding: 30px; border-radius: 16px;">
    <h2 style="color: var(--therapist-secondary, #1A4D80); margin-top: 0; margin-bottom: 25px;"><i class="fa-solid fa-book-open me-2"></i> Live Bookings & Log</h2>

    <ul class="nav nav-tabs" id="bookingsTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="future-tab" data-bs-toggle="tab" data-bs-target="#future" type="button" role="tab">Future Sessions</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab">Pending Adjustments</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">Historical Log</button>
        </li>
    </ul>

    <div class="tab-content" id="bookingsTabContent">
        <!-- Future Sessions -->
        <div class="tab-pane fade show active" id="future" role="tabpanel">
            <?php 
            if (empty($future)) {
                echo '<p style="color: #64748b; padding: 20px 0;">No future sessions scheduled.</p>';
            } else {
                foreach ($future as $session) {
                    renderSessionRow($session, 'future');
                }
            }
            ?>
        </div>
        
        <!-- Pending Adjustments -->
        <div class="tab-pane fade" id="pending" role="tabpanel">
            <?php 
            if (empty($pending)) {
                echo '<p style="color: #64748b; padding: 20px 0;">No pending adjustments at this time.</p>';
            } else {
                foreach ($pending as $session) {
                    renderSessionRow($session, 'pending');
                }
            }
            ?>
        </div>
        
        <!-- Historical Log -->
        <div class="tab-pane fade" id="history" role="tabpanel">
            <?php 
            if (empty($historical)) {
                echo '<p style="color: #64748b; padding: 20px 0;">No historical records found.</p>';
            } else {
                foreach ($historical as $session) {
                    renderSessionRow($session, 'history');
                }
            }
            ?>
        </div>
    </div>
</div>

<script>
// JS fallback for Bootstrap tabs
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('button[data-bs-toggle="tab"]');
    tabs.forEach(t => {
        t.addEventListener('click', function(e) {
            e.preventDefault();
            // remove active from all tabs
            tabs.forEach(tab => tab.classList.remove('active'));
            // remove active from all panes
            document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active', 'show'));
            
            // add active to clicked tab
            this.classList.add('active');
            // add active to target pane
            const targetId = this.getAttribute('data-bs-target').substring(1);
            const targetPane = document.getElementById(targetId);
            if(targetPane) {
                targetPane.classList.add('active', 'show');
            }
        });
    });
});
</script>
