<?php
$file = file_get_contents('assets/css/dashboard-style.css');
preg_match_all('/[^\n]*header[^\n]*/i', $file, $matches1);
preg_match_all('/[^\n]*nav[^\n]*/i', $file, $matches2);
echo "=== HEADER ===\n";
foreach ($matches1[0] as $line) {
    echo trim($line) . "\n";
}
echo "=== NAV ===\n";
foreach ($matches2[0] as $line) {
    echo trim($line) . "\n";
}
?>
