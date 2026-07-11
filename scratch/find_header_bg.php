<?php
$file = file_get_contents('assets/css/style.css');
preg_match_all('/[^\n]*--header-bg[^\n]*/i', $file, $matches);
foreach ($matches[0] as $line) {
    echo trim($line) . "\n";
}
?>
