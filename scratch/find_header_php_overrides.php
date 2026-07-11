<?php
$dir = 'volunteer';
$files = scandir($dir);
foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        $content = file_get_contents("$dir/$file");
        if (strpos($content, '#main-header') !== false || strpos($content, 'main-header') !== false) {
            echo "Found in $dir/$file:\n";
            // Print lines containing main-header
            $lines = explode("\n", $content);
            foreach ($lines as $i => $line) {
                if (strpos($line, 'main-header') !== false) {
                    echo "  Line " . ($i + 1) . ": " . trim($line) . "\n";
                }
            }
        }
    }
}
?>
