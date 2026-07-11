<?php
$file = file_get_contents('index.php');
$lines = explode("\n", $file);
foreach ($lines as $i => $line) {
    if (strpos($line, 'header') !== false || strpos($line, 'volunteer') !== false) {
        echo "Line " . ($i + 1) . ": " . trim($line) . "\n";
    }
}
?>
