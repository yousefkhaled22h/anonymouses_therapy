<?php
// onboarding.php - Safe Haven New Client Onboarding Survey
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db_connect.php';
require_once 'includes/i18n.php';
ob_start('translate_html_buffer');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Client') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Safe Haven - Find Your Match</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --sky-a: #8A7055;
  --sky-b: #A68B6A;
  --sky-c: #C4B097;
  --ink:   #3E2723;
  --muted: #8D6E63;
}

body {
  font-family: 'Segoe UI', system-ui, sans-serif;
  min-height: 100vh;
  background: linear-gradient(160deg, #FDFBF8 0%, #F4F1EA 45%, #EFEBE1 100%);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
  padding: 32px 16px 48px;
  color: var(--ink);
}

/* Logo */
.ob-logo {
  font-size: 1rem; font-weight: 800; color: var(--sky-a);
  margin-bottom: 22px;
  display: flex; align-items: center; gap: 8px;
}
.ob-logo span { opacity: .45; font-weight: 400; font-size: .78rem; }

/* Card */
.ob-card {
  background: rgba(255,255,255,0.97);
  border-radius: 24px;
  box-shadow: 0 24px 64px rgba(138,112,85,0.13);
  width: 100%; max-width: 600px;
  padding: 44px 50px;
  position: relative;
  overflow: hidden;
}
.ob-card::before {
  content: '';
  position: absolute; top: 0; left: 0; right: 0; height: 5px;
  background: linear-gradient(90deg, var(--sky-a), var(--sky-b), var(--sky-c));
}

/* Progress bar */
.ob-progress-wrap { margin-bottom: 30px; }
.ob-progress-bar { height: 5px; background: #EFEBE1; border-radius: 99px; overflow: hidden; }
.ob-progress-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--sky-a), var(--sky-c));
  border-radius: 99px;
  transition: width .55s cubic-bezier(.4,0,.2,1);
}
.ob-step-label {
  display: flex; justify-content: space-between;
  font-size: .76rem; color: var(--muted); margin-top: 8px;
}

/* Steps */
.ob-step { display: none; animation: fadeUp .38s cubic-bezier(.4,0,.2,1); }
.ob-step.active { display: block; }
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(16px); }
  to   { opacity: 1; transform: translateY(0); }
}

.ob-question {
  font-size: 1.38rem; font-weight: 800; color: var(--ink);
  line-height: 1.28; margin-bottom: 6px;
}
.ob-subtext { font-size: .88rem; color: var(--muted); margin-bottom: 22px; line-height: 1.55; }

