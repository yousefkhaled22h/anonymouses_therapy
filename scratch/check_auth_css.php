<?php
$files = [
    'login.php',
    'register.php',
    'volunteer/signin.php',
    'volunteer/signup.php'
];

foreach ($files as $file) {
    $path = __DIR__ . '/../' . $file;
    if (file_exists($path)) {
        echo "=== File: $file ===\n";
        $content = file_get_contents($path);
        
        // Print the lines containing <style> or <link rel="stylesheet"> or any button/hover styles
        $lines = explode("\n", $content);
        $in_style = false;
        foreach ($lines as $i => $line) {
            if (strpos($line, '<style>') !== false || strpos($line, '<style') !== false) {
                $in_style = true;
            }
            if (strpos($line, '<link rel="stylesheet"') !== false || $in_style) {
                echo ($i + 1) . ": " . trim($line) . "\n";
            }
            if (strpos($line, '</style>') !== false) {
                $in_style = false;
            }
        }
        echo "\n";
    }
}
