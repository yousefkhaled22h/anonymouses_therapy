$file = 'c:\xampp\htdocs\test2\onboarding.php'
$lines = Get-Content $file -Encoding UTF8

$newLines = @()
for ($i = 0; $i -le 441; $i++) {
    $newLines += $lines[$i]
}

$replacement = @"

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
"@

$newLines += $replacement

for ($i = 476; $i -lt $lines.Length; $i++) {
    $newLines += $lines[$i]
}

[System.IO.File]::WriteAllLines($file, $newLines, [System.Text.Encoding]::UTF8)
Write-Host "Fixed Step 4 HTML structure and emojis."
