<?php
// therapist_schedule.php Ã¢â‚¬â€œ Beige Collection + Drag-to-Select Availability Manager
$body_class = 'role-therapist';
require_once 'includes/header.php';
require_once 'includes/db_connect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Therapist') {
    header("Location: login.php"); exit();
}

$therapist_id = $_SESSION['therapist_id'];

// Check Verification Status
$is_verified = false;
try {
    $stmt = $pdo->prepare("SELECT verified FROM therapist WHERE therapist_id = ?");
    $stmt->execute([$therapist_id]);
    $is_verified = ($stmt->fetchColumn() ?? 0) == 1;
} catch (PDOException $e) {
    $is_verified = false;
}

if (!$is_verified) {
    header("Location: therapist_dashboard.php");
    exit();
}

$message = '';
$msg_type = 'success';

$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

function fetchSchedule($pdo, $tid) {
    $s = [];
    try {
        $stmt = $pdo->prepare("SELECT * FROM therapist_availability WHERE therapist_id = ?");
        $stmt->execute([$tid]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $s[$r['day_of_week']] = [
                'is_available' => $r['is_available'] == 1,
                'start_time'   => date('H:i', strtotime($r['start_time'])),
                'end_time'     => date('H:i', strtotime($r['end_time'])),
            ];
        }
    } catch (PDOException $e) {}
    return $s;
}

// Fetch therapist rate for EGP display
$rate_row = $pdo->prepare("SELECT hourly_rate FROM therapist WHERE therapist_id = ?");
$rate_row->execute([$therapist_id]);
$hourly_rate = floatval($rate_row->fetchColumn() ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_rate']) && is_numeric($_POST['rate_egp'])) {
        $new_rate = floatval($_POST['rate_egp']);
        $pdo->prepare("UPDATE therapist SET hourly_rate=? WHERE therapist_id=?")->execute([$new_rate,$therapist_id]);
        $hourly_rate = $new_rate;
        $message = "Rate updated to " . number_format($new_rate,0) . " EGP successfully.";
    } elseif (isset($_POST['availability'])) {
        try {
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM therapist_availability WHERE therapist_id=?")->execute([$therapist_id]);
            $ins = $pdo->prepare("INSERT INTO therapist_availability (therapist_id,day_of_week,start_time,end_time,is_available) VALUES (?,?,?,?,?)");
            foreach ($_POST['availability'] as $day => $data) {
                $active = isset($data['active']) ? 1 : 0;
                $start  = !empty($data['start']) ? $data['start'] : '09:00';
                $end    = !empty($data['end'])   ? $data['end']   : '17:00';
                $ins->execute([$therapist_id, $day, $start, $end, $active]);
            }
            // Server-side: validate end > start for each active day
            $time_error = false;
            foreach ($_POST['availability'] as $day => $data) {
                if (isset($data['active']) && !empty($data['start']) && !empty($data['end'])) {
                    if ($data['end'] <= $data['start']) {
                        $message  = "Invalid time range for {$day}: End time must be after Start time.";
                        $msg_type = 'error';
                        $time_error = true;
                        break;
                    }
                }
            }
            if (!$time_error) {
                $pdo->commit();
                $message = '__SUCCESS__';
            } else {
                $pdo->rollBack();
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $message  = "Error: " . $e->getMessage();
            $msg_type = 'error';
        }
    }
}

$saved = fetchSchedule($pdo, $therapist_id);
?>
<style>
/* Ã¢â€¢ÂÃ¢â€¢Â THERAPIST SCHEDULE Ã¢â‚¬â€ BLUE THEME Ã¢â€¢ÂÃ¢â€¢Â */
:root {
    --th-blue:    #337AB7;
    --th-blue-l:  #4A7AAB;
    --th-blue-dk: #286090;
    --th-blue-bg: #F4F9FD;
    --th-blue-bd: #D1E5F7;
    --charcoal:   #1E293B;
    --muted:      #64748B;
    --green-s:    #16A34A;
    --green-bg:   #F0FDF4;
}
body { background: #F0F4FA; }

.sch-wrap { max-width: 920px; margin: 0 auto; padding: 36px 24px 80px; }
.sch-back {
    display: inline-flex; align-items: center; gap: 8px;
    color: var(--muted); font-size: .88rem; font-weight: 600;
    text-decoration: none; margin-bottom: 22px; transition: color .2s;
}
.sch-back:hover { color: var(--th-blue); }

/* Hero card */
.sch-hero {
    background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%);
    border-radius: 24px; padding: 36px 38px; margin-bottom: 28px;
    border: 1px solid var(--th-blue-bd);
    box-shadow: 0 8px 28px rgba(37,99,235,.08);
    display: flex; align-items: center; justify-content: space-between; gap: 24px; flex-wrap: wrap;
}
.sch-hero-text h2 { font-size: 1.9rem; font-weight: 900; color: var(--charcoal); margin-bottom: 6px; }
.sch-hero-text p  { color: var(--muted); font-size: .97rem; line-height: 1.5; }

