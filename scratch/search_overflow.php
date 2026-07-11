<?php
$dir = realpath(__DIR__ . '/..');
$matches = [];

function search($current_dir, &$matches) {
    $items = scandir($current_dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $current_dir . '/' . $item;
        
        if (is_dir($path)) {
            if (in_array($item, ['.git', 'scratch', 'assets', 'vendor'])) {
                continue;
            }
            search($path, $matches);
        } else {
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            if ($ext === 'php' || $ext === 'js') {
                $content = file_get_contents($path);
                if (strpos($content, 'overflow') !== false && strpos($content, 'hidden') !== false) {
                    $matches[] = $path;
                }
            }
        }
    }
}

search($dir, $matches);
echo "=== Files containing overflow and hidden ===\n";
foreach ($matches as $path) {
    echo "- $path\n";
    $content = file_get_contents($path);
    $lines = explode("\n", $content);
    foreach ($lines as $i => $line) {
        if (strpos($line, 'overflow') !== false && strpos($line, 'hidden') !== false) {
            echo "    " . ($i + 1) . ": " . trim($line) . "\n";
        }
    }
}
