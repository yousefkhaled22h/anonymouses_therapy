<?php
$dir = realpath(__DIR__ . '/..');
$term = 'session_summary';
$matches = [];

function search($current_dir, $term, &$matches) {
    $items = scandir($current_dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $current_dir . '/' . $item;
        
        if (is_dir($path)) {
            if (in_array($item, ['.git', 'scratch', 'assets', 'vendor'])) {
                continue;
            }
            search($path, $term, $matches);
        } else {
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            if ($ext === 'php' || $ext === 'sql') {
                $content = file_get_contents($path);
                if (strpos($content, $term) !== false) {
                    $matches[] = $path;
                }
            }
        }
    }
}

search($dir, $term, $matches);
echo "=== References to session_summary ===\n";
foreach ($matches as $path) {
    echo "- $path\n";
}
