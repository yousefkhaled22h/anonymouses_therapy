<?php
$dir = realpath(__DIR__ . '/..');
$terms = ['teams_meeting_id', 'teams_join_url'];
$matches = [];

function search($current_dir, $terms, &$matches) {
    $items = scandir($current_dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $current_dir . '/' . $item;
        if (is_dir($path)) {
            if (in_array($item, ['.git', 'scratch', 'assets', 'vendor'])) {
                continue;
            }
            search($path, $terms, $matches);
        } else {
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            if ($ext === 'php' || $ext === 'sql' || $ext === 'js') {
                $content = file_get_contents($path);
                // Check if it's UTF-16LE and convert if so
                if ($ext === 'sql' && strpos($content, "\xFF\xFE") === 0) {
                    $content = mb_convert_encoding($content, 'UTF-8', 'UTF-16LE');
                }
                foreach ($terms as $term) {
                    if (strpos($content, $term) !== false) {
                        $matches[$term][] = $path;
                    }
                }
            }
        }
    }
}

search($dir, $terms, $matches);

foreach ($matches as $term => $paths) {
    echo "Term: $term\n";
    foreach (array_unique($paths) as $p) {
        echo "  - $p\n";
        $content = file_get_contents($p);
        if (pathinfo($p, PATHINFO_EXTENSION) === 'sql' && strpos($content, "\xFF\xFE") === 0) {
            $content = mb_convert_encoding($content, 'UTF-8', 'UTF-16LE');
        }
        $lines = explode("\n", $content);
        foreach ($lines as $i => $line) {
            if (strpos($line, $term) !== false) {
                echo "    " . ($i + 1) . ": " . trim($line) . "\n";
            }
        }
    }
    echo "\n";
}
