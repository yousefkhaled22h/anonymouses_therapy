$file = 'c:\xampp\htdocs\test2\register.php'
$lines = Get-Content $file -Raw
# Since I'm using regex to replace a huge block, I'll use a script block or line indexing.
# Or better yet, just replace the lines.
$linesArray = Get-Content $file

# Get everything up to line 288 (0..287)
$part1 = $linesArray[0..287]

# Get everything after line 658 (658..end)
$part2 = $linesArray[658..($linesArray.Length - 1)]

$newBody = @"
<!-- Unified Single Column Layout for All Roles -->
<div class="reg-container">
    <a href="role_selection.php" class="back-link">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        Back to role selection
    </a>

    <div class="reg-card">
        <div class="reg-header">
            <div class="auth-logo" style="margin-bottom: 20px;">
                <img class="img-fluid" src="assets/images/logo-premium.png" alt="Safe Haven" style="width: 80px; height: auto;">
            </div>
            <?php if (`$role === 'therapist'): ?>
                <h1>Join as Therapist</h1>
                <p>Professional Portal</p>
            <?php elseif (`$role === 'volunteer'): ?>
                <h1>Become a Volunteer</h1>
                <p>Community Helper Portal</p>
            <?php else: ?>
                <h1>Create Your Free Account</h1>
                <p>Join thousands who've found support on Safe Haven.</p>
            <?php endif; ?>
        </div>

        <form id="registerForm" action="api/auth/register.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="role" value="<?php echo ucfirst(`$role); ?>">

            <?php if (`$role === 'client'): ?>
                <div class="form-group">
                    <label for="name">Your Name</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="name" name="name" class="form-control" placeholder="How should we call you?" required>
                    </div>
                </div>
            <?php else: ?>
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" id="first_name" name="first_name" class="form-control" placeholder="First name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" id="last_name" name="last_name" class="form-control" placeholder="Last name" required>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (`$role === 'therapist'): ?>
                <div class="form-group">
                    <label for="specialization">Specialist Area</label>
                    <select id="specialization" name="specialization" class="form-control" required>
                        <option value="">Select your specialization</option>
                        <option value="Anxiety">Anxiety & Stress</option>
                        <option value="Depression">Depression</option>
                        <option value="Relationships">Relationship Issues</option>
                        <option value="Trauma">Trauma & PTSD</option>
                        <option value="Childhood">Childhood Development</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Upload License</label>
                    <div class="file-upload-box" onclick="document.getElementById('license').click()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        <p id="file-name">Click to upload license document</p>
                    </div>
                    <input type="file" id="license" name="license" style="display: none;" onchange="updateFileName(this)">
                    <p class="file-hint">Accepted formats: PDF, JPG, PNG (Max 5MB)</p>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-with-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" class="form-control" placeholder="your@email.com" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Create a secure password" required>
                    <span class="password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </span>
                </div>
            </div>

            <?php if (`$role === 'client'): ?>
                <label class="anonymous-toggle" for="is_anonymous">
                    <input type="checkbox" id="is_anonymous" name="is_anonymous" class="anonymous-checkbox" value="1">
                    <div class="anonymous-text">
                        <h3>🕵️ Join anonymously</h3>
                        <p>Your real identity stays completely private. We'll assign you an alias.</p>
                    </div>
                </label>
            <?php endif; ?>

            <button type="submit" class="btn-submit">
                <?php if (`$role === 'therapist'): ?>Submit for Approval
                <?php elseif (`$role === 'volunteer'): ?>Join Community
                <?php else: ?>Create My Free Account →<?php endif; ?>
            </button>

            <div class="auth-footer">
                Already have an account? <a href="login.php">Sign in</a>
            </div>

            <?php if (`$role === 'therapist'): ?>
                <div class="notice-box">Your license will be verified by our admin team within 24–48 hours</div>
            <?php elseif (`$role === 'volunteer'): ?>
                <div class="notice-box">Help others on their mental wellness journey by providing peer support</div>
            <?php elseif (`$role === 'client'): ?>
                <div style="margin-top: 25px; display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                    <div style="display:flex;align-items:center;gap:6px;font-size:0.78rem;color:#8D6E63;">
                        <i class="fas fa-shield-alt" style="color:#8A7055;"></i> Secure & Encrypted
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;font-size:0.78rem;color:#8D6E63;">
                        <i class="fas fa-user-secret" style="color:#8A7055;"></i> Anonymous Option
                    </div>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>
"@

$newContent = ($part1 -join "`r`n") + "`r`n" + $newBody + "`r`n" + ($part2 -join "`r`n")
Set-Content $file -Value $newContent -Encoding UTF8
Write-Host 'register.php updated to unified card layout.'
