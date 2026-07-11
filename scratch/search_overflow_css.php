<?php
$css = file_get_contents(__DIR__ . '/../assets/css/style.css');
$lines = explode("\n", $css);
echo "=== References to overflow in style.css ===\n";
foreach ($lines as $i => $line) {
    if (strpos($line, 'overflow') !== false) {
        echo ($i + 1) . ": " . trim($line) . "\n";
    }
}
