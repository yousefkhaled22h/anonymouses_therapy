<?php
$dir = realpath(__DIR__ . '/..');
$terms = ['summary_content', 'summary_generated_date'];
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
            if ($ext === 'php' || $ext === 'sql') {
                $content = file_get_contents($path);
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
    foreach ($paths as $p) {
        echo "  - $p\n";
        // print lines matching term
        $lines = explode("\n", file_get_contents($p));
        foreach ($lines as $i => $line) {
            if (strpos($line, $term) !== false) {
                echo "    " . ($i + 1) . ": " . trim($line) . "\n";
            }
        }
    }
    echo "\n";
}
