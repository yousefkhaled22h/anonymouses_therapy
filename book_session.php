<?php
// book_session.php – Beige Collection Booking Engine
require_once 'includes/db_connect.php';
session_start();

$therapist_id = $_GET['therapist_id'] ?? null;
if (!$therapist_id || !isset($_SESSION['user_id'])) { header("Location: therapists.php"); exit(); }

$stmt = $pdo->prepare("SELECT * FROM therapist WHERE therapist_id = ?");
$stmt->execute([$therapist_id]);
$therapist = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$therapist) { header("Location: therapists.php"); exit(); }

$name         = trim('Dr. ' . ($therapist['first_name'] ?? '') . ' ' . ($therapist['last_name'] ?? ''));
$hourly_rate  = floatval($therapist['hourly_rate'] ?? 0);
$service_fee  = round($hourly_rate * 0.30);   // 30% platform fee
$specialties  = $therapist['specialties'] ?? '';

$wallet_balance = 0;
if (in_array(strtolower($_SESSION['role'] ?? ''), ['client'])) {
    $ws = $pdo->prepare("SELECT wallet_balance FROM client WHERE user_id = ?");
    $ws->execute([$_SESSION['user_id']]);
    $wallet_balance = floatval($ws->fetchColumn());
}

// Fetch booked slots so we can grey them out
$booked_stmt = $pdo->prepare("
    SELECT DATE(session_date) as session_date, TIME(session_date) as session_time
    FROM private_sessions
    WHERE therapist_id = ? AND status NOT IN ('Cancelled','Refunded','cancelled')
");
$booked_stmt->execute([$therapist_id]);
$booked_raw = $booked_stmt->fetchAll(PDO::FETCH_ASSOC);
$booked_map = [];
foreach ($booked_raw as $b) {
    $booked_map[$b['session_date']][] = substr($b['session_time'], 0, 5);
}

// Fetch availability
$avail_stmt = $pdo->prepare("SELECT * FROM therapist_availability WHERE therapist_id = ? AND is_available = 1");
$avail_stmt->execute([$therapist_id]);
$avail_rows = $avail_stmt->fetchAll(PDO::FETCH_ASSOC);
$availability = [];
foreach ($avail_rows as $row) {
    $availability[$row['day_of_week']] = [
        'start' => substr($row['start_time'], 0, 5),
        'end'   => substr($row['end_time'],   0, 5),
    ];
}

$body_class = 'role-client';
include 'includes/header.php';
?>
<style>
/* ══ BEIGE BOOKING ENGINE ══ */
:root {
    --beige:   #F5F5DC; --cream:   #FFFDD0; --sand:   #EDC9AF;
    --bronze:  #7C5A3A; --bronze-l:#A8784F; --charcoal:#3D3530;
    --muted:   #7D6B5E; --green-s: #4A7C59; --green-bg:#EAF5EE;
}
body { background: #F5F0E6; }

.book-wrap { max-width: 860px; margin: 0 auto; padding: 32px 20px 80px; }

/* Therapist summary bar */
.book-header {
    display: flex; align-items: center; gap: 18px;
    background: rgba(255,255,255,.9); backdrop-filter: blur(10px);
    border-radius: 20px; padding: 20px 26px;
    border: 1px solid rgba(237,201,175,.5);
    box-shadow: 0 6px 24px rgba(124,90,58,.07); margin-bottom: 34px;
}
.book-header-photo { width: 56px; height: 56px; border-radius: 14px; flex-shrink: 0; background: linear-gradient(135deg, var(--sand),var(--beige)); display: flex; align-items:center; justify-content:center; font-size:1.4rem; font-weight:900; color:var(--bronze); }
.book-header-photo img { width:100%;height:100%;object-fit:cover;border-radius:14px; }
.book-header-name { font-size: 1.1rem; font-weight: 800; color: var(--charcoal); }
.book-header-spec { font-size: .82rem; color: var(--muted); }
.book-header-price { margin-left: auto; text-align: right; }
.book-price-big  { font-size: 1.4rem; font-weight: 900; color: var(--bronze); }
.book-price-note { font-size: .76rem; color: var(--muted); }

/* Progress steps */
.book-steps {
    display: flex; align-items: center; justify-content: center;
    gap: 0; margin-bottom: 38px; position: relative;
}
.bs-item { display: flex; align-items: center; gap: 0; }
.bs-circle {
    width: 38px; height: 38px; border-radius: 50%; font-weight: 800; font-size: .95rem;
    display: flex; align-items: center; justify-content: center; transition: all .3s;
    border: 2px solid #DDD4C6; background: white; color: var(--muted); z-index: 1;
}
.bs-circle.active { background: var(--bronze); border-color: var(--bronze); color: white; box-shadow: 0 4px 14px rgba(124,90,58,.3); }
.bs-circle.done   { background: var(--green-s); border-color: var(--green-s); color: white; }
.bs-label { font-size: .73rem; font-weight: 600; color: var(--muted); margin-top: 6px; }
.bs-wrap { display: flex; flex-direction: column; align-items: center; }
.bs-line { width: 80px; height: 2px; background: #DDD4C6; margin-bottom: 20px; }
.bs-line.done { background: var(--green-s); }

/* Step panels */
.step-panel { display: none; animation: stepIn .35s ease; }
.step-panel.active { display: block; }
@keyframes stepIn { from { opacity:0; transform:translateY(14px); } to { opacity:1; transform:translateY(0); } }

/* Section card */
.step-card {
    background: rgba(255,255,255,.9); backdrop-filter: blur(10px);
    border-radius: 22px; padding: 34px 36px;
    border: 1px solid rgba(237,201,175,.4);
    box-shadow: 0 6px 24px rgba(124,90,58,.07);
    margin-bottom: 20px;
}
.step-card-title { font-size: 1.2rem; font-weight: 800; color: var(--charcoal); margin-bottom: 22px; display: flex; align-items:center; gap:10px; }
.step-card-title i { color: var(--bronze); }

/* ── Full-screen session type selector ── */
.stype-screen {
    text-align: center; padding: 10px 0 28px;
}
.stype-headline {
    font-size: 1.9rem; font-weight: 900; color: var(--charcoal);
    margin-bottom: 8px; letter-spacing: -.4px;
}
.stype-headline span { color: var(--bronze); }
.stype-sub { font-size: 1rem; color: var(--muted); margin-bottom: 36px; line-height: 1.5; }

.stype-grid {
    display: grid; grid-template-columns: repeat(2,1fr);
    gap: 20px; margin-bottom: 30px;
}
.stype-card {
    position: relative; border-radius: 24px; padding: 40px 20px 32px;
    cursor: pointer; transition: all .3s cubic-bezier(.4,0,.2,1);
    border: 2.5px solid #EDE8DF; background: white;
    overflow: hidden;
    display: flex; flex-direction: column; align-items: center; gap: 0;
}
.stype-card::before {
    content: ''; position: absolute; inset: 0; opacity: 0;
    transition: opacity .3s;
    border-radius: 22px;
}
.stype-card.chat::before   { background: linear-gradient(145deg, #FFFDF5, #F5EDD8); }
.stype-card.voice::before  { background: linear-gradient(145deg, #F0FFF4, #DCFCE7); }
.stype-card.video::before  { background: linear-gradient(145deg, #EFF6FF, #DBEAFE); }
.stype-card:hover::before, .stype-card.sel::before { opacity: 1; }

.stype-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 50px rgba(0,0,0,.1);
}
.stype-card.chat:hover,  .stype-card.chat.sel  { border-color: var(--bronze); }
.stype-card.voice:hover, .stype-card.voice.sel { border-color: #16a34a; }
.stype-card.video:hover, .stype-card.video.sel { border-color: #2563eb; }
.stype-card.sel { transform: translateY(-6px); }
.stype-card.sel .stype-check { opacity: 1; transform: scale(1); }

.stype-icon-wrap {
    position: relative; z-index: 1;
    width: 88px; height: 88px; border-radius: 50%; margin-bottom: 18px;
    display: flex; align-items: center; justify-content: center;
    font-size: 2.4rem; transition: transform .3s;
}
.stype-card.chat  .stype-icon-wrap { background: linear-gradient(135deg, #FDF6EE, #F5EDD8); color: var(--bronze); }
.stype-card.voice .stype-icon-wrap { background: linear-gradient(135deg, #DCFCE7, #BBF7D0); color: #16a34a; }
.stype-card.video .stype-icon-wrap { background: linear-gradient(135deg, #DBEAFE, #BFDBFE); color: #2563eb; }
.stype-card:hover .stype-icon-wrap { transform: scale(1.08); }

.stype-title { position: relative; z-index:1; font-size: 1.2rem; font-weight: 900; color: var(--charcoal); margin-bottom: 8px; }
.stype-desc  { position: relative; z-index:1; font-size: .83rem; color: var(--muted); line-height: 1.5; }
.stype-check {
    position: absolute; top: 14px; right: 14px; z-index: 2;
    width: 26px; height: 26px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .75rem; color: white;
    opacity: 0; transform: scale(.6); transition: all .25s;
}
.stype-card.chat.sel  .stype-check { background: var(--bronze); }
.stype-card.voice.sel .stype-check { background: #16a34a; }
.stype-card.video.sel .stype-check { background: #2563eb; }

/* Duration pill */
.dur-section { margin-bottom: 6px; }
.dur-label { font-size: .82rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 10px; }

/* Method cards (kept for backward compat, hidden) */
.method-grid { display: none; }
.method-icon { font-size: 2rem; color: var(--bronze); margin-bottom: 10px; }
.method-name { font-weight: 700; color: var(--charcoal); font-size: .95rem; }

/* Duration toggle */
.duration-toggle { display: flex; background: #EDE8DF; border-radius: 50px; padding: 4px; gap: 4px; width: fit-content; margin-bottom: 28px; }
.dur-btn {
    padding: 10px 26px; border-radius: 50px; font-size: .9rem; font-weight: 700;
    border: none; cursor: pointer; transition: all .22s;
    background: transparent; color: var(--muted);
}
.dur-btn.active { background: white; color: var(--bronze); box-shadow: 0 3px 12px rgba(0,0,0,.1); }

/* View toggle */
.view-toggle { display: flex; gap: 8px; margin-bottom: 22px; }
.view-btn {
    padding: 8px 18px; border-radius: 50px; font-size: .83rem; font-weight: 700;
    border: 1.5px solid #DDD4C6; background: white; color: var(--muted);
    cursor: pointer; transition: all .2s; display: flex; align-items: center; gap: 6px;
}
.view-btn.active { background: var(--bronze); color: white; border-color: var(--bronze); }

/* ── CALENDAR GRID VIEW ── */
.cal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
.cal-month-name { font-size: 1.1rem; font-weight: 800; color: var(--charcoal); }
.cal-nav { width: 34px; height: 34px; border-radius: 50%; border: 1.5px solid #DDD4C6; background: white; cursor: pointer; font-size: .9rem; color: var(--muted); display: flex; align-items:center; justify-content:center; transition: all .2s; }
.cal-nav:hover { border-color: var(--bronze); color: var(--bronze); }
.cal-weekdays { display: grid; grid-template-columns: repeat(7,1fr); margin-bottom: 8px; }
.cal-wd { text-align: center; font-size: .75rem; font-weight: 700; color: var(--muted); padding: 6px 0; }
.cal-days { display: grid; grid-template-columns: repeat(7,1fr); gap: 4px; }
.cal-day {
    aspect-ratio: 1; border-radius: 12px; display: flex; align-items: center; justify-content: center;
    font-size: .88rem; font-weight: 600; cursor: pointer; transition: all .2s;
    border: 1.5px solid transparent;
}
.cal-day.empty { cursor: default; }
.cal-day.past  { color: #CCC; cursor: not-allowed; }
.cal-day.unavailable { color: #C8BDB5; cursor: not-allowed; }
.cal-day.available { color: var(--charcoal); }
.cal-day.available:hover { background: #FDF6EE; border-color: var(--bronze-l); color: var(--bronze); }
.cal-day.selected { background: var(--bronze); color: white; border-color: var(--bronze); box-shadow: 0 4px 12px rgba(124,90,58,.3); }
.cal-day.today-mark { background: #F5EDD8; color: var(--bronze); font-weight: 800; }

/* ── VERTICAL WEEK VIEW ── */
.week-list { display: flex; flex-direction: column; gap: 10px; }
.week-day-row {
    display: flex; align-items: flex-start; gap: 18px;
    background: #FDFAF5; border-radius: 14px; padding: 14px 18px;
    border: 1.5px solid #EDE8DF;
}
.week-day-row.selected-day { border-color: var(--bronze); background: #FDF6EE; }
.week-day-label { min-width: 90px; }
.week-day-name  { font-weight: 800; color: var(--charcoal); font-size: .97rem; }
.week-day-date  { font-size: .78rem; color: var(--muted); }
.week-slots     { display: flex; flex-wrap: wrap; gap: 7px; flex: 1; }
.week-no-slots  { font-size: .83rem; color: var(--muted); font-style: italic; align-self: center; }

/* Time slots */
.ts {
    padding: 7px 14px; border-radius: 50px; font-size: .82rem; font-weight: 700;
    border: 1.5px solid #DDD4C6; background: white; color: var(--charcoal);
    cursor: pointer; transition: all .18s;
}
.ts:hover { border-color: var(--bronze-l); color: var(--bronze); background: #FDF6EE; }
.ts.taken { background: #F1EDE8; color: #B5A99A; border-color: #E0D8CF; cursor: not-allowed; text-decoration: line-through; }
.ts.sel   { background: var(--bronze); color: white; border-color: var(--bronze); box-shadow: 0 3px 10px rgba(124,90,58,.28); }

/* Summary & payment */
.summary-bx {
    background: linear-gradient(135deg, #FDF6EE, #F5EDD8);
    border-radius: 18px; padding: 26px; margin-bottom: 22px;
    border: 1px solid rgba(237,201,175,.5);
}
.sum-row { display: flex; justify-content: space-between; padding: 9px 0; font-size: .94rem; color: var(--muted); border-bottom: 1px dashed rgba(0,0,0,.07); }
.sum-row:last-child { border-bottom: none; }
.sum-row .sv { font-weight: 700; color: var(--charcoal); }
.sum-total { display: flex; justify-content: space-between; padding: 14px 0 0; font-size: 1.18rem; font-weight: 900; color: var(--bronze); border-top: 2px solid rgba(124,90,58,.2); margin-top: 8px; }
.fee-breakdown { font-size: .77rem; color: var(--muted); margin-top: 4px; }

.wallet-badge { display: flex; align-items: center; gap: 10px; background: #EAF5EE; border-radius: 12px; padding: 14px 18px; margin-bottom: 14px; font-size: .9rem; color: var(--green-s); font-weight: 600; }
.wallet-badge i { font-size: 1.1rem; }

/* Payment Methods */
.pm-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 15px; }
.pm-card {
    border: 2px solid #EDE8DF; border-radius: 14px; padding: 18px 12px;
    text-align: center; cursor: pointer; transition: all 0.2s;
    background: white;
}
.pm-card:hover { border-color: var(--bronze-l); transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
.pm-card.active { border-color: var(--bronze); background: #FDF6EE; box-shadow: 0 5px 15px rgba(124,90,58,.1); }
.pm-icon { font-size: 1.8rem; margin-bottom: 8px; color: var(--bronze); }
.pm-name { font-size: 0.85rem; font-weight: 700; color: var(--charcoal); }

/* Nav buttons */
.book-nav { display: flex; gap: 14px; margin-top: 20px; }
.btn-bk-back {
    flex: 0 0 auto; padding: 14px 22px; border-radius: 50px;
    border: 1.5px solid #DDD4C6; background: white; color: var(--muted);
    font-weight: 700; cursor: pointer; transition: all .2s; font-size: .95rem;
}
.btn-bk-back:hover { border-color: var(--bronze); color: var(--bronze); }
.btn-bk-next {
    flex: 1; padding: 15px 24px; border-radius: 50px;
    background: linear-gradient(135deg, var(--bronze), var(--bronze-l));
    color: white; font-weight: 800; font-size: 1rem; border: none; cursor: pointer;
    box-shadow: 0 6px 20px rgba(124,90,58,.28); transition: all .22s;
    display: flex; align-items:center; justify-content:center; gap: 8px;
}
.btn-bk-next:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 10px 26px rgba(124,90,58,.38); }
.btn-bk-next:disabled { opacity: .45; cursor: not-allowed; transform: none; box-shadow: none; }

@media(max-width:600px){
    .stype-grid { grid-template-columns: 1fr; }
    .stype-card { flex-direction: row; gap: 16px; padding: 22px 18px; text-align: left; }
    .stype-icon-wrap { width: 60px; height: 60px; font-size: 1.6rem; flex-shrink: 0; margin-bottom: 0; }
    .step-card { padding: 22px 18px; }
}
</style>

<div class="book-wrap">

    <!-- Header summary bar -->
    <div class="book-header">
        <div class="book-header-photo">
            <?php if (!empty($therapist['profile_image'])): ?>
            <img src="<?php echo htmlspecialchars($therapist['profile_image']); ?>" alt="">
            <?php else: ?>
            <?php echo strtoupper(substr($therapist['first_name'],0,1).substr($therapist['last_name'],0,1)); ?>
            <?php endif; ?>
        </div>
        <div>
            <div class="book-header-name"><?php echo htmlspecialchars($name); ?></div>
            <div class="book-header-spec"><?php echo htmlspecialchars($specialties ?: 'Therapy & Counseling'); ?></div>
        </div>
        <div class="book-header-price">
            <div class="book-price-big"><?php echo number_format($hourly_rate,0); ?> EGP</div>
            <div class="book-price-note">per session · EGP</div>
        </div>
    </div>

    <!-- Progress indicators -->
    <div class="book-steps">
        <div class="bs-item">
            <div class="bs-wrap">
                <div class="bs-circle active" id="sc1">1</div>
                <div class="bs-label">Method</div>
            </div>
        </div>
        <div class="bs-line" id="sl12"></div>
        <div class="bs-item">
            <div class="bs-wrap">
                <div class="bs-circle" id="sc2">2</div>
                <div class="bs-label">Date &amp; Time</div>
            </div>
        </div>
        <div class="bs-line" id="sl23"></div>
        <div class="bs-item">
            <div class="bs-wrap">
                <div class="bs-circle" id="sc3">3</div>
                <div class="bs-label">Payment</div>
            </div>
        </div>
    </div>

    <!-- ══ STEP 1: Session Type (Full-screen selector) ══ -->
    <div class="step-panel active" id="sp1">
        <div class="step-card">
            <div class="stype-screen">
                <div class="stype-headline">How would you like to <span>connect?</span></div>
                <div class="stype-sub">Choose the session format that feels most comfortable for you.</div>

                <div class="stype-grid">
                    <!-- Voice & Text Session -->
                    <div class="stype-card voice" onclick="pickStype('Voice & Text', this)">
                        <span class="stype-check"><i class="fas fa-check"></i></span>
                        <div class="stype-icon-wrap" style="background: linear-gradient(135deg, #DCFCE7, #F5EDD8); color: #16a34a;">
                            <i class="fas fa-microphone-alt"></i> &nbsp;<i class="far fa-comment-dots"></i>
                        </div>
                        <div class="stype-title">Voice &amp; Text</div>
                        <div class="stype-desc">Flexible communication. Talk freely or message your therapist in a private, secure environment at your pace.</div>
                    </div>
                    <!-- Video Session -->
                    <div class="stype-card video" onclick="pickStype('Video Session', this)">
                        <span class="stype-check"><i class="fas fa-check"></i></span>
                        <div class="stype-icon-wrap"><i class="fas fa-video"></i></div>
                        <div class="stype-title">Video Session</div>
                        <div class="stype-desc">Face-to-face therapy from the comfort of home. Encrypted &amp; confidential.</div>
                    </div>
                </div>

                <!-- Duration -->                
                <div class="dur-section">
                    <div class="dur-label">Session Duration</div>
                    <div class="duration-toggle" style="margin: 0 auto 0;">
                        <button class="dur-btn" id="dur30" onclick="setDuration(30)">30 Min — <?php echo number_format($hourly_rate/2,0); ?> EGP</button>
                        <button class="dur-btn active" id="dur60" onclick="setDuration(60)">60 Min — <?php echo number_format($hourly_rate,0); ?> EGP</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="book-nav">
            <button class="btn-bk-next" id="btn1" disabled onclick="goStep(2)">Continue <i class="fas fa-arrow-right"></i></button>
        </div>
    </div>

    <!-- ══ STEP 2: Date & Time ══ -->
    <div class="step-panel" id="sp2">
        <div class="step-card">
            <div class="step-card-title"><i class="fas fa-calendar-alt"></i> Choose Date &amp; Time</div>

            <!-- View toggle -->
            <div class="view-toggle">
                <button class="view-btn active" id="vbtnCal" onclick="switchView('cal')"><i class="fas fa-th"></i> Calendar</button>
                <button class="view-btn" id="vbtnWeek" onclick="switchView('week')"><i class="fas fa-list"></i> Weekly List</button>
            </div>

            <!-- Calendar view -->
            <div id="viewCal">
                <div class="cal-header">
                    <button class="cal-nav" onclick="shiftMonth(-1)"><i class="fas fa-chevron-left"></i></button>
                    <span class="cal-month-name" id="calMonthName"></span>
                    <button class="cal-nav" onclick="shiftMonth(1)"><i class="fas fa-chevron-right"></i></button>
                </div>
                <div class="cal-weekdays">
                    <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d): ?>
                    <div class="cal-wd"><?php echo $d; ?></div>
                    <?php endforeach; ?>
                </div>
                <div class="cal-days" id="calDays"></div>
            </div>

            <!-- Vertical week view -->
            <div id="viewWeek" style="display:none;">
                <div class="week-list" id="weekList"></div>
            </div>

            <!-- Time slots (shown after date pick in calendar mode) -->
            <div id="timeSection" style="margin-top:22px; display:none;">
                <div style="font-size:.85rem;font-weight:700;color:var(--muted);margin-bottom:10px;letter-spacing:.3px;text-transform:uppercase;">Available Slots — <span id="timeSectionDate"></span></div>
                <div id="timeSlots" style="display:flex;flex-wrap:wrap;gap:8px;"></div>
            </div>
        </div>
        <div class="book-nav">
            <button class="btn-bk-back" onclick="goStep(1)"><i class="fas fa-arrow-left"></i></button>
            <button class="btn-bk-next" id="btn2" disabled onclick="goStep(3)">Continue <i class="fas fa-arrow-right"></i></button>
        </div>
    </div>

    <!-- ══ STEP 3: Payment ══ -->
    <div class="step-panel" id="sp3">
        <div class="step-card">
            <div class="step-card-title"><i class="fas fa-receipt"></i> Booking Summary</div>
            <div class="summary-bx">
                <div class="sum-row"><span>Therapist</span><span class="sv" id="s-name"><?php echo htmlspecialchars($name); ?></span></div>
                <div class="sum-row"><span>Session Type</span><span class="sv" id="s-method"></span></div>
                <div class="sum-row"><span>Duration</span><span class="sv" id="s-dur"></span></div>
                <div class="sum-row"><span>Date</span><span class="sv" id="s-date"></span></div>
                <div class="sum-row"><span>Time</span><span class="sv" id="s-time"></span></div>
                <div class="sum-row">
                    <span>Session Price</span>
                    <span class="sv" id="s-price"></span>
                </div>
                <div class="sum-row">
                    <span>Safe Haven Service Fee <span style="font-size:.72rem;">(30%)</span></span>
                    <span class="sv" id="s-fee"></span>
                </div>
                <div class="sum-total">
                    <span>Total Investment</span>
                    <span id="s-total"></span>
                </div>
                <div class="fee-breakdown" id="s-breakdown"></div>
            </div>

            <div class="step-card-title"><i class="fas fa-wallet"></i> Payment</div>
            <?php if ($wallet_balance > 0): ?>
            <div class="wallet-badge">
                <i class="fas fa-wallet"></i>
                Your wallet balance: <strong><?php echo number_format($wallet_balance,0); ?> EGP</strong>
            </div>
            <?php endif; ?>

            <div id="paymentInfo" style="background:#F9F6F0;border-radius:14px;padding:16px;font-size:.9rem;color:var(--muted); margin-bottom: 20px;"></div>

            <div class="step-card-title" style="margin-bottom: 10px; font-size: 1rem;"><i class="fas fa-credit-card"></i> Select Payment Method</div>
            <div class="pm-grid" id="pmGrid">
                <div class="pm-card active" onclick="selectPaymentMethod('InstaPay', this)">
                    <div class="pm-icon"><i class="fas fa-mobile-alt"></i></div>
                    <div class="pm-name">InstaPay</div>
                </div>
            </div>
        </div>
        <div class="book-nav">
            <button class="btn-bk-back" onclick="goStep(2)"><i class="fas fa-arrow-left"></i></button>
            <button class="btn-bk-next" id="btnConfirm" onclick="bookSession()">
                <i class="fas fa-lock"></i> Confirm &amp; Pay
            </button>
        </div>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// ── Data ──────────────────────────────────────────────────────
const THERAPIST_ID  = '<?php echo $therapist_id; ?>';
const AVAIL         = <?php echo json_encode($availability); ?>;
const BOOKED        = <?php echo json_encode($booked_map); ?>;
const BASE_RATE     = <?php echo $hourly_rate; ?>;
const SERVICE_FEE_R = 0.30;
const WALLET        = <?php echo $wallet_balance; ?>;

const dayNames  = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
const dayShort  = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
const monthNames= ['January','February','March','April','May','June','July','August','September','October','November','December'];

let bk = { method:'', duration:60, dateSQL:'', dateStr:'', time:'', paymentType: 'InstaPay' };
let calYear = new Date().getFullYear();
let calMonth= new Date().getMonth();
let currentView = 'cal';

function sessionPrice() { return bk.duration === 30 ? BASE_RATE / 2 : BASE_RATE; }
function serviceFee()   { return Math.round(sessionPrice() * SERVICE_FEE_R); }
function totalPrice()   { return sessionPrice() + serviceFee(); }

// ── Step nav ─────────────────────────────────────────────────
function goStep(s) {
    [1,2,3].forEach(i => {
        document.getElementById('sp'+i).classList.toggle('active', i===s);
        const c = document.getElementById('sc'+i);
        if (i < s)  { c.className='bs-circle done'; c.innerHTML='<i class="fas fa-check"></i>'; }
        if (i === s){ c.className='bs-circle active'; c.innerHTML=i; }
        if (i > s)  { c.className='bs-circle'; c.innerHTML=i; }
    });
    if (s>=2) document.getElementById('sl12').classList.add('done');
    else      document.getElementById('sl12').classList.remove('done');
    if (s>=3) document.getElementById('sl23').classList.add('done');
    else      document.getElementById('sl23').classList.remove('done');
    if (s===3) buildSummary();
    window.scrollTo({top:0,behavior:'smooth'});
}

// ── Session type selection (full-screen cards) ───────────────
function pickStype(m, el) {
    // Remove selection from all stype cards
    document.querySelectorAll('.stype-card').forEach(c => c.classList.remove('sel'));
    // Add to clicked
    el.classList.add('sel');
    bk.method = m;

    // Visual pulse feedback
    el.style.transition = 'transform .15s';
    el.style.transform  = 'scale(1.04)';
    setTimeout(() => { el.style.transform = ''; }, 150);

    // Enable continue
    document.getElementById('btn1').disabled = false;
    document.getElementById('btn1').style.boxShadow = '0 10px 28px rgba(124,90,58,.38)';
}
function setDuration(d) {
    bk.duration = d;
    document.getElementById('dur30').classList.toggle('active', d===30);
    document.getElementById('dur60').classList.toggle('active', d===60);
}

// ── View toggle ──────────────────────────────────────────────
function switchView(v) {
    currentView = v;
    document.getElementById('viewCal').style.display  = v==='cal'  ? '' : 'none';
    document.getElementById('viewWeek').style.display = v==='week' ? '' : 'none';
    document.getElementById('vbtnCal').classList.toggle('active', v==='cal');
    document.getElementById('vbtnWeek').classList.toggle('active', v==='week');
    if (v==='week') buildWeekView();
    else { renderCalendar(); }
}

// ── Calendar View ────────────────────────────────────────────
function renderCalendar() {
    const today = new Date(); today.setHours(0,0,0,0);
    const firstDay = new Date(calYear, calMonth, 1).getDay();
    const daysInMonth = new Date(calYear, calMonth+1, 0).getDate();
    document.getElementById('calMonthName').textContent = monthNames[calMonth] + ' ' + calYear;

    const grid = document.getElementById('calDays');
    grid.innerHTML = '';

    for (let i=0;i<firstDay;i++) {
        const el = document.createElement('div'); el.className='cal-day empty'; grid.appendChild(el);
    }
    for (let day=1; day<=daysInMonth; day++) {
        const d = new Date(calYear, calMonth, day); d.setHours(0,0,0,0);
        const dayName = dayNames[d.getDay()];
        const dateSQL = `${calYear}-${String(calMonth+1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
        const isToday = d.getTime()===today.getTime();
        const isPast  = d < today;
        const isAvail = AVAIL[dayName] !== undefined;
        const dateLabel = `${dayShort[d.getDay()]}, ${monthNames[calMonth]} ${day}`;

        const el = document.createElement('div');
        let cls = 'cal-day';
        if (isToday) cls += ' today-mark';
        if (isPast)  cls += ' past';
        else if (!isAvail) cls += ' unavailable';
        else cls += ' available';
        if (bk.dateSQL === dateSQL) cls += ' selected';
        el.className = cls;
        el.textContent = day;

        if (!isPast && isAvail) {
            el.onclick = () => selectDate(dateSQL, dateLabel, dayName, d);
        }
        grid.appendChild(el);
    }
}
function shiftMonth(dir) { calMonth += dir; if(calMonth>11){calMonth=0;calYear++;} if(calMonth<0){calMonth=11;calYear--;} renderCalendar(); }
renderCalendar();

function selectDate(sql, label, dayName, dObj) {
    bk.dateSQL = sql; bk.dateStr = label; bk.time = '';
    document.getElementById('btn2').disabled = true;
    renderCalendar();
    renderTimeSlots(dayName, sql);
    document.getElementById('timeSection').style.display = '';
    document.getElementById('timeSectionDate').textContent = label;
}

// ── Weekly List View ─────────────────────────────────────────
function buildWeekView() {
    const today = new Date(); today.setHours(0,0,0,0);
    const list = document.getElementById('weekList');
    list.innerHTML = '';
    for (let i=1;i<=7;i++) {
        const d = new Date(today); d.setDate(today.getDate()+i);
        const dayName = dayNames[d.getDay()];
        const sql = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
        const label = `${dayShort[d.getDay()]}, ${monthNames[d.getMonth()]} ${d.getDate()}`;
        const isAvail = AVAIL[dayName] !== undefined;

        const row = document.createElement('div');
        row.className = 'week-day-row' + (bk.dateSQL===sql?' selected-day':'');
        row.id = 'wrow_'+sql;
        let inner = `<div class="week-day-label"><div class="week-day-name">${dayShort[d.getDay()]}</div><div class="week-day-date">${monthNames[d.getMonth()].slice(0,3)} ${d.getDate()}</div></div>`;
        inner += `<div class="week-slots" id="wslots_${sql}">`;
        if (!isAvail) {
            inner += `<span class="week-no-slots">Not available</span>`;
        } else {
            inner += buildSlotHTML(dayName, sql);
        }
        inner += `</div>`;
        row.innerHTML = inner;
        list.appendChild(row);
    }
}

function formatTime12(ts) {
    let [h, m] = ts.split(':').map(Number);
    let ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12;
    h = h ? h : 12; 
    return `${h}:${m.toString().padStart(2, '0')} ${ampm}`;
}

function buildSlotHTML(dayName, dateSQL) {
    const avail = AVAIL[dayName];
    const booked = BOOKED[dateSQL] || [];
    const sh = parseInt(avail.start.split(':')[0]);
    const eh = parseInt(avail.end.split(':')[0]);
    const step = bk.duration === 30 ? 0.5 : 1;
    let html = '';
    for (let h=sh; h<eh; h+=step) {
        const hh = Math.floor(h); const mm = (h%1)===0.5?'30':'00';
        const ts = `${String(hh).padStart(2,'0')}:${mm}`;
        const isTaken = booked.includes(ts);
        const isSel   = bk.dateSQL===dateSQL && bk.time===ts;
        html += `<span class="ts${isTaken?' taken':isSel?' sel':''}" 
                      ${!isTaken?`onclick="pickSlot('${ts}','${dateSQL}','${dayName}',this)"`:''} 
                      title="${isTaken?'Already booked':''}">${formatTime12(ts)}</span>`;
    }
    return html;
}

function renderTimeSlots(dayName, dateSQL) {
    document.getElementById('timeSlots').innerHTML = buildSlotHTML(dayName, dateSQL);
}

function pickSlot(t, sql, dayName, el) {
    bk.time = t;
    if (currentView==='cal') {
        document.querySelectorAll('#timeSlots .ts').forEach(s=>s.classList.remove('sel'));
    } else {
        document.querySelectorAll('.week-slots .ts').forEach(s=>s.classList.remove('sel'));
        if (!bk.dateSQL || bk.dateSQL !== sql) {
            const today=new Date(); today.setHours(0,0,0,0);
            const d=new Date(sql); const dayShortL=dayShort[d.getDay()];
            bk.dateSQL=sql; bk.dateStr=`${dayShortL}, ${monthNames[d.getMonth()]} ${d.getDate()}`;
        }
    }
    el.classList.add('sel');
    document.getElementById('btn2').disabled = !bk.dateSQL;
}

// ── Summary ──────────────────────────────────────────────────
function buildSummary() {
    const sp  = sessionPrice();
    const sf  = serviceFee();
    const tot = totalPrice();
    document.getElementById('s-method').textContent = bk.method;
    document.getElementById('s-dur').textContent    = bk.duration + ' Minutes';
    document.getElementById('s-date').textContent   = bk.dateStr;
    document.getElementById('s-time').textContent   = bk.time;
    document.getElementById('s-price').textContent  = sp.toLocaleString() + ' EGP';
    document.getElementById('s-fee').textContent    = sf.toLocaleString() + ' EGP';
    document.getElementById('s-total').textContent  = tot.toLocaleString() + ' EGP';
    document.getElementById('s-breakdown').textContent =
        `Session Price ${sp.toLocaleString()} EGP + Service Fee ${sf.toLocaleString()} EGP = ${tot.toLocaleString()} EGP Total`;

    const pi = document.getElementById('paymentInfo');
    if (WALLET >= tot) {
        pi.innerHTML = `<i class="fas fa-check-circle" style="color:var(--green-s)"></i> Your wallet balance (${WALLET.toLocaleString()} EGP) covers the full amount. ✅`;
    } else if (WALLET > 0) {
        const rem = tot - WALLET;
        pi.innerHTML = `<i class="fas fa-info-circle" style="color:var(--bronze)"></i> ${WALLET.toLocaleString()} EGP from wallet — remaining <strong>${rem.toLocaleString()} EGP</strong> billed to card.`;
    } else {
        pi.innerHTML = `<i class="fas fa-credit-card" style="color:var(--bronze)"></i> Full amount of <strong>${tot.toLocaleString()} EGP</strong> will be charged.`;
    }
}

function selectPaymentMethod(type, el) {
    bk.paymentType = type;
    document.querySelectorAll('.pm-card').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
}

// ── Submit ────────────────────────────────────────────────────
function bookSession() {
    const btn = document.getElementById('btnConfirm');
    btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Processing…';
    $.post('api/booking/create.php', {
        therapist_id: THERAPIST_ID,
        method: bk.method,
        date:   bk.dateSQL,
        time:   bk.time,
        total:  totalPrice(),
        duration: bk.duration,
        payment_type: bk.paymentType
    }, function(res) {
        if (res.status==='success') {
            window.location.href = 'booking_success.php?session_id='+res.private_session_id;
        } else {
            alert('Error: ' + (res.message||'Unknown error'));
            btn.disabled=false;
            btn.innerHTML='<i class="fas fa-lock"></i> Confirm & Pay';
        }
    }, 'json').fail(function(){
        alert('Connection failed.'); btn.disabled=false; btn.innerHTML='<i class="fas fa-lock"></i> Confirm & Pay';
    });
}
</script>
<?php include 'includes/footer.php'; ?>