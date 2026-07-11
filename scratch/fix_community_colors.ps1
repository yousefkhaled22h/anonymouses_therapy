$file = 'c:\xampp\htdocs\test2\community.php'
$content = Get-Content $file -Raw

# 1. Fix --qna-blue: change #0ea5e9 to use sidebarTheme
$content = $content -replace [regex]::Escape('--qna-blue: #0ea5e9;'), '--qna-blue: <?php echo $sidebarTheme; ?>;'
$content = $content -replace [regex]::Escape('/* Light blue used in search bar and icons */'), '/* Matches role theme color */'

# 2. Fix dashboard-main background: #f1f5f9 to warm role-based color
$content = $content -replace [regex]::Escape('background: #f1f5f9;'), "background: <?php echo (`$user_role === 'Therapist') ? '#FDFBF8' : ((`$user_role === 'Volunteer') ? '#f9fbf9' : '#FAF8F5'); ?>;"

# 3. Fix search-container border: #bae6fd to warm role-based border
$content = $content -replace [regex]::Escape('border: 2px solid #bae6fd;'), "border: 2px solid <?php echo (`$user_role === 'Therapist') ? '#D4C5B0' : ((`$user_role === 'Volunteer') ? '#a8d5b5' : '#EBE5E0'); ?>;"

# 4. Fix search-btn background: var(--qna-blue) to var(--primary-color)
$content = $content -replace [regex]::Escape('background: var(--qna-blue);'), 'background: var(--primary-color);'

# 5. Fix search-btn hover: remove hardcoded blue #0284c7
$content = $content -replace [regex]::Escape('background: #0284c7;'), 'filter: brightness(0.88);'

Set-Content $file -Value $content -NoNewline -Encoding UTF8
Write-Host 'Done - community.php colors updated successfully'
