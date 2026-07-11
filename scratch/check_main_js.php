<?php
$content = file_get_contents(__DIR__ . '/../assets/js/main.js');
$lines = explode("\n", $content);
foreach ($lines as $i => $line) {
    if (strpos($line, 'overflow') !== false || strpos($line, 'scroll') !== false || strpos($line, 'hidden') !== false || strpos($line, 'preventDefault') !== false) {
        echo ($i + 1) . ": " . trim($line) . "\n";
    }
}
