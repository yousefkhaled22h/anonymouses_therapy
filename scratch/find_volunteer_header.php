<?php
$file = file_get_contents('assets/css/volunteer.css');
preg_match_all('/[^\n]*container[^\n]*/i', $file, $matches1);
preg_match_all('/[^\n]*header[^\n]*/i', $file, $matches2);
echo "=== CONTAINER ===\n";
foreach ($matches1[0] as $line) {
    echo trim($line) . "\n";
}
echo "=== HEADER ===\n";
foreach ($matches2[0] as $line) {
    echo trim($line) . "\n";
}
?>
