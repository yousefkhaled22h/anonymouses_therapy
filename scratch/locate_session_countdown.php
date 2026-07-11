<?php
$files = [
    'dashboard.php',
    'views/dashboard_home.php',
    'volunteer/dashboard.php'
];

foreach ($files as $file) {
    $path = __DIR__ . '/../' . $file;
    if (file_exists($path)) {
        echo "=== File: $file ===\n";
        $content = file_get_contents($path);
        $lines = explode("\n", $content);
        foreach ($lines as $i => $line) {
            if (strpos($line, 'countdown') !== false || strpos($line, 'mins') !== false || strpos($line, 'minutes') !== false || strpos($line, 'session_date') !== false) {
                echo ($i + 1) . ": " . trim($line) . "\n";
            }
        }
        echo "\n";
    }
}
