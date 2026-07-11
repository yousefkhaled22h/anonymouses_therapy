$file = 'c:\xampp\htdocs\test2\onboarding.php'
$lines = Get-Content $file -Encoding UTF8

$newLines = @()
for ($i = 0; $i -lt 410; $i++) {
    $newLines += $lines[$i]
}

$replacement = @"
  <!-- ====================================
       STEP 3 - Coping
  ==================================== -->
  <div class="ob-step" id="step3">
    <span class="ob-emoji">🌊</span>
    <h2 class="ob-question">How do you currently manage difficult emotions?</h2>
    <p class="ob-subtext">Be honest - this is just between you and Safe Haven.</p>
    <div class="ob-options">
      <button class="ob-option" data-tag="Skill-Seeker" onclick="selectOption(this,'q3')">
        <span class="ob-option-icon">🔍</span> I'm still searching for the right tools to help me
        <span class="ob-option-check"><i class="fas fa-check"></i></span>
      </button>
      <button class="ob-option" data-tag="Proactive" onclick="selectOption(this,'q3')">
        <span class="ob-option-icon">🧘</span> Self-care - breathing, journaling, or movement
        <span class="ob-option-check"><i class="fas fa-check"></i></span>
      </button>
      <button class="ob-option" data-tag="Avoidant" onclick="selectOption(this,'q3')">
        <span class="ob-option-icon">📱</span> I tend to distract myself - phone, food, anything
        <span class="ob-option-check"><i class="fas fa-check"></i></span>
      </button>
      <button class="ob-option" data-tag="High-Need" onclick="selectOption(this,'q3')">
        <span class="ob-option-icon">🤝</span> I rely on others, or sometimes on substances
        <span class="ob-option-check"><i class="fas fa-check"></i></span>
      </button>
    </div>
    <div class="ob-nav">
      <button class="btn-ob-back" onclick="goStep(2)"><i class="fas fa-arrow-left"></i></button>
      <button class="btn-ob-next" id="next3" onclick="goStep(4)" disabled>Continue <i class="fas fa-arrow-right"></i></button>
    </div>
    <div class="ob-privacy"><i class="fas fa-shield-alt"></i> Your responses are 100% private and never shared</div>
  </div>

  <!-- ====================================
       STEP 4 - Goals + vent
  ==================================== -->
  <div class="ob-step" id="step4">
    <span class="ob-emoji">🌱</span>
    <h2 class="ob-question">If we could help you reach one state of mind this month, what would it be?</h2>
    <p class="ob-subtext">Choose what resonates most right now - it can always change.</p>
    <div class="ob-options">
      <button class="ob-option" data-tag="Anxiety-Focus" onclick="selectOption(this,'q4')">
        <span class="ob-option-icon">🕊</span> Calm and at peace
        <span class="ob-option-check"><i class="fas fa-check"></i></span>
      </button>
      <button class="ob-option" data-tag="Trauma/Organization-Focus" onclick="selectOption(this,'q4')">
        <span class="ob-option-icon">⚡</span> In control of my life
        <span class="ob-option-check"><i class="fas fa-check"></i></span>
      </button>
      <button class="ob-option" data-tag="Loneliness-Focus" onclick="selectOption(this,'q4')">
        <span class="ob-option-icon">🫂</span> Connected and supported
        <span class="ob-option-check"><i class="fas fa-check"></i></span>
      </button>
      <button class="ob-option" data-tag="Depression-Focus" onclick="selectOption(this,'q4')">
        <span class="ob-option-icon">☀️</span> More positive and motivated
        <span class="ob-option-check"><i class="fas fa-check"></i></span>
      </button>
    </div>
"@

$newLines += $replacement

for ($i = 468; $i -lt $lines.Length; $i++) {
    $newLines += $lines[$i]
}

[System.IO.File]::WriteAllLines($file, $newLines, [System.Text.Encoding]::UTF8)
Write-Host "Fixed Step 3 and 4 successfully."
