<?php
$file = file_get_contents('assets/css/dashboard-style.css');
preg_match_all('/[^\n]*\.container[^\n]*/i', $file, $matches);
foreach ($matches[0] as $line) {
    echo trim($line) . "\n";
}
?>
