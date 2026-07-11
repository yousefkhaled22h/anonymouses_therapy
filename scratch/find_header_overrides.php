<?php
$files = ['volunteer/dashboard.css', 'volunteer/profile.css', 'assets/css/volunteer.css'];
foreach ($files as $file) {
    if (file_exists($file)) {
        echo "=== $file ===\n";
        $content = file_get_contents($file);
        preg_match_all('/[^\n]*header[^\n]*/i', $content, $matches);
        foreach (array_slice($matches[0], 0, 10) as $line) {
            echo trim($line) . "\n";
        }
    }
}
?>
