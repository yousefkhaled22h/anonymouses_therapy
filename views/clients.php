<?php
if (!defined('IN_DASHBOARD')) {
    header("Location: ../therapist_dashboard.php?view=clients");
    exit();
}

// Fetch unique clients for this therapist
$clients = [];
if ($therapist_id) {
    // Get unique clients with their stats
    $stmt = $pdo->prepare("
        SELECT 
            c.client_id, 
            c.name as client_name, 
            c.anonymous_id,
            COUNT(ps.private_session_id) as total_sessions,
            MAX(ps.session_date) as last_interaction,
            SUM(ps.amount) as total_spent
        FROM private_sessions ps
        JOIN client c ON ps.client_id = c.client_id
        WHERE ps.therapist_id = ? AND LOWER(ps.status) = 'completed'
        GROUP BY c.client_id
        ORDER BY last_interaction DESC
    ");
    $stmt->execute([$therapist_id]);
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Also fetch all past sessions grouped by client for the timeline
    $stmt2 = $pdo->prepare("
        SELECT private_session_id, client_id, session_date, communication_method, amount 
        FROM private_sessions 
        WHERE therapist_id = ? AND LOWER(status) = 'completed'
        ORDER BY session_date DESC
    ");
    $stmt2->execute([$therapist_id]);
    $all_sessions = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    $sessions_by_client = [];
    foreach ($all_sessions as $s) {
        $sessions_by_client[$s['client_id']][] = $s;
    }
}
?>

<style>
    /* Table Responsive Wrapper styling */
    .table-responsive {
        display: block;
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    @media (max-width: 992px) {
        .section-card {
            padding: 20px 15px !important;
        }
        .table th, .table td {
            padding: 12px 10px !important;
            font-size: 0.9rem !important;
        }
    }
    
    @media (max-width: 768px) {
        .table th, .table td {
            padding: 10px 8px !important;
            font-size: 0.85rem !important;
        }
        .table td .btn {
            padding: 6px 12px !important;
            font-size: 0.75rem !important;
            white-space: nowrap;
        }
    }
    
    @media (max-width: 480px) {
        .table th, .table td {
            padding: 8px 6px !important;
            font-size: 0.8rem !important;
        }
    }
</style>

<div class="section-card" style="background: #F8FAFC; padding: 30px; border-radius: 16px;">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--therapist-accent-light); padding-bottom: 15px; margin-bottom: 25px;">
        <h2 style="color: var(--therapist-secondary); margin: 0;"><i class="fa-solid fa-users me-2"></i> My Clients Matrix</h2>
    </div>

    <!-- Master Index Table -->
    <div id="masterIndexView">
        <div class="table-responsive">
            <table class="table" style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                <thead style="background: var(--therapist-primary); color: white;">
                    <tr>
                        <th style="padding: 15px; border: none;">Client Identifier</th>
                        <th style="padding: 15px; border: none;">Completed Sessions</th>
                        <th style="padding: 15px; border: none;">Last Interaction</th>
                        <th style="padding: 15px; border: none;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($clients)): ?>
                        <tr><td colspan="4" style="text-align: center; padding: 30px; color: #64748b;">No client history found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($clients as $client): 
                            $client_label = $client['client_name'] ?: ($client['anonymous_id'] ?: 'Client');
                            $initials = strtoupper(substr($client_label, 0, 2));
                            $last_date = $client['last_interaction'] ? date('M j, Y', strtotime($client['last_interaction'])) : 'N/A';
                            $client_data = json_encode([
                                'id' => $client['client_id'],
                                'name' => $client_label,
                                'initials' => $initials,
                                'total_sessions' => $client['total_sessions'],
                                'last_interaction' => $last_date,
                                'total_spent' => $client['total_spent'],
                                'sessions' => $sessions_by_client[$client['client_id']] ?? []
                            ]);
                        ?>
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 15px; vertical-align: middle;">
                                <div style="display: inline-flex; align-items: center; gap: 10px; background: var(--client-bg); color: var(--client-text); padding: 5px 15px 5px 5px; border-radius: 30px; font-weight: 600;">
                                    <div style="width: 32px; height: 32px; background: white; color: var(--client-text); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.85rem;">
                                        <?php echo $initials; ?>
                                    </div>
                                    <?php echo htmlspecialchars($client_label); ?>
                                </div>
                            </td>
                            <td style="padding: 15px; vertical-align: middle; color: #475569; font-weight: 500;"><?php echo $client['total_sessions']; ?></td>
                            <td style="padding: 15px; vertical-align: middle; color: #475569; font-weight: 500;"><?php echo $last_date; ?></td>
                            <td style="padding: 15px; vertical-align: middle;">
                                <button onclick='openClientFile(<?php echo htmlspecialchars($client_data, ENT_QUOTES, 'UTF-8'); ?>)' class="btn" style="background: var(--therapist-primary); color: white; border-radius: 8px; font-size: 0.85rem; font-weight: 600;">
                                    <i class="fa-solid fa-folder-open me-1"></i> View Clinical File
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Client Details Split-View -->
    <div id="clientSplitView" style="display: none;">
        <button onclick="closeClientFile()" class="btn" style="margin-bottom: 20px; background: transparent; color: var(--therapist-primary); font-weight: 600; padding: 0;">
            <i class="fas fa-arrow-left me-1"></i> Back to Matrix
        </button>
        
        <div class="row">
            <!-- Left Summary Card -->
            <div class="col-md-4">
                <div style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border: 1px solid var(--card-border);">
                    <div style="text-align: center; margin-bottom: 20px;">
                        <div id="cvInitials" style="width: 80px; height: 80px; background: var(--client-bg); color: var(--client-text); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: bold; margin: 0 auto 15px;"></div>
                        <h3 id="cvName" style="margin: 0; color: var(--client-text); font-size: 1.4rem;"></h3>
                    </div>
                    
                    <hr style="border-color: #e2e8f0; margin: 20px 0;">
                    
                    <div style="margin-bottom: 15px;">
                        <small style="color: #94a3b8; font-weight: 600; text-transform: uppercase; font-size: 0.75rem;">Total Sessions</small>
                        <div id="cvTotal" style="font-size: 1.2rem; font-weight: 700; color: #334155;"></div>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <small style="color: #94a3b8; font-weight: 600; text-transform: uppercase; font-size: 0.75rem;">Last Session</small>
                        <div id="cvLast" style="font-size: 1.1rem; font-weight: 600; color: #334155;"></div>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <small style="color: #94a3b8; font-weight: 600; text-transform: uppercase; font-size: 0.75rem;">Billing History</small>
                        <div id="cvSpent" style="font-size: 1.1rem; font-weight: 600; color: #10b981;"></div>
                    </div>
                </div>
            </div>
            
            <!-- Right Progress Tracker -->
            <div class="col-md-8">
                <div style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border: 1px solid var(--card-border);">
                    <h3 style="color: var(--therapist-secondary); margin-top: 0; font-size: 1.3rem; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px; margin-bottom: 20px;">
                        <i class="fa-solid fa-timeline me-2"></i> Interaction Timeline
                    </h3>
                    
                    <div id="cvTimeline" style="max-height: 250px; overflow-y: auto; padding-right: 10px; margin-bottom: 30px;">
                        <!-- Timeline injected via JS -->
                    </div>
                    
                    <h3 style="color: var(--therapist-secondary); font-size: 1.3rem; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px; margin-bottom: 20px;">
                        <i class="fa-solid fa-lock me-2"></i> Private Clinical Progress Notes
                    </h3>
                    
                    <form action="../api/submit_notes.php" method="POST">
                        <input type="hidden" name="patient_first_name" id="cvFormName">
                        <input type="hidden" name="therapist_first_name" value="<?php echo htmlspecialchars($name); ?>">
                        <input type="hidden" name="session_date" value="<?php echo date('Y-m-d'); ?>">
                        
                        <textarea class="form-control" name="treatment_reason" rows="5" placeholder="Record secure, private session notes here. These are strictly visible only to you..." style="width: 100%; padding: 15px; border: 1px solid #cbd5e1; border-radius: 10px; resize: vertical; margin-bottom: 15px; background: #FAFAFA;"></textarea>
                        
                        <button type="submit" class="btn" style="background: var(--therapist-primary); color: white; padding: 10px 25px; border-radius: 8px; font-weight: 600;">
                            <i class="fa-solid fa-save me-1"></i> Save Note
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function openClientFile(client) {
    document.getElementById('masterIndexView').style.display = 'none';
    document.getElementById('clientSplitView').style.display = 'block';
    
    // Populate Left Card
    document.getElementById('cvInitials').innerText = client.initials;
    document.getElementById('cvName').innerText = client.name;
    document.getElementById('cvTotal').innerText = client.total_sessions;
    document.getElementById('cvLast').innerText = client.last_interaction;
    document.getElementById('cvSpent').innerText = client.total_spent + ' EGP';
    
    // Populate Form Hidden Fields
    document.getElementById('cvFormName').value = client.name;
    
    // Populate Timeline
    const timelineContainer = document.getElementById('cvTimeline');
    timelineContainer.innerHTML = '';
    
    if (client.sessions.length === 0) {
        timelineContainer.innerHTML = '<p style="color: #94a3b8;">No past sessions found.</p>';
    } else {
        let html = '';
        client.sessions.forEach(s => {
            const dateStr = new Date(s.session_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
            html += `
                <div style="border-left: 2px solid var(--therapist-accent-light); padding-left: 20px; position: relative; margin-bottom: 20px;">
                    <div style="position: absolute; left: -6px; top: 0; width: 10px; height: 10px; border-radius: 50%; background: var(--therapist-primary);"></div>
                    <strong style="display: block; color: #334155; font-size: 1rem;">${dateStr}</strong>
                    <span style="font-size: 0.85rem; color: #64748b; background: #f1f5f9; padding: 3px 8px; border-radius: 4px; display: inline-block; margin-top: 5px;">
                        <i class="fa-solid fa-video me-1"></i> ${s.communication_method}
                    </span>
                </div>
            `;
        });
        timelineContainer.innerHTML = html;
    }
}

function closeClientFile() {
    document.getElementById('clientSplitView').style.display = 'none';
    document.getElementById('masterIndexView').style.display = 'block';
}
</script>