/* Alert */
.sch-alert {
    padding: 14px 20px; border-radius: 14px; margin-bottom: 22px;
    font-weight: 700; font-size: .92rem; display: flex; align-items: center; gap: 10px;
}
.sch-alert.success { background: var(--green-bg); color: var(--green-s); border: 1px solid rgba(22,163,74,.2); }
.sch-alert.error   { background: #FEE2E2; color: #991B1B; border: 1px solid #FECACA; }

/* Rate card */
.rate-card {
    background: white; border-radius: 22px; padding: 30px 36px; margin-bottom: 26px;
    border: 1px solid var(--th-blue-bd);
    box-shadow: 0 4px 18px rgba(37,99,235,.06);
}
.rate-card-title {
    font-size: 1.05rem; font-weight: 800; color: var(--charcoal);
    margin-bottom: 18px; display: flex; align-items: center; gap: 10px;
}
.rate-card-title i { color: var(--th-blue); }
.rate-input-row { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
.rate-egp-wrap  { position: relative; }
.rate-egp-label {
    position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
    font-weight: 800; color: var(--th-blue); font-size: 1rem; pointer-events: none;
}
.rate-input {
    padding: 14px 18px 14px 60px; font-size: 1.1rem; font-weight: 700;
    border: 2px solid var(--th-blue-bd); border-radius: 14px; outline: none;
    color: var(--charcoal); width: 200px; transition: border .2s; background: white;
}
.rate-input:focus { border-color: var(--th-blue); box-shadow: 0 0 0 4px rgba(37,99,235,.1); }
.rate-note { font-size: .8rem; color: var(--muted); line-height: 1.5; }
.btn-rate-save {
    padding: 14px 28px; border-radius: 50px;
    background: linear-gradient(135deg, var(--th-blue-dk), var(--th-blue-l));
    color: white; font-weight: 800; border: none; cursor: pointer;
    box-shadow: 0 4px 14px rgba(37,99,235,.28); transition: all .22s;
}
.btn-rate-save:hover { transform: translateY(-2px); box-shadow: 0 8px 22px rgba(37,99,235,.4); }

/* Schedule card */
.sched-card {
    background: white; border-radius: 22px; padding: 32px 36px;
    border: 1px solid var(--th-blue-bd);
    box-shadow: 0 4px 18px rgba(37,99,235,.06);
}
.sched-card-title {
    font-size: 1.05rem; font-weight: 800; color: var(--charcoal);
    margin-bottom: 8px; display: flex; align-items: center; gap: 10px;
}
.sched-card-title i { color: var(--th-blue); }

/* Ã¢â€â‚¬Ã¢â€â‚¬ Day rows Ã¢â‚¬â€ column layout so grid stays inside Ã¢â€â‚¬Ã¢â€â‚¬ */
.day-row-b {
    border-radius: 16px; margin-bottom: 12px;
    border: 1.5px solid #E2E8F0; background: #F8FAFC;
    transition: all .22s; overflow: hidden;
}
.day-row-b:hover  { border-color: var(--th-blue-bd); box-shadow: 0 4px 14px rgba(37,99,235,.08); }
.day-row-b.is-on  { border-color: var(--th-blue); background: #EFF6FF; }
.day-row-b.is-off { opacity: .5; }

/* Top strip: toggle + name */
.day-row-top {
    display: flex; align-items: center; gap: 16px;
    padding: 16px 20px 12px;
}
.day-name-b { font-weight: 800; color: var(--charcoal); flex: 1; font-size: .97rem; }


/* Grid section Ã¢â‚¬â€ always inside the box */
.drag-grid-wrap {
    padding: 0 20px 16px;
    border-top: 1px solid rgba(0,0,0,.05);
}
.drag-grid-section { overflow: hidden; }  /* clips the grid */

.drag-grid-hours { display: flex; gap: 2px; margin-bottom: 3px; padding-top: 10px; }
.drag-hour-lbl { flex: 1; font-size: .6rem; color: var(--muted); text-align: center; min-width: 0; }

.drag-grid {
    display: flex; gap: 2px; flex-wrap: nowrap;
    overflow: hidden;           /* Ã¢â€ Â no scrollbar, clips to card */
    width: 100%;
}
.drag-cell {
    flex: 1; min-width: 0; height: 30px; border-radius: 5px;
    background: #E2E8F0; cursor: pointer; transition: background .15s;
}
.drag-cell:hover    { background: rgba(37,99,235,.25); }
.drag-cell.sel      { background: var(--th-blue); }
.drag-cell.sel:hover{ background: var(--th-blue-l); }

/* Time inputs under grid */
.time-inputs-b { display: flex; align-items: center; gap: 10px; margin-top: 10px; }
.time-input-b {
    padding: 8px 14px; border-radius: 10px;
    border: 1.5px solid var(--th-blue-bd); font-size: .88rem;
    color: var(--charcoal); background: white; width: 140px;
    outline: none; transition: border .2s;
}
.time-input-b:focus { border-color: var(--th-blue); box-shadow: 0 0 0 3px rgba(37,99,235,.1); }
.time-sep { color: var(--muted); font-weight: 700; font-size: .85rem; }

/* Toggle switch Ã¢â‚¬â€ blue */
.tog-wrap { position: relative; display: inline-block; width: 48px; height: 26px; flex-shrink: 0; }
.tog-wrap input { opacity: 0; width: 0; height: 0; }
.tog-slider {
    position: absolute; cursor: pointer; inset: 0;
    background: #CBD5E1; border-radius: 26px; transition: .3s;
}
.tog-slider::before {
    content: ''; position: absolute;
    height: 19px; width: 19px; left: 4px; bottom: 4px;
    background: white; border-radius: 50%; transition: .3s;
    box-shadow: 0 2px 6px rgba(0,0,0,.15);
}
input:checked + .tog-slider { background: var(--th-blue); }
input:checked + .tog-slider::before { transform: translateX(22px); }

/* Save button */
.btn-save-sched {
    width: 100%; padding: 17px; border-radius: 50px; margin-top: 28px;
    background: linear-gradient(135deg, var(--th-blue-dk), var(--th-blue-l));
    color: white; font-weight: 900; font-size: 1.08rem; border: none; cursor: pointer;
    box-shadow: 0 6px 22px rgba(37,99,235,.28); transition: all .22s; letter-spacing: .3px;
}
.btn-save-sched:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(37,99,235,.4); }

@media(max-width:640px){
    .sched-card, .rate-card { padding: 22px 16px; }
    .time-input-b { width: 120px; }
}
</style>

<div class="sch-wrap">
    <a href="therapist_dashboard.php" class="sch-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

    <!-- Hero -->
    <div class="sch-hero">
        <div class="sch-hero-text">
            <h2>Manage Availability</h2>
            <p>Set your working hours and session rate. Clients will only see open slots &mdash; reserved times are automatically hidden.</p>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="sch-alert <?php echo $msg_type; ?>">
        <?php if ($message === '__SUCCESS__'): ?>
            <i class="fas fa-check-circle"></i> Availability schedule saved successfully.
        <?php else: ?>
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($message); ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Rate (EGP lock) -->
    <div class="rate-card">
        <div class="rate-card-title"><i class="fas fa-pound-sign"></i> Your Session Rate (EGP Only)</div>
        <form method="POST">
            <div class="rate-input-row">
                <div class="rate-egp-wrap">
                    <span class="rate-egp-label">EGP</span>
                    <input type="number" name="rate_egp" class="rate-input" min="0" step="50"
                           value="<?php echo number_format($hourly_rate,0,'',''); ?>" required>
                </div>
                <div class="rate-note">per 60-min session<br>30-min sessions are automatically half-price</div>
                <button type="submit" name="save_rate" class="btn-rate-save"><i class="fas fa-save"></i> Save Rate</button>
            </div>
        </form>
    </div>

    <!-- Availability grid -->
    <div class="sched-card">
        <div class="sched-card-title"><i class="fas fa-sliders-h"></i> Weekly Availability</div>
        <p style="font-size:.84rem;color:var(--muted);margin-bottom:22px;line-height:1.5;">
            Toggle each day on/off. Use the <strong>drag-to-select</strong> grid to pick your working hours, or type them manually below.
        </p>

        <form method="POST" id="availForm">
            <?php foreach ($days as $day):
                $is_on  = isset($saved[$day]) ? $saved[$day]['is_available'] : false;
                $start  = isset($saved[$day]) ? $saved[$day]['start_time'] : '09:00';
                $end    = isset($saved[$day]) ? $saved[$day]['end_time']   : '17:00';
            ?>
            <div class="day-row-b <?php echo $is_on?'is-on':'is-off'; ?>" id="drow_<?php echo $day; ?>">
                <!-- Top: toggle + day name -->
                <div class="day-row-top">
                    <label class="tog-wrap">
                        <input type="checkbox" name="availability[<?php echo $day; ?>][active]" value="1"
                               id="tog_<?php echo $day; ?>"
                               onchange="toggleDay('<?php echo $day; ?>')"
                               <?php echo $is_on?'checked':''; ?>>
                        <span class="tog-slider"></span>
                    </label>
                    <div class="day-name-b"><?php echo $day; ?></div>
                </div>

                <!-- Bottom: drag grid + time inputs (always inside the card) -->
                <div class="drag-grid-wrap" id="dgw_<?php echo $day; ?>" style="<?php echo $is_on?'':'opacity:.4;pointer-events:none;'; ?>">
                    <div class="drag-grid-section">
                        <div class="drag-grid-hours" id="dlbl_<?php echo $day; ?>"></div>
                        <div class="drag-grid" id="dgrid_<?php echo $day; ?>"></div>
                    </div>
                    <div class="time-inputs-b">
                        <input type="time" name="availability[<?php echo $day; ?>][start]"
                               id="start_<?php echo $day; ?>" class="time-input-b"
                               value="<?php echo $start; ?>"
                               onchange="syncDragFromInputs('<?php echo $day; ?>')">
                        <span class="time-sep">&ndash;</span>
                        <input type="time" name="availability[<?php echo $day; ?>][end]"
                               id="end_<?php echo $day; ?>" class="time-input-b"
                               value="<?php echo $end; ?>"
                               onchange="syncDragFromInputs('<?php echo $day; ?>')">
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <div id="timeErrorBanner" style="display:none;background:#FEE2E2;color:#991B1B;border:1px solid #FECACA;border-radius:14px;padding:14px 20px;margin-top:16px;font-weight:700;font-size:.92rem;display:none;align-items:center;gap:10px;">
                <i class="fas fa-exclamation-triangle"></i> <span id="timeErrorMsg"></span>
            </div>
            <button type="submit" class="btn-save-sched" name="save_avail" onclick="return validateTimes()">
                <i class="fas fa-check-circle"></i> Save Schedule
            </button>
        </form>
    </div>
</div>

<script>
const HOURS = Array.from({length:24}, (_,i) => i); // 0-23

// ── Task 1: Validate end > start before form submit ────────────────
function validateTimes() {
    const banner  = document.getElementById('timeErrorBanner');
    const msgSpan = document.getElementById('timeErrorMsg');
    const days    = <?php echo json_encode($days); ?>;
    for (const day of days) {
        const tog = document.getElementById('tog_' + day);
        if (!tog || !tog.checked) continue;
        const s = document.getElementById('start_' + day).value;
        const e = document.getElementById('end_'   + day).value;
        if (!s || !e) continue;
        if (e <= s) {
            msgSpan.textContent = day + ': End time (' + e + ') must be later than Start time (' + s + '). Midnight crossover is not supported — use two separate days.';
            banner.style.display = 'flex';
            banner.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return false;
        }
    }
    banner.style.display = 'none';
    return true;
}
const days  = <?php echo json_encode($days); ?>;

// Ã¢â€â‚¬Ã¢â€â‚¬ Build drag grids Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
days.forEach(day => {
    const lbl  = document.getElementById('dlbl_'  + day);
    const grid = document.getElementById('dgrid_' + day);

    HOURS.forEach(h => {
        // Label every 3 hours
        const ll = document.createElement('div');
        ll.className = 'drag-hour-lbl';
        if (h % 3 === 0) {
            let labelH = h % 12;
            labelH = labelH ? labelH : 12;
            let ampm = h >= 12 ? 'PM' : 'AM';
            ll.textContent = labelH + ampm;
        } else {
            ll.textContent = '';
        }
        lbl.appendChild(ll);

        const cell = document.createElement('div');
        cell.className = 'drag-cell';
        cell.dataset.day  = day;
        cell.dataset.hour = h;
        let labelH = h % 12; labelH = labelH ? labelH : 12;
        let ampm = h >= 12 ? 'PM' : 'AM';
        cell.title        = `${labelH}:00 ${ampm}`;
        grid.appendChild(cell);
    });

    setupDrag(day);
    syncDragFromInputs(day);
});

// Ã¢â€â‚¬Ã¢â€â‚¬ Drag-to-select logic Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
function setupDrag(day) {
    const grid = document.getElementById('dgrid_' + day);
    let dragging = false, startH = null, endH = null, adding = null;

    grid.addEventListener('mousedown', e => {
        const cell = e.target.closest('.drag-cell');
        if (!cell || cell.dataset.day !== day) return;
        dragging = true;
        startH = parseInt(cell.dataset.hour);
        endH   = startH;
        adding = !cell.classList.contains('sel'); // Toggle mode
        e.preventDefault();
        updateSelection(day, startH, endH, adding);
    });
    document.addEventListener('mousemove', e => {
        if (!dragging) return;
        const cell = e.target.closest('.drag-cell');
        if (cell && cell.dataset.day === day) {
            endH = parseInt(cell.dataset.hour);
            updateSelection(day, startH, endH, adding);
        }
    });
    document.addEventListener('mouseup', () => {
        if (dragging) { dragging = false; finalizeSelection(day); }
    });

    // Touch support
    grid.addEventListener('touchstart', e => {
        const touch = e.touches[0];
        const cell  = document.elementFromPoint(touch.clientX, touch.clientY)?.closest('.drag-cell');
        if (!cell || cell.dataset.day !== day) return;
        dragging = true; startH = parseInt(cell.dataset.hour); endH = startH;
        adding = !cell.classList.contains('sel');
        updateSelection(day, startH, endH, adding);
    }, {passive:true});
    grid.addEventListener('touchmove', e => {
        if (!dragging) return;
        const touch = e.touches[0];
        const cell  = document.elementFromPoint(touch.clientX, touch.clientY)?.closest('.drag-cell');
        if (cell && cell.dataset.day === day) { endH = parseInt(cell.dataset.hour); updateSelection(day, startH, endH, adding); }
    }, {passive:true});
    grid.addEventListener('touchend', () => { if (dragging) { dragging=false; finalizeSelection(day); } });
}

function updateSelection(day, sh, eh, adding) {
    const lo = Math.min(sh, eh), hi = Math.max(sh, eh);
    document.querySelectorAll(`#dgrid_${day} .drag-cell`).forEach(c => {
        const h = parseInt(c.dataset.hour);
        if (h >= lo && h <= hi) {
            c.classList.toggle('sel', adding);
        }
    });
}

function finalizeSelection(day) {
    const cells = [...document.querySelectorAll(`#dgrid_${day} .drag-cell.sel`)];
    if (cells.length === 0) return;
    const hours  = cells.map(c => parseInt(c.dataset.hour));
    const minH   = Math.min(...hours);
    const maxH   = Math.max(...hours) + 1;
    document.getElementById('start_' + day).value = `${String(minH).padStart(2,'0')}:00`;
    document.getElementById('end_'   + day).value = `${String(maxH).padStart(2,'0')}:00`;
}

function syncDragFromInputs(day) {
    const s = document.getElementById('start_' + day).value;
    const e = document.getElementById('end_'   + day).value;
    if (!s || !e) return;
    const sh = parseInt(s.split(':')[0]);
    const eh = parseInt(e.split(':')[0]);
    document.querySelectorAll(`#dgrid_${day} .drag-cell`).forEach(c => {
        const h = parseInt(c.dataset.hour);
        c.classList.toggle('sel', h >= sh && h < eh);
    });
}

// Ã¢â€â‚¬Ã¢â€â‚¬ Toggle day on/off Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
function toggleDay(day) {
    const chk  = document.getElementById('tog_' + day);
    const row  = document.getElementById('drow_' + day);
    const wrap = document.getElementById('dgw_'  + day);
    if (chk.checked) {
        row.classList.add('is-on'); row.classList.remove('is-off');
        wrap.style.opacity = '1'; wrap.style.pointerEvents = '';
    } else {
        row.classList.remove('is-on'); row.classList.add('is-off');
        wrap.style.opacity = '.4'; wrap.style.pointerEvents = 'none';
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
