<?php
$content = file_get_contents(__DIR__ . '/../volunteer/dashboard.css');
$lines = explode("\n", $content);
foreach ($lines as $i => $line) {
    if (strpos($line, 'background') !== false || strpos($line, '#') !== false || strpos($line, 'rgb') !== false) {
        echo ($i + 1) . ": " . trim($line) . "\n";
    }
}
