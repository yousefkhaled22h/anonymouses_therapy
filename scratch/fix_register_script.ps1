$file = 'c:\xampp\htdocs\test2\register.php'
$lines = Get-Content $file -Raw
$linesArray = $lines -split "`r?`n"

# Find where the script tag starts, or just find the closing </div> of the container.
# We will just rewrite from the end of the form.
$endIndex = 0
for ($i = 0; $i -lt $linesArray.Length; $i++) {
    if ($linesArray[$i] -match "</div>") {
        # Keep searching for the final </div> 
    }
}

# The easiest way: replace everything from "<script>" to the end of the file.
$scriptIndex = -1
for ($i = 0; $i -lt $linesArray.Length; $i++) {
    if ($linesArray[$i] -match "<script>") {
        $scriptIndex = $i
        break
    }
}

if ($scriptIndex -eq -1) {
    # If the script tag was deleted, find where to append it
    # Find `<?php include 'includes/footer.php'; ?>`
    for ($i = 0; $i -lt $linesArray.Length; $i++) {
        if ($linesArray[$i] -match "<?php include 'includes/footer.php'; ?>") {
            $scriptIndex = $i - 1
            break
        }
    }
}

# Actually, because the previous tool corrupted the file, let me just read the whole file,
# find the last "</div>" before the footer, and put the script there.
$fileContent = Get-Content $file -Raw

# Replace anything from <script> to <?php include 'includes/footer.php'; ?>
$pattern = '(?s)<script>.*?(?=<\?php include ''includes/footer\.php''; \?>)'
$newScript = @"
<script>
    function updateFileName(input) {
        const fileName = input.files[0] ? input.files[0].name : "Click to upload license document";
        document.getElementById('file-name').textContent = fileName;
    }

    function togglePassword() {
        const p = document.getElementById('password');
        const isText = p.getAttribute('type') === 'text';
        p.setAttribute('type', isText ? 'password' : 'text');
        const icon = document.getElementById('eyeIcon') || document.getElementById('eye-icon');
        if (icon && icon.tagName === 'I') {
            icon.className = isText ? 'fas fa-eye' : 'fas fa-eye-slash';
        }
    }

    const anonCheckbox = document.getElementById('is_anonymous');
    if (anonCheckbox) {
        anonCheckbox.addEventListener('change', function() {
            const nameInput = document.getElementById('name');
            if (this.checked) {
                const adjs = ['Happy', 'Calm', 'Brave', 'Gentle', 'Kind', 'Wise', 'Bright', 'Silent', 'Blue', 'Swift'];
                const nouns = ['River', 'Mountain', 'Sky', 'Star', 'Leaf', 'Panda', 'Eagle', 'Ocean', 'Moon', 'Fox'];
                const nick = adjs[Math.floor(Math.random() * adjs.length)] + ' ' + nouns[Math.floor(Math.random() * nouns.length)];
                if (!nameInput.dataset.original) {
                    nameInput.dataset.original = nameInput.value;
                }
                nameInput.value = nick + Math.floor(Math.random() * 999 + 100);
                nameInput.readOnly = true;
                nameInput.style.opacity = '0.7';
            } else {
                nameInput.value = nameInput.dataset.original || '';
                nameInput.readOnly = false;
                nameInput.style.opacity = '1';
            }
        });
    }

    document.getElementById('registerForm')?.addEventListener('submit', async function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        const submitBtn = this.querySelector('.btn-submit');
        const originalBtnText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Creating account...';
        try {
            const response = await fetch('api/auth/register.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                alert(result.message);
                window.location.href = result.redirect || 'login.php';
            } else {
                alert(result.message || 'Registration failed');
                submitBtn.disabled = false;
                submitBtn.textContent = originalBtnText;
            }
        } catch (error) {
            alert('An error occurred. Please try again.');
            submitBtn.disabled = false;
            submitBtn.textContent = originalBtnText;
        }
    });
</script>
"@

$fileContent = $fileContent -replace '(?s)(</div>\s*)(const response = await fetch.*?)(<\?php include ''includes/footer\.php''; \?>)', "`$1$newScript`r`n`$3"
# If the above didn't match because the <script> is still partially there:
$fileContent = $fileContent -replace '(?s)<script>.*?(?=<\?php include ''includes/footer\.php''; \?>)', $newScript

Set-Content $file -Value $fileContent -Encoding UTF8 -NoNewline
Write-Host "register.php script fixed"