/* PILL OPTIONS (Q1) */
.pill-options {
  display: flex; flex-direction: column; gap: 10px;
  margin-bottom: 4px;
}
.pill-opt {
  width: 100%; padding: 15px 22px;
  background: #FDFBF8;
  border: 2px solid #E0D8CC;
  border-radius: 50px;
  font-size: .97rem; font-weight: 500; color: #2d3748;
  cursor: pointer; text-align: center;
  transition: all .2s;
}
.pill-opt:hover {
  background: #F4F1EA;
  border-color: var(--sky-b);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(138,112,85,.1);
}
.pill-opt.selected {
  background: linear-gradient(135deg, #FDFBF8, #E0D8CC);
  border-color: var(--sky-a);
  color: var(--sky-a);
  font-weight: 700;
  box-shadow: 0 0 0 4px rgba(138,112,85,.12), 0 4px 14px rgba(138,112,85,.1);
}

/* More / fewer toggle */
.more-extra { display: none; flex-direction: column; gap: 10px; }
.more-extra.visible { display: flex; }

.pill-opt-more {
  background: #F4F1EA;
  color: var(--muted);
  font-size: .9rem;
}
.pill-skip {
  text-align: center; margin-top: 8px;
  font-size: .82rem; color: #b0bec5;
  cursor: pointer; text-decoration: underline; text-underline-offset: 3px;
  background: none; border: none;
  transition: color .2s;
}
.pill-skip:hover { color: var(--sky-a); }

/* ICON OPTIONS (Q2-4) */
.ob-options { display: flex; flex-direction: column; gap: 11px; }
.ob-option {
  display: flex; align-items: center; gap: 14px;
  padding: 15px 18px;
  border: 2px solid #E0D8CC; border-radius: 14px;
  cursor: pointer; background: white;
  transition: all .2s; font-size: .96rem; font-weight: 500;
  color: var(--ink); text-align: left; width: 100%; position: relative;
}
.ob-option:hover { border-color: var(--sky-b); background: #FDFBF8; transform: translateX(4px); }
.ob-option.selected {
  border-color: var(--sky-a);
  background: linear-gradient(135deg, #FDFBF8, #FDFBF8);
  color: var(--sky-a);
  box-shadow: 0 4px 16px rgba(138,112,85,.12);
}
.ob-option-icon { font-size: 1.3rem; width: 26px; flex-shrink: 0; text-align: center; }
.ob-option-check {
  position: absolute; right: 16px; top: 50%; transform: translateY(-50%);
  width: 21px; height: 21px; border-radius: 50%;
  border: 2px solid #D4C5B0;
  display: flex; align-items: center; justify-content: center;
  font-size: .68rem; color: transparent; transition: all .2s; background: transparent;
}
.ob-option.selected .ob-option-check { background: var(--sky-a); border-color: var(--sky-a); color: white; }

/* Navigation */
.ob-nav { display: flex; gap: 12px; margin-top: 26px; }
.btn-ob-back {
  flex: 0 0 auto; padding: 13px 20px; border-radius: 12px;
  border: 2px solid #E0D8CC; background: white; color: var(--muted);
  font-weight: 600; font-size: .9rem; cursor: pointer; transition: all .2s;
}
.btn-ob-back:hover { border-color: var(--sky-b); color: var(--sky-a); }
.btn-ob-next {
  flex: 1; padding: 14px 22px; border-radius: 12px;
  background: linear-gradient(135deg, var(--sky-a), var(--sky-b));
  color: white; font-weight: 700; font-size: .98rem;
  border: none; cursor: pointer;
  box-shadow: 0 6px 18px rgba(138,112,85,.28);
  transition: all .22s;
  display: flex; align-items: center; justify-content: center; gap: 8px;
}
.btn-ob-next:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(138,112,85,.38); }
.btn-ob-next:disabled { opacity: .45; cursor: not-allowed; transform: none; }

.ob-privacy {
  display: flex; align-items: center; gap: 8px;
  font-size: .74rem; color: #94a3b8; margin-top: 14px; justify-content: center;
}
.ob-privacy i { color: #22c55e; }

/* Textarea */
.ob-textarea {
  width: 100%; min-height: 120px;
  border: 2px solid #E0D8CC; border-radius: 14px;
  padding: 16px 18px; font-size: .95rem; color: var(--ink);
  font-family: inherit; resize: vertical;
  transition: border .2s, box-shadow .2s; background: white; line-height: 1.6;
}
.ob-textarea:focus { outline: none; border-color: var(--sky-a); box-shadow: 0 0 0 4px rgba(138,112,85,.1); }
.ob-ai-hint {
  display: flex; gap: 10px; align-items: flex-start;
  background: linear-gradient(135deg, #FDFBF8, #FDFBF8);
  border: 1px solid #E0D8CC; border-radius: 12px;
  padding: 12px 16px; margin-top: 10px; font-size: .82rem; color: #6D4C41; line-height: 1.5;
}
.ob-ai-hint i { flex-shrink: 0; margin-top: 2px; }

/* LOADING */
.ob-loading { display: none; text-align: center; padding: 24px 0; }
.ob-loading.active { display: block; }
.brain-spin {
  font-size: 3.5rem; display: block; margin: 0 auto 14px;
  animation: breathe 1.8s ease-in-out infinite;
}
@keyframes breathe { 0%,100%{transform:scale(1);opacity:.9} 50%{transform:scale(1.11);opacity:1} }
.ob-loading h3 { font-size: 1.2rem; font-weight: 800; color: var(--ink); margin-bottom: 6px; }
.ob-loading p  { color: var(--muted); font-size: .87rem; }
.dot-flash span {
  display: inline-block; width: 7px; height: 7px;
  border-radius: 50%; background: var(--sky-b); margin: 0 3px;
  animation: dotFlash 1.2s ease-in-out infinite;
}
.dot-flash span:nth-child(2) { animation-delay: .2s; }
.dot-flash span:nth-child(3) { animation-delay: .4s; }
@keyframes dotFlash { 0%,80%,100%{opacity:.2} 40%{opacity:1} }

/* MATCH REVEAL */
#matchReveal { display: none; }
#matchReveal.active { display: block; animation: fadeUp .5s ease; }
.ai-reflection {
  background: linear-gradient(135deg, #FDFBF8, #FDFBF8);
  border-left: 4px solid var(--sky-b); border-radius: 14px;
  padding: 16px 20px; margin-bottom: 20px;
  font-size: .9rem; color: #3E2723; line-height: 1.6;
  display: flex; gap: 12px; align-items: flex-start;
}
.ai-reflection i { font-size: 1.1rem; color: var(--sky-a); margin-top: 2px; flex-shrink: 0; }
.match-label { font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--sky-a); margin-bottom: 8px; }
.match-headline { font-size: 1.35rem; font-weight: 800; color: var(--ink); margin-bottom: 18px; line-height: 1.25; }
.t-card {
  background: white; border-radius: 18px; border: 1px solid #E0D8CC;
  padding: 22px; box-shadow: 0 6px 22px rgba(138,112,85,.08);
  margin-bottom: 20px; display: flex; gap: 18px; align-items: flex-start;
}
.t-avatar {
  width: 72px; height: 72px; border-radius: 14px; flex-shrink: 0;
  background: linear-gradient(135deg, var(--sky-a), var(--sky-c));
  display: flex; align-items: center; justify-content: center;
  font-size: 1.7rem; color: white; font-weight: 700;
  box-shadow: 0 4px 12px rgba(0,0,0,.1);
}
.t-info { flex: 1; }
.t-name { font-size: 1.1rem; font-weight: 800; color: var(--ink); margin-bottom: 2px; }
.t-meta { font-size: .8rem; color: var(--muted); }
.t-tags { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 9px; }
.t-tag { padding: 3px 10px; border-radius: 99px; font-size: .74rem; font-weight: 600; background: #E0D8CC; color: #3E2723; }
.t-bio { font-size: .85rem; color: #4b5563; margin-top: 8px; line-height: 1.5; }
.t-rate { font-size: 1.15rem; font-weight: 800; color: var(--sky-a); margin-top: 10px; }
.match-actions { display: flex; flex-direction: column; gap: 10px; }
.btn-book-match {
  width: 100%; padding: 15px; border-radius: 14px;
  background: linear-gradient(135deg, var(--sky-a), var(--sky-b));
  color: white; font-weight: 700; font-size: 1rem; border: none; cursor: pointer;
  box-shadow: 0 6px 20px rgba(138,112,85,.3); transition: all .22s;
  display: flex; align-items: center; justify-content: center; gap: 10px;
}
.btn-book-match:hover { transform: translateY(-3px); box-shadow: 0 10px 26px rgba(138,112,85,.4); }
.btn-maybe-later {
  width: 100%; padding: 13px; border-radius: 14px;
  background: transparent; color: var(--muted); font-size: .9rem;
  border: 2px solid #E0D8CC; cursor: pointer; font-weight: 600; transition: all .2s;
}
.btn-maybe-later:hover { background: #F4F1EA; color: var(--ink); border-color: #D4C5B0; }

/* FREE DASH */
#freeDash { display: none; }
#freeDash.active { display: block; animation: fadeUp .4s ease; }
.noni-bubble {
  background: linear-gradient(135deg, #fdf4ff, #fce7ff);
  border-radius: 18px; padding: 24px; text-align: center; margin-bottom: 20px;
  border: 1px solid #e9d5ff;
}
.noni-bubble .noni-emoji { font-size: 2.6rem; display: block; margin-bottom: 8px; }
.noni-bubble h3 { font-size: 1.05rem; font-weight: 800; color: #581c87; margin-bottom: 6px; }
.noni-bubble p { color: #7e22ce; font-size: .87rem; line-height: 1.6; }
.free-options { display: grid; grid-template-columns: 1fr 1fr; gap: 11px; }
.free-card {
  background: white; border-radius: 14px; padding: 17px;
  border: 1px solid #E0D8CC; text-decoration: none; color: var(--ink);
  display: flex; flex-direction: column; gap: 6px; transition: all .2s;
}
.free-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,.07); border-color: var(--sky-b); }
.free-card-icon { font-size: 1.6rem; }
.free-card-title { font-weight: 700; font-size: .88rem; }
.free-card-sub { font-size: .77rem; color: var(--muted); }
.btn-go-dashboard {
  width: 100%; padding: 14px; border-radius: 14px; margin-top: 14px;
  background: linear-gradient(135deg, var(--sky-a), var(--sky-b));
  color: white; font-weight: 700; font-size: .97rem; border: none; cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none;
}

@media (max-width: 480px) {
  .ob-card { padding: 30px 22px; }
  .free-options { grid-template-columns: 1fr; }
  .t-card { flex-direction: column; }
}
</style>
</head>
<body>

<div class="ob-logo">
  <img src="assets/images/logo.png" alt="Safe Haven Logo" style="height: 32px; width: auto; object-fit: contain;">
  Safe Haven <span>• Private &amp; Secure</span>
</div>

<div class="ob-card">

  <!-- Progress bar -->
  <div class="ob-progress-wrap" id="progressWrap">
    <div class="ob-progress-bar">
      <div class="ob-progress-fill" id="progressFill" style="width:25%"></div>
    </div>
    <div class="ob-step-label">
      <span id="stepLabel">Step 1 of 4</span>
      <span>100% Confidential</span>
    </div>
  </div>

  <!-- STEP 1 -->
  <div class="ob-step active" id="step1">
    <h2 class="ob-question">What brings you to Safe Haven today?</h2>
    <p class="ob-subtext">Select the area where you would like the most support.</p>

    <div class="pill-options" id="pillPrimary">
      <button class="pill-opt" data-tag="Anxiety"       onclick="selectPill(this)">Reduce Anxiety</button>
      <button class="pill-opt" data-tag="Depression"    onclick="selectPill(this)">Lift Depression &amp; Improve Mood</button>
      <button class="pill-opt" data-tag="Relationships" onclick="selectPill(this)">Manage Relationship Stress</button>
      <button class="pill-opt" data-tag="High-Need"     onclick="selectPill(this)">Get Help for Addiction</button>
      <button class="pill-opt" data-tag="Anxiety"       onclick="selectPill(this)">Boost Focus &amp; Manage ADHD</button>
    </div>

    <!-- Extra options (collapsed) -->
    <div class="more-extra" id="moreExtra">
      <button class="pill-opt" data-tag="Relationships" onclick="selectPill(this)">Manage Family Stress</button>
      <button class="pill-opt" data-tag="Relationships" onclick="selectPill(this)">Motherhood Support</button>
      <button class="pill-opt" data-tag="Anxiety"       onclick="selectPill(this)">Cope with Student Life Pressures</button>
      <button class="pill-opt" data-tag="Trauma"        onclick="selectPill(this)">Build Self-Esteem &amp; Confidence</button>
      <button class="pill-opt" data-tag="Trauma"        onclick="selectPill(this)">Get Unstuck in Life</button>
      <button class="pill-opt" data-tag="Anxiety"       onclick="selectPill(this)">Improve Emotional Balance</button>
      <button class="pill-opt" data-tag="Relationships" onclick="selectPill(this)">Explore Identity &amp; Self-Discovery</button>
      <button class="pill-opt" data-tag="Anxiety"       onclick="selectPill(this)">Navigate Work Stress</button>
      <button class="pill-opt" data-tag="High-Need"     onclick="selectPill(this)">Cut Down Alcohol or Drugs</button>
      <button class="pill-opt" data-tag="Trauma"        onclick="selectPill(this)">Heal from Bullying</button>
      <button class="pill-opt" data-tag="Depression"    onclick="selectPill(this)">Feel More in Control of Bipolar</button>
      <button class="pill-opt" data-tag="Trauma"        onclick="selectPill(this)">Understand &amp; Manage Borderline Personality</button>
      <button class="pill-opt" data-tag="Relationships" onclick="selectPill(this)">Find Support for Autism Spectrum</button>
      <button class="pill-opt" data-tag="Trauma"        onclick="selectPill(this)">Reduce Urges for Self-Harm</button>
      <button class="pill-opt" data-tag="Depression"    onclick="selectPill(this)">Improve General Mental Health &amp; Well-being</button>
    </div>

    <!-- More toggle -->
    <button class="pill-opt pill-opt-more" id="moreToggle" onclick="toggleMore()" style="margin-top:10px;display:flex;align-items:center;justify-content:center;gap:8px;font-size:.88rem;">
      <span id="moreToggleText">More options</span>
      <i class="fas fa-chevron-down" id="moreChevron" style="transition:transform .25s;font-size:.75rem;"></i>
    </button>

    <div class="ob-nav">
      <button class="btn-ob-next" id="next1" onclick="goStep(2)" disabled>
        Continue <i class="fas fa-arrow-right"></i>
      </button>
    </div>
    <button class="pill-skip" onclick="location.href='dashboard.php'">Skip questionnaire</button>
    <div class="ob-privacy"><i class="fas fa-shield-alt"></i> Your responses are 100% private and never shared</div>
  </div>

  <!-- STEP 2 -->
  <div class="ob-step" id="step2">
    <h2 class="ob-question">How often do you find yourself wishing for a bit of extra support?</h2>
    <p class="ob-subtext">There's no wrong answer - this helps us find the right fit for you.</p>
    <div class="ob-options">
      <button class="ob-option" data-tag="Low Intensity" onclick="selectOption(this,'q2')">
        Occasionally - I experience this sometimes.
        <span class="ob-option-check"><i class="fas fa-check"></i></span>
      </button>
      <button class="ob-option" data-tag="Medium Intensity" onclick="selectOption(this,'q2')">
        Frequently - it comes up quite often
        <span class="ob-option-check"><i class="fas fa-check"></i></span>
      </button>
      <button class="ob-option" data-tag="High Intensity" onclick="selectOption(this,'q2')">
        Daily - this is a constant presence in my life.
        <span class="ob-option-check"><i class="fas fa-check"></i></span>
      </button>
      <button class="ob-option" data-tag="Casual" onclick="selectOption(this,'q2')">
        I'm just exploring what's out there
        <span class="ob-option-check"><i class="fas fa-check"></i></span>
      </button>
    </div>
    <div class="ob-nav">
      <button class="btn-ob-back" onclick="goStep(1)"><i class="fas fa-arrow-left"></i></button>
      <button class="btn-ob-next" id="next2" onclick="goStep(3)" disabled>Continue <i class="fas fa-arrow-right"></i></button>
    </div>
    <div class="ob-privacy"><i class="fas fa-shield-alt"></i> Your responses are 100% private and never shared</div>
  </div>

  <!-- STEP 3 -->
  <div class="ob-step" id="step3">
    <h2 class="ob-question">How do you currently manage difficult emotions?</h2>
    <p class="ob-subtext">Be honest - this is just between you and Safe Haven.</p>
    <div class="ob-options">
      <button class="ob-option" data-tag="Skill-Seeker" onclick="selectOption(this,'q3')">
        I'm still searching for the right tools to help me
        <span class="ob-option-check"><i class="fas fa-check"></i></span>
      </button>
      <button class="ob-option" data-tag="Proactive" onclick="selectOption(this,'q3')">
        Self-care - breathing, journaling, or movement
        <span class="ob-option-check"><i class="fas fa-check"></i></span>
      </button>
      <button class="ob-option" data-tag="Avoidant" onclick="selectOption(this,'q3')">
        I tend to distract myself - phone, food, anything
        <span class="ob-option-check"><i class="fas fa-check"></i></span>
      </button>
      <button class="ob-option" data-tag="High-Need" onclick="selectOption(this,'q3')">
        I rely on others, or sometimes on substances
        <span class="ob-option-check"><i class="fas fa-check"></i></span>
      </button>
    </div>
    <div class="ob-nav">
      <button class="btn-ob-back" onclick="goStep(2)"><i class="fas fa-arrow-left"></i></button>
      <button class="btn-ob-next" id="next3" onclick="goStep(4)" disabled>Continue <i class="fas fa-arrow-right"></i></button>
    </div>
    <div class="ob-privacy"><i class="fas fa-shield-alt"></i> Your responses are 100% private and never shared</div>
  </div>

  <!-- STEP 4 -->
  <div class="ob-step" id="step4">
    <h2 class="ob-question">If we could help you reach one state of mind this month, what would it be?</h2>
    <p class="ob-subtext">Choose what resonates most right now - it can always change.</p>
    <div class="ob-options">
      <button class="ob-option" data-tag="Anxiety-Focus" onclick="selectOption(this,'q4')">
        Calm and at peace
        <span class="ob-option-check"><i class="fas fa-check"></i></span>
      </button>
      <button class="ob-option" data-tag="Trauma/Organization-Focus" onclick="selectOption(this,'q4')">
        In control of my life
        <span class="ob-option-check"><i class="fas fa-check"></i></span>
      </button>
      <button class="ob-option" data-tag="Loneliness-Focus" onclick="selectOption(this,'q4')">
        Connected and supported
        <span class="ob-option-check"><i class="fas fa-check"></i></span>
      </button>
      <button class="ob-option" data-tag="Depression-Focus" onclick="selectOption(this,'q4')">
        More positive and motivated
        <span class="ob-option-check"><i class="fas fa-check"></i></span>
      </button>
    </div>

    <div style="margin-top:20px;">
      <p style="font-size:.83rem;color:var(--muted);margin-bottom:8px;">Anything else you'd like to share? <em>(Optional)</em></p>
      <textarea class="ob-textarea" id="ventText" placeholder="Feel free to share any additional details here..."></textarea>
    </div>

    <div class="ob-nav">
      <button class="btn-ob-back" onclick="goStep(3)"><i class="fas fa-arrow-left"></i></button>
      <button class="btn-ob-next" id="next4" onclick="submitAndMatch()">
        <i class="fas fa-heart"></i> Find My Match
      </button>
    </div>
    <div class="ob-privacy"><i class="fas fa-shield-alt"></i> Your responses are 100% private and never shared</div>
  </div>

  <!-- LOADING -->
  <div class="ob-loading" id="loadingScreen">
    <span class="brain-spin">🔮</span>
    <h3>Analyzing your profile...</h3>
    <p>We're finding your ideal therapist</p>
    <div class="dot-flash" style="margin-top:16px;"><span></span><span></span><span></span></div>
  </div>

  <!-- MATCH REVEAL -->
  <div id="matchReveal">
    <div id="aiReflectionBox" class="ai-reflection" style="display:none;">
      <i class="fas fa-robot"></i>
      <span id="aiReflectionText"></span>
    </div>
    <div class="match-label">✨ Perfect Match Found</div>
    <div class="match-headline" id="matchHeadline">We've found your match.</div>
    <div class="t-card">
      <div class="t-avatar" id="therapistAvatar"></div>
      <div class="t-info">
        <div class="t-name" id="therapistName"></div>
        <div class="t-meta" id="therapistMeta"></div>
        <div class="t-tags" id="therapistTags"></div>
        <div class="t-bio"  id="therapistBio"></div>
        <div class="t-rate" id="therapistRate"></div>
      </div>
    </div>
    <div class="match-actions">
      <button class="btn-book-match" id="btnBookSession">
        <i class="fas fa-user-md"></i> View Profile
      </button>
      <button class="btn-maybe-later" onclick="location.href='dashboard.php'">
        Maybe Later - Back to Dashboard
      </button>
    </div>
    <div class="ob-privacy" style="margin-top:14px;"><i class="fas fa-shield-alt"></i> No commitment required</div>
  </div>

  <!-- FREE DASH -->
  <div id="freeDash">
    <div class="noni-bubble">
      <span class="noni-emoji">🤖</span>
      <h3>No pressure. Your Safe Haven is always open.</h3>
      <p>Explore our free community groups, or talk to <strong>Noni</strong>, your 24/7 AI Companion, while you settle in. Your matched therapist will be waiting whenever you're ready.</p>
    </div>
    <div class="free-options">
      <a href="group_management.php" class="free-card">
        <div class="free-card-icon">👥</div>
        <div class="free-card-title">Group Therapy</div>
        <div class="free-card-sub">Free sessions led by professionals</div>
      </a>
      <a href="community.php" class="free-card">
        <div class="free-card-icon">💬</div>
        <div class="free-card-title">Community Q&amp;A</div>
        <div class="free-card-sub">Ask questions, share anonymously</div>
      </a>
      <a href="my_diary.php" class="free-card">
        <div class="free-card-icon">📖</div>
        <div class="free-card-title">My Diary</div>
        <div class="free-card-sub">Write freely in your private space</div>
      </a>
      <a href="resources.php" class="free-card">
        <div class="free-card-icon">📚</div>
        <div class="free-card-title">Resource Library</div>
        <div class="free-card-sub">Guided exercises and wellness tools</div>
      </a>
    </div>
    <a href="dashboard.php" class="btn-go-dashboard">
      <i class="fas fa-home"></i> Go to My Safe Haven
    </a>
  </div>

</div><!-- /ob-card -->

<script>
const answers = { q1: '', q2: '', q3: '', q4: '' };
let currentStep = 1;

// Q1 pill selection
function selectPill(btn) {
  document.querySelectorAll('.pill-opt:not(#moreToggle)').forEach(b => b.classList.remove('selected'));
  btn.classList.add('selected');
  answers.q1 = btn.dataset.tag;
  document.getElementById('next1').disabled = false;
}


function toggleMore() {
  const panel   = document.getElementById('moreExtra');
  const text    = document.getElementById('moreToggleText');
  const chevron = document.getElementById('moreChevron');
  const open    = panel.classList.toggle('visible');
  text.textContent = open ? 'Fewer options' : 'More options';
  chevron.style.transform = open ? 'rotate(180deg)' : 'rotate(0)';
}

// Q2-Q4 icon-option selection
function selectOption(btn, key) {
  btn.closest('.ob-options').querySelectorAll('.ob-option').forEach(b => b.classList.remove('selected'));
  btn.classList.add('selected');
  answers[key] = btn.dataset.tag;
  const nextBtn = document.getElementById('next' + currentStep);
  if (nextBtn) nextBtn.disabled = false;
}

// Navigation
function goStep(step) {
  document.getElementById('step' + currentStep).classList.remove('active');
  currentStep = step;
  const el = document.getElementById('step' + step);
  if (el) el.classList.add('active');
  updateProgress(step);
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function updateProgress(step) {
  const pct = (step / 4) * 100;
  document.getElementById('progressFill').style.width = pct + '%';
  document.getElementById('stepLabel').textContent = 'Step ' + step + ' of 4';
}

// Submit & match
async function submitAndMatch() {
  document.getElementById('step4').classList.remove('active');
  document.getElementById('progressWrap').style.display = 'none';
  document.getElementById('loadingScreen').classList.add('active');

  const payload = {
    q1: answers.q1,
    q2: answers.q2,
    q3: answers.q3,
    q4: answers.q4,
    vent: document.getElementById('ventText').value
  };

  try {
    const res = await fetch('api/onboarding/match_therapist.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    await new Promise(r => setTimeout(r, 2000));
    document.getElementById('loadingScreen').classList.remove('active');
    if (data.status === 'success' && data.therapist) {
      buildMatchReveal(data);
    } else {
      showFreeDash();
    }
  } catch (e) {
    document.getElementById('loadingScreen').classList.remove('active');
    showFreeDash();
  }
}

function buildMatchReveal(data) {
  const t = data.therapist;
  const tag = data.matched_tag || 'Therapy';
  const fullName = 'Dr. ' + t.first_name + ' ' + t.last_name;

  if (data.ai_reflection) {
    document.getElementById('aiReflectionText').textContent = data.ai_reflection;
    document.getElementById('aiReflectionBox').style.display = 'flex';
  }
  document.getElementById('matchHeadline').textContent =
    "We've found your match. Meet " + fullName + ", an expert in " + tag + " who fits your specific needs.";

  const avatarEl = document.getElementById('therapistAvatar');
  if (t.profile_image) {
    avatarEl.style.cssText = 'background:none;border-radius:14px;overflow:hidden;';
    avatarEl.innerHTML = '<img src="' + t.profile_image + '" style="width:100%;height:100%;object-fit:cover;" onerror="this.parentElement.textContent=\'' + t.first_name[0] + t.last_name[0] + '\'">';
  } else {
    avatarEl.textContent = t.first_name[0] + t.last_name[0];
  }
  document.getElementById('therapistName').textContent = fullName;
  document.getElementById('therapistMeta').textContent = (t.years_experience || 0) + ' yrs experience  •  ⭐ ' + Number(t.rating || 5).toFixed(1);

  const tagsEl = document.getElementById('therapistTags');
  const specs = t.specialties ? t.specialties.split(',').map(s => s.trim()).filter(Boolean) : [tag];
  specs.slice(0, 4).forEach(s => {
    const span = document.createElement('span');
    span.className = 't-tag'; span.textContent = s;
    tagsEl.appendChild(span);
  });
  document.getElementById('therapistBio').textContent = t.bio
    ? t.bio.substring(0, 130) + (t.bio.length > 130 ? '…' : '')
    : 'Specialised in helping clients work through challenges with compassion.';
  document.getElementById('therapistRate').textContent = t.hourly_rate ? Number(t.hourly_rate).toFixed(0) + ' EGP / session' : 'Contact for pricing';
  document.getElementById('btnBookSession').onclick = () => { window.location.href = 'public_therapist_cv.php?id=' + t.user_id; };

  document.getElementById('matchReveal').classList.add('active');
  document.getElementById('progressWrap').style.display = 'none';
}

function showFreeDash() {
  document.getElementById('matchReveal').classList.remove('active');
  document.getElementById('freeDash').classList.add('active');
  document.getElementById('progressWrap').style.display = 'none';
}
</script>
</body>
</html>
