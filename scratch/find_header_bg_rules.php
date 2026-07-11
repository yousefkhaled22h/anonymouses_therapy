<?php
$files = ['assets/css/style.css', 'assets/css/dashboard-style.css'];
foreach ($files as $file) {
    echo "=== $file ===\n";
    $content = file_get_contents($file);
    // Find all rules starting with header or #main-header containing background
    preg_match_all('/([^\}\{]*header[^\}\{]*)\{([^}]*background[^}]*)\}/i', $content, $matches);
    foreach ($matches[0] as $match) {
        echo trim($match) . "\n\n";
    }
}
?>
