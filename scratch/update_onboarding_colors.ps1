$file = 'c:\xampp\htdocs\test2\onboarding.php'
$content = Get-Content $file -Raw

# Root variables
$content = $content -replace '--sky-a: #2c6fa8;', '--sky-a: #8A7055;'
$content = $content -replace '--sky-b: #4a9fd4;', '--sky-b: #A68B6A;'
$content = $content -replace '--sky-c: #80c8ee;', '--sky-c: #C4B097;'
$content = $content -replace '--ink:   #1a2640;', '--ink:   #3E2723;'
$content = $content -replace '--muted: #64748b;', '--muted: #8D6E63;'

# Background gradient
$content = $content -replace 'linear-gradient\(160deg, #dff2fb 0%, #f0f9ff 45%, #fdf8f2 100%\)', 'linear-gradient(160deg, #FDFBF8 0%, #F4F1EA 45%, #EFEBE1 100%)'

# Pill / Option Backgrounds (Blueish whites -> Beigeish whites)
$content = $content -replace '#f8fbff', '#FDFBF8'
$content = $content -replace '#eff8ff', '#F4F1EA'
$content = $content -replace '#f4f8fc', '#F4F1EA'
$content = $content -replace '#f0f9ff', '#FDFBF8'
$content = $content -replace '#e8f4fb', '#EFEBE1'
$content = $content -replace '#eaf6ff', '#FDFBF8'
$content = $content -replace '#e8f7ff', '#FDFBF8'
$content = $content -replace '#f5fcff', '#FDFBF8'
$content = $content -replace '#f0fcff', '#FDFBF8'
$content = $content -replace '#f8fafc', '#F4F1EA'

# Borders (Blueish grays -> Beigeish grays)
$content = $content -replace '#dbeafe', '#E0D8CC'
$content = $content -replace '#e2ecf5', '#E0D8CC'
$content = $content -replace '#d1e8f5', '#D4C5B0'
$content = $content -replace '#bae6fd', '#E0D8CC'
$content = $content -replace '#c7ddf0', '#D4C5B0'

# AI Hint text color
$content = $content -replace '#0369a1', '#6D4C41'
$content = $content -replace '#0c4a6e', '#3E2723'

# Tags text color
$content = $content -replace '#1e40af', '#3E2723'

# Box shadows (blue to brown)
$content = $content -replace 'rgba\(44,111,168,', 'rgba(138,112,85,'

Set-Content $file -Value $content -Encoding UTF8 -NoNewline
Write-Host "Updated onboarding.php colors."
