$file = 'c:\xampp\htdocs\test2\onboarding.php'
$content = [System.IO.File]::ReadAllText($file, [System.Text.Encoding]::UTF8)

# Title and borders
$content = $content -replace 'Safe Haven â€“ Find Your Match', 'Safe Haven - Find Your Match'
$content = $content -replace 'â”€â”€', '--'
$content = $content -replace 'â€¢', '•'

# Emojis
$content = $content -replace 'ðŸŒ¿', '🌿'
$content = $content -replace 'ðŸ’­', '💭'
$content = $content -replace 'ðŸŒŠ', '🌊'
$content = $content -replace 'ðŸŒ±', '🌱'
$content = $content -replace 'ðŸ”®', '🔮'
$content = $content -replace 'âœ¨', '✨'
$content = $content -replace 'ðŸ¤–', '🤖'
$content = $content -replace 'ðŸ‘¥', '👥'
$content = $content -replace 'ðŸ’¬', '💬'
$content = $content -replace 'ðŸ“–', '📖'
$content = $content -replace 'ðŸ“š', '📚'
$content = $content -replace 'âš¡', '⚡'
$content = $content -replace 'ðŸ•Š', '🕊'
$content = $content -replace 'ðŸ§‚', '🫂'
$content = $content -replace 'â˜€ï¸', '☀️'
$content = $content -replace 'â€¼ï¸', '‼️'
$content = $content -replace 'â€', '' # stray parts

[System.IO.File]::WriteAllText($file, $content, [System.Text.Encoding]::UTF8)
Write-Host "Fixed encoding issues."
